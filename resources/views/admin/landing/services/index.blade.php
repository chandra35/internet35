@extends('layouts.admin')

@section('title', 'Layanan')
@section('page-title', 'Kelola Layanan')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item">Landing Page</li>
    <li class="breadcrumb-item active">Layanan</li>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-concierge-bell mr-1"></i> Daftar Layanan
            </h3>
            <div class="card-tools">
                @can('landing.services.create')
                <button type="button" class="btn btn-primary btn-sm" id="btnCreate">
                    <i class="fas fa-plus mr-1"></i> Tambah Layanan
                </button>
                @endcan
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="servicesTable">
                    <thead>
                        <tr>
                            <th width="60">Order</th>
                            <th width="60">Icon</th>
                            <th>Judul</th>
                            <th>Deskripsi</th>
                            <th width="80">Status</th>
                            <th width="120">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($services as $service)
                        <tr data-id="{{ $service->id }}">
                            <td class="text-center">{{ $service->order }}</td>
                            <td class="text-center">
                                @if($service->icon)
                                    <i class="{{ $service->icon }} fa-2x text-primary"></i>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>{{ $service->title }}</td>
                            <td>{{ Str::limit($service->description, 80) }}</td>
                            <td>
                                @if($service->is_active)
                                    <span class="badge badge-success">Aktif</span>
                                @else
                                    <span class="badge badge-danger">Nonaktif</span>
                                @endif
                            </td>
                            <td>
                                @can('landing.services.edit')
                                    <button type="button" class="btn btn-sm btn-info btn-edit" data-id="{{ $service->id }}" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                @endcan
                                @can('landing.services.delete')
                                    <button type="button" class="btn btn-sm btn-danger btn-delete" data-id="{{ $service->id }}" data-name="{{ $service->title }}" title="Hapus">
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
                    <h5 class="modal-title">Layanan</h5>
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
        $('#servicesTable').DataTable({
            order: [[0, 'asc']],
            columnDefs: [
                { orderable: false, targets: [1, 5] }
            ],
            language: dtLanguageID
        });

        $('#btnCreate').on('click', function() {
            $('#formModal .modal-title').text('Tambah Layanan');
            $('#modalContent').html('<div class="text-center py-5"><i class="fas fa-spinner fa-spin fa-2x"></i></div>');
            $('#formModal').modal('show');
            
            $.get('{{ route('admin.landing.services.create') }}', function(response) {
                $('#modalContent').html(response.html);
                initFormHandlers();
            });
        });

        $(document).on('click', '.btn-edit', function() {
            const id = $(this).data('id');
            $('#formModal .modal-title').text('Edit Layanan');
            $('#modalContent').html('<div class="text-center py-5"><i class="fas fa-spinner fa-spin fa-2x"></i></div>');
            $('#formModal').modal('show');
            
            $.get(`{{ url('admin/landing/services') }}/${id}/edit`, function(response) {
                $('#modalContent').html(response.html);
                initFormHandlers();
            });
        });

        $(document).on('click', '.btn-delete', function() {
            const id = $(this).data('id');
            const name = $(this).data('name');
            
            Swal.fire({
                title: 'Hapus Layanan?',
                html: `Apakah Anda yakin ingin menghapus layanan <strong>${name}</strong>?`,
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
                        url: `{{ url('admin/landing/services') }}/${id}`,
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
        $('#serviceForm').on('submit', function(e) {
            e.preventDefault();
            
            const form = $(this);
            const formData = new FormData(this);
            const url = form.attr('action');
            const submitBtn = form.find('button[type="submit"]');
            const originalText = submitBtn.html();
            
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
                        Swal.fire({ icon: 'error', title: 'Validasi Gagal', html: errorMsg });
                    } else {
                        Swal.fire('Error!', 'Terjadi kesalahan saat menyimpan data', 'error');
                    }
                }
            });
        });

        // Icon preview
        $('#icon').on('input', function() {
            const icon = $(this).val();
            $('#iconPreview').html(`<i class="${icon} fa-3x"></i>`);
        });
    }
</script>
@endpush
