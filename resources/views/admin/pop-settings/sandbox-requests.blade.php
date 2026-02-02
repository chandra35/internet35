@extends('layouts.admin')

@section('title', 'Review Sandbox Request')

@section('page-title', 'Review Sandbox Request')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.payment-gateways.index') }}">Payment Gateway</a></li>
    <li class="breadcrumb-item active">Review Sandbox</li>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-3">
        @include('admin.pop-settings.partials.sidebar')
    </div>
    <div class="col-lg-9">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-clipboard-check mr-2"></i>Daftar Request Verifikasi Sandbox</h3>
            </div>
            <div class="card-body p-0">
                @if($pendingRequests->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Admin POP</th>
                                <th>Gateway</th>
                                <th>Status</th>
                                <th>Tanggal Request</th>
                                <th class="text-center" width="150">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pendingRequests as $gateway)
                            <tr>
                                <td>
                                    <strong>{{ $gateway->user->name }}</strong>
                                    <br><small class="text-muted">{{ $gateway->user->email }}</small>
                                </td>
                                <td>
                                    <strong>{{ $gateway->gateway_name }}</strong>
                                </td>
                                <td>{!! $gateway->sandbox_status_badge !!}</td>
                                <td>{{ $gateway->sandbox_requested_at ? $gateway->sandbox_requested_at->format('d M Y H:i') : '-' }}</td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-info view-request" 
                                            data-id="{{ $gateway->id }}" title="Lihat Detail">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-success approve-request" 
                                            data-id="{{ $gateway->id }}" title="Approve">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger reject-request" 
                                            data-id="{{ $gateway->id }}" title="Reject">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-5">
                    <i class="fas fa-clipboard-check fa-4x text-success mb-3"></i>
                    <h5>Tidak ada Request Pending</h5>
                    <p class="text-muted">Semua request verifikasi sandbox sudah diproses</p>
                </div>
                @endif
            </div>
        </div>

        <!-- History -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-history mr-2"></i>Riwayat Verifikasi</h3>
            </div>
            <div class="card-body p-0">
                @if($processedRequests->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Admin POP</th>
                                <th>Gateway</th>
                                <th>Status</th>
                                <th>Diproses</th>
                                <th>Catatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($processedRequests as $gateway)
                            <tr>
                                <td>
                                    <strong>{{ $gateway->user->name }}</strong>
                                </td>
                                <td>{{ $gateway->gateway_name }}</td>
                                <td>{!! $gateway->sandbox_status_badge !!}</td>
                                <td>{{ $gateway->sandbox_verified_at ? $gateway->sandbox_verified_at->format('d M Y H:i') : '-' }}</td>
                                <td>{{ $gateway->sandbox_notes ?? '-' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="card-footer">
                    {{ $processedRequests->links() }}
                </div>
                @else
                <div class="text-center py-4">
                    <p class="text-muted mb-0">Belum ada riwayat verifikasi</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Detail Modal -->
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-file-alt mr-2"></i>Detail Request</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div id="detailContent">
                    <div class="text-center py-4">
                        <i class="fas fa-spinner fa-spin fa-2x"></i>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="rejectForm">
                @csrf
                <input type="hidden" name="gateway_id" id="reject_gateway_id">
                <input type="hidden" name="status" value="rejected">
                
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-times-circle mr-2 text-danger"></i>Tolak Request</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Alasan Penolakan <span class="text-danger">*</span></label>
                        <textarea name="notes" class="form-control" rows="4" required 
                                  placeholder="Jelaskan alasan penolakan..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-times mr-1"></i>Tolak Request
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
$(function() {
    // View request detail
    $('.view-request').on('click', function() {
        const id = $(this).data('id');
        $('#detailContent').html('<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i></div>');
        $('#detailModal').modal('show');
        
        $.get(`{{ url('admin/payment-gateways') }}/${id}/edit`, function(data) {
            let html = `
                <div class="row">
                    <div class="col-md-6">
                        <h6>Informasi</h6>
                        <table class="table table-sm">
                            <tr><td>Gateway</td><td><strong>${data.gateway_name || data.gateway_type}</strong></td></tr>
                            <tr><td>Admin POP</td><td>${data.user?.name || '-'}</td></tr>
                            <tr><td>Mode</td><td>${data.is_sandbox ? 'Sandbox' : 'Production'}</td></tr>
                            <tr><td>Tanggal Request</td><td>${data.sandbox_requested_at || '-'}</td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6>Dokumen Verifikasi</h6>
            `;
            
            if (data.verification_documents && Object.keys(data.verification_documents).length > 0) {
                for (const [key, value] of Object.entries(data.verification_documents)) {
                    html += `
                        <div class="mb-2">
                            <strong>${key.replace(/_/g, ' ').toUpperCase()}:</strong>
                            <a href="${value}" target="_blank" class="btn btn-sm btn-outline-primary ml-2">
                                <i class="fas fa-external-link-alt"></i> Lihat
                            </a>
                        </div>
                    `;
                }
            } else {
                html += '<p class="text-muted">Tidak ada dokumen</p>';
            }
            
            html += `
                    </div>
                </div>
            `;
            
            if (data.sandbox_notes) {
                html += `
                    <hr>
                    <h6>Catatan</h6>
                    <p>${data.sandbox_notes}</p>
                `;
            }
            
            $('#detailContent').html(html);
        }).fail(function() {
            $('#detailContent').html('<div class="alert alert-danger">Gagal memuat data</div>');
        });
    });

    // Approve request
    $('.approve-request').on('click', function() {
        const id = $(this).data('id');
        const btn = $(this);
        
        Swal.fire({
            title: 'Approve Request?',
            text: 'Request sandbox akan disetujui',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            confirmButtonText: 'Ya, Approve!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');
                
                $.post(`{{ url('admin/payment-gateways') }}/${id}/review-sandbox`, {
                    _token: '{{ csrf_token() }}',
                    status: 'approved',
                    notes: ''
                }, function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        toastr.error(response.message);
                        btn.prop('disabled', false).html('<i class="fas fa-check"></i>');
                    }
                }).fail(function(xhr) {
                    toastr.error(xhr.responseJSON?.message || 'Gagal memproses request');
                    btn.prop('disabled', false).html('<i class="fas fa-check"></i>');
                });
            }
        });
    });

    // Reject request
    $('.reject-request').on('click', function() {
        const id = $(this).data('id');
        $('#reject_gateway_id').val(id);
        $('#rejectModal').modal('show');
    });

    $('#rejectForm').on('submit', function(e) {
        e.preventDefault();
        
        const id = $('#reject_gateway_id').val();
        const btn = $(this).find('button[type="submit"]');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Memproses...');

        $.post(`{{ url('admin/payment-gateways') }}/${id}/review-sandbox`, $(this).serialize(), function(response) {
            if (response.success) {
                toastr.success(response.message);
                $('#rejectModal').modal('hide');
                setTimeout(() => location.reload(), 1000);
            } else {
                toastr.error(response.message);
            }
        }).fail(function(xhr) {
            toastr.error(xhr.responseJSON?.message || 'Gagal memproses request');
        }).always(function() {
            btn.prop('disabled', false).html('<i class="fas fa-times mr-1"></i>Tolak Request');
        });
    });
});
</script>
@endpush
