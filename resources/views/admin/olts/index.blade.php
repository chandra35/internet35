@extends('layouts.admin')

@section('title', 'OLT')

@section('page-title', 'Manajemen OLT (Optical Line Terminal)')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">OLT</li>
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
                        <h3>{{ $olts->total() }}</h3>
                        <p>Total OLT</p>
                    </div>
                    <div class="icon"><i class="fas fa-server"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>{{ $olts->where('status', 'active')->count() }}</h3>
                        <p>Aktif</p>
                    </div>
                    <div class="icon"><i class="fas fa-check-circle"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3>{{ $olts->where('status', 'maintenance')->count() }}</h3>
                        <p>Maintenance</p>
                    </div>
                    <div class="icon"><i class="fas fa-tools"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3>{{ $olts->where('status', 'inactive')->count() }}</h3>
                        <p>Tidak Aktif</p>
                    </div>
                    <div class="icon"><i class="fas fa-times-circle"></i></div>
                </div>
            </div>
        </div>
        @endif

        @if(!$popId && auth()->user()->hasRole('superadmin'))
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> Silakan pilih POP terlebih dahulu untuk melihat data OLT.
        </div>
        @else
        <!-- OLT List -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-server mr-2"></i>Daftar OLT</h3>
                <div class="card-tools">
                    @can('olts.create')
                    <a href="{{ route('admin.olts.create') }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i> Tambah OLT
                    </a>
                    @endcan
                </div>
            </div>
            <div class="card-body">
                <!-- Filters -->
                <div class="row mb-3">
                    <div class="col-md-3">
                        <select class="form-control select2" id="filterBrand">
                            <option value="">Semua Brand</option>
                            @foreach(\App\Models\Olt::BRANDS as $key => $label)
                            <option value="{{ $key }}" {{ request('brand') == $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-control" id="filterStatus">
                            <option value="">Semua Status</option>
                            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Aktif</option>
                            <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Tidak Aktif</option>
                            <option value="maintenance" {{ request('status') == 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <input type="text" class="form-control" id="filterSearch" placeholder="Cari nama, IP, kode..." value="{{ request('search') }}">
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-secondary btn-block" onclick="resetFilters()">
                            <i class="fas fa-redo"></i> Reset
                        </button>
                    </div>
                </div>

                <!-- OLT Table -->
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover">
                        <thead class="bg-light">
                            <tr>
                                <th width="5%">#</th>
                                <th>Nama</th>
                                <th>Brand/Model</th>
                                <th>IP Address</th>
                                <th>PON Ports</th>
                                <th>ONU</th>
                                <th>Status</th>
                                <th>Last Sync</th>
                                <th width="15%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($olts as $index => $olt)
                            <tr>
                                <td>{{ $olts->firstItem() + $index }}</td>
                                <td>
                                    <strong>{{ $olt->name }}</strong>
                                    <br><small class="text-muted">{{ $olt->code }}</small>
                                </td>
                                <td>
                                    <span class="badge badge-primary">{{ $olt->brand_name }}</span>
                                    @if($olt->model)
                                    <br><small>{{ $olt->model }}</small>
                                    @endif
                                </td>
                                <td><code>{{ $olt->ip_address }}</code></td>
                                <td class="text-center">{{ $olt->total_pon_ports }}</td>
                                <td class="text-center">
                                    <span class="badge badge-info">{{ $olt->onus_count ?? 0 }}</span>
                                </td>
                                <td class="text-center">
                                    @if($olt->status == 'active')
                                        <span class="badge badge-success"><i class="fas fa-check"></i> Aktif</span>
                                    @elseif($olt->status == 'maintenance')
                                        <span class="badge badge-warning"><i class="fas fa-tools"></i> Maintenance</span>
                                    @else
                                        <span class="badge badge-danger"><i class="fas fa-times"></i> Tidak Aktif</span>
                                    @endif
                                </td>
                                <td>
                                    @if($olt->last_sync_at)
                                        <small>{{ $olt->last_sync_at->diffForHumans() }}</small>
                                    @else
                                        <small class="text-muted">Belum sync</small>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('admin.olts.show', $olt) }}" class="btn btn-info" title="Detail">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @can('olts.sync')
                                        <button class="btn btn-success" onclick="syncOlt('{{ $olt->id }}')" title="Sync">
                                            <i class="fas fa-sync-alt"></i>
                                        </button>
                                        @endcan
                                        @can('olts.edit')
                                        <a href="{{ route('admin.olts.edit', $olt) }}" class="btn btn-warning" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        @endcan
                                        @can('olts.delete')
                                        <button class="btn btn-danger" onclick="deleteOlt('{{ $olt->id }}', '{{ $olt->name }}')" title="Hapus">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="text-center py-4">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">Belum ada data OLT</p>
                                    @can('olts.create')
                                    <a href="{{ route('admin.olts.create') }}" class="btn btn-primary">
                                        <i class="fas fa-plus"></i> Tambah OLT Pertama
                                    </a>
                                    @endcan
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if($olts->hasPages())
                <div class="mt-3">
                    {{ $olts->withQueryString()->links() }}
                </div>
                @endif
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

@push('js')
<script>
$(function() {
    $('.select2').select2({ theme: 'bootstrap4', width: '100%' });
    
    // POP Selector
    $('#selectPop').on('change', function() {
        var popId = $(this).val();
        window.location.href = '{{ route("admin.olts.index") }}?pop_id=' + popId;
    });

    // Filters
    $('#filterBrand, #filterStatus').on('change', applyFilters);
    $('#filterSearch').on('keypress', function(e) {
        if (e.which == 13) applyFilters();
    });
});

function applyFilters() {
    var params = new URLSearchParams(window.location.search);
    
    var brand = $('#filterBrand').val();
    var status = $('#filterStatus').val();
    var search = $('#filterSearch').val();
    
    if (brand) params.set('brand', brand); else params.delete('brand');
    if (status) params.set('status', status); else params.delete('status');
    if (search) params.set('search', search); else params.delete('search');
    
    window.location.href = '{{ route("admin.olts.index") }}?' + params.toString();
}

function resetFilters() {
    var popId = '{{ $popId }}';
    window.location.href = '{{ route("admin.olts.index") }}' + (popId ? '?pop_id=' + popId : '');
}

function syncOlt(id) {
    Swal.fire({
        title: 'Sync OLT?',
        text: 'Proses ini akan mengambil data ONU terbaru dari OLT',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        confirmButtonText: '<i class="fas fa-sync-alt"></i> Ya, Sync!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '/admin/olts/' + id + '/sync',
                type: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                beforeSend: function() {
                    Swal.fire({
                        title: 'Syncing...',
                        text: 'Mohon tunggu',
                        allowOutsideClick: false,
                        didOpen: () => { Swal.showLoading(); }
                    });
                },
                success: function(res) {
                    Swal.fire('Berhasil!', res.message, 'success').then(() => location.reload());
                },
                error: function(xhr) {
                    Swal.fire('Error!', xhr.responseJSON?.message || 'Gagal sync OLT', 'error');
                }
            });
        }
    });
}

function deleteOlt(id, name) {
    Swal.fire({
        title: 'Hapus OLT?',
        html: `Anda yakin ingin menghapus OLT <strong>${name}</strong>?<br><small class="text-danger">Semua data ONU terkait akan ikut terhapus!</small>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: '<i class="fas fa-trash"></i> Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '/admin/olts/' + id,
                type: 'DELETE',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                success: function(res) {
                    toastr.success(res.message || 'OLT berhasil dihapus');
                    location.reload();
                },
                error: function(xhr) {
                    toastr.error(xhr.responseJSON?.message || 'Gagal menghapus OLT');
                }
            });
        }
    });
}
</script>
@endpush
