@extends('layouts.admin')

@section('title', 'ODP')

@section('page-title', 'Manajemen ODP (Optical Distribution Point)')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">ODP</li>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <!-- Superadmin POP Selector -->
        @if($popUsers && auth()->user()->hasRole('superadmin'))
        <div class="card card-outline card-info mb-3">
            <div class="card-body py-2">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <i class="fas fa-user-shield text-info fa-lg"></i>
                        <strong class="ml-2">Mode Superadmin:</strong>
                    </div>
                    <div class="col">
                        <select class="form-control select2" id="selectPop" style="width: 100%;">
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

        <!-- Statistics -->
        @if($popId)
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3>{{ $stats['total'] }}</h3>
                        <p>Total ODP</p>
                    </div>
                    <div class="icon"><i class="fas fa-box"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>{{ $stats['active'] }}</h3>
                        <p>Aktif</p>
                    </div>
                    <div class="icon"><i class="fas fa-check-circle"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3>{{ $stats['maintenance'] }}</h3>
                        <p>Maintenance</p>
                    </div>
                    <div class="icon"><i class="fas fa-tools"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3>{{ $stats['inactive'] }}</h3>
                        <p>Tidak Aktif</p>
                    </div>
                    <div class="icon"><i class="fas fa-times-circle"></i></div>
                </div>
            </div>
        </div>
        @endif

        <!-- Filter & Action -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-filter mr-2"></i>Filter</h3>
                <div class="card-tools">
                    @can('odps.create')
                    @if($popId)
                    <a href="{{ route('admin.odps.create', ['pop_id' => $popId]) }}" class="btn btn-success btn-sm">
                        <i class="fas fa-plus mr-1"></i> Tambah ODP
                    </a>
                    @endif
                    @endcan
                </div>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('admin.odps.index') }}">
                    @if($popId)
                    <input type="hidden" name="pop_id" value="{{ $popId }}">
                    @endif
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>ODC</label>
                                <select name="odc_id" class="form-control select2">
                                    <option value="">Semua ODC</option>
                                    @foreach($odcs as $odc)
                                        <option value="{{ $odc->id }}" {{ request('odc_id') == $odc->id ? 'selected' : '' }}>
                                            {{ $odc->code }} - {{ $odc->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Status</label>
                                <select name="status" class="form-control">
                                    <option value="">Semua Status</option>
                                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Aktif</option>
                                    <option value="maintenance" {{ request('status') == 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Tidak Aktif</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Cari</label>
                                <input type="text" name="search" class="form-control" placeholder="Nama, Kode, Alamat, No. Tiang..." value="{{ request('search') }}">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search"></i> Cari
                                    </button>
                                    <a href="{{ route('admin.odps.index', ['pop_id' => $popId]) }}" class="btn btn-secondary">
                                        <i class="fas fa-undo"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- ODP List -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-box mr-2"></i>Daftar ODP</h3>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover table-striped">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Nama</th>
                            <th>ODC</th>
                            <th>Port ODC</th>
                            <th>Lokasi</th>
                            <th>Port</th>
                            <th>Pelanggan</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($odps as $odp)
                        <tr>
                            <td><code>{{ $odp->code }}</code></td>
                            <td><strong>{{ $odp->name }}</strong></td>
                            <td>
                                @if($odp->odc)
                                <a href="{{ route('admin.odcs.show', $odp->odc) }}">{{ $odp->odc->code }}</a>
                                @else
                                -
                                @endif
                            </td>
                            <td><span class="badge badge-secondary">Port {{ $odp->odc_port }}</span></td>
                            <td>
                                @if($odp->hasCoordinates())
                                    <a href="https://www.google.com/maps?q={{ $odp->latitude }},{{ $odp->longitude }}" target="_blank" class="text-primary">
                                        <i class="fas fa-map-marker-alt"></i> Lihat Map
                                    </a>
                                @else
                                    <span class="text-muted">Belum diset</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge badge-info">{{ $odp->used_ports }}/{{ $odp->total_ports }}</span>
                                <div class="progress progress-xs mt-1" style="width: 60px;">
                                    <div class="progress-bar bg-{{ $odp->port_usage_percent > 80 ? 'danger' : ($odp->port_usage_percent > 50 ? 'warning' : 'success') }}" 
                                         style="width: {{ $odp->port_usage_percent }}%"></div>
                                </div>
                            </td>
                            <td>
                                <a href="{{ route('admin.customers.index', ['pop_id' => $odp->pop_id, 'odp_id' => $odp->id]) }}" class="badge badge-primary">
                                    {{ $odp->customers_count }} Pelanggan
                                </a>
                            </td>
                            <td>
                                <span class="badge badge-{{ $odp->status_badge }}">{{ $odp->status_label }}</span>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="{{ route('admin.odps.show', $odp) }}" class="btn btn-sm btn-info" title="Detail">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    @can('odps.edit')
                                    <a href="{{ route('admin.odps.edit', $odp) }}" class="btn btn-sm btn-warning" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    @endcan
                                    @can('odps.delete')
                                    <button type="button" class="btn btn-sm btn-danger btn-delete" 
                                            data-id="{{ $odp->id }}" 
                                            data-name="{{ $odp->code }}"
                                            title="Hapus">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center py-4">
                                @if(!$popId)
                                    <i class="fas fa-info-circle fa-2x text-info mb-2"></i>
                                    <p class="mb-0">Silakan pilih POP terlebih dahulu</p>
                                @else
                                    <i class="fas fa-inbox fa-2x text-muted mb-2"></i>
                                    <p class="mb-0">Belum ada data ODP</p>
                                    @can('odps.create')
                                    <a href="{{ route('admin.odps.create', ['pop_id' => $popId]) }}" class="btn btn-success btn-sm mt-2">
                                        <i class="fas fa-plus mr-1"></i> Tambah ODP Pertama
                                    </a>
                                    @endcan
                                @endif
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($odps->hasPages())
            <div class="card-footer">
                {{ $odps->links() }}
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Delete Form -->
<form id="deleteForm" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>
@endsection

@section('scripts')
<script>
$(function() {
    // POP Selector
    $('#selectPop').on('change', function() {
        const popId = $(this).val();
        if (popId) {
            window.location.href = '{{ route("admin.odps.index") }}?pop_id=' + popId;
        }
    });

    // Delete ODP
    $('.btn-delete').on('click', function() {
        const id = $(this).data('id');
        const name = $(this).data('name');

        Swal.fire({
            title: 'Hapus ODP?',
            text: `Apakah Anda yakin ingin menghapus ODP "${name}"?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                const form = $('#deleteForm');
                form.attr('action', '{{ route("admin.odps.index") }}/' + id);
                form.submit();
            }
        });
    });
});
</script>
@endsection
