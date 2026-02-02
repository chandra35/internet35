@extends('layouts.admin')

@section('title', 'Permissions')
@section('page-title', 'Permissions Management')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">Permissions</li>
@endsection

@push('css')
<style>
    .permission-group {
        background: #fff;
        border-radius: 0.5rem;
        box-shadow: 0 0 15px rgba(0,0,0,0.05);
        margin-bottom: 1.5rem;
        transition: all 0.3s ease;
    }
    .permission-group:hover {
        box-shadow: 0 5px 25px rgba(0,0,0,0.1);
    }
    .permission-group-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 1rem 1.25rem;
        border-radius: 0.5rem 0.5rem 0 0;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .permission-group-header h5 {
        margin: 0;
        font-weight: 600;
        font-size: 1rem;
    }
    .permission-group-header .badge {
        background: rgba(255,255,255,0.2);
        font-size: 0.75rem;
        padding: 0.35rem 0.65rem;
    }
    .permission-group-body {
        padding: 1rem;
        min-height: 100px;
    }
    .permission-item {
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 0.375rem;
        padding: 0.75rem 1rem;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        cursor: grab;
        transition: all 0.2s ease;
    }
    .permission-item:hover {
        background: #e9ecef;
        border-color: #dee2e6;
    }
    .permission-item.ui-sortable-helper {
        box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        cursor: grabbing;
        transform: rotate(2deg);
    }
    .permission-item.ui-sortable-placeholder {
        visibility: visible !important;
        background: #e3f2fd;
        border: 2px dashed #2196f3;
    }
    .permission-item.dragging {
        opacity: 0.5;
    }
    .permission-name {
        font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
        font-size: 0.875rem;
        color: #6c5ce7;
        font-weight: 500;
    }
    .permission-desc {
        font-size: 0.8rem;
        color: #6c757d;
        margin-top: 0.25rem;
    }
    .permission-info {
        flex: 1;
    }
    .permission-actions {
        display: flex;
        gap: 0.25rem;
    }
    .permission-actions .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
    }
    .drag-handle {
        color: #adb5bd;
        margin-right: 0.75rem;
        cursor: grab;
    }
    .drag-handle:hover {
        color: #6c757d;
    }
    .permission-stats {
        display: flex;
        gap: 1.5rem;
        margin-bottom: 1.5rem;
        flex-wrap: wrap;
    }
    .stat-card {
        background: #fff;
        border-radius: 0.5rem;
        padding: 1.25rem;
        flex: 1;
        min-width: 180px;
        box-shadow: 0 0 15px rgba(0,0,0,0.05);
        display: flex;
        align-items: center;
    }
    .stat-icon {
        width: 50px;
        height: 50px;
        border-radius: 0.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 1rem;
        font-size: 1.25rem;
    }
    .stat-icon.total { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
    .stat-icon.groups { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white; }
    .stat-icon.ungrouped { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; }
    .stat-icon.roles { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; }
    .stat-value {
        font-size: 1.75rem;
        font-weight: 700;
        line-height: 1;
        color: #343a40;
    }
    .stat-label {
        font-size: 0.8rem;
        color: #6c757d;
        margin-top: 0.25rem;
    }
    .search-box {
        position: relative;
    }
    .search-box input {
        padding-left: 2.5rem;
        border-radius: 0.5rem;
        border: 2px solid #e9ecef;
        transition: all 0.2s ease;
    }
    .search-box input:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.15);
    }
    .search-box .search-icon {
        position: absolute;
        left: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: #adb5bd;
    }
    .empty-group {
        text-align: center;
        padding: 2rem;
        color: #adb5bd;
    }
    .empty-group i {
        font-size: 2rem;
        margin-bottom: 0.5rem;
    }
    .permission-group.drop-active {
        border: 2px dashed #667eea;
    }
    .filter-pills {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    .filter-pill {
        padding: 0.4rem 0.85rem;
        border-radius: 2rem;
        border: 1px solid #e9ecef;
        background: #fff;
        cursor: pointer;
        transition: all 0.2s ease;
        font-size: 0.8rem;
    }
    .filter-pill:hover {
        background: #f8f9fa;
    }
    .filter-pill.active {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-color: transparent;
    }
    .roles-using {
        display: inline-flex;
        gap: 0.25rem;
        flex-wrap: wrap;
    }
    .role-badge {
        font-size: 0.7rem;
        padding: 0.15rem 0.4rem;
        border-radius: 0.25rem;
        background: #e9ecef;
        color: #495057;
    }
    .group-color-0 .permission-group-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
    .group-color-1 .permission-group-header { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }
    .group-color-2 .permission-group-header { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
    .group-color-3 .permission-group-header { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
    .group-color-4 .permission-group-header { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }
    .group-color-5 .permission-group-header { background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%); color: #333; }
    .group-color-6 .permission-group-header { background: linear-gradient(135deg, #d299c2 0%, #fef9d7 100%); color: #333; }
    .group-color-7 .permission-group-header { background: linear-gradient(135deg, #89f7fe 0%, #66a6ff 100%); }
    .group-color-8 .permission-group-header { background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%); color: #333; }
    .group-color-9 .permission-group-header { background: linear-gradient(135deg, #a1c4fd 0%, #c2e9fb 100%); color: #333; }
</style>
@endpush

@section('content')
    <!-- Statistics -->
    <div class="permission-stats">
        <div class="stat-card">
            <div class="stat-icon total">
                <i class="fas fa-key"></i>
            </div>
            <div>
                <div class="stat-value">{{ $totalPermissions }}</div>
                <div class="stat-label">Total Permissions</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon groups">
                <i class="fas fa-folder"></i>
            </div>
            <div>
                <div class="stat-value">{{ $totalGroups }}</div>
                <div class="stat-label">Groups</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon ungrouped">
                <i class="fas fa-question-circle"></i>
            </div>
            <div>
                <div class="stat-value">{{ $ungroupedCount }}</div>
                <div class="stat-label">Ungrouped</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon roles">
                <i class="fas fa-user-shield"></i>
            </div>
            <div>
                <div class="stat-value">{{ $totalRoles }}</div>
                <div class="stat-label">Roles</div>
            </div>
        </div>
    </div>

    <!-- Toolbar -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-lg-4 col-md-6 mb-3 mb-lg-0">
                    <div class="search-box">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" class="form-control" id="searchPermission" placeholder="Cari permission...">
                    </div>
                </div>
                <div class="col-lg-5 col-md-6 mb-3 mb-lg-0">
                    <div class="filter-pills" id="groupFilters">
                        <span class="filter-pill active" data-group="all">Semua</span>
                        @foreach($groups as $group)
                            <span class="filter-pill" data-group="{{ $group }}">{{ ucfirst($group) }}</span>
                        @endforeach
                        @if($ungroupedCount > 0)
                            <span class="filter-pill" data-group="ungrouped">Ungrouped</span>
                        @endif
                    </div>
                </div>
                <div class="col-lg-3 text-lg-right">
                    @can('permissions.scan')
                    <button type="button" class="btn btn-warning" id="btnScan">
                        <i class="fas fa-sync mr-1"></i> Scan
                    </button>
                    @endcan
                    @can('permissions.create')
                    <button type="button" class="btn btn-primary" id="btnCreate">
                        <i class="fas fa-plus mr-1"></i> Tambah
                    </button>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    <!-- Info Alert -->
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <i class="fas fa-info-circle mr-2"></i>
        <strong>Tips:</strong> Drag & drop permission ke group lain untuk memindahkannya. Klik <i class="fas fa-grip-vertical"></i> dan seret ke group tujuan.
        <button type="button" class="close" data-dismiss="alert">
            <span>&times;</span>
        </button>
    </div>

    <!-- Permission Groups -->
    <div class="row" id="permissionGroups">
        @php $colorIndex = 0; @endphp
        @foreach($permissionsByGroup as $groupName => $permissions)
        <div class="col-lg-6 permission-group-wrapper" data-group="{{ $groupName ?: 'ungrouped' }}">
            <div class="permission-group group-color-{{ $colorIndex % 10 }}">
                <div class="permission-group-header">
                    <h5>
                        <i class="fas fa-folder-open mr-2"></i>
                        {{ $groupName ? ucfirst($groupName) : 'Ungrouped' }}
                    </h5>
                    <span class="badge permission-count">{{ count($permissions) }} permissions</span>
                </div>
                <div class="permission-group-body sortable-group" data-group="{{ $groupName }}">
                    @forelse($permissions as $permission)
                    <div class="permission-item" data-id="{{ $permission->id }}" data-name="{{ $permission->name }}">
                        <div class="d-flex align-items-center flex-grow-1">
                            <i class="fas fa-grip-vertical drag-handle"></i>
                            <div class="permission-info">
                                <div class="permission-name">{{ $permission->name }}</div>
                                @if($permission->description)
                                    <div class="permission-desc">{{ $permission->description }}</div>
                                @endif
                                @if($permission->roles->count() > 0)
                                    <div class="roles-using mt-1">
                                        <small class="text-muted mr-1">Digunakan:</small>
                                        @foreach($permission->roles->take(3) as $role)
                                            <span class="role-badge">{{ $role->name }}</span>
                                        @endforeach
                                        @if($permission->roles->count() > 3)
                                            <span class="role-badge">+{{ $permission->roles->count() - 3 }}</span>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="permission-actions">
                            @can('permissions.edit')
                            <button type="button" class="btn btn-sm btn-info btn-edit" data-id="{{ $permission->id }}" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            @endcan
                            @can('permissions.delete')
                            <button type="button" class="btn btn-sm btn-danger btn-delete" data-id="{{ $permission->id }}" data-name="{{ $permission->name }}" title="Hapus" {{ $permission->roles->count() > 0 ? 'disabled' : '' }}>
                                <i class="fas fa-trash"></i>
                            </button>
                            @endcan
                        </div>
                    </div>
                    @empty
                    <div class="empty-group">
                        <i class="fas fa-inbox d-block"></i>
                        <p class="mb-0">Tidak ada permission</p>
                        <small>Drag permission ke sini</small>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
        @php $colorIndex++; @endphp
        @endforeach
    </div>

    <!-- Modal -->
    <div class="modal fade" id="permissionModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-key mr-2"></i> Permission</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="modalContent">
                    <div class="text-center py-5">
                        <i class="fas fa-spinner fa-spin fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
<script>
    $(document).ready(function() {
        // Initialize sortable for drag and drop
        $('.sortable-group').sortable({
            connectWith: '.sortable-group',
            handle: '.drag-handle',
            placeholder: 'permission-item ui-sortable-placeholder',
            tolerance: 'pointer',
            revert: 100,
            start: function(e, ui) {
                ui.item.addClass('dragging');
                // Remove empty message when dragging
                $('.empty-group').hide();
            },
            stop: function(e, ui) {
                ui.item.removeClass('dragging');
                // Show empty message if group is empty
                $('.sortable-group').each(function() {
                    if ($(this).find('.permission-item').length === 0) {
                        if ($(this).find('.empty-group').length === 0) {
                            $(this).append('<div class="empty-group"><i class="fas fa-inbox d-block"></i><p class="mb-0">Tidak ada permission</p><small>Drag permission ke sini</small></div>');
                        } else {
                            $(this).find('.empty-group').show();
                        }
                    }
                });
            },
            receive: function(e, ui) {
                const permissionId = ui.item.data('id');
                const newGroup = $(this).data('group');
                
                // Hide empty message
                $(this).find('.empty-group').hide();
                
                // Update permission group via AJAX
                $.ajax({
                    url: `{{ url('admin/permissions') }}/${permissionId}/update-group`,
                    type: 'PUT',
                    data: { group: newGroup },
                    success: function(response) {
                        if (response.success) {
                            toastr.success('Permission dipindahkan ke group: ' + (newGroup || 'Ungrouped'));
                            updateGroupCounts();
                        }
                    },
                    error: function(xhr) {
                        toastr.error('Gagal memindahkan permission!');
                        location.reload(); // Reload to reset position
                    }
                });
            }
        }).disableSelection();

        // Search functionality
        $('#searchPermission').on('input', function() {
            const searchTerm = $(this).val().toLowerCase();
            
            $('.permission-item').each(function() {
                const name = $(this).data('name').toLowerCase();
                const desc = $(this).find('.permission-desc').text().toLowerCase();
                
                if (name.includes(searchTerm) || desc.includes(searchTerm)) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });

            // Hide empty groups when searching
            $('.permission-group-wrapper').each(function() {
                const visibleItems = $(this).find('.permission-item:visible').length;
                if (visibleItems === 0 && searchTerm !== '') {
                    $(this).hide();
                } else {
                    $(this).show();
                }
            });
        });

        // Group filter pills
        $('.filter-pill').on('click', function() {
            $('.filter-pill').removeClass('active');
            $(this).addClass('active');
            
            const group = $(this).data('group');
            $('#searchPermission').val('');
            $('.permission-item').show();
            
            if (group === 'all') {
                $('.permission-group-wrapper').show();
            } else if (group === 'ungrouped') {
                $('.permission-group-wrapper').hide();
                $('.permission-group-wrapper[data-group="ungrouped"], .permission-group-wrapper[data-group=""]').show();
            } else {
                $('.permission-group-wrapper').hide();
                $(`.permission-group-wrapper[data-group="${group}"]`).show();
            }
        });

        // Scan button
        $('#btnScan').on('click', function() {
            const btn = $(this);
            const originalText = btn.html();
            btn.html('<i class="fas fa-spinner fa-spin mr-1"></i> Scanning...').prop('disabled', true);

            $.post('{{ route('admin.permissions.scan') }}', function(response) {
                btn.html(originalText).prop('disabled', false);
                
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Scan Berhasil!',
                        html: `
                            <div class="text-left">
                                <p class="mb-2">${response.message}</p>
                                <div class="d-flex justify-content-around mb-3">
                                    <div class="text-center">
                                        <div class="h4 text-success mb-0">${response.created}</div>
                                        <small class="text-muted">Permission Baru</small>
                                    </div>
                                    <div class="text-center">
                                        <div class="h4 text-info mb-0">${response.existing}</div>
                                        <small class="text-muted">Sudah Ada</small>
                                    </div>
                                </div>
                                ${response.created > 0 ? '<div class="alert alert-success py-2"><strong>Baru:</strong><br><code>' + response.created_permissions.join('</code>, <code>') + '</code></div>' : ''}
                            </div>
                        `,
                        showConfirmButton: true,
                        confirmButtonText: 'OK'
                    }).then(() => {
                        if (response.created > 0) {
                            location.reload();
                        }
                    });
                }
            }).fail(function(xhr) {
                btn.html(originalText).prop('disabled', false);
                toastr.error(xhr.responseJSON?.message || 'Terjadi kesalahan!');
            });
        });

        // Create button
        $('#btnCreate').on('click', function() {
            $('#permissionModal .modal-title').html('<i class="fas fa-plus mr-2"></i> Tambah Permission');
            $('#modalContent').html('<div class="text-center py-5"><i class="fas fa-spinner fa-spin fa-2x"></i></div>');
            $('#permissionModal').modal('show');
            
            $.get('{{ route('admin.permissions.create') }}', function(response) {
                $('#modalContent').html(response.html);
                initFormHandlers();
            });
        });

        // Edit button
        $(document).on('click', '.btn-edit', function() {
            const id = $(this).data('id');
            $('#permissionModal .modal-title').html('<i class="fas fa-edit mr-2"></i> Edit Permission');
            $('#modalContent').html('<div class="text-center py-5"><i class="fas fa-spinner fa-spin fa-2x"></i></div>');
            $('#permissionModal').modal('show');
            
            $.get(`{{ url('admin/permissions') }}/${id}/edit`, function(response) {
                $('#modalContent').html(response.html);
                initFormHandlers();
            });
        });

        // Delete button
        $(document).on('click', '.btn-delete:not([disabled])', function() {
            const id = $(this).data('id');
            const name = $(this).data('name');
            const item = $(this).closest('.permission-item');
            
            Swal.fire({
                title: 'Hapus Permission?',
                html: `Anda yakin ingin menghapus <code>${name}</code>?<br><small class="text-danger">Data yang dihapus tidak dapat dikembalikan.</small>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `{{ url('admin/permissions') }}/${id}`,
                        type: 'DELETE',
                        success: function(response) {
                            if (response.success) {
                                item.fadeOut(300, function() {
                                    const parent = $(this).closest('.sortable-group');
                                    $(this).remove();
                                    updateGroupCounts();
                                    // Show empty message if needed
                                    if (parent.find('.permission-item').length === 0) {
                                        parent.append('<div class="empty-group"><i class="fas fa-inbox d-block"></i><p class="mb-0">Tidak ada permission</p><small>Drag permission ke sini</small></div>');
                                    }
                                });
                                toastr.success(response.message);
                            } else {
                                toastr.error(response.message);
                            }
                        },
                        error: function(xhr) {
                            toastr.error(xhr.responseJSON?.message || 'Terjadi kesalahan!');
                        }
                    });
                }
            });
        });
    });

    function initFormHandlers() {
        const form = $('#permissionForm');
        const submitBtn = form.find('button[type="submit"]');
        
        form.on('submit', function(e) {
            e.preventDefault();
            
            const originalBtnText = submitBtn.html();
            submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...');
            
            $.ajax({
                url: form.attr('action'),
                type: 'POST',
                data: form.serialize(),
                success: function(response) {
                    submitBtn.prop('disabled', false).html(originalBtnText);
                    if (response.success) {
                        $('#permissionModal').modal('hide');
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: response.message,
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
                    }
                },
                error: function(xhr) {
                    submitBtn.prop('disabled', false).html(originalBtnText);
                    if (xhr.status === 422) {
                        const errors = xhr.responseJSON.errors;
                        let errorMsg = '<ul class="text-left mb-0">';
                        Object.keys(errors).forEach(key => {
                            errors[key].forEach(msg => {
                                errorMsg += '<li>' + msg + '</li>';
                            });
                        });
                        errorMsg += '</ul>';
                        Swal.fire({
                            icon: 'error',
                            title: 'Validasi Gagal',
                            html: errorMsg
                        });
                    } else {
                        toastr.error(xhr.responseJSON?.message || 'Terjadi kesalahan!');
                    }
                }
            });
        });
    }

    function updateGroupCounts() {
        $('.permission-group').each(function() {
            const count = $(this).find('.permission-item').length;
            $(this).find('.permission-count').text(count + ' permissions');
        });
    }
</script>
@endpush
