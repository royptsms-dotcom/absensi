@extends('layouts.app')

@section('title', 'Data Karyawan')

@section('content')
<div class="col-span-12">
    <div class="card">
        <div class="card-header flex justify-between items-center">
            <h5>Daftar Karyawan</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th onclick="sortTable(0)" style="cursor:pointer" title="Urutkan ID">ID</th>
                            <th onclick="sortTable(1)" style="cursor:pointer" title="Urutkan Nama">NAMA</th>
                            <th>DEPARTEMEN</th>
                            <th class="text-center">AKSI</th>
                        </tr>
                    </thead>

                    <tbody id="employeeTable">

                        @forelse($employees as $emp)
                        <tr>
                            <td><strong>{{ $emp->employee_id }}</strong></td>
                            <td>{{ $emp->name }}</td>
                            <td>{{ $emp->department }}</td>
                            <td class="text-center">
                                <a href="{{ route('employees.edit', $emp->id) }}" class="btn btn-sm btn-primary">Edit</a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center py-5">
                                <p class="text-muted">Data masih kosong. Silahkan lakukan <strong>Import Absensi</strong> terlebih dahulu untuk mendaftarkan karyawan secara otomatis.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
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
                
                // Numeric sort for ID if applicable
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

