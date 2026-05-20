@extends('layouts.admin')

@section('title', 'Pelanggan')

@section('page-title', 'Manajemen Pelanggan')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">Pelanggan</li>
@endsection

@push('css')
<style>
    .customer-photo {
        width: 38px;
        height: 38px;
        border-radius: 50%;
        object-fit: cover;
        flex-shrink: 0;
    }
    .stat-card {
        transition: all 0.3s ease;
    }
    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    .bulk-toolbar {
        display: none;
        background: #343a40;
        color: white;
        padding: 10px 15px;
        border-radius: 6px;
        margin-bottom: 15px;
        animation: slideDown 0.2s ease;
    }
    .bulk-toolbar.show { display: flex; flex-wrap: wrap; }
    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .cb-cell { width: 35px; text-align: center; }
    .cb-cell .custom-control { display: inline-block; }
    .badge-isolir {
        font-size: 0.7rem;
        cursor: pointer;
        transition: opacity 0.2s;
    }
    .badge-isolir:hover { opacity: 0.8; }
    /* Filter bar */
    .filter-bar { background: #f8f9fa; border-radius: 8px; padding: 14px 16px; margin-bottom: 14px; border: 1px solid #e9ecef; }
    #searchInput { border-right: none; }
    #searchInput:focus { box-shadow: none; border-color: #80bdff; }
    .search-input-group .input-group-text { background: white; border-left: none; color: #6c757d; }
    .filter-tag { display: inline-flex; align-items: center; background: #e8f4ff; color: #1565c0; border-radius: 20px; padding: 2px 10px; font-size: 0.75rem; font-weight: 500; margin: 2px 3px; }
    .filter-tag .remove { cursor: pointer; margin-left: 5px; font-size: 0.85rem; }
    .filter-tag .remove:hover { color: #c0392b; }
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
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-users mr-2"></i>Daftar Pelanggan
        </h3>
        <div class="card-tools">
            @can('customers.create')
            <a href="{{ route('admin.customers.import') }}" class="btn btn-success btn-sm mr-1">
                <i class="fas fa-file-import mr-1"></i> Import Excel
            </a>
            <a href="{{ route('admin.customers.create') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus mr-1"></i> Tambah Pelanggan
            </a>
            @endcan
        </div>
    </div>
    <div class="card-body">
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
                @php $hasFilter = request()->hasAny(['search','status','package_id','city_code','router_id','auto_isolir']); @endphp
                @if($hasFilter)
                <div class="col-auto">
                    <a href="{{ route('admin.customers.index', $popId ? ['pop_id' => $popId] : []) }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-undo mr-1"></i> Reset
                    </a>
                </div>
                @endif
            </div>
            {{-- Active filter tags --}}
            @if($hasFilter)
            <div class="mt-2" style="font-size:0.78rem;">
                <span class="text-muted mr-1">Filter aktif:</span>
                @if(request('search'))<span class="filter-tag"><i class="fas fa-search mr-1"></i>{{ request('search') }}</span>@endif
                @if(request('status'))<span class="filter-tag"><i class="fas fa-circle mr-1"></i>{{ \App\Models\Customer::statusLabels()[request('status')] ?? request('status') }}</span>@endif
                @if(request('package_id'))<span class="filter-tag"><i class="fas fa-box mr-1"></i>{{ $packages->firstWhere('id', request('package_id'))?->name ?? '-' }}</span>@endif
                @if(request('city_code'))<span class="filter-tag"><i class="fas fa-map-marker-alt mr-1"></i>{{ $filterCities->firstWhere('code', request('city_code'))?->name ?? request('city_code') }}</span>@endif
                @if(request('router_id'))<span class="filter-tag"><i class="fas fa-network-wired mr-1"></i>{{ $routers->firstWhere('id', request('router_id'))?->name ?? '-' }}</span>@endif
                @if(request('auto_isolir') !== null && request('auto_isolir') !== '')<span class="filter-tag"><i class="fas fa-shield-alt mr-1"></i>Auto Isolir: {{ request('auto_isolir') === '1' ? 'Aktif' : 'Nonaktif' }}</span>@endif
            </div>
            @endif
        </div>
        @if($popId && auth()->user()->hasRole('superadmin'))
        <input type="hidden" id="hiddenPopId" value="{{ $popId }}">
        @endif

        <!-- Bulk Action Toolbar -->
        <div class="bulk-toolbar align-items-center justify-content-between" id="bulkToolbar">
            <div>
                <i class="fas fa-check-square mr-2"></i>
                <span id="selectedCount">0</span> pelanggan dipilih
            </div>
            <div class="btn-group">
                <button type="button" class="btn btn-warning btn-sm" id="btnBulkActivate">
                    <i class="fas fa-check-circle mr-1"></i> Aktifkan Terpilih
                </button>
                <button type="button" class="btn btn-info btn-sm" id="btnBulkSyncMikrotik">
                    <i class="fas fa-sync mr-1"></i> Sync ke Mikrotik
                </button>
                <button type="button" class="btn btn-success btn-sm" id="btnBulkEnableIsolir">
                    <i class="fas fa-shield-alt mr-1"></i> Aktifkan Auto Isolir
                </button>
                <button type="button" class="btn btn-outline-light btn-sm" id="btnBulkDisableIsolir">
                    <i class="fas fa-unlock mr-1"></i> Nonaktifkan Auto Isolir
                </button>
                <button type="button" class="btn btn-success btn-sm" id="btnBulkGeneratePortal">
                    <i class="fas fa-user-plus mr-1"></i> Buat Akun Portal
                </button>
            </div>
            <div class="w-100 text-center mt-2 pt-2" id="selectAllBanner" style="display:none;border-top:1px solid rgba(255,255,255,.2);font-size:.82rem;">
                Semua <strong>{{ $customers->count() }}</strong> pelanggan di halaman ini dipilih.
                <a href="#" id="btnSelectAllPages" class="text-warning font-weight-bold ml-1">Pilih semua <strong>{{ $customers->total() }}</strong> pelanggan</a>
            </div>
            <div class="w-100 text-center mt-2 pt-2" id="selectAllActiveNotice" style="display:none;border-top:1px solid rgba(255,255,255,.2);font-size:.82rem;">
                <i class="fas fa-check-circle text-warning mr-1"></i>
                Semua <strong>{{ $customers->total() }}</strong> pelanggan dipilih.
                <a href="#" id="btnClearSelectAll" class="text-warning font-weight-bold ml-1">Batalkan</a>
            </div>
        </div>

        <!-- Table -->
        @if($customers->isEmpty())
        <div class="text-center py-5">
            <i class="fas fa-users fa-3x text-muted mb-3"></i>
            <p class="text-muted">Belum ada pelanggan terdaftar.</p>
            @can('customers.create')
            <a href="{{ route('admin.customers.create') }}" class="btn btn-primary">
                <i class="fas fa-plus mr-1"></i> Tambah Pelanggan Pertama
            </a>
            @endcan
        </div>
        @else
        <div class="table-responsive">
            <table class="table table-hover table-striped">
                <thead class="thead-dark">
                    <tr>
                        <th class="cb-cell">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="checkAll">
                                <label class="custom-control-label" for="checkAll"></label>
                            </div>
                        </th>
                        <th>ID</th>
                        <th>Pelanggan</th>
                        <th>Kontak</th>
                        <th>Paket</th>
                        <th>PPPoE</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Data</th>
                        <th>Aktif s/d</th>
                        <th width="150">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($customers as $customer)
                    <tr>
                        <td class="cb-cell">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input customer-check" id="chk{{ $customer->id }}" value="{{ $customer->id }}">
                                <label class="custom-control-label" for="chk{{ $customer->id }}"></label>
                            </div>
                        </td>
                        <td>
                            <code>{{ $customer->customer_id }}</code>
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                @if($customer->photo_selfie_url)
                                <img src="{{ $customer->photo_selfie_url }}" class="customer-photo mr-2">
                                @else
                                <div class="customer-photo bg-secondary d-flex align-items-center justify-content-center mr-2">
                                    <i class="fas fa-user text-white"></i>
                                </div>
                                @endif
                                <div>
                                    <strong>{{ $customer->name }}</strong>
                                    @if($customer->nickname)
                                    <br><small class="text-muted"><i class="fas fa-tag fa-xs mr-1"></i>{{ $customer->nickname }}</small>
                                    @endif
                                    @if($customer->city)
                                    <br><small class="text-muted">{{ $customer->city->name }}</small>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>
                            <i class="fas fa-phone text-muted mr-1"></i>{{ $customer->phone }}
                            @if($customer->email)
                            <br><small class="text-muted"><i class="fas fa-envelope mr-1"></i>{{ $customer->email }}</small>
                            @endif
                        </td>
                        <td>
                            @if($customer->package)
                            <span class="badge badge-info">{{ $customer->package->name }}</span>
                            <br><small class="text-muted">Rp {{ number_format($customer->monthly_fee, 0, ',', '.') }}/bln</small>
                            @else
                            <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            @if($customer->pppoe_username)
                            <code>{{ $customer->pppoe_username }}</code>
                            <button type="button" class="btn btn-xs btn-outline-warning ml-1 btn-show-password" data-id="{{ $customer->id }}" title="Lihat Password">
                                <i class="fas fa-key"></i>
                            </button>
                            @else
                            <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <span class="badge badge-{{ $customer->status_color }}">{{ $customer->status_label }}</span>
                            @if($customer->mikrotik_status !== 'not_synced')
                            <br><small class="text-{{ $customer->mikrotik_status === 'enabled' ? 'success' : 'danger' }}">
                                <i class="fas fa-{{ $customer->mikrotik_status === 'enabled' ? 'check' : 'times' }}"></i>
                                {{ $customer->mikrotik_status }}
                            </small>
                            @endif
                            <br>
                            @if($customer->auto_isolir)
                            <span class="badge badge-info badge-isolir" data-id="{{ $customer->id }}" data-isolir="1" title="Auto-isolir aktif — klik untuk nonaktifkan">
                                <i class="fas fa-shield-alt mr-1"></i>Auto Isolir
                            </span>
                            @else
                            <span class="badge badge-light badge-isolir" data-id="{{ $customer->id }}" data-isolir="0" title="Auto-isolir nonaktif — klik untuk aktifkan">
                                <i class="fas fa-unlock mr-1"></i>No Isolir
                            </span>
                            @endif
                        </td>
                        <td class="text-center">
                            @php $completeness = $customer->data_completeness; @endphp
                            @if($completeness['complete'])
                            <span class="badge badge-success" title="Data lengkap">
                                <i class="fas fa-check-circle mr-1"></i>Lengkap
                            </span>
                            @else
                            <span class="badge badge-warning" title="Kurang: {{ implode(', ', $completeness['missing']) }}" style="cursor:help">
                                <i class="fas fa-exclamation-circle mr-1"></i>{{ $completeness['percentage'] }}%
                            </span>
                            @endif
                        </td>
                        <td>
                            @if($customer->active_until)
                            <span class="{{ $customer->active_until->isPast() ? 'text-danger' : '' }}">
                                {{ $customer->active_until->format('d/m/Y') }}
                            </span>
                            @if($customer->active_until->isPast())
                            <br><small class="text-danger">Expired!</small>
                            @elseif($customer->active_until->diffInDays(now()) <= 7)
                            <br><small class="text-warning">{{ $customer->active_until->diffInDays(now()) }} hari lagi</small>
                            @endif
                            @else
                            <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            <div class="btn-group">
                                <a href="{{ route('admin.customers.show', $customer) }}" class="btn btn-sm btn-info" title="Detail">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @can('customers.edit')
                                <a href="{{ route('admin.customers.edit', $customer) }}" class="btn btn-sm btn-warning" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                @endcan
                                <div class="btn-group">
                                    <button type="button" class="btn btn-sm btn-secondary dropdown-toggle" data-toggle="dropdown">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-right">
                                        <a class="dropdown-item btn-change-status" href="#" data-id="{{ $customer->id }}" data-status="active">
                                            <i class="fas fa-check-circle text-success mr-2"></i> Aktifkan
                                        </a>
                                        <a class="dropdown-item btn-change-status" href="#" data-id="{{ $customer->id }}" data-status="suspended">
                                            <i class="fas fa-ban text-warning mr-2"></i> Suspend
                                        </a>
                                        @if($customer->router_id && $customer->pppoe_username)
                                        <div class="dropdown-divider"></div>
                                        @if($customer->status === 'suspended')
                                        <a class="dropdown-item btn-buka-isolir" href="#" data-id="{{ $customer->id }}" data-name="{{ $customer->name }}" data-package="{{ $customer->package?->name ?? '-' }}">
                                            <i class="fas fa-unlock text-success mr-2"></i> Buka Isolir
                                        </a>
                                        @else
                                        <a class="dropdown-item btn-isolir" href="#" data-id="{{ $customer->id }}" data-name="{{ $customer->name }}" data-username="{{ $customer->pppoe_username }}">
                                            <i class="fas fa-ban text-orange mr-2"></i> Isolir (PPPoE)
                                        </a>
                                        @endif
                                        @endif
                                        <div class="dropdown-divider"></div>
                                        @can('customers.delete')
                                        <a class="dropdown-item text-danger btn-delete" href="#" data-id="{{ $customer->id }}" data-name="{{ $customer->name }}">
                                            <i class="fas fa-trash mr-2"></i> Hapus
                                        </a>
                                        @endcan
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <div class="d-flex justify-content-between align-items-center mt-3">
            <div>
                <small class="text-muted">
                    Menampilkan {{ $customers->firstItem() ?? 0 }} - {{ $customers->lastItem() ?? 0 }} dari {{ $customers->total() }} pelanggan
                </small>
            </div>
            <div>
                {{ $customers->withQueryString()->links() }}
            </div>
        </div>
        @endif
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
        window.location.href = url.toString();
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
    const bulkToolbar = document.getElementById('bulkToolbar');
    const selectedCount = document.getElementById('selectedCount');
    const totalAll = {{ $customers->total() }};
    let selectAll = false;

    function resetSelectAll() {
        selectAll = false;
        document.getElementById('selectAllBanner').style.display = 'none';
        document.getElementById('selectAllActiveNotice').style.display = 'none';
    }

    function updateBulkToolbar() {
        const checked = document.querySelectorAll('.customer-check:checked');
        const total = document.querySelectorAll('.customer-check').length;
        const count = checked.length;
        if (!selectAll) selectedCount.textContent = count;
        if (count > 0 || selectAll) {
            bulkToolbar.classList.add('show');
        } else {
            bulkToolbar.classList.remove('show');
            resetSelectAll();
        }
        if (!selectAll) {
            if (count === total && total > 0 && totalAll > total) {
                document.getElementById('selectAllBanner').style.display = 'block';
                document.getElementById('selectAllActiveNotice').style.display = 'none';
            } else {
                document.getElementById('selectAllBanner').style.display = 'none';
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
        return selectAll ? totalAll : getSelectedIds().length;
    }

    // Select all pages
    $(document).on('click', '#btnSelectAllPages', function(e) {
        e.preventDefault();
        selectAll = true;
        selectedCount.textContent = 'Semua ' + totalAll;
        document.getElementById('selectAllBanner').style.display = 'none';
        document.getElementById('selectAllActiveNotice').style.display = 'block';
    });

    // Cancel select all
    $(document).on('click', '#btnClearSelectAll', function(e) {
        e.preventDefault();
        resetSelectAll();
        document.querySelectorAll('.customer-check').forEach(cb => { cb.checked = false; });
        document.getElementById('checkAll').checked = false;
        selectedCount.textContent = '0';
        bulkToolbar.classList.remove('show');
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
