@extends('layouts.admin')

@section('title', 'Detail Paket')

@section('page-title', 'Detail Paket')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.packages.index') }}">Paket Internet</a></li>
    <li class="breadcrumb-item active">{{ $package->name }}</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8">
        <!-- Package Info Card -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-cube mr-2"></i>Informasi Paket
                </h3>
                <div class="card-tools">
                    {!! $package->sync_status_badge_html !!}
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless table-sm">
                            <tr>
                                <th style="width: 140px;">Nama Paket</th>
                                <td>{{ $package->name }}</td>
                            </tr>
                            <tr>
                                <th>Profile Mikrotik</th>
                                <td><code>{{ $package->mikrotik_profile_name }}</code></td>
                            </tr>
                            <tr>
                                <th>Router</th>
                                <td>
                                    <a href="{{ route('admin.routers.manage', $package->router) }}">
                                        {{ $package->router->name }}
                                        @if($package->router->identity)
                                            <small class="text-muted">({{ $package->router->identity }})</small>
                                        @endif
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                <th>Harga</th>
                                <td class="text-success font-weight-bold">{{ $package->formatted_price }}</td>
                            </tr>
                            <tr>
                                <th>Masa Aktif</th>
                                <td>{{ $package->validity_days }} hari</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless table-sm">
                            <tr>
                                <th style="width: 140px;">Download</th>
                                <td>{{ $package->formatted_download }}</td>
                            </tr>
                            <tr>
                                <th>Upload</th>
                                <td>{{ $package->formatted_upload }}</td>
                            </tr>
                            <tr>
                                <th>Rate Limit</th>
                                <td><code>{{ $package->rate_limit ?? '-' }}</code></td>
                            </tr>
                            <tr>
                                <th>Status</th>
                                <td>
                                    @if($package->is_active)
                                        <span class="badge badge-success">Aktif</span>
                                    @else
                                        <span class="badge badge-secondary">Nonaktif</span>
                                    @endif
                                    @if($package->is_public)
                                        <span class="badge badge-info">Publik</span>
                                    @else
                                        <span class="badge badge-warning">Private</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Mikrotik ID</th>
                                <td><code>{{ $package->mikrotik_profile_id ?? '-' }}</code></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- PPP Settings Card -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-network-wired mr-2"></i>Pengaturan PPP
                </h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless table-sm">
                            <tr>
                                <th style="width: 140px;">Local Address</th>
                                <td>{{ $package->local_address ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Remote Address</th>
                                <td>{{ $package->remote_address ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>DNS Server</th>
                                <td>{{ $package->dns_server ?? '-' }}</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless table-sm">
                            <tr>
                                <th style="width: 140px;">Parent Queue</th>
                                <td>{{ $package->parent_queue ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Address List</th>
                                <td>{{ $package->address_list ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Only One</th>
                                <td>
                                    @if($package->only_one)
                                        <span class="badge badge-success">Ya</span>
                                    @else
                                        <span class="badge badge-secondary">Tidak</span>
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Description Card -->
        @if($package->description)
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-file-alt mr-2"></i>Deskripsi
                </h3>
            </div>
            <div class="card-body">
                {!! nl2br(e($package->description)) !!}
            </div>
        </div>
        @endif

        <!-- Mikrotik Raw Data -->
        @if($package->mikrotik_data)
        <div class="card collapsed-card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-code mr-2"></i>Data Mikrotik (Raw)
                </h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <pre class="bg-dark text-light p-3 rounded" style="max-height: 300px; overflow: auto;">{{ json_encode($package->mikrotik_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
            </div>
        </div>
        @endif
    </div>

    <div class="col-md-4">
        <!-- Actions Card -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-cog mr-2"></i>Aksi
                </h3>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    @can('packages.edit')
                    <button type="button" class="btn btn-primary btn-block mb-2" onclick="editPackage('{{ $package->id }}')">
                        <i class="fas fa-edit mr-2"></i>Edit Paket
                    </button>
                    @endcan

                    @can('packages.sync')
                    <button type="button" class="btn btn-success btn-block mb-2" onclick="syncPackage('{{ $package->id }}')" id="btnSync">
                        <i class="fas fa-sync mr-2"></i>Sync ke Mikrotik
                    </button>
                    @endcan

                    @can('packages.delete')
                    <button type="button" class="btn btn-danger btn-block" onclick="deletePackage('{{ $package->id }}')">
                        <i class="fas fa-trash mr-2"></i>Hapus Paket
                    </button>
                    @endcan
                </div>
            </div>
        </div>

        <!-- Sync Status Card -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-sync-alt mr-2"></i>Status Sinkronisasi
                </h3>
            </div>
            <div class="card-body">
                <table class="table table-borderless table-sm">
                    <tr>
                        <th>Status</th>
                        <td>{!! $package->sync_status_badge_html !!}</td>
                    </tr>
                    <tr>
                        <th>Terakhir Sync</th>
                        <td>
                            @if($package->last_synced_at)
                                {{ $package->last_synced_at->format('d M Y H:i') }}
                                <br>
                                <small class="text-muted">{{ $package->last_synced_at->diffForHumans() }}</small>
                            @else
                                <span class="text-muted">Belum pernah sync</span>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Timestamps Card -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-clock mr-2"></i>Timestamps
                </h3>
            </div>
            <div class="card-body">
                <table class="table table-borderless table-sm">
                    <tr>
                        <th>Dibuat</th>
                        <td>
                            {{ $package->created_at->format('d M Y H:i') }}
                            @if($package->creator)
                                <br><small class="text-muted">oleh {{ $package->creator->name }}</small>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Diupdate</th>
                        <td>{{ $package->updated_at->format('d M Y H:i') }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="formModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Paket</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="formModalBody">
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
function editPackage(id) {
    $('#formModalBody').html('<div class="text-center py-5"><i class="fas fa-spinner fa-spin fa-2x"></i></div>');
    $('#formModal').modal('show');

    $.get(`{{ url('admin/packages') }}/${id}/edit`, function(response) {
        if (response.success) {
            $('#formModalBody').html(response.html);
            initForm();
        }
    });
}

function syncPackage(id) {
    Swal.fire({
        title: 'Sync ke Mikrotik?',
        text: 'Profile akan di-push/update ke router Mikrotik',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, Sync!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            $('#btnSync').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Syncing...');
            
            $.post(`{{ url('admin/packages') }}/${id}/sync`, {_token: '{{ csrf_token() }}'}, function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    setTimeout(() => location.reload(), 1000);
                } else {
                    toastr.error(response.message);
                    $('#btnSync').prop('disabled', false).html('<i class="fas fa-sync mr-2"></i>Sync ke Mikrotik');
                }
            }).fail(function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Terjadi kesalahan!');
                $('#btnSync').prop('disabled', false).html('<i class="fas fa-sync mr-2"></i>Sync ke Mikrotik');
            });
        }
    });
}

function deletePackage(id) {
    Swal.fire({
        title: 'Hapus Paket?',
        text: 'Data paket akan dihapus permanen dari aplikasi. Profile di Mikrotik tidak akan dihapus.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: `{{ url('admin/packages') }}/${id}`,
                type: 'DELETE',
                data: {_token: '{{ csrf_token() }}'},
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        setTimeout(() => {
                            window.location.href = '{{ route("admin.packages.index") }}';
                        }, 1000);
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
}

function initForm() {
    $('.select2').select2({
        theme: 'bootstrap-5',
        dropdownParent: $('#formModal')
    });
}

function submitForm() {
    const form = $('#packageForm');
    const formData = form.serialize();
    const url = form.attr('action');
    const method = form.find('input[name="_method"]').val() || 'POST';

    $.ajax({
        url: url,
        type: method === 'PUT' ? 'POST' : method,
        data: formData,
        success: function(response) {
            if (response.success) {
                $('#formModal').modal('hide');
                toastr.success(response.message);
                setTimeout(() => location.reload(), 1000);
            } else {
                toastr.error(response.message);
            }
        },
        error: function(xhr) {
            if (xhr.status === 422) {
                const errors = xhr.responseJSON.errors;
                Object.keys(errors).forEach(field => {
                    const input = $(`[name="${field}"]`);
                    input.addClass('is-invalid');
                    input.siblings('.invalid-feedback').text(errors[field][0]);
                });
            } else {
                toastr.error(xhr.responseJSON?.message || 'Terjadi kesalahan!');
            }
        }
    });
}
</script>
@endpush
