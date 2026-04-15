@extends('layouts.app')

@section('title', 'Pengaturan Sistem')

@section('content')
<div class="grid grid-cols-12 gap-x-6">
    <div class="col-span-12 lg:col-span-6">
        <div class="card">
            <div class="card-header">
                <h5>Konfigurasi Jam Kerja</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('settings.update') }}" method="POST">
                    @csrf
                    <div class="mb-4">
                        <label class="form-label">Batas Scan Masuk (Pagi)</label>
                        <p class="text-muted small">Karyawan dianggap terlambat jika scan setelah jam ini.</p>
                        <input type="time" name="check_in_limit" class="form-control" value="{{ $settings['check_in_limit'] ?? '08:00' }}" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Batas Scan Pulang (Sore)</label>
                        <p class="text-muted small">Scan terakhir dianggap "Pulang" jika setelah jam ini.</p>
                        <input type="time" name="check_out_limit" class="form-control" value="{{ $settings['check_out_limit'] ?? '17:00' }}" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Simpan Pengaturan</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

