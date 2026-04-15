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
            'file' => 'required|mimes:xlsx,xls,csv',
            'month_year' => 'required'
        ]);

        $file = $request->file('file');
        $data = Excel::toArray([], $file);

        // Process logic: Summarize data per person
        // This is a dummy logic based on common attendance file structures
        // We will group by name and calculate stats
        $rows = $data[0]; 
        $attendanceData = [];
        $selectedMonthYear = $request->month_year; // Format: YYYY-MM
        
        $standardEntryTime = "08:00:00"; // Nanti bisa diambil dari settings

        // Process each row
        // Skip header (row 0)
        for ($i = 1; $i < count($rows); $i++) {
            $name = $rows[$i][1] ?? null; // Column B
            $dateTimeStr = $rows[$i][3] ?? null; // Column D

            if (!$name || !$dateTimeStr) continue;

            try {
                // Handle different date formats or Excel serial dates
                if (is_numeric($dateTimeStr)) {
                    $dateObj = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($dateTimeStr);
                } else {
                    $dateObj = \Carbon\Carbon::createFromFormat('d/m/Y H:i:s', $dateTimeStr);
                }

                $recordMonthYear = $dateObj->format('Y-m');
                if ($recordMonthYear !== $selectedMonthYear) continue;

                $dateKey = $dateObj->format('Y-m-d');

                if (!isset($attendanceData[$name])) {
                    $attendanceData[$name] = [
                        'name' => $name,
                        'present_days' => [], // To count unique days
                        'late_count' => 0,
                        'present' => 0,
                        'late' => 0,
                        'absent' => 0,
                        'leave' => 0,
                    ];
                }

                // If it's the first record of the day for this person, check if late
                if (!isset($attendanceData[$name]['present_days'][$dateKey])) {
                    $attendanceData[$name]['present_days'][$dateKey] = $dateObj->format('H:i:s');
                    $attendanceData[$name]['present']++;
                    
                    if ($dateObj->format('H:i:s') > $standardEntryTime) {
                        $attendanceData[$name]['late']++;
                    }
                } else {
                    // Update if this record is earlier than the stored one (to find scan masuk)
                    if ($dateObj->format('H:i:s') < $attendanceData[$name]['present_days'][$dateKey]) {
                        // Previous record was late, check if this one is not
                        $wasLate = $attendanceData[$name]['present_days'][$dateKey] > $standardEntryTime;
                        $isLate = $dateObj->format('H:i:s') > $standardEntryTime;
                        
                        if ($wasLate && !$isLate) {
                            $attendanceData[$name]['late']--;
                        }
                        
                        $attendanceData[$name]['present_days'][$dateKey] = $dateObj->format('H:i:s');
                    }
                }

            } catch (\Exception $e) {
                \Log::warning("Failed to parse date: $dateTimeStr for $name");
                continue;
            }
        }

        if (empty($attendanceData)) {
            return redirect()->route('rekap.index')->with('error', 'Tidak ada data ditemukan untuk bulan ' . $selectedMonthYear . '. Pastikan data di file XLSX sesuai dengan bulan yang dipilih.');
        }

        $finalData = collect($attendanceData)->map(function($item) {
            unset($item['present_days']); // Clean up internal helper
            return $item;
        })->values()->toArray();

        Session::put('attendanceData', $finalData);


        Session::put('selectedMonth', $request->month_year);

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
