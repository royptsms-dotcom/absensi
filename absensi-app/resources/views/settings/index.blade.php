@extends('layouts.app')

@section('title', 'Setting Aplikasi')

@section('content')
<div class="card lg:w-1/2">
    <div class="card-header">
        <h5>Konfigurasi Umum</h5>
    </div>
    <div class="card-body">
        <form action="{{ route('settings.update') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label class="form-label">Nama Perusahaan</label>
                <input type="text" class="form-control" value="My Company">
            </div>
            <div class="mb-3">
                <label class="form-label">Email Notifikasi</label>
                <input type="email" class="form-control" value="admin@company.com">
            </div>
            <div class="mb-3">
                <label class="form-label">Jam Masuk Standar</label>
                <input type="time" class="form-control" value="08:00">
            </div>
            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
        </form>
    </div>
</div>
@endsection
