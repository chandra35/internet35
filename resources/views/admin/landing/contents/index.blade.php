@extends('layouts.admin')

@section('title', 'Konten Landing')
@section('page-title', 'Kelola Konten Landing')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item">Landing Page</li>
    <li class="breadcrumb-item active">Konten</li>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-file-alt mr-1"></i> Konten Teks Landing Page
            </h3>
            <div class="card-tools">
                @can('landing.contents.create')
                <button type="button" class="btn btn-primary btn-sm" id="btnCreate">
                    <i class="fas fa-plus mr-1"></i> Tambah Konten
                </button>
                @endcan
            </div>
        </div>
        <div class="card-body">
            @foreach($groupedContents as $section => $items)
            <div class="card card-outline card-primary mb-3">
                <div class="card-header">
                    <h3 class="card-title text-uppercase">
                        <i class="fas fa-folder mr-1"></i> {{ $section }}
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th width="100">Key</th>
                                <th>Judul</th>
                                <th>Subtitle</th>
                                <th>Konten</th>
                                <th width="80">Status</th>
                                <th width="120">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($items as $content)
                            <tr>
                                <td><code>{{ $content->key }}</code></td>
                                <td>{{ Str::limit($content->title, 40) }}</td>
                                <td>{{ Str::limit($content->subtitle, 40) }}</td>
                                <td>
                                    @if($content->image)
                                        <img src="{{ asset('storage/contents/' . $content->image) }}" style="max-height:30px;">
                                    @endif
                                    {{ Str::limit(strip_tags($content->content), 50) }}
                                </td>
                                <td>
                                    @if($content->is_active)
                                        <span class="badge badge-success">Aktif</span>
                                    @else
                                        <span class="badge badge-danger">Nonaktif</span>
                                    @endif
                                </td>
                                <td>
                                    @can('landing.contents.edit')
                                        <button type="button" class="btn btn-sm btn-info btn-edit" data-id="{{ $content->id }}" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    @endcan
                                    @can('landing.contents.delete')
                                        <button type="button" class="btn btn-sm btn-danger btn-delete" data-id="{{ $content->id }}" data-name="{{ $content->title ?? $content->key }}" title="Hapus">
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
            @endforeach

            @if($contents->isEmpty())
            <div class="text-center py-5 text-muted">
                <i class="fas fa-inbox fa-3x mb-3"></i>
                <p>Belum ada konten. Klik tombol "Tambah Konten" untuk memulai.</p>
            </div>
            @endif
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="formModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Konten</h5>
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
        $('#btnCreate').on('click', function() {
            $('#formModal .modal-title').text('Tambah Konten');
            $('#modalContent').html('<div class="text-center py-5"><i class="fas fa-spinner fa-spin fa-2x"></i></div>');
            $('#formModal').modal('show');
            
            $.get('{{ route('admin.landing.contents.create') }}', function(response) {
                $('#modalContent').html(response.html);
                initFormHandlers();
            });
        });

        $(document).on('click', '.btn-edit', function() {
            const id = $(this).data('id');
            $('#formModal .modal-title').text('Edit Konten');
            $('#modalContent').html('<div class="text-center py-5"><i class="fas fa-spinner fa-spin fa-2x"></i></div>');
            $('#formModal').modal('show');
            
            $.get(`{{ url('admin/landing/contents') }}/${id}/edit`, function(response) {
                $('#modalContent').html(response.html);
                initFormHandlers();
            });
        });

        $(document).on('click', '.btn-delete', function() {
            const id = $(this).data('id');
            const name = $(this).data('name');
            
            Swal.fire({
                title: 'Hapus Konten?',
                html: `Apakah Anda yakin ingin menghapus <strong>${name}</strong>?`,
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
                        url: `{{ url('admin/landing/contents') }}/${id}`,
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
        $('#contentForm').on('submit', function(e) {
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

        bsCustomFileInput.init();
    }
</script>
@endpush
                        });
                    } else {
                        toastr.error('Terjadi kesalahan');
                    }
                }
            });
        });

        bsCustomFileInput.init();
    }
</script>
@endpush
