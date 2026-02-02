@extends('layouts.admin')

@section('title', 'Testimoni')
@section('page-title', 'Kelola Testimoni')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item">Landing Page</li>
    <li class="breadcrumb-item active">Testimoni</li>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-quote-left mr-1"></i> Daftar Testimoni
            </h3>
            <div class="card-tools">
                @can('landing.testimonials.create')
                <button type="button" class="btn btn-primary btn-sm" id="btnCreate">
                    <i class="fas fa-plus mr-1"></i> Tambah Testimoni
                </button>
                @endcan
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="testimonialsTable">
                    <thead>
                        <tr>
                            <th width="60">Order</th>
                            <th width="60">Foto</th>
                            <th>Nama</th>
                            <th>Testimoni</th>
                            <th width="100">Rating</th>
                            <th width="80">Status</th>
                            <th width="120">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($testimonials as $testimonial)
                        <tr data-id="{{ $testimonial->id }}">
                            <td class="text-center">{{ $testimonial->order }}</td>
                            <td>
                                @if($testimonial->image)
                                    <img src="{{ asset('storage/testimonials/' . $testimonial->image) }}" class="img-circle" style="width:40px;height:40px;object-fit:cover;">
                                @else
                                    <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center" style="width:40px;height:40px;">
                                        <i class="fas fa-user text-white"></i>
                                    </div>
                                @endif
                            </td>
                            <td>
                                <strong>{{ $testimonial->name }}</strong>
                                @if($testimonial->position || $testimonial->company)
                                    <br><small class="text-muted">{{ $testimonial->position }} {{ $testimonial->company ? '- ' . $testimonial->company : '' }}</small>
                                @endif
                            </td>
                            <td>{{ Str::limit($testimonial->content, 80) }}</td>
                            <td>
                                @for($i = 1; $i <= 5; $i++)
                                    <i class="fas fa-star {{ $i <= $testimonial->rating ? 'text-warning' : 'text-muted' }}"></i>
                                @endfor
                            </td>
                            <td>
                                @if($testimonial->is_active)
                                    <span class="badge badge-success">Aktif</span>
                                @else
                                    <span class="badge badge-danger">Nonaktif</span>
                                @endif
                            </td>
                            <td>
                                @can('landing.testimonials.edit')
                                    <button type="button" class="btn btn-sm btn-info btn-edit" data-id="{{ $testimonial->id }}" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                @endcan
                                @can('landing.testimonials.delete')
                                    <button type="button" class="btn btn-sm btn-danger btn-delete" data-id="{{ $testimonial->id }}" data-name="{{ $testimonial->name }}" title="Hapus">
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
                    <h5 class="modal-title">Testimoni</h5>
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
        $('#testimonialsTable').DataTable({
            order: [[0, 'asc']],
            columnDefs: [
                { orderable: false, targets: [1, 6] }
            ],
            language: dtLanguageID
        });

        $('#btnCreate').on('click', function() {
            $('#formModal .modal-title').text('Tambah Testimoni');
            $('#modalContent').html('<div class="text-center py-5"><i class="fas fa-spinner fa-spin fa-2x"></i></div>');
            $('#formModal').modal('show');
            
            $.get('{{ route('admin.landing.testimonials.create') }}', function(response) {
                $('#modalContent').html(response.html);
                initFormHandlers();
            });
        });

        $(document).on('click', '.btn-edit', function() {
            const id = $(this).data('id');
            $('#formModal .modal-title').text('Edit Testimoni');
            $('#modalContent').html('<div class="text-center py-5"><i class="fas fa-spinner fa-spin fa-2x"></i></div>');
            $('#formModal').modal('show');
            
            $.get(`{{ url('admin/landing/testimonials') }}/${id}/edit`, function(response) {
                $('#modalContent').html(response.html);
                initFormHandlers();
            });
        });

        $(document).on('click', '.btn-delete', function() {
            const id = $(this).data('id');
            const name = $(this).data('name');
            
            Swal.fire({
                title: 'Hapus Testimoni?',
                html: `Apakah Anda yakin ingin menghapus testimoni dari <strong>${name}</strong>?`,
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
                        url: `{{ url('admin/landing/testimonials') }}/${id}`,
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
        $('#testimonialForm').on('submit', function(e) {
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

        // Image preview
        $('input[type="file"]').on('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    $('#imagePreview').attr('src', e.target.result).show();
                };
                reader.readAsDataURL(file);
            }
        });

        bsCustomFileInput.init();
    }
</script>
@endpush
