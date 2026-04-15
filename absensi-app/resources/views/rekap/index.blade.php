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
                                <th>ID</th>
                                <th>Nama Karyawan</th>
                                <th class="text-center">Hadir</th>
                                <th class="text-center text-danger">Terlambat</th>
                                <th class="text-center text-primary">Pulang</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $settingsPath = storage_path('app/settings.json');
                                $settings = file_exists($settingsPath) ? json_decode(file_get_contents($settingsPath), true) : ['check_in_limit' => '08:00', 'check_out_limit' => '17:00'];
                                $limit = $settings['check_in_limit'] . ':00';
                            @endphp
                            @foreach($attendanceData as $row)
                            <tr>
                                <td>{{ $row['id'] ?? '-' }}</td>
                                <td>{{ $row['name'] ?? 'Tanpa Nama' }}</td>
                                <td class="text-center">{{ $row['present'] ?? 0 }} hari</td>
                                <td class="text-center">
                                    <span class="badge {{ ($row['late'] ?? 0) > 0 ? 'bg-light-danger text-danger' : 'bg-light-success text-success' }}">
                                        {{ $row['late'] ?? 0 }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge {{ ($row['out'] ?? 0) > 0 ? 'bg-light-primary text-primary' : 'bg-light-secondary text-secondary' }}">
                                        {{ $row['out'] ?? 0 }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-light-info" onclick="showDetail({{ json_encode($row) }}, '{{ $limit }}')">
                                        <i data-feather="eye" style="width: 14px; height: 14px;"></i>
                                    </button>
                                </td>
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

<!-- Modal Detail: Dipindah ke luar grid utama agar tidak bocor -->
<div class="modal fade" id="modalDetail" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary">
                <h5 class="modal-title text-white" id="modalDetailLabel">Detail Log Absensi: <span id="modalName" class="text-white fw-bold"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-striped table-hover mb-0">
                        <thead class="bg-light sticky-top">
                            <tr>
                                <th class="py-3 px-4">Tanggal</th>
                                <th class="py-3 px-4">Jam Masuk</th>
                                <th class="py-3 px-4">Jam Pulang</th>
                                <th class="py-3 px-4 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody id="modalTableBody">
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

@endsection


@section('scripts')
<script>
    function showDetail(data, limit) {
        document.getElementById('modalName').innerText = data.name + " (" + data.id + ")";
        const tbody = document.getElementById('modalTableBody');
        tbody.innerHTML = '';

        // Sort dates
        const dates = Object.keys(data.present_days).sort();

        dates.forEach(date => {
            const dayData = data.present_days[date];
            const hasIn = dayData.first !== '-';
            const hasOut = dayData.last !== '-';
            const isLate = hasIn && dayData.first > limit;
            
            let statusBadge = '';
            if (!hasIn) {
                statusBadge = '<span class="badge bg-light-secondary text-secondary">Tidak Absen Masuk</span>';
            } else if (isLate) {
                statusBadge = '<span class="badge bg-danger">Terlambat</span>';
            } else {
                statusBadge = '<span class="badge bg-success">Tepat Waktu</span>';
            }

            let row = `<tr>
                <td class="align-middle">${date}</td>
                <td class="align-middle ${isLate ? 'text-danger fw-bold' : ''}">${dayData.first}</td>
                <td class="align-middle">${dayData.last}</td>
                <td class="align-middle text-center">
                    ${statusBadge}
                </td>
            </tr>`;
            tbody.innerHTML += row;
        });


        var myModal = new bootstrap.Modal(document.getElementById('modalDetail'));
        myModal.show();
    }
</script>
@endsection


