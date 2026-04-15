@extends('layouts.app')

@section('title', 'Edit Data Karyawan')

@section('content')
<div class="grid grid-cols-12">
    <div class="col-span-12 lg:col-span-6">
        <div class="card">
            <div class="card-header">
                <h5>Edit Profil: #{{ $employee->employee_id }}</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('employees.update', $employee->id) }}" method="POST">
                    @csrf
                    <div class="mb-4">
                        <label class="form-label">No. ID (Karyawan)</label>
                        <input type="text" class="form-control" value="{{ $employee->employee_id }}" disabled>
                        <p class="text-muted small">ID tidak dapat diubah untuk menjaga integritas data absensi.</p>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" name="name" class="form-control" value="{{ $employee->name }}" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Departemen / Divisi</label>
                        <input type="text" name="department" class="form-control" value="{{ $employee->department }}" required>
                    </div>
                    
                    <div class="flex gap-2">
                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                        <a href="{{ route('employees.index') }}" class="btn btn-light-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
