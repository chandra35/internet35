@extends('layouts.admin')

@section('title', 'FAQ')
@section('page-title', 'Kelola FAQ')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item">Landing Page</li>
    <li class="breadcrumb-item active">FAQ</li>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-question-circle mr-1"></i> Daftar FAQ
            </h3>
            <div class="card-tools">
                @can('landing.faqs.create')
                <button type="button" class="btn btn-primary btn-sm" id="btnCreate">
                    <i class="fas fa-plus mr-1"></i> Tambah FAQ
                </button>
                @endcan
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="faqsTable">
                    <thead>
                        <tr>
                            <th width="60">Order</th>
                            <th>Pertanyaan</th>
                            <th>Jawaban</th>
                            <th width="100">Kategori</th>
                            <th width="80">Status</th>
                            <th width="120">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($faqs as $faq)
                        <tr data-id="{{ $faq->id }}">
                            <td class="text-center">{{ $faq->order }}</td>
                            <td>{{ Str::limit($faq->question, 60) }}</td>
                            <td>{{ Str::limit($faq->answer, 80) }}</td>
                            <td>{{ $faq->category ?? '-' }}</td>
                            <td>
                                @if($faq->is_active)
                                    <span class="badge badge-success">Aktif</span>
                                @else
                                    <span class="badge badge-danger">Nonaktif</span>
                                @endif
                            </td>
                            <td>
                                @can('landing.faqs.edit')
                                    <button type="button" class="btn btn-sm btn-info btn-edit" data-id="{{ $faq->id }}" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                @endcan
                                @can('landing.faqs.delete')
                                    <button type="button" class="btn btn-sm btn-danger btn-delete" data-id="{{ $faq->id }}" data-name="{{ Str::limit($faq->question, 30) }}" title="Hapus">
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
                    <h5 class="modal-title">FAQ</h5>
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
        $('#faqsTable').DataTable({
            order: [[0, 'asc']],
            columnDefs: [
                { orderable: false, targets: [5] }
            ],
            language: dtLanguageID
        });

        $('#btnCreate').on('click', function() {
            $('#formModal .modal-title').text('Tambah FAQ');
            $('#modalContent').html('<div class="text-center py-5"><i class="fas fa-spinner fa-spin fa-2x"></i></div>');
            $('#formModal').modal('show');
            
            $.get('{{ route('admin.landing.faqs.create') }}', function(response) {
                $('#modalContent').html(response.html);
                initFormHandlers();
            });
        });

        $(document).on('click', '.btn-edit', function() {
            const id = $(this).data('id');
            $('#formModal .modal-title').text('Edit FAQ');
            $('#modalContent').html('<div class="text-center py-5"><i class="fas fa-spinner fa-spin fa-2x"></i></div>');
            $('#formModal').modal('show');
            
            $.get(`{{ url('admin/landing/faqs') }}/${id}/edit`, function(response) {
                $('#modalContent').html(response.html);
                initFormHandlers();
            });
        });

        $(document).on('click', '.btn-delete', function() {
            const id = $(this).data('id');
            const name = $(this).data('name');
            
            Swal.fire({
                title: 'Hapus FAQ?',
                html: `Apakah Anda yakin ingin menghapus FAQ ini?`,
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
                        url: `{{ url('admin/landing/faqs') }}/${id}`,
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
        $('#faqForm').on('submit', function(e) {
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
    }
</script>
@endpush
                        Object.keys(errors).forEach(key => {
                            toastr.error(errors[key][0]);
                        });
                    } else {
                        toastr.error('Terjadi kesalahan');
                    }
                }
            });
        });
    }
</script>
@endpush
