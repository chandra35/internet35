@extends('layouts.admin')

@section('title', 'Manajemen Router')
@section('page-title', 'Manajemen Router Mikrotik')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">Router</li>
@endsection

@section('content')
    <!-- Statistics Cards -->
    <div class="row">
        <div class="col-lg-3 col-md-6 col-sm-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $routers->count() }}</h3>
                    <p>Total Router</p>
                </div>
                <div class="icon"><i class="fas fa-server"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $routers->where('status', 'online')->count() }}</h3>
                    <p>Router Online</p>
                </div>
                <div class="icon"><i class="fas fa-check-circle"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ $routers->where('status', 'offline')->count() }}</h3>
                    <p>Router Offline</p>
                </div>
                <div class="icon"><i class="fas fa-times-circle"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ $routers->where('status', 'unknown')->count() }}</h3>
                    <p>Status Unknown</p>
                </div>
                <div class="icon"><i class="fas fa-question-circle"></i></div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-router mr-1"></i> Daftar Router
            </h3>
            <div class="card-tools">
                @can('routers.create')
                <button type="button" class="btn btn-primary btn-sm" id="btnCreate">
                    <i class="fas fa-plus mr-1"></i> Tambah Router
                </button>
                @endcan
            </div>
        </div>
        <div class="card-body">
            <!-- Filters -->
            <div class="row mb-3">
                <div class="col-md-3">
                    <select class="form-control select2" id="filterStatus">
                        <option value="">Semua Status</option>
                        <option value="online">Online</option>
                        <option value="offline">Offline</option>
                        <option value="unknown">Unknown</option>
                    </select>
                </div>
                @if(!auth()->user()->hasRole('admin-pop'))
                <div class="col-md-3">
                    <select class="form-control select2" id="filterPop">
                        <option value="">Semua POP</option>
                        @foreach($pops as $pop)
                            <option value="{{ $pop->name }}">{{ $pop->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="col-md-2">
                    <button type="button" class="btn btn-secondary btn-block" id="btnReset">
                        <i class="fas fa-undo"></i> Reset
                    </button>
                </div>
            </div>

            <!-- DataTable -->
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="routersTable">
                    <thead>
                        <tr>
                            <th width="50">Status</th>
                            <th>Nama</th>
                            <th>Host</th>
                            <th>Identity</th>
                            <th>ROS</th>
                            <th>Board</th>
                            @if(!auth()->user()->hasRole('admin-pop'))
                            <th>POP</th>
                            @endif
                            <th>Uptime</th>
                            <th width="150">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($routers as $router)
                        <tr data-id="{{ $router->id }}">
                            <td class="text-center">
                                @if($router->status === 'online')
                                    <span class="badge badge-success" title="Online"><i class="fas fa-circle"></i></span>
                                @elseif($router->status === 'offline')
                                    <span class="badge badge-danger" title="Offline"><i class="fas fa-circle"></i></span>
                                @else
                                    <span class="badge badge-secondary" title="Unknown"><i class="fas fa-circle"></i></span>
                                @endif
                            </td>
                            <td>
                                <strong>{{ $router->name }}</strong>
                                @if(!$router->is_active)
                                    <span class="badge badge-warning">Nonaktif</span>
                                @endif
                            </td>
                            <td>
                                <code>{{ $router->host }}:{{ $router->use_ssl ? $router->api_ssl_port : $router->api_port }}</code>
                                @if($router->use_ssl)
                                    <i class="fas fa-lock text-success ml-1" title="SSL"></i>
                                @endif
                            </td>
                            <td>{{ $router->identity ?? '-' }}</td>
                            <td>
                                @if($router->ros_version)
                                    <span class="badge badge-{{ $router->isRos7() ? 'primary' : 'info' }}">
                                        v{{ $router->ros_version }}
                                    </span>
                                @else
                                    -
                                @endif
                            </td>
                            <td>{{ $router->board_name ?? '-' }}</td>
                            @if(!auth()->user()->hasRole('admin-pop'))
                            <td>{{ $router->pop?->name ?? '-' }}</td>
                            @endif
                            <td>{{ $router->uptime ?? '-' }}</td>
                            <td>
                                @can('routers.manage')
                                <a href="{{ route('admin.routers.manage', $router) }}" class="btn btn-sm btn-success" title="Kelola">
                                    <i class="fas fa-cogs"></i>
                                </a>
                                @endcan
                                <button type="button" class="btn btn-sm btn-info btn-refresh" data-id="{{ $router->id }}" title="Refresh">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                                @can('routers.edit')
                                <button type="button" class="btn btn-sm btn-warning btn-edit" data-id="{{ $router->id }}" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                @endcan
                                @can('routers.delete')
                                <button type="button" class="btn btn-sm btn-danger btn-delete" data-id="{{ $router->id }}" data-name="{{ $router->name }}" title="Hapus">
                                    <i class="fas fa-trash"></i>
                                </button>
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
    <div class="modal fade" id="routerModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Router</h5>
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
        // Initialize DataTable
        table = $('#routersTable').DataTable({
            processing: true,
            order: [[1, 'asc']],
            columnDefs: [
                { orderable: false, targets: [-1] }
            ],
            language: dtLanguageID
        });

        // Filters
        $('#filterStatus').on('change', function() {
            var val = $(this).val();
            if (val === 'online') {
                table.column(0).search('success').draw();
            } else if (val === 'offline') {
                table.column(0).search('danger').draw();
            } else if (val === 'unknown') {
                table.column(0).search('secondary').draw();
            } else {
                table.column(0).search('').draw();
            }
        });

        @if(!auth()->user()->hasRole('admin-pop'))
        $('#filterPop').on('change', function() {
            var col = {{ auth()->user()->hasRole('admin-pop') ? 5 : 6 }};
            table.column(col).search($(this).val()).draw();
        });
        @endif

        $('#btnReset').on('click', function() {
            $('#filterStatus').val('');
            $('#filterPop').val('').trigger('change');
            table.search('').columns().search('').draw();
        });

        // Create button
        $('#btnCreate').on('click', function() {
            $('#routerModal .modal-title').text('Tambah Router');
            $('#modalContent').html('<div class="text-center py-5"><i class="fas fa-spinner fa-spin fa-2x"></i></div>');
            $('#routerModal').modal('show');
            
            $.get('{{ route('admin.routers.create') }}', function(response) {
                $('#modalContent').html(response.html);
                initFormHandlers();
            });
        });

        // Edit button
        $(document).on('click', '.btn-edit', function() {
            const id = $(this).data('id');
            $('#routerModal .modal-title').text('Edit Router');
            $('#modalContent').html('<div class="text-center py-5"><i class="fas fa-spinner fa-spin fa-2x"></i></div>');
            $('#routerModal').modal('show');
            
            $.get(`{{ url('admin/routers') }}/${id}/edit`, function(response) {
                $('#modalContent').html(response.html);
                initFormHandlers();
            });
        });

        // Delete button
        $(document).on('click', '.btn-delete', function() {
            const id = $(this).data('id');
            const name = $(this).data('name');
            
            Swal.fire({
                title: 'Hapus Router?',
                html: `Anda yakin ingin menghapus router <strong>${name}</strong>?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fas fa-trash"></i> Ya, Hapus',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `{{ url('admin/routers') }}/${id}`,
                        type: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        success: function(response) {
                            if (response.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Berhasil!',
                                    text: response.message,
                                    timer: 1500,
                                    showConfirmButton: false
                                }).then(() => location.reload());
                            }
                        },
                        error: function(xhr) {
                            Swal.fire('Error', xhr.responseJSON?.message || 'Terjadi kesalahan!', 'error');
                        }
                    });
                }
            });
        });

        // Refresh button
        $(document).on('click', '.btn-refresh', function() {
            const btn = $(this);
            const id = btn.data('id');
            
            btn.prop('disabled', true).find('i').addClass('fa-spin');
            
            $.get(`{{ url('admin/routers') }}/${id}/refresh`, function(response) {
                btn.prop('disabled', false).find('i').removeClass('fa-spin');
                
                if (response.success) {
                    toastr.success('Status router berhasil diperbarui');
                    location.reload();
                } else {
                    toastr.warning(response.message || 'Router offline');
                    location.reload();
                }
            }).fail(function() {
                btn.prop('disabled', false).find('i').removeClass('fa-spin');
                toastr.error('Gagal refresh status');
            });
        });
    });

    function initFormHandlers() {
        $('.select2').select2({
            theme: 'bootstrap-5',
            dropdownParent: $('#routerModal')
        });

        // Test connection
        $('#btnTestConnection').on('click', function() {
            const btn = $(this);
            const originalText = btn.html();
            
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Testing...');
            
            $.ajax({
                url: '{{ route('admin.routers.test-connection') }}',
                type: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                data: {
                    host: $('#host').val(),
                    username: $('#username').val(),
                    password: $('#password').val(),
                    api_port: $('#api_port').val(),
                    api_ssl_port: $('#api_ssl_port').val(),
                    use_ssl: $('#use_ssl').is(':checked') ? 1 : 0
                },
                success: function(response) {
                    btn.prop('disabled', false).html(originalText);
                    
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Koneksi Berhasil!',
                            html: `
                                <div class="text-left">
                                    <table class="table table-sm">
                                        <tr><th>Identity:</th><td>${response.data.identity || '-'}</td></tr>
                                        <tr><th>ROS Version:</th><td>${response.data.version || '-'}</td></tr>
                                        <tr><th>Board:</th><td>${response.data.board_name || '-'}</td></tr>
                                        <tr><th>Architecture:</th><td>${response.data.architecture || '-'}</td></tr>
                                        <tr><th>Uptime:</th><td>${response.data.uptime || '-'}</td></tr>
                                    </table>
                                </div>
                            `
                        });
                        
                        // Auto-fill name if empty
                        if (!$('#name').val() && response.data.identity) {
                            $('#name').val(response.data.identity);
                        }
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Koneksi Gagal',
                            text: response.message
                        });
                    }
                },
                error: function(xhr) {
                    btn.prop('disabled', false).html(originalText);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: xhr.responseJSON?.message || 'Terjadi kesalahan'
                    });
                }
            });
        });

        // Toggle SSL ports visibility
        $('#use_ssl').on('change', function() {
            if ($(this).is(':checked')) {
                $('#api_ssl_port').closest('.form-group').show();
            } else {
                $('#api_ssl_port').closest('.form-group').hide();
            }
        }).trigger('change');

        // Form submit
        $('#routerForm').on('submit', function(e) {
            e.preventDefault();
            
            const form = this;
            const formData = new FormData(form);
            const submitBtn = $(form).find('button[type="submit"]');
            const originalText = submitBtn.html();
            
            submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...');
            
            $.ajax({
                url: form.action,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        $('#routerModal').modal('hide');
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: response.message,
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => location.reload());
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
</script>
@endpush
