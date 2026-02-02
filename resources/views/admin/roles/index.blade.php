@extends('layouts.admin')

@section('title', 'Roles')
@section('page-title', 'Roles Management')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">Roles</li>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-user-tag mr-1"></i> Daftar Roles
            </h3>
            <div class="card-tools">
                @can('roles.create')
                <button type="button" class="btn btn-primary btn-sm" id="btnCreate">
                    <i class="fas fa-plus mr-1"></i> Tambah Role
                </button>
                @endcan
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="rolesTable">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Deskripsi</th>
                            <th width="120">Permissions</th>
                            <th width="80">Users</th>
                            <th width="150">Dibuat</th>
                            <th width="120">Aksi</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Form -->
    <div class="modal fade" id="roleModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-user-tag mr-2"></i>Role</h5>
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

    <!-- Modal View -->
    <div class="modal fade" id="viewModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title"><i class="fas fa-eye mr-2"></i>Detail Role</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="viewContent">
                    <div class="text-center py-5">
                        <i class="fas fa-spinner fa-spin fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
<script>
    let table;

    $(document).ready(function() {
        // Initialize DataTable
        table = $('#rolesTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: '{{ route('admin.roles.index') }}',
            columns: [
                { data: 'name' },
                { data: 'description' },
                { data: 'permissions_count' },
                { data: 'users_count' },
                { data: 'created_at' },
                { data: 'actions', orderable: false, searchable: false }
            ],
            order: [[0, 'asc']],
            language: dtLanguageID
        });

        // Create button
        $('#btnCreate').on('click', function() {
            $('#roleModal .modal-title').html('<i class="fas fa-plus mr-2"></i> Tambah Role');
            $('#modalContent').html('<div class="text-center py-5"><i class="fas fa-spinner fa-spin fa-2x"></i></div>');
            $('#roleModal').modal('show');
            
            $.get('{{ route('admin.roles.create') }}', function(response) {
                $('#modalContent').html(response.html);
                initFormHandlers();
            });
        });

        // View button
        $(document).on('click', '.btn-view', function() {
            const id = $(this).data('id');
            $('#viewModal .modal-title').html('<i class="fas fa-eye mr-2"></i> Detail Role');
            $('#viewContent').html('<div class="text-center py-5"><i class="fas fa-spinner fa-spin fa-2x"></i></div>');
            $('#viewModal').modal('show');
            
            $.get(`{{ url('admin/roles') }}/${id}`, function(response) {
                $('#viewContent').html(response.html);
            });
        });

        // Edit button
        $(document).on('click', '.btn-edit', function() {
            const id = $(this).data('id');
            $('#roleModal .modal-title').html('<i class="fas fa-edit mr-2"></i> Edit Role');
            $('#modalContent').html('<div class="text-center py-5"><i class="fas fa-spinner fa-spin fa-2x"></i></div>');
            $('#roleModal').modal('show');
            
            $.get(`{{ url('admin/roles') }}/${id}/edit`, function(response) {
                $('#modalContent').html(response.html);
                initFormHandlers();
            });
        });

        // Delete button
        $(document).on('click', '.btn-delete', function() {
            const id = $(this).data('id');
            const name = $(this).data('name');
            confirmDelete(`{{ url('admin/roles') }}/${id}`, name, table);
        });
    });

    function initFormHandlers() {
        // Check all permissions in group
        $(document).off('change', '.check-all-group').on('change', '.check-all-group', function() {
            const group = $(this).data('group');
            $(`.perm-${group}`).prop('checked', this.checked);
            updateGroupProgress();
        });

        // Check all permissions
        $('#checkAllPermissions').off('change').on('change', function() {
            $('input[name="permissions[]"]').prop('checked', this.checked);
            $('.check-all-group').prop('checked', this.checked);
            updateGroupProgress();
        });

        // Update group checkbox when individual permissions change
        $(document).off('change', 'input[name="permissions[]"]').on('change', 'input[name="permissions[]"]', function() {
            updateGroupCheckboxes();
            updateGroupProgress();
        });

        // Initial state
        updateGroupCheckboxes();
        updateGroupProgress();

        // Form submit
        const form = $('#roleForm');
        const submitBtn = form.find('button[type="submit"]');
        
        form.off('submit').on('submit', function(e) {
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
                        $('#roleModal').modal('hide');
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: response.message,
                            timer: 1500,
                            showConfirmButton: false
                        });
                        table.ajax.reload();
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

    function updateGroupCheckboxes() {
        $('.check-all-group').each(function() {
            const group = $(this).data('group');
            const total = $(`.perm-${group}`).length;
            const checked = $(`.perm-${group}:checked`).length;
            $(this).prop('checked', total === checked && total > 0);
            $(this).prop('indeterminate', checked > 0 && checked < total);
        });

        const totalPerms = $('input[name="permissions[]"]').length;
        const checkedPerms = $('input[name="permissions[]"]:checked').length;
        $('#checkAllPermissions').prop('checked', totalPerms === checkedPerms && totalPerms > 0);
        $('#checkAllPermissions').prop('indeterminate', checkedPerms > 0 && checkedPerms < totalPerms);
    }

    function updateGroupProgress() {
        $('.permission-group-card').each(function() {
            const group = $(this).data('group');
            const total = $(this).find('input[name="permissions[]"]').length;
            const checked = $(this).find('input[name="permissions[]"]:checked').length;
            $(this).find('.group-progress').text(`${checked}/${total}`);
            
            const percentage = total > 0 ? (checked / total) * 100 : 0;
            $(this).find('.progress-bar').css('width', percentage + '%');
        });

        // Update total
        const totalPerms = $('input[name="permissions[]"]').length;
        const checkedPerms = $('input[name="permissions[]"]:checked').length;
        $('#totalSelected').text(checkedPerms);
    }
</script>
@endpush
