@extends('layouts.admin')

@section('title', 'Notifikasi ONU Baru')

@section('page-title', 'Notifikasi ONU Baru Terdeteksi')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.pop-settings.isp-info') }}">Pengaturan POP</a></li>
    <li class="breadcrumb-item active">Notifikasi ONU Baru</li>
@endsection

@php $cfg = $popSetting->unregNotifSetting(); @endphp

@section('content')
<div class="row">
    <div class="col-lg-3">
        @include('admin.pop-settings.partials.sidebar')
    </div>
    <div class="col-lg-9">
        @if($popUsers && auth()->user()->hasRole('superadmin'))
        <div class="card card-outline card-info mb-3">
            <div class="card-body py-2">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <i class="fas fa-user-shield text-info fa-lg"></i>
                        <strong class="ml-2">Mode Superadmin:</strong>
                    </div>
                    <div class="col">
                        <select class="form-control select2" id="selectPopUser">
                            <option value="">-- Pilih Admin POP --</option>
                            @foreach($popUsers as $popUser)
                                <option value="{{ $popUser->id }}" {{ $userId == $popUser->id ? 'selected' : '' }}>
                                    {{ $popUser->name }} ({{ $popUser->email }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <form id="unregNotifForm">
            @csrf
            @if($userId)<input type="hidden" name="user_id" value="{{ $userId }}">@endif

            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-satellite-dish mr-2"></i> Pengaturan Notifikasi ONU Baru
                    </h3>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-1"></i>
                        Sistem akan memindai OLT secara berkala dan menampilkan notifikasi
                        di lonceng atas setiap kali ONU baru (belum teregistrasi) terdeteksi.
                    </div>

                    <div class="form-group">
                        <div class="custom-control custom-switch custom-switch-on-success">
                            <input type="hidden" name="enabled" value="0">
                            <input type="checkbox" class="custom-control-input" id="enabled" name="enabled" value="1" {{ $cfg['enabled'] ? 'checked' : '' }}>
                            <label class="custom-control-label" for="enabled">
                                <strong>Aktifkan deteksi ONU baru</strong>
                            </label>
                        </div>
                        <small class="text-muted">Matikan jika tidak ingin menerima notifikasi sama sekali.</small>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="scan_interval">Interval Pemindaian OLT</label>
                                <select class="form-control" id="scan_interval" name="scan_interval">
                                    @foreach([60=>'Setiap 1 menit',120=>'Setiap 2 menit',300=>'Setiap 5 menit',600=>'Setiap 10 menit',1800=>'Setiap 30 menit',3600=>'Setiap 1 jam'] as $sec=>$lbl)
                                        <option value="{{ $sec }}" {{ (int)$cfg['scan_interval']===$sec?'selected':'' }}>{{ $lbl }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Seberapa sering server menanyakan OLT untuk daftar ONU baru.</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="poll_interval">Interval Refresh Lonceng (Browser)</label>
                                <select class="form-control" id="poll_interval" name="poll_interval">
                                    @foreach([15=>'15 detik',30=>'30 detik',60=>'1 menit',120=>'2 menit',300=>'5 menit'] as $sec=>$lbl)
                                        <option value="{{ $sec }}" {{ (int)$cfg['poll_interval']===$sec?'selected':'' }}>{{ $lbl }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Seberapa sering browser menarik notifikasi terbaru.</small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="hidden" name="toast" value="0">
                                    <input type="checkbox" class="custom-control-input" id="toast" name="toast" value="1" {{ $cfg['toast'] ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="toast">Tampilkan pop-up (toast)</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="hidden" name="sound" value="0">
                                    <input type="checkbox" class="custom-control-input" id="sound" name="sound" value="1" {{ $cfg['sound'] ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="sound">Bunyi suara peringatan</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="olts">OLT yang Dipantau</label>
                        <select class="form-control select2" id="olts" name="olts[]" multiple>
                            @foreach($olts as $olt)
                                <option value="{{ $olt->id }}" {{ in_array($olt->id, (array)$cfg['olts']) ? 'selected' : '' }}>
                                    {{ $olt->name }} <small>({{ strtoupper($olt->brand) }})</small>
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">
                            Kosongkan untuk memantau <strong>semua OLT aktif</strong> di POP ini.
                        </small>
                    </div>

                </div>
                <div class="card-footer text-right">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-1"></i> Simpan Pengaturan
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('js')
<script>
$(function () {
    $('#olts').select2({ theme: 'bootstrap-5', placeholder: 'Pilih OLT (kosong = semua)' });

    $('#selectPopUser').change(function () {
        const v = $(this).val();
        if (v) window.location.href = '{{ route("admin.pop-settings.unreg-notif") }}?user_id=' + v;
    });

    $('#unregNotifForm').submit(function (e) {
        e.preventDefault();
        const $btn = $(this).find('button[type="submit"]');
        const orig = $btn.html();
        $btn.html('<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...').prop('disabled', true);

        $.ajax({
            url: '{{ route("admin.pop-settings.update-unreg-notif") }}',
            method: 'POST',
            data: $(this).serialize(),
            success: function (res) {
                if (res.success) toastr.success(res.message);
                else toastr.error(res.message || 'Gagal menyimpan');
            },
            error: function (xhr) {
                const errs = xhr.responseJSON?.errors;
                if (errs) Object.values(errs).flat().forEach(m => toastr.error(m));
                else toastr.error(xhr.responseJSON?.message || 'Terjadi kesalahan');
            },
            complete: function () { $btn.html(orig).prop('disabled', false); }
        });
    });
});
</script>
@endpush
