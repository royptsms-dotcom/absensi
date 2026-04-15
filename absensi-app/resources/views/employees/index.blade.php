@extends('layouts.app')

@section('title', 'Data Karyawan')

@section('content')
<div class="card">
    <div class="card-header flex justify-between items-center">
        <h5>Daftar Karyawan</h5>
        <button class="btn btn-primary btn-sm">Tambah Karyawan</button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama</th>
                        <th>Departemen</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="4" class="text-center">Data masih kosong manually added soon.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
