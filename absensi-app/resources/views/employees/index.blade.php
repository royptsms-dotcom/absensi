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
                            <th>ID</th>
                            <th>NAMA</th>
                            <th>DEPARTEMEN</th>
                            <th class="text-center">AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
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
