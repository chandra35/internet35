@extends('layouts.admin')

@section('title', 'Manajemen Paket')
@section('page-title', 'Manajemen Paket')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">Paket</li>
@endsection

@push('css')
<style>
    .speed-badge {
        font-size: 0.9rem;
        padding: 0.4rem 0.8rem;
    }
    .profile-name {
        font-family: monospace;
        background: #f8f9fa;
        padding: 2px 6px;
        border-radius: 3px;
        font-size: 0.85rem;
    }
    .profile-item {
        cursor: pointer;
        transition: background 0.2s;
    }
    .profile-item:hover {
        background: #f8f9fa;
    }
    .profile-item.selected {
        background: #e3f2fd;
    }
    .profile-item.has-package {
        opacity: 0.6;
    }
</style>
@endpush

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-box mr-2"></i>Daftar Paket Internet
        </h3>
        <div class="card-tools">
            @can('packages.create')
            <button type="button" class="btn btn-primary btn-sm" id="btnAddPackage">
                <i class="fas fa-plus mr-1"></i> Buat Paket dari Profile
            </button>
            @endcan
        </div>
    </div>
    <div class="card-body">
        <!-- Filters -->
        <form method="GET" class="mb-4">
            <div class="row">
                <div class="col-md-3">
                    <select name="router_id" class="form-control form-control-sm" onchange="this.form.submit()">
                        <option value="">-- Semua Router --</option>
                        @foreach($routers as $router)
                        <option value="{{ $router->id }}" {{ request('router_id') == $router->id ? 'selected' : '' }}>
                            {{ $router->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-control form-control-sm" onchange="this.form.submit()">
                        <option value="">-- Status --</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Aktif</option>
                        <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Tidak Aktif</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <div class="input-group input-group-sm">
                        <input type="text" name="search" class="form-control" placeholder="Cari paket..." value="{{ request('search') }}">
                        <div class="input-group-append">
                            <button type="submit" class="btn btn-default">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    @if(request()->hasAny(['router_id', 'status', 'search']))
                    <a href="{{ route('admin.packages.index') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-times mr-1"></i>Reset
                    </a>
                    @endif
                </div>
            </div>
        </form>

        <div class="alert alert-info mb-3">
            <i class="fas fa-info-circle mr-1"></i>
            <strong>Info:</strong> PPP Profile dikelola di menu 
            <a href="{{ route('admin.ppp-profiles.index') }}" class="alert-link">PPP Profiles</a>.
            Halaman ini untuk mengatur harga dan detail bisnis paket.
        </div>

        @if($packages->isEmpty())
            <div class="text-center py-5">
                <i class="fas fa-box-open fa-4x text-muted mb-3"></i>
                <p class="text-muted mb-3">Belum ada paket. Buat paket dari PPP Profile yang sudah disinkronkan.</p>
                @can('packages.create')
                <button type="button" class="btn btn-primary" id="btnAddPackageEmpty">
                    <i class="fas fa-plus mr-1"></i> Buat Paket dari Profile
                </button>
                @endcan
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>Nama Paket</th>
                            <th>Profile Mikrotik</th>
                            <th>Router</th>
                            <th>Kecepatan</th>
                            <th>Harga</th>
                            <th>Status</th>
                            <th style="width: 120px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($packages as $index => $package)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>
                                <div class="font-weight-bold">{{ $package->name }}</div>
                                @if($package->description)
                                <small class="text-muted">{{ Str::limit($package->description, 50) }}</small>
                                @endif
                            </td>
                            <td>
                                <span class="profile-name">{{ $package->mikrotik_profile_name }}</span>
                            </td>
                            <td>
                                <span class="badge badge-light">
                                    <i class="fas fa-server mr-1"></i>{{ $package->router->name ?? '-' }}
                                </span>
                            </td>
                            <td>
                                @if($package->rate_limit)
                                <span class="badge badge-info speed-badge">
                                    <i class="fas fa-arrow-down mr-1"></i>{{ $package->formatted_download }}
                                </span>
                                <span class="badge badge-success speed-badge">
                                    <i class="fas fa-arrow-up mr-1"></i>{{ $package->formatted_upload }}
                                </span>
                                <br><small class="text-muted mt-1 d-block">{{ $package->rate_limit }}</small>
                                @else
                                <span class="badge badge-secondary">Unlimited</span>
                                @endif
                            </td>
                            <td>
                                <strong class="text-success">{{ $package->formatted_price }}</strong>
                                <br><small class="text-muted">{{ $package->validity_days }} hari</small>
                            </td>
                            <td>
                                @if($package->is_active)
                                <span class="badge badge-success">Aktif</span>
                                @else
                                <span class="badge badge-secondary">Tidak Aktif</span>
                                @endif
                                @if($package->is_public)
                                <span class="badge badge-info" title="Tampil di Portal Pelanggan"><i class="fas fa-eye"></i></span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group">
                                    @can('packages.edit')
                                    <button type="button" class="btn btn-xs btn-outline-primary btn-edit" 
                                            data-id="{{ $package->id }}" title="Edit Harga">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    @endcan
                                    @can('packages.delete')
                                    <button type="button" class="btn btn-xs btn-outline-danger btn-delete" 
                                            data-id="{{ $package->id }}" 
                                            data-name="{{ $package->name }}" title="Hapus">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

<!-- Modal Pilih Profile -->
<div class="modal fade" id="selectProfileModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-id-card mr-2"></i>Pilih PPP Profile</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Pilih Router</label>
                    <select class="form-control" id="selectRouter">
                        <option value="">-- Pilih Router --</option>
                        @foreach($routers as $router)
                        <option value="{{ $router->id }}">{{ $router->name }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div id="profileListContainer" style="display:none;">
                    <label>Pilih Profile <small class="text-muted">(Profile yang sudah punya paket tidak bisa dipilih)</small></label>
                    <div id="profileListLoading" class="text-center py-3" style="display:none;">
                        <i class="fas fa-spinner fa-spin fa-2x"></i>
                        <p class="mt-2 mb-0">Memuat profile...</p>
                    </div>
                    <div class="list-group" id="profileList" style="max-height: 350px; overflow-y: auto;"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Form Paket -->
<div class="modal fade" id="packageFormModal" tabindex="-1" data-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-box mr-2"></i><span id="packageModalTitle">Buat Paket</span></h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form id="packageForm">
                <div class="modal-body">
                    <input type="hidden" id="packageId" name="id">
                    <input type="hidden" id="profileId" name="profile_id">
                    <input type="hidden" id="routerId" name="router_id">
                    
                    <!-- Profile Info (read-only) -->
                    <div class="card bg-light mb-3">
                        <div class="card-body py-2">
                            <div class="row">
                                <div class="col-6">
                                    <small class="text-muted">Profile Mikrotik</small>
                                    <div class="font-weight-bold" id="displayProfileName">-</div>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">Rate Limit</small>
                                    <div class="font-weight-bold" id="displayRateLimit">-</div>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-6">
                                    <small class="text-muted">Router</small>
                                    <div id="displayRouter">-</div>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">Remote Address</small>
                                    <div id="displayRemoteAddress">-</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Nama Paket <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="packageName" name="name" required>
                        <small class="text-muted">Nama yang ditampilkan ke pelanggan</small>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Harga (Rp) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="packagePrice" name="price" 
                                       min="0" step="500" required>
                                <div id="ppnCalculation" class="mt-2 p-2 bg-light rounded" style="display:none; font-size: 0.85rem;">
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">Harga sebelum PPN:</span>
                                        <span class="font-weight-bold" id="priceBeforePpn">-</span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">PPN 11%:</span>
                                        <span class="text-info" id="ppnAmount">-</span>
                                    </div>
                                    <hr class="my-1">
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">Total (include PPN):</span>
                                        <span class="font-weight-bold text-success" id="totalWithPpn">-</span>
                                    </div>
                                </div>
                                <small class="text-muted">Harga sudah termasuk PPN 11%</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Masa Berlaku (hari) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="packageValidity" name="validity_days" 
                                       min="1" value="30" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Deskripsi</label>
                        <textarea class="form-control" id="packageDescription" name="description" rows="2" 
                                  placeholder="Deskripsi singkat untuk pelanggan"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="packageActive" name="is_active" value="1" checked>
                                <label class="custom-control-label" for="packageActive">Paket Aktif</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="packagePublic" name="is_public" value="1" checked>
                                <label class="custom-control-label" for="packagePublic">Tampil di Portal</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success" id="btnSavePackage">
                        <i class="fas fa-save mr-1"></i> Simpan Paket
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
$(function() {
    let selectedProfile = null;
    
    // Format currency
    function formatRupiah(num) {
        return 'Rp ' + num.toLocaleString('id-ID');
    }
    
    // Calculate PPN breakdown
    function calculatePpn(priceWithPpn) {
        if (!priceWithPpn || priceWithPpn <= 0) {
            $('#ppnCalculation').hide();
            return;
        }
        
        const ppnRate = 0.11; // 11%
        const priceBeforePpn = Math.round(priceWithPpn / (1 + ppnRate));
        const ppnAmount = priceWithPpn - priceBeforePpn;
        
        $('#priceBeforePpn').text(formatRupiah(priceBeforePpn));
        $('#ppnAmount').text('+ ' + formatRupiah(ppnAmount));
        $('#totalWithPpn').text(formatRupiah(priceWithPpn));
        $('#ppnCalculation').show();
    }
    
    // Listen to price input changes
    $('#packagePrice').on('input', function() {
        const price = parseInt($(this).val()) || 0;
        calculatePpn(price);
    });
    
    // Open profile selector
    $('#btnAddPackage, #btnAddPackageEmpty').on('click', function() {
        $('#selectRouter').val('');
        $('#profileListContainer').hide();
        $('#profileList').empty();
        selectedProfile = null;
        $('#selectProfileModal').modal('show');
    });
    
    // Load profiles when router selected
    $('#selectRouter').on('change', function() {
        const routerId = $(this).val();
        if (!routerId) {
            $('#profileListContainer').hide();
            return;
        }
        
        $('#profileListContainer').show();
        $('#profileListLoading').show();
        $('#profileList').empty();
        
        $.get(`{{ url('admin/packages/profiles') }}/${routerId}`, function(response) {
            $('#profileListLoading').hide();
            
            if (response.success && response.data.length > 0) {
                response.data.forEach(function(profile) {
                    const hasPackage = profile.has_package;
                    const rateLimit = profile.rate_limit || 'Unlimited';
                    
                    const html = `
                        <a href="#" class="list-group-item list-group-item-action profile-item ${hasPackage ? 'has-package' : ''}" 
                           data-profile='${JSON.stringify(profile)}' 
                           ${hasPackage ? 'title="Sudah memiliki paket"' : ''}>
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>${profile.name}</strong>
                                    ${hasPackage ? '<span class="badge badge-warning ml-2">Sudah ada paket</span>' : ''}
                                    <br>
                                    <small class="text-muted">
                                        Rate: <span class="text-info">${rateLimit}</span>
                                        ${profile.remote_address ? ' | Pool: ' + profile.remote_address : ''}
                                    </small>
                                </div>
                                ${!hasPackage ? '<i class="fas fa-chevron-right text-muted"></i>' : ''}
                            </div>
                        </a>
                    `;
                    $('#profileList').append(html);
                });
            } else {
                $('#profileList').html(`
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-inbox fa-2x mb-2"></i>
                        <p class="mb-0">Tidak ada profile tersedia.<br>
                        <a href="{{ route('admin.ppp-profiles.index') }}">Sync profile dari Mikrotik</a> terlebih dahulu.</p>
                    </div>
                `);
            }
        }).fail(function() {
            $('#profileListLoading').hide();
            $('#profileList').html('<div class="text-center py-3 text-danger">Gagal memuat profile</div>');
        });
    });
    
    // Select profile
    $(document).on('click', '.profile-item:not(.has-package)', function(e) {
        e.preventDefault();
        selectedProfile = $(this).data('profile');
        
        // Fill form
        $('#packageId').val('');
        $('#profileId').val(selectedProfile.id);
        $('#routerId').val(selectedProfile.router_id);
        $('#displayProfileName').text(selectedProfile.name);
        $('#displayRateLimit').text(selectedProfile.rate_limit || 'Unlimited');
        $('#displayRouter').text(selectedProfile.router_name || '-');
        $('#displayRemoteAddress').text(selectedProfile.remote_address || '-');
        $('#packageName').val(selectedProfile.name);
        $('#packagePrice').val('');
        $('#packageValidity').val(30);
        $('#packageDescription').val('');
        $('#packageActive').prop('checked', true);
        $('#packagePublic').prop('checked', true);
        
        // Reset PPN calculation
        $('#ppnCalculation').hide();
        
        $('#packageModalTitle').text('Buat Paket Baru');
        $('#selectProfileModal').modal('hide');
        $('#packageFormModal').modal('show');
    });
    
    // Edit package
    $(document).on('click', '.btn-edit', function() {
        const id = $(this).data('id');
        
        $.get(`{{ url('admin/packages') }}/${id}`, function(response) {
            if (response.success) {
                const pkg = response.data;
                
                $('#packageId').val(pkg.id);
                $('#profileId').val('');
                $('#routerId').val(pkg.router_id);
                $('#displayProfileName').text(pkg.mikrotik_profile_name);
                $('#displayRateLimit').text(pkg.rate_limit || 'Unlimited');
                $('#displayRouter').text(pkg.router?.name || '-');
                $('#displayRemoteAddress').text(pkg.remote_address || '-');
                $('#packageName').val(pkg.name);
                $('#packagePrice').val(pkg.price);
                $('#packageValidity').val(pkg.validity_days);
                $('#packageDescription').val(pkg.description || '');
                $('#packageActive').prop('checked', pkg.is_active);
                $('#packagePublic').prop('checked', pkg.is_public);
                
                // Calculate PPN for existing price
                calculatePpn(parseInt(pkg.price) || 0);
                
                $('#packageModalTitle').text('Edit Paket');
                $('#packageFormModal').modal('show');
            } else {
                toastr.error(response.message || 'Gagal memuat data');
            }
        });
    });
    
    // Save package
    $('#packageForm').on('submit', function(e) {
        e.preventDefault();
        
        const btn = $('#btnSavePackage');
        const originalHtml = btn.html();
        btn.html('<i class="fas fa-spinner fa-spin mr-1"></i>Menyimpan...').prop('disabled', true);
        
        const id = $('#packageId').val();
        const url = id ? `{{ url('admin/packages') }}/${id}` : '{{ route("admin.packages.store") }}';
        const method = id ? 'PUT' : 'POST';
        
        $.ajax({
            url: url,
            type: method,
            data: $(this).serialize() + '&_token={{ csrf_token() }}',
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    $('#packageFormModal').modal('hide');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    toastr.error(response.message);
                    btn.html(originalHtml).prop('disabled', false);
                }
            },
            error: function(xhr) {
                btn.html(originalHtml).prop('disabled', false);
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON.errors;
                    let msg = '';
                    for (let field in errors) {
                        msg += errors[field].join('<br>') + '<br>';
                    }
                    toastr.error(msg);
                } else {
                    toastr.error(xhr.responseJSON?.message || 'Terjadi kesalahan');
                }
            }
        });
    });
    
    // Delete package
    $(document).on('click', '.btn-delete', function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        
        Swal.fire({
            title: 'Hapus Paket?',
            html: `Paket <strong>${name}</strong> akan dihapus.<br>
                   <small class="text-muted">PPP Profile di Mikrotik tidak akan dihapus.</small>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `{{ url('admin/packages') }}/${id}`,
                    type: 'DELETE',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message);
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            toastr.error(response.message);
                        }
                    },
                    error: function() {
                        toastr.error('Terjadi kesalahan!');
                    }
                });
            }
        });
    });
});
</script>
@endpush
