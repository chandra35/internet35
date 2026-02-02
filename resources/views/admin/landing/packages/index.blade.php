@extends('layouts.admin')

@section('title', 'Paket Internet')
@section('page-title', 'Kelola Paket Internet')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item">Landing Page</li>
    <li class="breadcrumb-item active">Paket</li>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-box mr-1"></i> Daftar Paket Internet
            </h3>
            <div class="card-tools">
                @can('landing.packages.create')
                <button type="button" class="btn btn-primary btn-sm" id="btnCreate">
                    <i class="fas fa-plus mr-1"></i> Tambah Paket
                </button>
                @endcan
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="packagesTable">
                    <thead>
                        <tr>
                            <th width="60">Order</th>
                            <th>Nama Paket</th>
                            <th>Harga</th>
                            <th>Kecepatan</th>
                            <th width="80">Popular</th>
                            <th width="80">Status</th>
                            <th width="120">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($packages as $package)
                        <tr data-id="{{ $package->id }}">
                            <td class="text-center">{{ $package->order }}</td>
                            <td>{{ $package->name }}</td>
                            <td>Rp {{ number_format($package->price, 0, ',', '.') }}/{{ $package->period }}</td>
                            <td>{{ $package->speed }} Mbps</td>
                            <td class="text-center">
                                @if($package->is_popular)
                                    <span class="badge badge-warning"><i class="fas fa-star"></i></span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($package->is_active)
                                    <span class="badge badge-success">Aktif</span>
                                @else
                                    <span class="badge badge-danger">Nonaktif</span>
                                @endif
                            </td>
                            <td>
                                @can('landing.packages.edit')
                                    <button type="button" class="btn btn-sm btn-info btn-edit" data-id="{{ $package->id }}" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                @endcan
                                @can('landing.packages.delete')
                                    <button type="button" class="btn btn-sm btn-danger btn-delete" data-id="{{ $package->id }}" data-name="{{ $package->name }}" title="Hapus">
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
    <div class="modal fade" id="formModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Paket</h5>
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
    $(document).ready(function() {
        $('#packagesTable').DataTable({
            order: [[0, 'asc']],
            columnDefs: [
                { orderable: false, targets: [6] }
            ],
            language: dtLanguageID
        });

        $('#btnCreate').on('click', function() {
            $('#formModal .modal-title').text('Tambah Paket');
            $('#modalContent').html('<div class="text-center py-5"><i class="fas fa-spinner fa-spin fa-2x"></i></div>');
            $('#formModal').modal('show');
            
            $.get('{{ route('admin.landing.packages.create') }}', function(response) {
                $('#modalContent').html(response.html);
                initFormHandlers();
            });
        });

        $(document).on('click', '.btn-edit', function() {
            const id = $(this).data('id');
            $('#formModal .modal-title').text('Edit Paket');
            $('#modalContent').html('<div class="text-center py-5"><i class="fas fa-spinner fa-spin fa-2x"></i></div>');
            $('#formModal').modal('show');
            
            $.get(`{{ url('admin/landing/packages') }}/${id}/edit`, function(response) {
                $('#modalContent').html(response.html);
                initFormHandlers();
            });
        });

        $(document).on('click', '.btn-delete', function() {
            const id = $(this).data('id');
            const name = $(this).data('name');
            
            Swal.fire({
                title: 'Hapus Paket?',
                html: `Apakah Anda yakin ingin menghapus paket <strong>${name}</strong>?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Menghapus...',
                        text: 'Mohon tunggu',
                        allowOutsideClick: false,
                        didOpen: () => { Swal.showLoading(); }
                    });
                    $.ajax({
                        url: `{{ url('admin/landing/packages') }}/${id}`,
                        type: 'DELETE',
                        success: function(response) {
                            if (response.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Terhapus!',
                                    text: response.message,
                                    timer: 1500,
                                    showConfirmButton: false
                                }).then(() => location.reload());
                            }
                        },
                        error: function() {
                            Swal.fire('Error!', 'Gagal menghapus data', 'error');
                        }
                    });
                }
            });
        });
    });

    function initFormHandlers() {
        $('#packageForm').on('submit', function(e) {
            e.preventDefault();
            
            const form = $(this);
            const formData = new FormData(this);
            const url = form.attr('action');
            const submitBtn = form.find('button[type="submit"]');
            const originalText = submitBtn.html();
            
            // Disable button and show loading
            submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...');
            
            $.ajax({
                url: url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: response.message,
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            $('#formModal').modal('hide');
                            location.reload();
                        });
                    }
                },
                error: function(xhr) {
                    submitBtn.prop('disabled', false).html(originalText);
                    if (xhr.status === 422) {
                        const errors = xhr.responseJSON.errors;
                        let errorMsg = '';
                        Object.keys(errors).forEach(key => {
                            errorMsg += errors[key][0] + '<br>';
                        });
                        Swal.fire({
                            icon: 'error',
                            title: 'Validasi Gagal',
                            html: errorMsg
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: 'Terjadi kesalahan saat menyimpan data'
                        });
                    }
                }
            });
        });

        // Add feature
        $(document).on('click', '#addFeature', function() {
            const container = $('#featuresContainer');
            const index = container.find('.feature-item').length;
            container.append(`
                <div class="input-group mb-2 feature-item">
                    <input type="text" class="form-control" name="features[]" placeholder="Fitur baru...">
                    <div class="input-group-append">
                        <button type="button" class="btn btn-danger btn-remove-feature"><i class="fas fa-times"></i></button>
                    </div>
                </div>
            `);
        });

        // Remove feature
        $(document).on('click', '.btn-remove-feature', function() {
            $(this).closest('.feature-item').remove();
        });
    }
</script>
@endpush
