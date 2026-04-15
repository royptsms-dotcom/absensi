@extends('layouts.app')

@section('title', 'Rekap Absensi')

@section('content')
<div class="grid grid-cols-12 gap-x-6">
    <div class="col-span-12 mb-4">
        <div class="card">
            <div class="card-header">
                <h5>Import & Proses Absensi</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('rekap.import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="row align-items-end">
                        <div class="col-md-5 mb-0">
                            <label for="file" class="form-label font-bold">1. Pilih File XLSX / CSV</label>
                            <input type="file" name="file" class="form-control" id="file" required>
                        </div>
                        <div class="col-md-4 mb-0">
                            <label for="month" class="form-label font-bold">2. Bulan & Tahun</label>
                            <input type="month" name="month_year" class="form-control" id="month" value="{{ date('Y-m') }}" required>
                        </div>
                        <div class="col-md-3 mb-0">
                            <button type="submit" class="btn btn-primary w-full shadow-sm d-flex align-items-center justify-content-center" style="height: 42px; background: linear-gradient(45deg, #4680ff, #3f4d67); border: none; transition: all 0.3s ease;">
                                <i data-feather="play-circle" class="mr-2" style="width:18px; height:18px;"></i> 
                                <span class="fw-bold">PROSES REKAP</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <style>
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(70, 128, 255, 0.4);
            filter: brightness(1.1);
        }
    </style>



    @if(isset($attendanceData))
    <div class="col-span-12">
        <div class="card">
            <div class="card-header flex justify-between items-center">
                <h5>Hasil Rekapan: {{ $selectedMonth }}</h5>
                <a href="{{ route('rekap.export', ['data' => base64_encode(serialize($attendanceData))]) }}" class="btn btn-success btn-sm shadow-sm">
                    <i data-feather="download" class="mr-1" style="width:14px"></i> Unduh XLSX
                </a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">

                        <thead>
                            <tr>
                                <th onclick="sortTable(0)" style="cursor:pointer" title="Urutkan ID">ID</th>
                                <th onclick="sortTable(1)" style="cursor:pointer" title="Urutkan Nama">Nama Karyawan</th>
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
                                    <button class="btn btn-sm btn-light-info" onclick="showDetail({{ json_encode($row) }}, {{ json_encode($settings) }})">
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
    <div class="col-span-12">
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
    function showDetail(data, settings) {
        document.getElementById('modalName').innerText = data.name + " (" + data.id + ")";
        const tbody = document.getElementById('modalTableBody');
        tbody.innerHTML = '';

        const dates = Object.keys(data.present_days).sort();

        dates.forEach(dateStr => {
            const dayData = data.present_days[dateStr];
            const dateObj = new Date(dateStr);
            const isSaturday = dateObj.getDay() === 6; // 6 = Saturday

            const inLimit = isSaturday ? settings.saturday_in_limit : settings.check_in_limit;
            const fullLimit = inLimit + ":00";

            const hasIn = dayData.first !== '-';
            const isLate = hasIn && dayData.first > fullLimit;
            
            let statusBadge = '';
            if (!hasIn) {
                statusBadge = '<span class="badge bg-light-secondary text-secondary">Tidak Absen Masuk</span>';
            } else if (isLate) {
                statusBadge = '<span class="badge bg-danger">Terlambat</span>';
            } else {
                statusBadge = '<span class="badge bg-success">Tepat Waktu</span>';
            }

            let row = `<tr>
                <td class="align-middle px-4">${dateStr} ${isSaturday ? '<span class="badge bg-light-success text-success">Sabtu</span>' : ''}</td>
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

    function sortTable(n) {
        var table, rows, switching, i, x, y, shouldSwitch, dir, switchcount = 0;
        table = document.querySelector("table");
        switching = true;
        dir = "asc";
        while (switching) {
            switching = false;
            rows = table.rows;
            for (i = 1; i < (rows.length - 1); i++) {
                shouldSwitch = false;
                x = rows[i].getElementsByTagName("TD")[n];
                y = rows[i + 1].getElementsByTagName("TD")[n];
                
                let xVal = x.innerText.toLowerCase();
                let yVal = y.innerText.toLowerCase();
                
                if (!isNaN(xVal) && !isNaN(yVal)) {
                    xVal = parseFloat(xVal);
                    yVal = parseFloat(yVal);
                }

                if (dir == "asc") {
                    if (xVal > yVal) { shouldSwitch = true; break; }
                } else if (dir == "desc") {
                    if (xVal < yVal) { shouldSwitch = true; break; }
                }
            }
            if (shouldSwitch) {
                rows[i].parentNode.insertBefore(rows[i + 1], rows[i]);
                switching = true;
                switchcount++;
            } else {
                if (switchcount == 0 && dir == "asc") {
                    dir = "desc";
                    switching = true;
                }
            }
        }
    }
</script>

@endsection



