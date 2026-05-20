{{-- Active filter tags --}}
@php $hasFilter = request()->hasAny(['search','status','package_id','city_code','router_id','auto_isolir']); @endphp
@if($hasFilter)
<div class="mb-2" style="font-size:0.78rem;">
    <span class="text-muted mr-1">Filter aktif:</span>
    @if(request('search'))<span class="filter-tag"><i class="fas fa-search mr-1"></i>{{ request('search') }}</span>@endif
    @if(request('status'))<span class="filter-tag"><i class="fas fa-circle mr-1"></i>{{ \App\Models\Customer::statusLabels()[request('status')] ?? request('status') }}</span>@endif
    @if(request('package_id'))<span class="filter-tag"><i class="fas fa-box mr-1"></i>{{ $packages->firstWhere('id', request('package_id'))?->name ?? '-' }}</span>@endif
    @if(request('city_code'))<span class="filter-tag"><i class="fas fa-map-marker-alt mr-1"></i>{{ $filterCities->firstWhere('code', request('city_code'))?->name ?? request('city_code') }}</span>@endif
    @if(request('router_id'))<span class="filter-tag"><i class="fas fa-network-wired mr-1"></i>{{ $routers->firstWhere('id', request('router_id'))?->name ?? '-' }}</span>@endif
    @if(request('auto_isolir') !== null && request('auto_isolir') !== '')<span class="filter-tag"><i class="fas fa-shield-alt mr-1"></i>Auto Isolir: {{ request('auto_isolir') === '1' ? 'Aktif' : 'Nonaktif' }}</span>@endif
    <a href="{{ route('admin.customers.index', $popId ? ['pop_id' => $popId] : []) }}" class="btn btn-outline-secondary btn-sm ml-1" id="btnResetFilters">
        <i class="fas fa-undo mr-1"></i> Reset
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
