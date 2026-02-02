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
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
    }
    .stat-card {
        transition: all 0.3s ease;
    }
    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
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
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-users mr-2"></i>Daftar Pelanggan
        </h3>
        <div class="card-tools">
            @can('customers.create')
            <a href="{{ route('admin.customers.create') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus mr-1"></i> Tambah Pelanggan
            </a>
            @endcan
        </div>
    </div>
    <div class="card-body">
        <!-- Filters -->
        <form action="{{ route('admin.customers.index') }}" method="GET" class="mb-3">
            @if($popId && auth()->user()->hasRole('superadmin'))
            <input type="hidden" name="pop_id" value="{{ $popId }}">
            @endif
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <input type="text" name="search" class="form-control" placeholder="Cari nama, ID, telepon..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <select name="status" class="form-control select2">
                            <option value="">Semua Status</option>
                            @foreach(\App\Models\Customer::statusLabels() as $key => $label)
                            <option value="{{ $key }}" {{ request('status') == $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <select name="router_id" class="form-control select2">
                            <option value="">Semua Router</option>
                            @foreach($routers as $router)
                            <option value="{{ $router->id }}" {{ request('router_id') == $router->id ? 'selected' : '' }}>{{ $router->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-secondary">
                        <i class="fas fa-search mr-1"></i> Cari
                    </button>
                    <a href="{{ route('admin.customers.index', $popId ? ['pop_id' => $popId] : []) }}" class="btn btn-outline-secondary">
                        <i class="fas fa-undo"></i> Reset
                    </a>
                </div>
            </div>
        </form>

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
                        <th>ID</th>
                        <th>Pelanggan</th>
                        <th>Kontak</th>
                        <th>Paket</th>
                        <th>PPPoE</th>
                        <th class="text-center">Status</th>
                        <th>Aktif s/d</th>
                        <th width="150">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($customers as $customer)
                    <tr>
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
    // Select2 sudah diinisialisasi secara global di layout admin

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
});
</script>
@endpush
