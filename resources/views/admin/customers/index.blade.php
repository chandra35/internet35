@extends('layouts.admin')

@section('title', 'Pelanggan')

@section('page-title', 'Manajemen Pelanggan')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">Pelanggan</li>
@endsection

@push('css')
<style>
    /* Stat cards */
    .stat-card { transition: transform 0.2s, box-shadow 0.2s; cursor: pointer; }
    .stat-card:hover { transform: translateY(-3px); box-shadow: 0 6px 18px rgba(0,0,0,0.15) !important; }
    /* Customer list card */
    .card-customers { border: none !important; border-radius: 10px !important; overflow: hidden; }
    .card-customers > .card-header {
        background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
        border-bottom: none; padding: 14px 20px;
    }
    .card-customers > .card-header .card-title { color: white; font-size: 1rem; font-weight: 600; }
    /* Customer avatar */
    .cust-avatar {
        width: 36px; height: 36px; border-radius: 50%; flex-shrink: 0;
        object-fit: cover; display: flex; align-items: center; justify-content: center;
        font-weight: 700; font-size: 0.9rem; color: white;
    }
    /* Table */
    .table-customers { font-size: 0.875rem; }
    .table-customers thead th {
        background: #f4f6fb; color: #5a6a7e;
        font-size: 0.69rem; font-weight: 700;
        text-transform: uppercase; letter-spacing: 0.5px;
        border-top: none; border-bottom: 2px solid #dde3ec;
        padding: 10px 14px; white-space: nowrap;
    }
    .table-customers tbody td {
        padding: 10px 14px; vertical-align: middle;
        border-top: 1px solid #f0f2f8;
    }
    .table-customers tbody tr:hover td { background: #f0f5ff; }
    /* Action buttons */
    .btn-action {
        width: 28px; height: 28px; padding: 0;
        display: inline-flex; align-items: center; justify-content: center;
        border-radius: 50% !important; font-size: 0.7rem;
    }
    .btn-action.dropdown-toggle::after { display: none; }
    /* Bulk toolbar */
    .bulk-toolbar {
        display: none;
        background: linear-gradient(135deg, #2d3748, #4a5568);
        color: white; padding: 12px 16px; border-radius: 8px;
        margin-bottom: 14px; animation: slideDown 0.2s ease;
        box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    }
    .bulk-toolbar.show { display: flex; flex-wrap: wrap; align-items: center; }
    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-8px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    .cb-cell { width: 38px; text-align: center; }
    .cb-cell .custom-control { display: inline-block; }
    .badge-isolir { font-size: 0.67rem; cursor: pointer; transition: opacity 0.15s; }
    .badge-isolir:hover { opacity: 0.75; }
    /* Filter bar */
    .filter-bar {
        background: white; border: 1px solid #dde3ec; border-radius: 10px;
        padding: 14px 16px; margin-bottom: 14px; box-shadow: 0 1px 5px rgba(0,0,0,0.04);
    }
    #searchInput { border-right: none; border-radius: 6px 0 0 6px; }
    #searchInput:focus { box-shadow: none; border-color: #80bdff; }
    .search-input-group .input-group-text {
        background: white; border-left: none; color: #6c757d;
        border-radius: 0 6px 6px 0; cursor: pointer;
    }
    .filter-tag {
        display: inline-flex; align-items: center; background: #e8f1ff;
        color: #2c5fb3; border: 1px solid #c0d6f9; border-radius: 20px;
        padding: 2px 10px; font-size: 0.73rem; font-weight: 500; margin: 2px 3px;
    }
</style>
@endpush

@section('content')
<!-- POP Selector for Superadmin -->
@if($popUsers && auth()->user()->hasRole('superadmin'))
<div class="card card-outline card-info mb-3">
    <div class="card-body py-2">
        <div class="row align-items-center">
            <div class="col-auto">
                <i class="fas fa-user-shield text-info fa-lg"></i>
                <strong class="ml-2">Mode Superadmin:</strong>
            </div>
            <div class="col-md-4">
                <select class="form-control select2" id="selectPop" onchange="changePop(this.value)">
                    <option value="">-- Pilih POP --</option>
                    @foreach($popUsers as $pop)
                        <option value="{{ $pop->id }}" {{ $popId == $pop->id ? 'selected' : '' }}>
                            {{ $pop->name }} ({{ $pop->email }})
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
</div>
@endif

@if(!$popId && auth()->user()->hasRole('superadmin'))
<div class="alert alert-warning">
    <i class="fas fa-exclamation-triangle mr-2"></i>
    Pilih POP terlebih dahulu untuk mengelola pelanggan.
</div>
@else

<!-- Statistics -->
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info stat-card">
            <div class="inner">
                <h3>{{ number_format($stats['total']) }}</h3>
                <p>Total Pelanggan</p>
            </div>
            <div class="icon">
                <i class="fas fa-users"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success stat-card">
            <div class="inner">
                <h3>{{ number_format($stats['active']) }}</h3>
                <p>Aktif</p>
            </div>
            <div class="icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <a href="{{ route('admin.customers.index', ['status' => 'active']) }}" class="small-box-footer">
                Lihat <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning stat-card">
            <div class="inner">
                <h3>{{ number_format($stats['pending']) }}</h3>
                <p>Pending</p>
            </div>
            <div class="icon">
                <i class="fas fa-clock"></i>
            </div>
            <a href="{{ route('admin.customers.index', ['status' => 'pending']) }}" class="small-box-footer">
                Lihat <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger stat-card">
            <div class="inner">
                <h3>{{ number_format($stats['suspended']) }}</h3>
                <p>Suspended</p>
            </div>
            <div class="icon">
                <i class="fas fa-ban"></i>
            </div>
            <a href="{{ route('admin.customers.index', ['status' => 'suspended']) }}" class="small-box-footer">
                Lihat <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
</div>

<!-- Customer List -->
<div class="card card-customers shadow-sm">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-users mr-2"></i>Daftar Pelanggan
        </h3>
        <div class="card-tools">
            @can('customers.create')
            <a href="{{ route('admin.customers.import') }}" class="btn btn-light btn-sm mr-1">
                <i class="fas fa-file-import mr-1"></i> Import
            </a>
            <a href="{{ route('admin.customers.create') }}" class="btn btn-warning btn-sm">
                <i class="fas fa-plus mr-1"></i> Tambah Pelanggan
            </a>
            @endcan
        </div>
    </div>
    <div class="card-body p-3">
        <!-- Filter Bar -->
        <div class="filter-bar">
            {{-- Baris 1: Live Search --}}
            <div class="row mb-2">
                <div class="col-12">
                    <div class="input-group search-input-group">
                        <input type="text" id="searchInput" class="form-control form-control"
                               placeholder="&#xf002;  Cari nama, panggilan, ID pelanggan, telepon, email, PPPoE..."
                               value="{{ request('search') }}" autocomplete="off">
                        <div class="input-group-append">
                            <span class="input-group-text" id="searchStatusIcon">
                                @if(request('search'))
                                <i class="fas fa-times text-muted" id="iconClear" style="cursor:pointer;" title="Hapus pencarian"></i>
                                @else
                                <i class="fas fa-search" id="iconSearch"></i>
                                @endif
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            {{-- Baris 2: Dropdown Filters --}}
            <div class="row g-2 align-items-center">
                <div class="col-6 col-sm-4 col-md-2">
                    <select id="filterStatus" class="form-control form-control-sm select2" data-param="status" title="Status">
                        <option value="">Semua Status</option>
                        @foreach(\App\Models\Customer::statusLabels() as $key => $label)
                        <option value="{{ $key }}" {{ request('status') == $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-sm-4 col-md-2">
                    <select id="filterPackage" class="form-control form-control-sm select2" data-param="package_id" title="Paket">
                        <option value="">Semua Paket</option>
                        @foreach($packages as $pkg)
                        <option value="{{ $pkg->id }}" {{ request('package_id') == $pkg->id ? 'selected' : '' }}>{{ $pkg->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-sm-4 col-md-2">
                    <select id="filterCity" class="form-control form-control-sm select2" data-param="city_code" title="Wilayah">
                        <option value="">Semua Wilayah</option>
                        @foreach($filterCities as $city)
                        <option value="{{ $city->code }}" {{ request('city_code') == $city->code ? 'selected' : '' }}>{{ $city->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-sm-4 col-md-2">
                    <select id="filterRouter" class="form-control form-control-sm select2" data-param="router_id" title="Router">
                        <option value="">Semua Router</option>
                        @foreach($routers as $router)
                        <option value="{{ $router->id }}" {{ request('router_id') == $router->id ? 'selected' : '' }}>{{ $router->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-sm-4 col-md-2">
                    <select id="filterAutoIsolir" class="form-control form-control-sm" data-param="auto_isolir" title="Auto Isolir">
                        <option value="">Auto Isolir</option>
                        <option value="1" {{ request('auto_isolir') === '1' ? 'selected' : '' }}>Aktif</option>
                        <option value="0" {{ request('auto_isolir') === '0' ? 'selected' : '' }}>Nonaktif</option>
                    </select>
                </div>
            </div>
        </div>

        <div id="tableWrapper">
            @include('admin.customers._table')
        </div>
    </div>
</div>
@endif
@endsection

@push('js')
<script>
function changePop(popId) {
    if (popId) {
        window.location.href = '{{ route("admin.customers.index") }}?pop_id=' + popId;
    }
}

$(function() {
    // ========== AJAX Table Loader ==========
    function loadTable(url) {
        const $w = $('#tableWrapper');
        $w.css({opacity: '0.5', 'pointer-events': 'none'});
        selectAll = false;
        $.ajax({
            url: url.toString(),
            headers: {'X-Requested-With': 'XMLHttpRequest'},
            success: function(html) {
                $w.html(html).css({opacity: '1', 'pointer-events': ''});
                history.pushState(null, '', url.toString());
                const q = new URL(url.toString(), window.location.origin).searchParams.get('search') || '';
                $('#searchStatusIcon').html(q
                    ? '<i class="fas fa-times text-muted" id="iconClear" style="cursor:pointer;" title="Hapus pencarian"></i>'
                    : '<i class="fas fa-search" id="iconSearch"></i>');
            },
            error: function() {
                $w.css({opacity: '1', 'pointer-events': ''});
                toastr.error('Gagal memuat data');
            }
        });
    }

    // ========== Live Search & Filter ==========
    function updateFilter(param, value) {
        const url = new URL(window.location.href);
        if (value === '' || value === null || value === undefined) {
            url.searchParams.delete(param);
        } else {
            url.searchParams.set(param, value);
        }
        url.searchParams.delete('page');
        @if($popId && auth()->user()->hasRole('superadmin'))
        url.searchParams.set('pop_id', '{{ $popId }}');
        @endif
        loadTable(url);
    }

    // Debounced live search
    let searchTimer;
    $('#searchInput').on('input', function() {
        clearTimeout(searchTimer);
        const val = this.value;
        // Show spinner
        $('#searchStatusIcon').html('<i class="fas fa-spinner fa-spin text-muted"></i>');
        searchTimer = setTimeout(() => {
            updateFilter('search', val.trim());
        }, 450);
    }).on('keydown', function(e) {
        if (e.key === 'Escape') { updateFilter('search', ''); }
    });

    // Clear search icon click
    $(document).on('click', '#iconClear', function() {
        updateFilter('search', '');
    });

    // Dropdown filters — instant on change
    $('select[data-param]').on('change', function() {
        updateFilter($(this).data('param'), $(this).val());
    });

    // AJAX pagination
    $(document).on('click', '#tableWrapper .pagination a', function(e) {
        e.preventDefault();
        loadTable(new URL($(this).attr('href'), window.location.origin));
    });

    // AJAX reset filters
    $(document).on('click', '#btnResetFilters', function(e) {
        e.preventDefault();
        $('#searchInput').val('');
        loadTable(new URL($(this).attr('href'), window.location.origin));
    });

    function getTotalAll() { return parseInt($('#tableTotalCount').data('total') || 0); }
    let selectAll = false;

    function resetSelectAll() {
        selectAll = false;
        $('#selectAllBanner').hide();
        $('#selectAllActiveNotice').hide();
    }

    function updateBulkToolbar() {
        const checked = document.querySelectorAll('.customer-check:checked');
        const total = document.querySelectorAll('.customer-check').length;
        const count = checked.length;
        if (!selectAll) $('#selectedCount').text(count);
        if (count > 0 || selectAll) {
            $('#bulkToolbar').addClass('show');
        } else {
            $('#bulkToolbar').removeClass('show');
            resetSelectAll();
        }
        if (!selectAll) {
            if (count === total && total > 0 && getTotalAll() > total) {
                $('#selectAllBanner').show();
                $('#selectAllActiveNotice').hide();
            } else {
                $('#selectAllBanner').hide();
            }
        }
    }

    // Check all on page
    $(document).on('change', '#checkAll', function() {
        if (!this.checked) resetSelectAll();
        document.querySelectorAll('.customer-check').forEach(cb => { cb.checked = this.checked; });
        updateBulkToolbar();
    });

    // Individual checkbox
    $(document).on('change', '.customer-check', function() {
        if (!this.checked) resetSelectAll();
        const total = document.querySelectorAll('.customer-check').length;
        const checked = document.querySelectorAll('.customer-check:checked').length;
        document.getElementById('checkAll').checked = (total === checked && total > 0);
        updateBulkToolbar();
    });

    function getSelectedIds() {
        return Array.from(document.querySelectorAll('.customer-check:checked')).map(cb => cb.value);
    }

    // Build AJAX data: handles both page-selection and select-all-pages mode
    function buildBulkData(extra) {
        const data = Object.assign({ _token: '{{ csrf_token() }}' }, extra || {});
        if (selectAll) {
            data.select_all = 1;
            @if(auth()->user()->hasRole('superadmin') && isset($popId) && $popId)
            data.pop_id = '{{ $popId }}';
            @endif
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('status')) data.filter_status = urlParams.get('status');
            if (urlParams.get('router_id')) data.filter_router_id = urlParams.get('router_id');
            if (urlParams.get('package_id')) data.filter_package_id = urlParams.get('package_id');
            if (urlParams.get('city_code')) data.filter_city_code = urlParams.get('city_code');
        } else {
            data.customer_ids = getSelectedIds();
        }
        return data;
    }

    function getDisplayCount() {
        return selectAll ? getTotalAll() : getSelectedIds().length;
    }

    // Select all pages
    $(document).on('click', '#btnSelectAllPages', function(e) {
        e.preventDefault();
        selectAll = true;
        $('#selectedCount').text('Semua ' + getTotalAll());
        $('#selectAllBanner').hide();
        $('#selectAllActiveNotice').show();
    });

    // Cancel select all
    $(document).on('click', '#btnClearSelectAll', function(e) {
        e.preventDefault();
        resetSelectAll();
        document.querySelectorAll('.customer-check').forEach(cb => { cb.checked = false; });
        document.getElementById('checkAll').checked = false;
        $('#selectedCount').text('0');
        $('#bulkToolbar').removeClass('show');
    });

    // Bulk activate pending customers
    $('#btnBulkActivate').on('click', function() {
        const count = getDisplayCount();
        if (!selectAll && count === 0) return;
        Swal.fire({
            title: 'Aktifkan Pelanggan?',
            html: `<p>Aktifkan <strong>${count}</strong> pelanggan terpilih?</p>
                   <small class="text-muted">Hanya pelanggan berstatus <strong>Pending</strong> yang akan diproses. Setelah aktif, pelanggan akan mendapat invoice pada hari tagihan berikutnya.</small>`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-check-circle mr-1"></i> Ya, Aktifkan',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#e6a817',
        }).then(result => {
            if (result.isConfirmed) {
                Swal.fire({ title: 'Memproses...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
                $.ajax({
                    url: '{{ route("admin.customers.bulk-activate") }}',
                    method: 'POST',
                    data: buildBulkData(),
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: response.message,
                            timer: 2000,
                            showConfirmButton: false,
                        }).then(() => location.reload());
                    },
                    error: function(xhr) {
                        Swal.fire('Error', xhr.responseJSON?.message || 'Gagal mengaktifkan pelanggan', 'error');
                    }
                });
            }
        });
    });

    // Bulk enable auto isolir
    $('#btnBulkEnableIsolir').on('click', function() {
        const count = getDisplayCount();
        if (!selectAll && count === 0) return;
        Swal.fire({
            title: 'Aktifkan Auto Isolir?',
            html: `<p>Aktifkan auto-isolir untuk <strong>${count}</strong> pelanggan?</p>
                   <small class="text-muted">Pelanggan akan otomatis diisolir saat jatuh tempo dan belum ada pembayaran.</small>`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-shield-alt mr-1"></i> Ya, Aktifkan',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#17a2b8',
        }).then(result => {
            if (result.isConfirmed) bulkAutoIsolir(true);
        });
    });

    // Bulk disable auto isolir
    $('#btnBulkDisableIsolir').on('click', function() {
        const count = getDisplayCount();
        if (!selectAll && count === 0) return;
        Swal.fire({
            title: 'Nonaktifkan Auto Isolir?',
            html: `<p>Nonaktifkan auto-isolir untuk <strong>${count}</strong> pelanggan?</p>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-unlock mr-1"></i> Ya, Nonaktifkan',
            cancelButtonText: 'Batal',
        }).then(result => {
            if (result.isConfirmed) bulkAutoIsolir(false);
        });
    });

    // Bulk sync to Mikrotik
    $('#btnBulkSyncMikrotik').on('click', function() {
        const count = getDisplayCount();
        if (!selectAll && count === 0) return;
        Swal.fire({
            title: 'Sync ke Mikrotik?',
            html: `<p>Sync <strong>${count}</strong> pelanggan ke Mikrotik?</p>
                   <small class="text-muted">PPP Secret akan dibuat di router masing-masing pelanggan. Pelanggan yang sudah tersinkronisasi akan dilewati.</small>`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-sync mr-1"></i> Ya, Sync',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#17a2b8',
        }).then(result => {
            if (result.isConfirmed) bulkSyncMikrotik();
        });
    });

    function bulkSyncMikrotik() {
        Swal.fire({ title: 'Memproses sync...', html: 'Mohon tunggu, proses sync ke Mikrotik sedang berjalan...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        $.ajax({
            url: '{{ route("admin.customers.bulk-sync-mikrotik") }}',
            method: 'POST',
            data: buildBulkData(),
            success: function(response) {
                let html = `<p>${response.message}</p>`;
                if (response.details) {
                    html += '<div class="text-left" style="max-height:200px;overflow-y:auto;"><small>';
                    response.details.forEach(d => {
                        const icon = d.success ? '✅' : '❌';
                        html += `${icon} ${d.name}: ${d.status}<br>`;
                    });
                    html += '</small></div>';
                }
                Swal.fire({
                    icon: response.success ? 'success' : 'warning',
                    title: response.success ? 'Berhasil!' : 'Selesai',
                    html: html,
                }).then(() => location.reload());
            },
            error: function(xhr) {
                Swal.fire('Error', xhr.responseJSON?.message || 'Gagal memproses sync', 'error');
            }
        });
    }

    function bulkAutoIsolir(enable) {
        Swal.fire({ title: 'Memproses...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        $.ajax({
            url: '{{ route("admin.customers.bulk-auto-isolir") }}',
            method: 'POST',
            data: buildBulkData({ auto_isolir: enable ? 1 : 0 }),
            success: function(response) {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: response.message,
                    timer: 2000,
                    showConfirmButton: false,
                }).then(() => location.reload());
            },
            error: function(xhr) {
                Swal.fire('Error', xhr.responseJSON?.message || 'Gagal memproses', 'error');
            }
        });
    }

    // Bulk generate portal account
    $('#btnBulkGeneratePortal').on('click', function() {
        const count = getDisplayCount();
        if (!selectAll && count === 0) return;
        Swal.fire({
            title: 'Buat Akun Portal?',
            html: `<p>Buat akun portal untuk <strong>${count}</strong> pelanggan?</p>
                   <small class="text-muted">Hanya pelanggan yang sudah punya email dan belum punya akun yang akan diproses. Password menggunakan password PPPoE atau di-generate otomatis.</small>`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-user-plus mr-1"></i> Ya, Buat Akun',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#28a745',
        }).then(result => {
            if (result.isConfirmed) bulkGeneratePortal();
        });
    });

    function bulkGeneratePortal() {
        Swal.fire({ title: 'Membuat akun portal...', html: 'Mohon tunggu...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        $.ajax({
            url: '{{ route("admin.customers.bulk-generate-portal") }}',
            method: 'POST',
            data: buildBulkData(),
            success: function(response) {
                let html = `<p>${response.message}</p>`;
                if (response.details) {
                    html += '<div class="text-left" style="max-height:200px;overflow-y:auto;"><small>';
                    response.details.forEach(d => {
                        const icon = d.success ? '✅' : '❌';
                        html += `${icon} ${d.name}: ${d.status}<br>`;
                    });
                    html += '</small></div>';
                }
                Swal.fire({
                    icon: response.success ? 'success' : 'warning',
                    title: response.success ? 'Berhasil!' : 'Selesai',
                    html: html,
                }).then(() => location.reload());
            },
            error: function(xhr) {
                Swal.fire('Error', xhr.responseJSON?.message || 'Gagal membuat akun portal', 'error');
            }
        });
    }

    // ========== Individual auto-isolir toggle (badge click) ==========
    $(document).on('click', '.badge-isolir', function(e) {
        e.stopPropagation();
        const badge = $(this);
        const id = badge.data('id');
        const currentState = badge.data('isolir');
        const newState = currentState ? 0 : 1;
        const label = newState ? 'Aktifkan' : 'Nonaktifkan';

        $.ajax({
            url: '{{ route("admin.customers.bulk-auto-isolir") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                customer_ids: [id],
                auto_isolir: newState,
            },
            success: function(response) {
                toastr.success(response.message);
                // Update badge inline
                if (newState) {
                    badge.removeClass('badge-light').addClass('badge-info')
                         .attr('title', 'Auto-isolir aktif — klik untuk nonaktifkan')
                         .data('isolir', 1)
                         .html('<i class="fas fa-shield-alt mr-1"></i>Auto Isolir');
                } else {
                    badge.removeClass('badge-info').addClass('badge-light')
                         .attr('title', 'Auto-isolir nonaktif — klik untuk aktifkan')
                         .data('isolir', 0)
                         .html('<i class="fas fa-unlock mr-1"></i>No Isolir');
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Gagal mengubah auto-isolir');
            }
        });
    });

    // Show password
    $(document).on('click', '.btn-show-password', function() {
        const id = $(this).data('id');
        const btn = $(this);
        
        Swal.fire({
            title: 'Lihat Password PPPoE?',
            text: 'Tindakan ini akan dicatat di activity log.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, Tampilkan',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.get(`{{ url('admin/customers') }}/${id}/password`, function(response) {
                    if (response.success) {
                        Swal.fire({
                            title: 'Password PPPoE',
                            html: `<input type="text" class="form-control text-center" value="${response.password}" readonly id="pwdField">`,
                            confirmButtonText: 'Salin',
                            showCancelButton: true,
                            cancelButtonText: 'Tutup'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                navigator.clipboard.writeText(response.password);
                                toastr.success('Password berhasil disalin');
                            }
                        });
                    }
                });
            }
        });
    });

    // Change status
    $(document).on('click', '.btn-change-status', function(e) {
        e.preventDefault();
        const id = $(this).data('id');
        const status = $(this).data('status');
        const statusLabel = status === 'active' ? 'Aktifkan' : (status === 'suspended' ? 'Suspend' : status);
        
        let html = `<p>Ubah status pelanggan menjadi <strong>${statusLabel}</strong>?</p>`;
        if (status === 'suspended') {
            html += `<div class="form-group text-left">
                <label>Alasan Suspend:</label>
                <textarea id="suspendReason" class="form-control" rows="2" placeholder="Opsional..."></textarea>
            </div>`;
        }
        
        Swal.fire({
            title: 'Ubah Status',
            html: html,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Ubah',
            cancelButtonText: 'Batal',
            preConfirm: () => {
                return { reason: $('#suspendReason').val() || null };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                $.post(`{{ url('admin/customers') }}/${id}/status`, {
                    _token: '{{ csrf_token() }}',
                    status: status,
                    reason: result.value.reason
                }, function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        location.reload();
                    } else {
                        toastr.error(response.message);
                    }
                }).fail(function(xhr) {
                    toastr.error(xhr.responseJSON?.message || 'Gagal mengubah status');
                });
            }
        });
    });

    // Delete
    $(document).on('click', '.btn-delete', function(e) {
        e.preventDefault();
        const id = $(this).data('id');
        const name = $(this).data('name');
        
        Swal.fire({
            title: 'Hapus Pelanggan?',
            html: `<p>Anda akan menghapus pelanggan <strong>${name}</strong>.</p><p class="text-danger">Data pelanggan termasuk invoice dan pembayaran akan dihapus!</p>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `{{ url('admin/customers') }}/${id}`,
                    type: 'DELETE',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message);
                            location.reload();
                        } else {
                            toastr.error(response.message);
                        }
                    },
                    error: function(xhr) {
                        toastr.error(xhr.responseJSON?.message || 'Gagal menghapus pelanggan');
                    }
                });
            }
        });
    });

    // Isolir pelanggan (PPPoE profile change)
    $(document).on('click', '.btn-isolir', function(e) {
        e.preventDefault();
        const id = $(this).data('id');
        const name = $(this).data('name');
        const username = $(this).data('username');

        Swal.fire({
            title: '<i class="fas fa-ban text-warning"></i> Isolir Pelanggan?',
            html: `<p>Isolir pelanggan <strong>${name}</strong> (<code>${username}</code>)?</p>
                   <p class="text-muted small mb-2">Profile PPPoE akan diubah ke <code>isolir</code> dan koneksi aktif akan diputus.</p>
                   <div class="form-group text-left">
                       <label>Alasan isolir:</label>
                       <textarea id="isolirReason" class="form-control form-control-sm" rows="2" placeholder="Contoh: Belum bayar bulan ini">Isolir manual oleh admin</textarea>
                   </div>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-ban mr-1"></i> Ya, Isolir',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#e6a817',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                const reason = $('#isolirReason').val() || 'Isolir manual oleh admin';
                return $.ajax({
                    url: `{{ url('admin/customers') }}/${id}/isolir`,
                    method: 'POST',
                    data: { _token: '{{ csrf_token() }}', reason: reason },
                }).then(response => response).catch(xhr => {
                    Swal.showValidationMessage(xhr.responseJSON?.message || 'Gagal melakukan isolir');
                });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                Swal.fire({
                    title: 'Berhasil!',
                    text: result.value.message,
                    icon: 'success',
                }).then(() => location.reload());
            }
        });
    });

    // Buka isolir pelanggan
    $(document).on('click', '.btn-buka-isolir', function(e) {
        e.preventDefault();
        const id = $(this).data('id');
        const name = $(this).data('name');
        const pkg = $(this).data('package');

        Swal.fire({
            title: '<i class="fas fa-unlock text-success"></i> Buka Isolir?',
            html: `<p>Buka isolir pelanggan <strong>${name}</strong>?</p>
                   <p class="text-muted small">Profile PPPoE dikembalikan ke paket <strong>${pkg}</strong> dan koneksi di-reconnect.</p>`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-unlock mr-1"></i> Ya, Buka Isolir',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#28a745',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                return $.ajax({
                    url: `{{ url('admin/customers') }}/${id}/buka-isolir`,
                    method: 'POST',
                    data: { _token: '{{ csrf_token() }}' },
                }).then(response => response).catch(xhr => {
                    Swal.showValidationMessage(xhr.responseJSON?.message || 'Gagal membuka isolir');
                });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                const icon = result.value.partial ? 'warning' : 'success';
                Swal.fire({
                    title: result.value.partial ? 'Sebagian Berhasil' : 'Berhasil!',
                    text: result.value.message,
                    icon: icon,
                }).then(() => location.reload());
            }
        });
    });
});
</script>
@endpush
