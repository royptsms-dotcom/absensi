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

        
        $standardEntryTime = "08:00:00";
        $attendanceData = [];

        foreach ($data as $rows) {
            if (count($rows) <= 1) continue; 

            for ($i = 1; $i < count($rows); $i++) {
                $name = $rows[$i][1] ?? null; 
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

                    if (!isset($attendanceData[$name])) {
                        $attendanceData[$name] = [
                            'name' => $name,
                            'present_days' => [], 
                            'present' => 0, 'late' => 0, 'absent' => 0, 'leave' => 0,
                        ];
                    }

                    if (!isset($attendanceData[$name]['present_days'][$dateKey])) {
                        $attendanceData[$name]['present_days'][$dateKey] = $dateObj->format('H:i:s');
                        $attendanceData[$name]['present']++;
                        if ($dateObj->format('H:i:s') > $standardEntryTime) $attendanceData[$name]['late']++;
                    } else {
                        if ($dateObj->format('H:i:s') < $attendanceData[$name]['present_days'][$dateKey]) {
                            $wasLate = $attendanceData[$name]['present_days'][$dateKey] > $standardEntryTime;
                            $isLate = $dateObj->format('H:i:s') > $standardEntryTime;
                            if ($wasLate && !$isLate) $attendanceData[$name]['late']--;
                            $attendanceData[$name]['present_days'][$dateKey] = $dateObj->format('H:i:s');
                        }
                    }
                } catch (\Exception $e) { continue; }
            }
        }

        if (empty($attendanceData)) {
            return redirect()->route('rekap.index')->with('error', "Data pada bulan $selectedMonthYear tidak ditemukan di dalam file.");
        }

        $finalData = collect($attendanceData)->map(function($item) {
            unset($item['present_days']); return $item;
        })->values()->toArray();

        Session::put('attendanceData', $finalData);
        Session::put('selectedMonth', $selectedMonthYear);

        return redirect()->route('rekap.index')->with('success', 'Data berhasil diimport dan direkap.');
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
