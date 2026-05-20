{{-- Active filter tags --}}
@php $hasFilter = request()->hasAny(['search','status','package_id','city_code','router_id','auto_isolir']); @endphp
@if($hasFilter)
<div class="mb-2 d-flex align-items-center flex-wrap" style="font-size:0.78rem;gap:4px;">
    <span class="text-muted mr-1"><i class="fas fa-filter mr-1"></i>Filter:</span>
    @if(request('search'))<span class="filter-tag"><i class="fas fa-search mr-1"></i>{{ request('search') }}</span>@endif
    @if(request('status'))<span class="filter-tag"><i class="fas fa-circle mr-1"></i>{{ \App\Models\Customer::statusLabels()[request('status')] ?? request('status') }}</span>@endif
    @if(request('package_id'))<span class="filter-tag"><i class="fas fa-box mr-1"></i>{{ $packages->firstWhere('id', request('package_id'))?->name ?? '-' }}</span>@endif
    @if(request('city_code'))<span class="filter-tag"><i class="fas fa-map-marker-alt mr-1"></i>{{ $filterCities->firstWhere('code', request('city_code'))?->name ?? request('city_code') }}</span>@endif
    @if(request('router_id'))<span class="filter-tag"><i class="fas fa-network-wired mr-1"></i>{{ $routers->firstWhere('id', request('router_id'))?->name ?? '-' }}</span>@endif
    @if(request('auto_isolir') !== null && request('auto_isolir') !== '')<span class="filter-tag"><i class="fas fa-shield-alt mr-1"></i>Auto Isolir: {{ request('auto_isolir') === '1' ? 'Aktif' : 'Nonaktif' }}</span>@endif
    <a href="{{ route('admin.customers.index', $popId ? ['pop_id' => $popId] : []) }}" class="btn btn-outline-secondary btn-sm py-0 ml-1" id="btnResetFilters" style="font-size:0.73rem;">
        <i class="fas fa-times mr-1"></i> Reset
    </a>
</div>
@endif

{{-- Hidden: total count for JS getTotalAll() --}}
<span id="tableTotalCount" data-total="{{ $customers->total() }}" style="display:none;"></span>

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
    <div style="width:72px;height:72px;border-radius:50%;background:#f0f5ff;display:inline-flex;align-items:center;justify-content:center;margin-bottom:16px;">
        <i class="fas fa-users fa-2x" style="color:#b0bfd4;"></i>
    </div>
    <p class="text-muted mb-3" style="font-size:0.95rem;">Belum ada pelanggan ditemukan.</p>
    @can('customers.create')
    <a href="{{ route('admin.customers.create') }}" class="btn btn-primary btn-sm">
        <i class="fas fa-plus mr-1"></i> Tambah Pelanggan
    </a>
    @endcan
</div>
@else
<div class="table-responsive">
    <table class="table table-customers table-hover mb-0">
        <thead>
            <tr>
                <th class="cb-cell">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="checkAll">
                        <label class="custom-control-label" for="checkAll"></label>
                    </div>
                </th>
                <th>Pelanggan</th>
                <th>Kontak</th>
                <th>Paket</th>
                <th>PPPoE</th>
                <th>Status</th>
                <th>Aktif s/d</th>
                <th class="text-right">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @foreach($customers as $customer)
            @php
                $avatarColors = ['#4e73df','#1cc88a','#36b9cc','#e74a3b','#f6c23e','#6f42c1','#fd7e14','#20c9a6','#858796'];
                $avatarBg = $avatarColors[abs(crc32($customer->name)) % count($avatarColors)];
                $initial = strtoupper(mb_substr($customer->name, 0, 1));
            @endphp
            <tr>
                <td class="cb-cell">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input customer-check" id="chk{{ $customer->id }}" value="{{ $customer->id }}">
                        <label class="custom-control-label" for="chk{{ $customer->id }}"></label>
                    </div>
                </td>
                <td>
                    <div class="d-flex align-items-center">
                        @if($customer->photo_selfie_url)
                        <img src="{{ $customer->photo_selfie_url }}" class="cust-avatar mr-2" style="border:2px solid #e9ecef;">
                        @else
                        <div class="cust-avatar mr-2" style="background:{{ $avatarBg }};">{{ $initial }}</div>
                        @endif
                        <div>
                            <a href="{{ route('admin.customers.show', $customer) }}" class="text-dark font-weight-bold" style="text-decoration:none;">{{ $customer->name }}</a>
                            <br>
                            <small class="text-muted" style="font-size:0.71rem;">
                                <code style="font-size:0.7rem;background:transparent;color:#6c757d;">{{ $customer->customer_id }}</code>
                                @if($customer->nickname) &middot; {{ $customer->nickname }}@endif
                            </small>
                            @if($customer->city)
                            <br><small class="text-muted" style="font-size:0.7rem;"><i class="fas fa-map-marker-alt mr-1"></i>{{ $customer->city->name }}</small>
                            @endif
                        </div>
                    </div>
                </td>
                <td>
                    <div style="font-size:0.83rem;"><i class="fas fa-phone text-muted mr-1" style="font-size:0.75rem;"></i>{{ $customer->phone }}</div>
                    @if($customer->email)
                    <div class="text-muted" style="font-size:0.76rem;"><i class="fas fa-envelope mr-1" style="font-size:0.7rem;"></i>{{ $customer->email }}</div>
                    @endif
                </td>
                <td>
                    @if($customer->package)
                    <span class="badge badge-pill" style="background:#17a2b8;color:white;font-size:0.71rem;padding:4px 9px;">{{ $customer->package->name }}</span>
                    <br><small class="text-muted" style="font-size:0.71rem;">Rp {{ number_format($customer->monthly_fee, 0, ',', '.') }}/bln</small>
                    @else
                    <span class="text-muted">—</span>
                    @endif
                </td>
                <td>
                    @if($customer->pppoe_username)
                    <div class="d-flex align-items-center">
                        <code style="font-size:0.76rem;background:transparent;color:#495057;">{{ $customer->pppoe_username }}</code>
                        <button type="button" class="btn btn-action btn-outline-warning ml-1 btn-show-password" data-id="{{ $customer->id }}" title="Lihat Password">
                            <i class="fas fa-key"></i>
                        </button>
                    </div>
                    @else
                    <span class="text-muted">—</span>
                    @endif
                </td>
                <td>
                    <span class="badge badge-pill badge-{{ $customer->status_color }}" style="font-size:0.71rem;padding:4px 9px;">{{ $customer->status_label }}</span>
                    @if($customer->mikrotik_status !== 'not_synced')
                    <br><small class="text-{{ $customer->mikrotik_status === 'enabled' ? 'success' : 'danger' }}" style="font-size:0.68rem;">
                        <i class="fas fa-circle" style="font-size:0.45rem;vertical-align:middle;"></i>
                        MK {{ $customer->mikrotik_status }}
                    </small>
                    @endif
                    <br>
                    @if($customer->auto_isolir)
                    <span class="badge badge-isolir badge-info" data-id="{{ $customer->id }}" data-isolir="1" title="Auto-isolir aktif — klik untuk nonaktifkan">
                        <i class="fas fa-shield-alt mr-1"></i>Auto Isolir
                    </span>
                    @else
                    <span class="badge badge-isolir" data-id="{{ $customer->id }}" data-isolir="0" title="Auto-isolir nonaktif — klik untuk aktifkan" style="background:#e9ecef;color:#6c757d;">
                        <i class="fas fa-unlock mr-1"></i>No Isolir
                    </span>
                    @endif
                </td>
                <td>
                    @if($customer->active_until)
                    <div class="{{ $customer->active_until->isPast() ? 'text-danger font-weight-bold' : '' }}" style="font-size:0.83rem;">
                        <i class="fas fa-calendar-alt mr-1 text-muted" style="font-size:0.72rem;"></i>{{ $customer->active_until->format('d/m/Y') }}
                    </div>
                    @if($customer->active_until->isPast())
                    <small class="text-danger" style="font-size:0.7rem;"><i class="fas fa-exclamation-circle mr-1"></i>Expired</small>
                    @elseif($customer->active_until->diffInDays(now()) <= 7)
                    <small class="text-warning" style="font-size:0.7rem;"><i class="fas fa-clock mr-1"></i>{{ $customer->active_until->diffInDays(now()) }} hari lagi</small>
                    @endif
                    @else
                    <span class="text-muted">—</span>
                    @endif
                </td>
                <td class="text-right" style="white-space:nowrap;">
                    <a href="{{ route('admin.customers.show', $customer) }}" class="btn btn-sm btn-action btn-info" title="Detail">
                        <i class="fas fa-eye"></i>
                    </a>
                    @can('customers.edit')
                    <a href="{{ route('admin.customers.edit', $customer) }}" class="btn btn-sm btn-action btn-warning" title="Edit">
                        <i class="fas fa-edit"></i>
                    </a>
                    @endcan
                    <div class="btn-group">
                        <button type="button" class="btn btn-sm btn-action btn-secondary dropdown-toggle" data-toggle="dropdown" title="Lainnya">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-right shadow-sm" style="font-size:0.82rem;min-width:165px;border-radius:8px;border-color:#e0e6f0;">
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
                                <i class="fas fa-ban text-danger mr-2"></i> Isolir (PPPoE)
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
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

<!-- Pagination -->
<div class="d-flex justify-content-between align-items-center mt-3 px-1">
    <small class="text-muted">
        Menampilkan <strong>{{ $customers->firstItem() ?? 0 }}</strong>–<strong>{{ $customers->lastItem() ?? 0 }}</strong>
        dari <strong>{{ $customers->total() }}</strong> pelanggan
    </small>
    <div>
        {{ $customers->withQueryString()->links() }}
    </div>
</div>
@endif
