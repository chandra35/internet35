@extends('layouts.admin')

@section('title', 'IP Pools')

@section('page-title', 'Manajemen IP Pool')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.routers.index') }}">Router</a></li>
    <li class="breadcrumb-item active">IP Pools</li>
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
                            <i class="fas fa-plus mr-1"></i> Tambah IP Pool
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

        <!-- Pools List -->
        @if($selectedRouter)
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-network-wired mr-2"></i>
                    IP Pools - {{ $selectedRouter->name }}
                </h3>
                <div class="card-tools">
                    <span class="badge badge-info">{{ $pools->count() }} pool</span>
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
                            <th>IP Ranges</th>
                            <th>Total IP</th>
                            <th>Next Pool</th>
                            <th>Status Sync</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pools as $pool)
                        <tr>
                            <td>
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input check-item" 
                                           id="check{{ $pool->id }}" 
                                           value="{{ $pool->id }}" 
                                           data-name="{{ $pool->name }}"
                                           data-mikrotik-id="{{ $pool->mikrotik_id }}">
                                    <label class="custom-control-label" for="check{{ $pool->id }}"></label>
                                </div>
                            </td>
                            <td><strong>{{ $pool->name }}</strong></td>
                            <td><code>{{ $pool->ranges }}</code></td>
                            <td>
                                <span class="badge badge-primary">{{ $pool->total_ips }} IP</span>
                                <button class="btn btn-xs btn-outline-info btn-usage" data-id="{{ $pool->id }}" title="Cek penggunaan">
                                    <i class="fas fa-chart-pie"></i>
                                </button>
                            </td>
                            <td>{{ $pool->next_pool ?: '-' }}</td>
                            <td>
                                @if($pool->is_synced)
                                    <span class="badge badge-success"><i class="fas fa-check mr-1"></i>Synced</span>
                                @else
                                    <span class="badge badge-warning"><i class="fas fa-exclamation mr-1"></i>Not in Mikrotik</span>
                                @endif
                            </td>
                            <td>
                                <button class="btn btn-sm btn-warning btn-edit" data-id="{{ $pool->id }}" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-danger btn-delete" 
                                        data-id="{{ $pool->id }}" 
                                        data-name="{{ $pool->name }}" 
                                        data-mikrotik-id="{{ $pool->mikrotik_id }}"
                                        title="Hapus">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                Belum ada IP Pool. Klik <strong>Sync dari Mikrotik</strong> untuk mengambil data.
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
<div class="modal fade" id="poolModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-network-wired mr-2"></i><span id="modalTitle">Tambah IP Pool</span></h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form id="poolForm">
                <div class="modal-body">
                    <input type="hidden" id="poolId" name="id">
                    
                    <div class="form-group">
                        <label>Nama Pool <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="poolName" name="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label>IP Ranges <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="poolRanges" name="ranges" required placeholder="contoh: 192.168.1.10-192.168.1.100">
                        <small class="text-muted">Format: start-end atau beberapa range dipisah koma</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Next Pool</label>
                        <select class="form-control" id="nextPool" name="next_pool">
                            <option value="">-- Tidak Ada --</option>
                        </select>
                        <small class="text-muted">Pool cadangan jika pool ini penuh</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Komentar</label>
                        <textarea class="form-control" id="poolComment" name="comment" rows="2"></textarea>
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

<!-- Usage Modal -->
<div class="modal fade" id="usageModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-chart-pie mr-2"></i>Penggunaan IP Pool</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body" id="usageContent">
                <div class="text-center">
                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                    <p class="mt-2">Memuat data...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Sync Preview Modal -->
<div class="modal fade" id="syncPreviewModal" tabindex="-1" data-backdrop="static">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-info">
                <h5 class="modal-title text-white"><i class="fas fa-sync-alt mr-2"></i>Preview Sync IP Pools</h5>
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
                        <br><small class="text-muted">Jika dicentang, IP Pool juga akan dihapus dari router Mikrotik</small>
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
    let currentPools = @json($pools);
    let deleteIds = [];
    let syncData = null;
    
    // POP selector change
    $('#selectPop').change(function() {
        const selectedPop = $(this).val();
        if (selectedPop) {
            window.location.href = '{{ route("admin.ip-pools.index") }}?pop_id=' + selectedPop;
        }
    });
    
    // Router selector change
    $('#selectRouter').change(function() {
        const selectedRouter = $(this).val();
        if (selectedRouter) {
            let url = '{{ route("admin.ip-pools.index") }}?router_id=' + selectedRouter;
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
    
    // Load pools for dropdown
    function loadPoolOptions(excludeId = null) {
        let options = '<option value="">-- Tidak Ada --</option>';
        currentPools.forEach(function(pool) {
            if (pool.id !== excludeId) {
                options += `<option value="${pool.name}">${pool.name}</option>`;
            }
        });
        $('#nextPool').html(options);
    }
    
    // Sync pools - show preview first
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
            url: '{{ url("admin/ip-pools") }}/' + routerId + '/preview',
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
                            <span class="info-box-text">Pool Baru</span>
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
                <h6 class="text-success"><i class="fas fa-plus mr-1"></i>Pool Baru (akan ditambahkan)</h6>
                <table class="table table-sm table-bordered mb-3">
                    <thead class="thead-light">
                        <tr>
                            <th>Nama</th>
                            <th>Ranges</th>
                            <th>Next Pool</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            data.new.forEach(function(p) {
                html += `
                    <tr class="table-success">
                        <td><strong>${p.name}</strong></td>
                        <td><code>${p.ranges}</code></td>
                        <td>${p['next-pool'] || '-'}</td>
                    </tr>
                `;
            });
            html += '</tbody></table>';
        }
        
        if (data.update.length > 0) {
            html += `
                <h6 class="text-warning"><i class="fas fa-sync mr-1"></i>Pool Diupdate (ada perubahan)</h6>
                <table class="table table-sm table-bordered mb-3">
                    <thead class="thead-light">
                        <tr>
                            <th>Nama</th>
                            <th>Ranges</th>
                            <th>Next Pool</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            data.update.forEach(function(p) {
                html += `
                    <tr class="table-warning">
                        <td><strong>${p.name}</strong></td>
                        <td><code>${p.ranges}</code></td>
                        <td>${p['next-pool'] || '-'}</td>
                    </tr>
                `;
            });
            html += '</tbody></table>';
        }
        
        if (data.restore && data.restore.length > 0) {
            html += `
                <h6 class="text-info"><i class="fas fa-undo mr-1"></i>Pool Direstore (dihapus di DB tapi ada di Mikrotik)</h6>
                <table class="table table-sm table-bordered mb-3">
                    <thead class="thead-light">
                        <tr>
                            <th>Nama</th>
                            <th>Ranges</th>
                            <th>Next Pool</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            data.restore.forEach(function(p) {
                html += `
                    <tr class="table-info">
                        <td><strong>${p.name}</strong></td>
                        <td><code>${p.ranges}</code></td>
                        <td>${p['next-pool'] || '-'}</td>
                    </tr>
                `;
            });
            html += '</tbody></table>';
        }
        
        if (data.unchanged.length > 0) {
            html += `
                <h6 class="text-secondary"><i class="fas fa-check mr-1"></i>Pool Tidak Berubah</h6>
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
            url: '{{ url("admin/ip-pools") }}/' + routerId + '/sync',
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
                toastr.error(xhr.responseJSON?.message || 'Gagal sync IP Pool');
            },
            complete: function() {
                btn.html(originalHtml).prop('disabled', false);
            }
        });
    });
    
    // Create pool
    $('#btnCreate').click(function() {
        $('#poolId').val('');
        $('#poolForm')[0].reset();
        $('#modalTitle').text('Tambah IP Pool');
        $('#poolName').prop('readonly', false);
        loadPoolOptions();
        $('#poolModal').modal('show');
    });
    
    // Edit pool
    $('.btn-edit').click(function() {
        const id = $(this).data('id');
        
        $.get('{{ url("admin/ip-pools") }}/' + id, function(res) {
            if (res.success) {
                const p = res.data;
                $('#poolId').val(p.id);
                $('#poolName').val(p.name).prop('readonly', true);
                $('#poolRanges').val(p.ranges);
                loadPoolOptions(p.id);
                setTimeout(() => $('#nextPool').val(p.next_pool), 100);
                $('#poolComment').val(p.comment);
                $('#modalTitle').text('Edit IP Pool');
                $('#poolModal').modal('show');
            }
        });
    });
    
    // Check usage
    $('.btn-usage').click(function() {
        const id = $(this).data('id');
        
        $('#usageContent').html(`
            <div class="text-center">
                <i class="fas fa-spinner fa-spin fa-2x"></i>
                <p class="mt-2">Memuat data...</p>
            </div>
        `);
        $('#usageModal').modal('show');
        
        $.get('{{ url("admin/ip-pools") }}/' + id + '/usage', function(res) {
            if (res.success) {
                const d = res.data;
                const usedPercent = Math.round((d.used / d.total) * 100) || 0;
                const colorClass = usedPercent > 80 ? 'danger' : (usedPercent > 50 ? 'warning' : 'success');
                
                $('#usageContent').html(`
                    <div class="text-center mb-3">
                        <h2 class="mb-0 text-${colorClass}">${usedPercent}%</h2>
                        <small class="text-muted">Terpakai</small>
                    </div>
                    <div class="progress mb-3" style="height: 25px;">
                        <div class="progress-bar bg-${colorClass}" style="width: ${usedPercent}%">${d.used} / ${d.total}</div>
                    </div>
                    <table class="table table-sm">
                        <tr><th>Total IP</th><td class="text-right">${d.total}</td></tr>
                        <tr><th>Terpakai</th><td class="text-right text-${colorClass}">${d.used}</td></tr>
                        <tr><th>Tersedia</th><td class="text-right text-success">${d.available}</td></tr>
                    </table>
                `);
            } else {
                $('#usageContent').html(`
                    <div class="alert alert-danger mb-0">
                        <i class="fas fa-exclamation-triangle mr-2"></i>${res.message}
                    </div>
                `);
            }
        }).fail(function(xhr) {
            $('#usageContent').html(`
                <div class="alert alert-danger mb-0">
                    <i class="fas fa-exclamation-triangle mr-2"></i>Gagal memuat data
                </div>
            `);
        });
    });
    
    // Submit form
    $('#poolForm').submit(function(e) {
        e.preventDefault();
        
        const id = $('#poolId').val();
        const isEdit = !!id;
        const url = isEdit 
            ? '{{ url("admin/ip-pools") }}/' + id 
            : '{{ url("admin/ip-pools") }}/' + routerId + '/store';
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
                    $('#poolModal').modal('hide');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    toastr.error(res.message);
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Gagal menyimpan IP Pool');
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
        $('#deleteMessage').html(`Apakah Anda yakin ingin menghapus IP Pool <strong>${name}</strong>?`);
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
        $('#deleteMessage').html(`Apakah Anda yakin ingin menghapus <strong>${deleteIds.length}</strong> IP Pool berikut?<br><code>${names}</code>`);
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
            url: '{{ url("admin/ip-pools/bulk-delete") }}',
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
                toastr.error(xhr.responseJSON?.message || 'Gagal menghapus IP Pool');
            },
            complete: function() {
                btn.html(originalHtml).prop('disabled', false);
            }
        });
    });
});
</script>
@endpush
