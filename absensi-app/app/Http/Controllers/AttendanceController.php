<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Session;

class AttendanceController extends Controller
{
    public function index()
    {
        $attendanceData = Session::get('attendanceData');
        $selectedMonth = Session::get('selectedMonth');
        return view('rekap.index', compact('attendanceData', 'selectedMonth'));
    }


    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required',
            'month_year' => 'required'
        ]);

        $settingsPath = storage_path('app/settings.json');
        $settings = file_exists($settingsPath) ? json_decode(file_get_contents($settingsPath), true) : ['check_in_limit' => '08:00', 'check_out_limit' => '17:00'];
        
        $checkInLimit = $settings['check_in_limit'];
        $checkOutLimit = $settings['check_out_limit'];

        $file = $request->file('file');
        
        try {
            // Auto detect format
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getRealPath());
            $data = [];
            foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
                $data[] = $worksheet->toArray();
            }
        } catch (\Exception $e) {
            return redirect()->route('rekap.index')->with('error', 'Gagal membaca format file: ' . $e->getMessage());
        }

        $selectedMonthYear = $request->month_year; 
        $attendanceData = [];

        foreach ($data as $rows) {
            if (count($rows) <= 1) continue; 

            for ($i = 1; $i < count($rows); $i++) {
                $name = $rows[$i][1] ?? null; 
                $id = $rows[$i][2] ?? '-'; // Column C (No.ID)
                $dateTimeStr = $rows[$i][3] ?? null; 

                if (!$name || !$dateTimeStr) continue;

                try {
                    $dateObj = null;
                    if (is_numeric($dateTimeStr)) {
                        $dateObj = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($dateTimeStr);
                    } else {
                        $formats = ['d/m/Y H:i:s', 'd/m/Y H:i', 'Y-m-d H:i:s', 'd-m-Y H:i:s', 'm/d/Y H:i:s'];
                        foreach ($formats as $format) {
                            try { $dateObj = \Carbon\Carbon::createFromFormat($format, $dateTimeStr); break; } catch (\Exception $e) {}
                        }
                    }

                    if (!$dateObj) continue;

                    $recordMonthYear = $dateObj->format('Y-m');
                    if ($recordMonthYear !== $selectedMonthYear) continue;

                    $dateKey = $dateObj->format('Y-m-d');
                    $name = trim($name);
                    $dept = trim($rows[$i][0] ?? '-'); 
                    $key = $name . '_' . $id;

                    if (!isset($attendanceData[$key])) {
                        // Daftarkan ke Database jika belum ada
                        \App\Models\Employee::firstOrCreate(
                            ['employee_id' => $id],
                            ['name' => $name, 'department' => $dept]
                        );

                        $attendanceData[$key] = [
                            'id' => $id,
                            'name' => $name,
                            'department' => $dept,
                            'present_days' => [], 
                            'present' => 0,
                            'late' => 0,
                            'out' => 0,
                            'absent' => 0,
                            'leave' => 0,
                        ];
                    }

                    $currentTime = $dateObj->format('H:i:s');
                    $isMorning = $currentTime < '12:00:00';

                    if (!isset($attendanceData[$key]['present_days'][$dateKey])) {
                        $attendanceData[$key]['present_days'][$dateKey] = [
                            'first' => $isMorning ? $currentTime : '-',
                            'last' => !$isMorning ? $currentTime : '-'
                        ];
                        $attendanceData[$key]['present']++;
                    } else {
                        if ($isMorning) {
                            // Update earliest morning scan
                            if ($attendanceData[$key]['present_days'][$dateKey]['first'] == '-' || $currentTime < $attendanceData[$key]['present_days'][$dateKey]['first']) {
                                $attendanceData[$key]['present_days'][$dateKey]['first'] = $currentTime;
                            }
                        } else {
                            // Update latest afternoon scan
                            if ($attendanceData[$key]['present_days'][$dateKey]['last'] == '-' || $currentTime > $attendanceData[$key]['present_days'][$dateKey]['last']) {
                                $attendanceData[$key]['present_days'][$dateKey]['last'] = $currentTime;
                            }
                        }
                    }

                } catch (\Exception $e) { continue; }
            }
        }

        if (empty($attendanceData)) {
            return redirect()->route('rekap.index')->with('error', "Data pada bulan $selectedMonthYear tidak ditemukan. Pastikan kolom sesuai dan bulan dipilih dengan benar.");
        }

        // Final calculation for Late and Out based on first/last scans
        $finalData = collect($attendanceData)->map(function($item) use ($checkInLimit, $checkOutLimit) {
            foreach ($item['present_days'] as $day) {
                if ($day['first'] !== '-' && $day['first'] > $checkInLimit . ':00') {
                    $item['late']++;
                }
                if ($day['last'] !== '-' && $day['last'] >= $checkOutLimit . ':00') {
                    $item['out']++;
                }
            }
            // unset($item['present_days']); // Jangan hapus ini agar bisa dipreview
            return $item;
        })->sortBy('id')->values()->toArray();




        Session::put('attendanceData', $finalData);
        Session::put('selectedMonth', $selectedMonthYear);

        return redirect()->route('rekap.index')->with('success', 'Data berhasil direkap dengan ID dan Jam Pulang.');
    }




    public function export(Request $request)
    {
        $dataEncoded = $request->query('data');
        if (!$dataEncoded) return redirect()->back();

        $attendanceData = unserialize(base64_decode($dataEncoded));
        
        // Use Maatwebsite Excel to export
        // For simplicity, we'll return a simple collection-based export
        return Excel::download(new class($attendanceData) implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings {
            protected $data;
            public function __construct($data) { $this->data = collect($data); }
            public function collection() { return $this->data; }
            public function headings(): array { return ['Nama Karyawan', 'Hadir', 'Terlambat', 'Alpa', 'Izin/Sakit']; }
        }, 'rekap_absensi_' . date('Ymd_His') . '.xlsx');
    }
}
