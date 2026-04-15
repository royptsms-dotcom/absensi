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
            $filePath = $file->getRealPath();
            $spreadsheet = null;
            
            // List semua "Kunci" (Reader) yang mungkin bisa membuka file ini
            $readers = [
                'Xlsx', 'Xls', 'Html', 'Csv', 'Xml'
            ];

            foreach ($readers as $type) {
                try {
                    $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($type);
                    // Jika CSV, coba gunakan Tab sebagai pemisah (Sering di mesin absensi)
                    if ($type == 'Csv') {
                        $reader->setDelimiter("\t");
                    }
                    
                    if ($reader->canRead($filePath)) {
                        $reader->setReadDataOnly(true);
                        $spreadsheet = $reader->load($filePath);
                        break; // BERHASIL! Berhenti mencoba yang lain
                    }
                } catch (\Exception $e) { continue; }
            }

            // Jika masih gagal kabeh, coba paksa load otomatis
            if (!$spreadsheet) {
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
            }

            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();
            $data = [$rows]; 
        } catch (\Exception $e) {
            return redirect()->route('rekap.index')->with('error', 'File ini tidak bisa dibuka oleh server (Mungkin dipassword atau korup). Solusi: Buka file di Excel PC Anda, lalu "Save As" ke format "Excel Workbook (.xlsx)" baru kemudian upload kesini. Error: ' . $e->getMessage());
        }




        $selectedMonthYear = $request->month_year; 
        $attendanceData = [];
        $totalRecordsFound = 0;

        foreach ($data as $rows) {
            if (count($rows) <= 1) continue; 

            $emptyCounter = 0;
            for ($i = 1; $i < count($rows); $i++) {
                $name = $rows[$i][1] ?? null; 
                $id = $rows[$i][2] ?? '-';
                $dateTimeStr = $rows[$i][3] ?? null; 

                // Jika baris benar-benar kosong, hitung. Jika sudah 5x kosong beruntun, anggap data sudah habis (Break)
                if (empty($name) && empty($dateTimeStr)) {
                    $emptyCounter++;
                    if ($emptyCounter > 5) break; 
                    continue;
                }
                $emptyCounter = 0; // Reset jika ketemu data lagi

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

                    $totalRecordsFound++;
                    $dateKey = $dateObj->format('Y-m-d');
                    $name = ucwords(strtolower(trim($name)));
                    $dept = trim($rows[$i][0] ?? '-'); 
                    $key = $name . '_' . $id;

                    if (!isset($attendanceData[$key])) {
                        \App\Models\Employee::updateOrCreate(
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
                            if ($attendanceData[$key]['present_days'][$dateKey]['first'] == '-' || $currentTime < $attendanceData[$key]['present_days'][$dateKey]['first']) {
                                $attendanceData[$key]['present_days'][$dateKey]['first'] = $currentTime;
                            }
                        } else {
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
        
        return Excel::download(new class($attendanceData) implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings, \Maatwebsite\Excel\Concerns\WithMapping {
            protected $data;
            public function __construct($data) { $this->data = collect($data); }
            
            public function collection() { return $this->data; }

            public function headings(): array 
            { 
                return ['ID', 'NAMA KARYAWAN', 'HADIR', 'TERLAMBAT', 'PULANG']; 
            }

            public function map($row): array
            {
                return [
                    $row['id'],
                    $row['name'],
                    $row['present'] . ' hari',
                    $row['late'],
                    $row['out']
                ];
            }
        }, 'rekap_absensi_' . date('Ymd_His') . '.xlsx');
    }

    public function exportDetail(Request $request)
    {
        $dataJson = $request->query('data');
        if (!$dataJson) return redirect()->back();

        $employee = json_decode(base64_decode($dataJson), true);
        
        $settingsPath = storage_path('app/settings.json');
        $settings = file_exists($settingsPath) ? json_decode(file_get_contents($settingsPath), true) : ['check_in_limit' => '08:00', 'check_out_limit' => '17:00'];

        return Excel::download(new class($employee, $settings) implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings, \Maatwebsite\Excel\Concerns\WithMapping {
            protected $employee;
            protected $settings;

            public function __construct($employee, $settings) { 
                $this->employee = $employee; 
                $this->settings = $settings;
            }
            
            public function collection() { 
                $days = $this->employee['present_days'];
                ksort($days);
                return collect($days)->map(function($times, $date) {
                    return array_merge(['date' => $date], $times);
                });
            }

            public function headings(): array 
            { 
                return ['TANGGAL', 'JAM MASUK', 'JAM PULANG', 'STATUS']; 
            }

            public function map($row): array
            {
                $isSaturday = \Carbon\Carbon::parse($row['date'])->isSaturday();
                $limit = $isSaturday ? ($this->settings['saturday_in_limit'] ?? '08:00') : ($this->settings['check_in_limit'] ?? '08:00');
                
                $status = 'Tepat Waktu';
                if ($row['first'] == '-') {
                    $status = 'Tidak Absen Masuk';
                } elseif ($row['first'] > $limit . ':00') {
                    $status = 'Terlambat';
                }

                return [
                    $row['date'],
                    $row['first'],
                    $row['last'],
                    $status
                ];
            }
        }, 'detail_absensi_' . str_replace(' ', '_', $employee['name']) . '_' . date('Ymd') . '.xlsx');
    }
}
