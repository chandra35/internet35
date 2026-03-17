@extends('layouts.admin')

@section('title', 'Perawatan Data')
@section('page-title', 'Perawatan Data')

@section('breadcrumb')
    <li class="breadcrumb-item active">Perawatan Data</li>
@endsection

@section('content')
    {{-- POP Selector for Superadmin --}}
    @if($popUsers)
    <div class="row mb-3">
        <div class="col-md-4">
            <div class="card card-outline card-warning">
                <div class="card-body py-2">
                    <label class="mb-1 font-weight-bold">Pilih POP</label>
                    <select id="popSelector" class="form-control">
                        <option value="">-- Pilih POP --</option>
                        @foreach($popUsers as $pop)
                            <option value="{{ $pop->id }}" {{ $popId == $pop->id ? 'selected' : '' }}>{{ $pop->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Warning Banner --}}
    <div class="callout callout-danger">
        <h5><i class="fas fa-exclamation-triangle"></i> Zona Berbahaya</h5>
        <p>Halaman ini berisi operasi yang <strong>menghapus data secara massal</strong>. Semua penghapusan menggunakan <em>soft delete</em> sehingga masih dapat dipulihkan oleh developer. Pastikan Anda memahami konsekuensi sebelum melanjutkan.</p>
    </div>

    {{-- Statistics --}}
    @if(!empty($stats))
    <div class="row">
        <div class="col-md-4">
            <div class="info-box bg-light">
                <span class="info-box-icon bg-info"><i class="fas fa-users"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Total Pelanggan</span>
                    <span class="info-box-number" id="statCustomers">{{ number_format($stats['customers_total'] ?? 0) }}</span>
                    <span class="text-sm">Aktif: {{ $stats['customers_active'] ?? 0 }} | Synced: {{ $stats['customers_synced'] ?? 0 }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="info-box bg-light">
                <span class="info-box-icon bg-success"><i class="fas fa-box"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Total Paket</span>
                    <span class="info-box-number" id="statPackages">{{ number_format($stats['packages_total'] ?? 0) }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="info-box bg-light">
                <span class="info-box-icon bg-purple"><i class="fas fa-network-wired"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Total PPP Profile</span>
                    <span class="info-box-number" id="statProfiles">{{ number_format($stats['profiles_total'] ?? 0) }}</span>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- No POP selected --}}
    @if(!$popId)
    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i> Pilih POP terlebih dahulu untuk melihat opsi perawatan data.
    </div>
    @else

    {{-- Clear Customers --}}
    <div class="card card-outline card-danger">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-users text-danger mr-2"></i>Kosongkan Data Pelanggan</h3>
        </div>
        <div class="card-body">
            <p>Menghapus <strong>semua data pelanggan</strong> pada POP ini. Data akan di-soft delete dan masih dapat dipulihkan.</p>
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-sm table-borderless mb-3">
                        <tr><td class="text-muted" style="width:180px">Jumlah pelanggan</td><td><strong>{{ $stats['customers_total'] ?? 0 }}</strong></td></tr>
                        <tr><td class="text-muted">Pelanggan aktif</td><td><strong>{{ $stats['customers_active'] ?? 0 }}</strong></td></tr>
                        <tr><td class="text-muted">Tersinkronisasi</td><td><strong>{{ $stats['customers_synced'] ?? 0 }}</strong></td></tr>
                    </table>
                </div>
            </div>
            <div class="custom-control custom-checkbox mb-3">
                <input type="checkbox" class="custom-control-input" id="chkDeleteMikrotikCustomers">
                <label class="custom-control-label text-danger" for="chkDeleteMikrotikCustomers">
                    <strong>Hapus juga PPP Secret dari Mikrotik</strong>
                    <small class="d-block text-muted">Jika dicentang, semua PPP Secret pelanggan yang tersinkronisasi akan dihapus dari router Mikrotik.</small>
                </label>
            </div>
            <button class="btn btn-danger" onclick="confirmClear('customers')" {{ ($stats['customers_total'] ?? 0) == 0 ? 'disabled' : '' }}>
                <i class="fas fa-trash mr-1"></i> Kosongkan Semua Pelanggan
            </button>
        </div>
    </div>

    {{-- Clear Packages --}}
    <div class="card card-outline card-danger">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-box text-danger mr-2"></i>Kosongkan Data Paket</h3>
        </div>
        <div class="card-body">
            <p>Menghapus <strong>semua data paket</strong> pada router POP ini. Data akan di-soft delete.</p>
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-sm table-borderless mb-3">
                        <tr><td class="text-muted" style="width:180px">Jumlah paket</td><td><strong>{{ $stats['packages_total'] ?? 0 }}</strong></td></tr>
                    </table>
                </div>
            </div>
            <button class="btn btn-danger" onclick="confirmClear('packages')" {{ ($stats['packages_total'] ?? 0) == 0 ? 'disabled' : '' }}>
                <i class="fas fa-trash mr-1"></i> Kosongkan Semua Paket
            </button>
        </div>
    </div>

    {{-- Clear Profiles --}}
    <div class="card card-outline card-danger">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-network-wired text-danger mr-2"></i>Kosongkan Data PPP Profile</h3>
        </div>
        <div class="card-body">
            <p>Menghapus <strong>semua data PPP Profile</strong> pada router POP ini. Data akan di-soft delete. Profile bawaan (default, default-encryption) akan dilewati saat penghapusan Mikrotik.</p>
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-sm table-borderless mb-3">
                        <tr><td class="text-muted" style="width:180px">Jumlah profile</td><td><strong>{{ $stats['profiles_total'] ?? 0 }}</strong></td></tr>
                    </table>
                </div>
            </div>
            <div class="custom-control custom-checkbox mb-3">
                <input type="checkbox" class="custom-control-input" id="chkDeleteMikrotikProfiles">
                <label class="custom-control-label text-danger" for="chkDeleteMikrotikProfiles">
                    <strong>Hapus juga PPP Profile dari Mikrotik</strong>
                    <small class="d-block text-muted">Jika dicentang, semua PPP Profile (kecuali default) akan dihapus dari router Mikrotik.</small>
                </label>
            </div>
            <button class="btn btn-danger" onclick="confirmClear('profiles')" {{ ($stats['profiles_total'] ?? 0) == 0 ? 'disabled' : '' }}>
                <i class="fas fa-trash mr-1"></i> Kosongkan Semua Profile
            </button>
        </div>
    </div>

    @endif
@endsection

@push('js')
<script>
const CLEAR_CONFIG = {
    customers: {
        url: '{{ route("admin.data-maintenance.clear-customers") }}',
        title: 'Kosongkan Semua Pelanggan?',
        text: 'Semua data pelanggan pada POP ini akan dihapus. Tindakan ini sangat berbahaya!',
        confirmText: 'KOSONGKAN PELANGGAN',
        hasMikrotik: true,
        mikrotikCheckbox: 'chkDeleteMikrotikCustomers'
    },
    packages: {
        url: '{{ route("admin.data-maintenance.clear-packages") }}',
        title: 'Kosongkan Semua Paket?',
        text: 'Semua data paket pada POP ini akan dihapus. Pelanggan yang menggunakan paket ini akan kehilangan referensi paket.',
        confirmText: 'KOSONGKAN PAKET',
        hasMikrotik: false
    },
    profiles: {
        url: '{{ route("admin.data-maintenance.clear-profiles") }}',
        title: 'Kosongkan Semua PPP Profile?',
        text: 'Semua data PPP Profile pada POP ini akan dihapus.',
        confirmText: 'KOSONGKAN PROFILE',
        hasMikrotik: true,
        mikrotikCheckbox: 'chkDeleteMikrotikProfiles'
    }
};

function getPopId() {
    let sel = document.getElementById('popSelector');
    return sel ? sel.value : '{{ $popId }}';
}

function confirmClear(type) {
    let config = CLEAR_CONFIG[type];
    if (!config) return;

    // Step 1: Warning
    Swal.fire({
        title: config.title,
        html: '<p>' + config.text + '</p>' +
              (config.hasMikrotik && document.getElementById(config.mikrotikCheckbox)?.checked
                ? '<p class="text-danger font-weight-bold">⚠️ Termasuk penghapusan dari Mikrotik!</p>' : ''),
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Ya, Lanjutkan',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (!result.isConfirmed) return;

        // Step 2: Type confirmation text
        Swal.fire({
            title: 'Ketik Teks Konfirmasi',
            html: '<p>Ketik <strong class="text-danger">' + config.confirmText + '</strong> untuk melanjutkan:</p>',
            input: 'text',
            inputPlaceholder: config.confirmText,
            inputValidator: (value) => {
                if (value !== config.confirmText) {
                    return 'Teks konfirmasi tidak sesuai!';
                }
            },
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Lanjutkan',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (!result.isConfirmed) return;

            // Step 3: Password verification
            Swal.fire({
                title: 'Verifikasi Password',
                html: '<p>Masukkan password akun Anda untuk mengonfirmasi:</p>',
                input: 'password',
                inputPlaceholder: 'Password Anda',
                inputValidator: (value) => {
                    if (!value) return 'Password harus diisi!';
                },
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                confirmButtonText: 'Hapus Sekarang',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (!result.isConfirmed) return;

                let password = result.value;
                executeClear(type, config, password);
            });
        });
    });
}

function executeClear(type, config, password) {
    let data = {
        password: password,
        confirmation: config.confirmText,
        pop_id: getPopId()
    };

    if (config.hasMikrotik) {
        data.delete_from_mikrotik = document.getElementById(config.mikrotikCheckbox)?.checked ? 1 : 0;
    }

    Swal.fire({
        title: 'Memproses...',
        html: 'Mohon tunggu, proses penghapusan sedang berjalan...',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    $.ajax({
        url: config.url,
        type: 'POST',
        data: data,
        success: function(res) {
            if (res.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    html: res.message,
                    confirmButtonText: 'OK'
                }).then(() => location.reload());
            } else {
                Swal.fire('Gagal', res.message, 'error');
            }
        },
        error: function(xhr) {
            let msg = xhr.responseJSON?.message || 'Terjadi kesalahan';
            Swal.fire('Error', msg, 'error');
        }
    });
}

// POP selector change handler
$(document).ready(function() {
    $('#popSelector').on('change', function() {
        let popId = $(this).val();
        if (popId) {
            window.location.href = '{{ route("admin.data-maintenance.index") }}?pop_id=' + popId;
        } else {
            window.location.href = '{{ route("admin.data-maintenance.index") }}';
        }
    });
});
</script>
@endpush
