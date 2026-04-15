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
        $rows = $data[0]; // Assuming first sheet
        \Log::info('Excel Data Sample:', ['sample' => array_slice($rows, 0, 5)]);

        $attendanceData = [];

        // Skip header (usually row 0)
        for ($i = 1; $i < count($rows); $i++) {
            $name = $rows[$i][0] ?? null; 
            if (!$name || empty(trim($name))) continue;

            if (!isset($attendanceData[$name])) {
                $attendanceData[$name] = [
                    'name' => $name,
                    'present' => 0,
                    'late' => 0,
                    'absent' => 0,
                    'leave' => 0,
                ];
            }

            $status = strtolower($rows[$i][2] ?? ''); 
            if (str_contains($status, 'hadir') || str_contains($status, 'masuk') || str_contains($status, 'present')) {
                $attendanceData[$name]['present']++;
            } elseif (str_contains($status, 'telat') || str_contains($status, 'terlambat') || str_contains($status, 'late')) {
                $attendanceData[$name]['late']++;
            } elseif (str_contains($status, 'alpa') || str_contains($status, 'tanpa') || str_contains($status, 'absent')) {
                $attendanceData[$name]['absent']++;
            } elseif (str_contains($status, 'izin') || str_contains($status, 'sakit') || str_contains($status, 'leave')) {
                $attendanceData[$name]['leave']++;
            }
        }

        if (empty($attendanceData)) {
            return redirect()->route('rekap.index')->with('error', 'Tidak ada data valid yang ditemukan sesuai format kolom (Nama di Kolom A, Status di Kolom C).');
        }

        Session::put('attendanceData', array_values($attendanceData));

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
