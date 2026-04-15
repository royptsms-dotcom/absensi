@extends('layouts.app')

@section('title', 'Rekap Absensi')

@section('content')
<div class="grid grid-cols-12 gap-x-6">
    <div class="col-span-12 lg:col-span-4">
        <div class="card">
            <div class="card-header">
                <h5>Import File XLSX</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('rekap.import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label for="file" class="form-label">Pilih File XLSX</label>
                        <input type="file" name="file" class="form-control" id="file" required>
                    </div>
                    <div class="mb-3">
                        <label for="month" class="form-label">Bulan & Tahun Rekapan</label>
                        <input type="month" name="month_year" class="form-control" id="month" value="{{ date('Y-m') }}" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-full">Proses Rekap</button>
                </form>
            </div>


        </div>
    </div>

    @if(isset($attendanceData))
    <div class="col-span-12 lg:col-span-8">
        <div class="card">
            <div class="card-header flex justify-between items-center">
                <h5>Hasil Rekapan: {{ $selectedMonth }}</h5>
                <a href="{{ route('rekap.export', ['data' => base64_encode(serialize($attendanceData))]) }}" class="btn btn-success btn-sm">Unduh XLSX</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Nama Karyawan</th>
                                <th>Hadir</th>
                                <th>Terlambat</th>
                                <th>Alpa</th>
                                <th>Izin/Sakit</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($attendanceData as $row)
                            <tr>
                                <td>{{ $row['name'] }}</td>
                                <td>{{ $row['present'] }}</td>
                                <td>{{ $row['late'] }}</td>
                                <td>{{ $row['absent'] }}</td>
                                <td>{{ $row['leave'] }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @else
    <div class="col-span-12 lg:col-span-8">
        <div class="card">
            <div class="card-body text-center py-10">
                <i data-feather="file-text" class="mb-4 text-muted" style="width: 50px; height: 50px;"></i>
                <h5>Silahkan import file untuk menampilkan data</h5>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
