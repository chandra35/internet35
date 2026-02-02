@extends('layouts.admin')

@section('title', 'Users')
@section('page-title', 'Users Management')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">Users</li>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-users mr-1"></i> Daftar Users
            </h3>
            <div class="card-tools">
                @can('users.create')
                <button type="button" class="btn btn-primary btn-sm" id="btnCreate">
                    <i class="fas fa-plus mr-1"></i> Tambah User
                </button>
                @endcan
            </div>
        </div>
        <div class="card-body">
            <!-- Filters -->
            <div class="row mb-3">
                <div class="col-md-3">
                    <select class="form-control select2" id="filterRole">
                        <option value="">Semua Role</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->name }}">{{ ucfirst($role->name) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select class="form-control select2" id="filterStatus">
                        <option value="">Semua Status</option>
                        <option value="1">Aktif</option>
                        <option value="0">Nonaktif</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-secondary btn-block" id="btnReset">
                        <i class="fas fa-undo"></i> Reset
                    </button>
                </div>
            </div>

            <!-- DataTable -->
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="usersTable">
                    <thead>
                        <tr>
                            <th width="200">Nama</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Roles</th>
                            <th width="80">Status</th>
                            <th width="150">Dibuat</th>
                            <th width="120">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $user)
                        <tr data-id="{{ $user->id }}">
                            <td>
                                <div class="d-flex align-items-center">
                                    <img src="{{ $user->avatar_url }}" class="img-circle mr-2" style="width:35px;height:35px;object-fit:cover;">
                                    <span>{{ $user->name }}</span>
                                </div>
                            </td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->phone ?? '-' }}</td>
                            <td>
                                @foreach($user->roles as $role)
                                    @php
                                        $colors = ['superadmin' => 'danger', 'admin' => 'primary', 'admin-pop' => 'info', 'client' => 'success'];
                                        $color = $colors[$role->name] ?? 'secondary';
                                    @endphp
                                    <span class="badge badge-{{ $color }}">{{ $role->name }}</span>
                                @endforeach
                            </td>
                            <td>
                                @if($user->is_active)
                                    <span class="badge badge-success">Aktif</span>
                                @else
                                    <span class="badge badge-danger">Nonaktif</span>
                                @endif
                            </td>
                            <td>{{ $user->created_at->format('d/m/Y H:i') }}</td>
                            <td>
                                @if(auth()->user()->hasRole('superadmin'))
                                    <button type="button" class="btn btn-sm btn-warning btn-password" data-id="{{ $user->id }}" data-name="{{ $user->name }}" title="Lihat Password">
                                        <i class="fas fa-key"></i>
                                    </button>
                                @endif
                                @can('users.edit')
                                    <button type="button" class="btn btn-sm btn-info btn-edit" data-id="{{ $user->id }}" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                @endcan
                                @can('users.delete')
                                    @if($user->id !== auth()->id() && !$user->hasRole('superadmin'))
                                        <button type="button" class="btn btn-sm btn-danger btn-delete" data-id="{{ $user->id }}" data-name="{{ $user->name }}" title="Hapus">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    @endif
                                @endcan
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="userModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">User</h5>
                    <button type="button" class="close" data-dismiss="modal">
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
<script>
    let table;

    $(document).ready(function() {
        // Initialize DataTable (client-side)
        table = $('#usersTable').DataTable({
            processing: true,
            order: [[5, 'desc']],
            columnDefs: [
                { orderable: false, searchable: false, targets: [6] }
            ],
            language: dtLanguageID
        });

        // Check URL parameters for initial filter
        const urlParams = new URLSearchParams(window.location.search);
        const statusParam = urlParams.get('status');
        if (statusParam) {
            if (statusParam === 'active' || statusParam === '1') {
                $('#filterStatus').val('1');
                table.column(4).search('Aktif').draw();
            } else if (statusParam === 'inactive' || statusParam === '0') {
                $('#filterStatus').val('0');
                table.column(4).search('Nonaktif').draw();
            }
            // Clean URL without reloading
            window.history.replaceState({}, document.title, window.location.pathname);
        }

        // Filter handlers - client side filtering
        $('#filterRole').on('change', function() {
            var val = $(this).val();
            table.column(3).search(val).draw();
        });

        $('#filterStatus').on('change', function() {
            var val = $(this).val();
            if (val === '1') {
                table.column(4).search('Aktif').draw();
            } else if (val === '0') {
                table.column(4).search('Nonaktif').draw();
            } else {
                table.column(4).search('').draw();
            }
        });

        $('#btnReset').on('click', function() {
            $('#filterRole').val('').trigger('change');
            $('#filterStatus').val('');
            table.search('').columns().search('').draw();
        });

        // Create button
        $('#btnCreate').on('click', function() {
            $('#userModal .modal-title').text('Tambah User');
            $('#modalContent').html('<div class="text-center py-5"><i class="fas fa-spinner fa-spin fa-2x"></i></div>');
            $('#userModal').modal('show');
            
            $.get('{{ route('admin.users.create') }}', function(response) {
                $('#modalContent').html(response.html);
                initFormHandlers();
            });
        });

        // Edit button (delegated)
        $(document).on('click', '.btn-edit', function() {
            const id = $(this).data('id');
            $('#userModal .modal-title').text('Edit User');
            $('#modalContent').html('<div class="text-center py-5"><i class="fas fa-spinner fa-spin fa-2x"></i></div>');
            $('#userModal').modal('show');
            
            $.get(`{{ url('admin/users') }}/${id}/edit`, function(response) {
                $('#modalContent').html(response.html);
                initFormHandlers();
            });
        });

        // Delete button (delegated)
        $(document).on('click', '.btn-delete', function() {
            const id = $(this).data('id');
            const name = $(this).data('name');
            confirmDelete(`{{ url('admin/users') }}/${id}`, name, function() {
                // Reload page instead of ajax reload for client-side DataTable
                location.reload();
            });
        });

        // View password button (delegated)
        $(document).on('click', '.btn-password', function() {
            const id = $(this).data('id');
            const name = $(this).data('name');
            
            Swal.fire({
                title: 'Lihat Password?',
                html: `Anda akan melihat password untuk <strong>${name}</strong>.<br><br><small class="text-muted">Tindakan ini akan dicatat di activity log.</small>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ffc107',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fas fa-key"></i> Ya, Tampilkan',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.get(`{{ url('admin/users') }}/${id}/password`, function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: 'Password ' + name,
                                html: `<div class="input-group">
                                    <input type="text" class="form-control text-center font-weight-bold" value="${response.password}" id="passwordDisplay" readonly style="font-size:1.2rem;">
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-primary" type="button" onclick="copyPassword()">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>
                                </div>
                                <small class="text-muted mt-2 d-block">Klik icon untuk copy password</small>`,
                                icon: 'info',
                                confirmButtonText: 'Tutup',
                                didOpen: () => {
                                    window.copyPassword = function() {
                                        const input = document.getElementById('passwordDisplay');
                                        input.select();
                                        document.execCommand('copy');
                                        toastr.success('Password berhasil dicopy!');
                                    }
                                }
                            });
                        } else {
                            Swal.fire('Gagal', response.message, 'error');
                        }
                    }).fail(function(xhr) {
                        Swal.fire('Gagal', xhr.responseJSON?.message || 'Terjadi kesalahan!', 'error');
                    });
                }
            });
        });
    });

    function initFormHandlers() {
        $('.select2').select2({
            theme: 'bootstrap-5',
            dropdownParent: $('#userModal')
        });

        $('#userForm').on('submit', function(e) {
            e.preventDefault();
            
            const form = this;
            const formData = new FormData(form);
            const submitBtn = $(form).find('button[type="submit"]');
            const originalText = submitBtn.html();
            
            // Disable button and show loading
            submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...');
            
            $.ajax({
                url: form.action,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        $('#userModal').modal('hide');
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: response.message,
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        submitBtn.prop('disabled', false).html(originalText);
                        Swal.fire('Gagal', response.message, 'error');
                    }
                },
                error: function(xhr) {
                    submitBtn.prop('disabled', false).html(originalText);
                    if (xhr.status === 422) {
                        const errors = xhr.responseJSON.errors;
                        let errorMessages = '';
                        for (const key in errors) {
                            errorMessages += errors[key].join('<br>') + '<br>';
                        }
                        Swal.fire({
                            icon: 'error',
                            title: 'Validasi Gagal',
                            html: errorMessages
                        });
                    } else {
                        Swal.fire('Error', xhr.responseJSON?.message || 'Terjadi kesalahan!', 'error');
                    }
                }
            });
        });
    }

    // Auto-open create modal if ?add=pop
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('add') === 'pop') {
        setTimeout(function() {
            $('#btnCreate').trigger('click');
            // Clean URL
            history.replaceState({}, document.title, window.location.pathname);
        }, 500);
    }
</script>
@endpush
