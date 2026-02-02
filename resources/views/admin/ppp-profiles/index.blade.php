@extends('layouts.admin')

@section('title', 'PPP Profiles')

@section('page-title', 'Manajemen PPP Profile')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.routers.index') }}">Router</a></li>
    <li class="breadcrumb-item active">PPP Profiles</li>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <!-- Superadmin POP Selector -->
        @if($pops && auth()->user()->hasRole('superadmin'))
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
                            @foreach($pops as $pop)
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

        <!-- Router Selector -->
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-server mr-2"></i>Pilih Router</h3>
            </div>
            <div class="card-body">
                @if($routers->count() > 0)
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <select class="form-control select2" id="selectRouter" style="width: 100%;">
                            <option value="">-- Pilih Router --</option>
                            @foreach($routers as $router)
                                <option value="{{ $router->id }}" {{ $selectedRouter?->id == $router->id ? 'selected' : '' }}>
                                    {{ $router->name }} ({{ $router->host }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        @if($selectedRouter)
                        <button type="button" class="btn btn-info" id="btnSync">
                            <i class="fas fa-sync-alt mr-1"></i> Sync dari Mikrotik
                        </button>
                        <button type="button" class="btn btn-success" id="btnCreate">
                            <i class="fas fa-plus mr-1"></i> Tambah Profile
                        </button>
                        <button type="button" class="btn btn-danger d-none" id="btnBulkDelete">
                            <i class="fas fa-trash mr-1"></i> Hapus Terpilih (<span id="selectedCount">0</span>)
                        </button>
                        @endif
                    </div>
                </div>
                @else
                <div class="alert alert-warning mb-0">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    @if(!$popId)
                        Silakan pilih POP terlebih dahulu.
                    @else
                        Belum ada router untuk POP ini. <a href="{{ route('admin.routers.create') }}">Tambah Router</a>
                    @endif
                </div>
                @endif
            </div>
        </div>

        <!-- Profiles List -->
        @if($selectedRouter)
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-id-card mr-2"></i>
                    PPP Profiles - {{ $selectedRouter->name }}
                </h3>
                <div class="card-tools">
                    <span class="badge badge-info">{{ $profiles->count() }} profile</span>
                </div>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover table-striped">
                    <thead>
                        <tr>
                            <th width="40">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="checkAll">
                                    <label class="custom-control-label" for="checkAll"></label>
                                </div>
                            </th>
                            <th>Nama</th>
                            <th>Local Address</th>
                            <th>Remote Address (Pool)</th>
                            <th>Rate Limit</th>
                            <th>Status Sync</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($profiles as $profile)
                        <tr>
                            <td>
                                @if(!$profile->is_default)
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input check-item" 
                                           id="check{{ $profile->id }}" 
                                           value="{{ $profile->id }}" 
                                           data-name="{{ $profile->name }}"
                                           data-mikrotik-id="{{ $profile->mikrotik_id }}">
                                    <label class="custom-control-label" for="check{{ $profile->id }}"></label>
                                </div>
                                @endif
                            </td>
                            <td>
                                <strong>{{ $profile->name }}</strong>
                                @if($profile->is_default)
                                    <span class="badge badge-primary">Default</span>
                                @endif
                            </td>
                            <td>{{ $profile->local_address ?: '-' }}</td>
                            <td>
                                @if($profile->remote_address)
                                    <span class="badge badge-info">{{ $profile->remote_address }}</span>
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                @if($profile->rate_limit)
                                    <code>{{ $profile->rate_limit }}</code>
                                @else
                                    <span class="text-muted">Unlimited</span>
                                @endif
                            </td>
                            <td>
                                @if($profile->is_synced)
                                    <span class="badge badge-success"><i class="fas fa-check mr-1"></i>Synced</span>
                                @else
                                    <span class="badge badge-warning"><i class="fas fa-exclamation mr-1"></i>Not in Mikrotik</span>
                                @endif
                            </td>
                            <td>
                                <button class="btn btn-sm btn-info btn-view" data-id="{{ $profile->id }}" title="Detail">
                                    <i class="fas fa-eye"></i>
                                </button>
                                @if(!$profile->is_default)
                                <button class="btn btn-sm btn-warning btn-edit" data-id="{{ $profile->id }}" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-danger btn-delete" 
                                        data-id="{{ $profile->id }}" 
                                        data-name="{{ $profile->name }}" 
                                        data-mikrotik-id="{{ $profile->mikrotik_id }}"
                                        title="Hapus">
                                    <i class="fas fa-trash"></i>
                                </button>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                Belum ada profile. Klik <strong>Sync dari Mikrotik</strong> untuk mengambil data.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Create/Edit Modal -->
<div class="modal fade" id="profileModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-id-card mr-2"></i><span id="modalTitle">Tambah Profile</span></h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form id="profileForm">
                <div class="modal-body">
                    <input type="hidden" id="profileId" name="id">
                    
                    <div class="form-group">
                        <label>Nama Profile <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="profileName" name="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Local Address</label>
                        <input type="text" class="form-control" id="localAddress" name="local_address" placeholder="contoh: 10.10.10.1">
                        <small class="text-muted">IP address router untuk PPP</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Remote Address (IP Pool)</label>
                        <select class="form-control" id="remoteAddress" name="remote_address">
                            <option value="">-- Pilih IP Pool --</option>
                        </select>
                        <small class="text-muted">IP Pool untuk pelanggan</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Rate Limit</label>
                        <input type="text" class="form-control" id="rateLimit" name="rate_limit" placeholder="contoh: 10M/10M">
                        <small class="text-muted">Format: upload/download (contoh: 5M/10M)</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Komentar</label>
                        <textarea class="form-control" id="profileComment" name="comment" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btnSubmit">
                        <i class="fas fa-save mr-1"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Modal -->
<div class="modal fade" id="viewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-id-card mr-2"></i>Detail Profile</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body" id="viewContent">
                <!-- Content loaded via AJAX -->
            </div>
        </div>
    </div>
</div>

<!-- Sync Preview Modal -->
<div class="modal fade" id="syncPreviewModal" tabindex="-1" data-backdrop="static">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-info">
                <h5 class="modal-title text-white"><i class="fas fa-sync-alt mr-2"></i>Preview Sync PPP Profiles</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body" id="syncPreviewContent">
                <div class="text-center py-5">
                    <i class="fas fa-spinner fa-spin fa-3x text-info"></i>
                    <p class="mt-3">Mengambil data dari Mikrotik...</p>
                </div>
            </div>
            <div class="modal-footer d-none" id="syncPreviewFooter">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-info" id="btnConfirmSync">
                    <i class="fas fa-save mr-1"></i> Simpan ke Database
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger">
                <h5 class="modal-title text-white"><i class="fas fa-trash mr-2"></i>Konfirmasi Hapus</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <p id="deleteMessage">Apakah Anda yakin ingin menghapus item ini?</p>
                
                <div class="custom-control custom-checkbox mt-3">
                    <input type="checkbox" class="custom-control-input" id="deleteFromMikrotik" checked>
                    <label class="custom-control-label" for="deleteFromMikrotik">
                        <strong>Hapus juga dari Mikrotik</strong>
                        <br><small class="text-muted">Jika dicentang, profile juga akan dihapus dari router Mikrotik</small>
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger" id="btnConfirmDelete">
                    <i class="fas fa-trash mr-1"></i> Hapus
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
$(function() {
    const routerId = '{{ $selectedRouter?->id }}';
    const popId = '{{ $popId }}';
    let deleteIds = [];
    let syncData = null;
    
    // POP selector change
    $('#selectPop').change(function() {
        const selectedPop = $(this).val();
        if (selectedPop) {
            window.location.href = '{{ route("admin.ppp-profiles.index") }}?pop_id=' + selectedPop;
        }
    });
    
    // Router selector change
    $('#selectRouter').change(function() {
        const selectedRouter = $(this).val();
        if (selectedRouter) {
            let url = '{{ route("admin.ppp-profiles.index") }}?router_id=' + selectedRouter;
            if (popId) {
                url += '&pop_id=' + popId;
            }
            window.location.href = url;
        }
    });
    
    // Check all checkbox
    $('#checkAll').change(function() {
        $('.check-item').prop('checked', $(this).is(':checked'));
        updateBulkDeleteButton();
    });
    
    // Individual checkbox
    $(document).on('change', '.check-item', function() {
        updateBulkDeleteButton();
        
        // Update checkAll state
        const total = $('.check-item').length;
        const checked = $('.check-item:checked').length;
        $('#checkAll').prop('checked', total === checked);
        $('#checkAll').prop('indeterminate', checked > 0 && checked < total);
    });
    
    function updateBulkDeleteButton() {
        const count = $('.check-item:checked').length;
        $('#selectedCount').text(count);
        if (count > 0) {
            $('#btnBulkDelete').removeClass('d-none');
        } else {
            $('#btnBulkDelete').addClass('d-none');
        }
    }
    
    // Load IP Pools for dropdown
    function loadIPPools() {
        if (!routerId) return;
        
        $.get('{{ url("admin/ip-pools") }}/' + routerId + '/list', function(res) {
            if (res.success) {
                let options = '<option value="">-- Pilih IP Pool --</option>';
                res.data.forEach(function(pool) {
                    options += `<option value="${pool.name}">${pool.name} (${pool.ranges})</option>`;
                });
                $('#remoteAddress').html(options);
            }
        });
    }
    
    // Sync profiles - show preview first
    $('#btnSync').click(function() {
        const btn = $(this);
        const originalHtml = btn.html();
        btn.html('<i class="fas fa-spinner fa-spin mr-1"></i> Memuat...').prop('disabled', true);
        
        // Reset modal
        $('#syncPreviewFooter').addClass('d-none');
        $('#syncPreviewContent').html(`
            <div class="text-center py-5">
                <i class="fas fa-spinner fa-spin fa-3x text-info"></i>
                <p class="mt-3">Mengambil data dari Mikrotik...</p>
            </div>
        `);
        $('#syncPreviewModal').modal('show');
        
        $.ajax({
            url: '{{ url("admin/ppp-profiles") }}/' + routerId + '/preview',
            method: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function(res) {
                if (res.success) {
                    syncData = res.data;
                    renderSyncPreview(res.data);
                    $('#syncPreviewFooter').removeClass('d-none');
                } else {
                    $('#syncPreviewContent').html(`
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle mr-2"></i>${res.message}
                        </div>
                    `);
                }
            },
            error: function(xhr) {
                $('#syncPreviewContent').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        ${xhr.responseJSON?.message || 'Gagal mengambil data dari Mikrotik'}
                    </div>
                `);
            },
            complete: function() {
                btn.html(originalHtml).prop('disabled', false);
            }
        });
    });
    
    function renderSyncPreview(data) {
        let restoreCount = data.restore ? data.restore.length : 0;
        let html = `
            <div class="row mb-3">
                <div class="col-md-3">
                    <div class="info-box bg-success">
                        <span class="info-box-icon"><i class="fas fa-plus"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Profile Baru</span>
                            <span class="info-box-number">${data.new.length}</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="info-box bg-warning">
                        <span class="info-box-icon"><i class="fas fa-sync"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Akan Diupdate</span>
                            <span class="info-box-number">${data.update.length}</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="info-box bg-info">
                        <span class="info-box-icon"><i class="fas fa-undo"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Akan Direstore</span>
                            <span class="info-box-number">${restoreCount}</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="info-box bg-secondary">
                        <span class="info-box-icon"><i class="fas fa-check"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Tidak Berubah</span>
                            <span class="info-box-number">${data.unchanged.length}</span>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        if (data.new.length > 0) {
            html += `
                <h6 class="text-success"><i class="fas fa-plus mr-1"></i>Profile Baru (akan ditambahkan)</h6>
                <table class="table table-sm table-bordered mb-3">
                    <thead class="thead-light">
                        <tr>
                            <th>Nama</th>
                            <th>Local Address</th>
                            <th>Remote Address</th>
                            <th>Rate Limit</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            data.new.forEach(function(p) {
                html += `
                    <tr class="table-success">
                        <td><strong>${p.name}</strong></td>
                        <td>${p['local-address'] || '-'}</td>
                        <td>${p['remote-address'] || '-'}</td>
                        <td>${p['rate-limit'] || 'Unlimited'}</td>
                    </tr>
                `;
            });
            html += '</tbody></table>';
        }
        
        if (data.update.length > 0) {
            html += `
                <h6 class="text-warning"><i class="fas fa-sync mr-1"></i>Profile Diupdate (ada perubahan)</h6>
                <table class="table table-sm table-bordered mb-3">
                    <thead class="thead-light">
                        <tr>
                            <th>Nama</th>
                            <th>Local Address</th>
                            <th>Remote Address</th>
                            <th>Rate Limit</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            data.update.forEach(function(p) {
                html += `
                    <tr class="table-warning">
                        <td><strong>${p.name}</strong></td>
                        <td>${p['local-address'] || '-'}</td>
                        <td>${p['remote-address'] || '-'}</td>
                        <td>${p['rate-limit'] || 'Unlimited'}</td>
                    </tr>
                `;
            });
            html += '</tbody></table>';
        }
        
        if (data.restore && data.restore.length > 0) {
            html += `
                <h6 class="text-info"><i class="fas fa-undo mr-1"></i>Profile Direstore (dihapus di DB tapi ada di Mikrotik)</h6>
                <table class="table table-sm table-bordered mb-3">
                    <thead class="thead-light">
                        <tr>
                            <th>Nama</th>
                            <th>Local Address</th>
                            <th>Remote Address</th>
                            <th>Rate Limit</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            data.restore.forEach(function(p) {
                html += `
                    <tr class="table-info">
                        <td><strong>${p.name}</strong></td>
                        <td>${p['local-address'] || '-'}</td>
                        <td>${p['remote-address'] || '-'}</td>
                        <td>${p['rate-limit'] || 'Unlimited'}</td>
                    </tr>
                `;
            });
            html += '</tbody></table>';
        }
        
        if (data.unchanged.length > 0) {
            html += `
                <h6 class="text-secondary"><i class="fas fa-check mr-1"></i>Profile Tidak Berubah</h6>
                <div class="mb-3">
            `;
            data.unchanged.forEach(function(p) {
                html += `<span class="badge badge-secondary mr-1 mb-1">${p.name}</span>`;
            });
            html += '</div>';
        }
        
        if (data.not_in_mikrotik && data.not_in_mikrotik.length > 0) {
            html += `
                <h6 class="text-danger"><i class="fas fa-exclamation-triangle mr-1"></i>Tidak Ada di Mikrotik (akan ditandai tidak sync)</h6>
                <div class="mb-3">
            `;
            data.not_in_mikrotik.forEach(function(p) {
                html += `<span class="badge badge-danger mr-1 mb-1">${p.name}</span>`;
            });
            html += '</div>';
        }
        
        $('#syncPreviewContent').html(html);
    }
    
    // Confirm sync
    $('#btnConfirmSync').click(function() {
        const btn = $(this);
        const originalHtml = btn.html();
        btn.html('<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...').prop('disabled', true);
        
        $.ajax({
            url: '{{ url("admin/ppp-profiles") }}/' + routerId + '/sync',
            method: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function(res) {
                if (res.success) {
                    toastr.success(res.message);
                    $('#syncPreviewModal').modal('hide');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    toastr.error(res.message);
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Gagal sync profile');
            },
            complete: function() {
                btn.html(originalHtml).prop('disabled', false);
            }
        });
    });
    
    // Create profile
    $('#btnCreate').click(function() {
        $('#profileId').val('');
        $('#profileForm')[0].reset();
        $('#modalTitle').text('Tambah Profile');
        $('#profileName').prop('readonly', false);
        loadIPPools();
        $('#profileModal').modal('show');
    });
    
    // Edit profile
    $('.btn-edit').click(function() {
        const id = $(this).data('id');
        
        $.get('{{ url("admin/ppp-profiles") }}/' + id, function(res) {
            if (res.success) {
                const p = res.data;
                $('#profileId').val(p.id);
                $('#profileName').val(p.name).prop('readonly', true);
                $('#localAddress').val(p.local_address);
                loadIPPools();
                setTimeout(() => $('#remoteAddress').val(p.remote_address), 500);
                $('#rateLimit').val(p.rate_limit);
                $('#profileComment').val(p.comment);
                $('#modalTitle').text('Edit Profile');
                $('#profileModal').modal('show');
            }
        });
    });
    
    // View profile
    $('.btn-view').click(function() {
        const id = $(this).data('id');
        
        $.get('{{ url("admin/ppp-profiles") }}/' + id, function(res) {
            if (res.success) {
                const p = res.data;
                let html = `
                    <table class="table table-bordered">
                        <tr><th width="35%">Nama</th><td>${p.name}</td></tr>
                        <tr><th>Local Address</th><td>${p.local_address || '-'}</td></tr>
                        <tr><th>Remote Address</th><td>${p.remote_address || '-'}</td></tr>
                        <tr><th>Rate Limit</th><td>${p.rate_limit || 'Unlimited'}</td></tr>
                        <tr><th>Bridge</th><td>${p.bridge || '-'}</td></tr>
                        <tr><th>DNS Server</th><td>${p.dns_server || '-'}</td></tr>
                        <tr><th>Session Timeout</th><td>${p.session_timeout || '-'}</td></tr>
                        <tr><th>Idle Timeout</th><td>${p.idle_timeout || '-'}</td></tr>
                        <tr><th>Parent Queue</th><td>${p.parent_queue || '-'}</td></tr>
                        <tr><th>Komentar</th><td>${p.comment || '-'}</td></tr>
                        <tr><th>Mikrotik ID</th><td><code>${p.mikrotik_id || '-'}</code></td></tr>
                        <tr><th>Last Synced</th><td>${p.last_synced_at || '-'}</td></tr>
                    </table>
                `;
                $('#viewContent').html(html);
                $('#viewModal').modal('show');
            }
        });
    });
    
    // Submit form
    $('#profileForm').submit(function(e) {
        e.preventDefault();
        
        const id = $('#profileId').val();
        const isEdit = !!id;
        const url = isEdit 
            ? '{{ url("admin/ppp-profiles") }}/' + id 
            : '{{ url("admin/ppp-profiles") }}/' + routerId + '/store';
        const method = isEdit ? 'PUT' : 'POST';
        
        const btn = $('#btnSubmit');
        const originalHtml = btn.html();
        btn.html('<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...').prop('disabled', true);
        
        $.ajax({
            url: url,
            method: method,
            data: $(this).serialize() + '&_token={{ csrf_token() }}',
            success: function(res) {
                if (res.success) {
                    toastr.success(res.message);
                    $('#profileModal').modal('hide');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    toastr.error(res.message);
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Gagal menyimpan profile');
            },
            complete: function() {
                btn.html(originalHtml).prop('disabled', false);
            }
        });
    });
    
    // Single delete
    $('.btn-delete').click(function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        const mikrotikId = $(this).data('mikrotik-id');
        
        deleteIds = [{ id: id, name: name, mikrotikId: mikrotikId }];
        $('#deleteMessage').html(`Apakah Anda yakin ingin menghapus profile <strong>${name}</strong>?`);
        $('#deleteFromMikrotik').prop('checked', !!mikrotikId);
        $('#deleteModal').modal('show');
    });
    
    // Bulk delete
    $('#btnBulkDelete').click(function() {
        deleteIds = [];
        $('.check-item:checked').each(function() {
            deleteIds.push({
                id: $(this).val(),
                name: $(this).data('name'),
                mikrotikId: $(this).data('mikrotik-id')
            });
        });
        
        const names = deleteIds.map(d => d.name).join(', ');
        $('#deleteMessage').html(`Apakah Anda yakin ingin menghapus <strong>${deleteIds.length}</strong> profile berikut?<br><code>${names}</code>`);
        $('#deleteFromMikrotik').prop('checked', true);
        $('#deleteModal').modal('show');
    });
    
    // Confirm delete
    $('#btnConfirmDelete').click(function() {
        const btn = $(this);
        const originalHtml = btn.html();
        const deleteFromMT = $('#deleteFromMikrotik').is(':checked');
        
        btn.html('<i class="fas fa-spinner fa-spin mr-1"></i> Menghapus...').prop('disabled', true);
        
        const ids = deleteIds.map(d => d.id);
        
        $.ajax({
            url: '{{ url("admin/ppp-profiles/bulk-delete") }}',
            method: 'POST',
            data: { 
                _token: '{{ csrf_token() }}',
                ids: ids,
                delete_from_mikrotik: deleteFromMT ? 1 : 0
            },
            success: function(res) {
                if (res.success) {
                    toastr.success(res.message);
                    $('#deleteModal').modal('hide');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    toastr.error(res.message);
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Gagal menghapus profile');
            },
            complete: function() {
                btn.html(originalHtml).prop('disabled', false);
            }
        });
    });
});
</script>
@endpush
