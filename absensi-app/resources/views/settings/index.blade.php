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
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <h6 class="mb-3 font-bold text-primary">Reguler (Senin - Jumat)</h6>
                            <div class="mb-3">
                                <label class="form-label">Batas Scan Masuk</label>
                                <input type="time" name="check_in_limit" class="form-control" value="{{ $settings['check_in_limit'] ?? '08:00' }}" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Batas Scan Pulang</label>
                                <input type="time" name="check_out_limit" class="form-control" value="{{ $settings['check_out_limit'] ?? '17:00' }}" required>
                            </div>
                        </div>
                        <div class="col-md-6 mb-4 border-l pl-4">
                            <h6 class="mb-3 font-bold text-success">Khusus Hari Sabtu</h6>
                            <div class="mb-3">
                                <label class="form-label">Batas Scan Masuk (Sabtu)</label>
                                <input type="time" name="saturday_in_limit" class="form-control" value="{{ $settings['saturday_in_limit'] ?? '08:00' }}" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Batas Scan Pulang (Sabtu)</label>
                                <input type="time" name="saturday_out_limit" class="form-control" value="{{ $settings['saturday_out_limit'] ?? '13:00' }}" required>
                            </div>
                        </div>
                    </div>
                    <hr class="my-4">
                    <button type="submit" class="btn btn-primary">Simpan Semua Pengaturan</button>
                </form>
            </div>

        </div>
    </div>
</div>
@endsection

