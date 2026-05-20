@extends('layouts.admin')

@section('title', 'Log Notifikasi')

@section('page-title', 'Log Notifikasi')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">Log Notifikasi</li>
@endsection

@section('content')
@include('admin.partials.pop-selector', ['popUsers' => $popUsers ?? null, 'popId' => $popId ?? null])

@if(!($popId ?? null) && auth()->user()->hasRole('superadmin'))
<div class="alert alert-warning">
    <i class="fas fa-exclamation-triangle mr-2"></i>
    Pilih POP terlebih dahulu untuk menampilkan log notifikasi.
</div>
@else
<div class="row">
    <div class="col-lg-3">
        @include('admin.pop-settings.partials.sidebar')
    </div>
    <div class="col-lg-9">
        {{-- Stats Cards --}}
        <div class="row">
            <div class="col-md-2 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3>{{ $stats['total'] ?? 0 }}</h3>
                        <p>Total</p>
                    </div>
                    <div class="icon"><i class="fas fa-paper-plane"></i></div>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="small-box bg-primary">
                    <div class="inner">
                        <h3>{{ $stats['today'] ?? 0 }}</h3>
                        <p>Hari Ini</p>
                    </div>
                    <div class="icon"><i class="fas fa-calendar-day"></i></div>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>{{ $stats['sent'] ?? 0 }}</h3>
                        <p>Terkirim</p>
                    </div>
                    <div class="icon"><i class="fas fa-check-circle"></i></div>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3>{{ $stats['failed'] ?? 0 }}</h3>
                        <p>Gagal</p>
                    </div>
                    <div class="icon"><i class="fas fa-times-circle"></i></div>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="small-box bg-gradient-indigo">
                    <div class="inner">
                        <h3>{{ $stats['email_count'] ?? 0 }}</h3>
                        <p>Email</p>
                    </div>
                    <div class="icon"><i class="fas fa-envelope"></i></div>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="small-box bg-gradient-teal">
                    <div class="inner">
                        <h3>{{ $stats['wa_count'] ?? 0 }}</h3>
                        <p>WhatsApp</p>
                    </div>
                    <div class="icon"><i class="fab fa-whatsapp"></i></div>
                </div>
            </div>
        </div>

        {{-- Filters --}}
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-filter mr-1"></i> Filter</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
                </div>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('admin.notification-logs.index', request()->only('user_id')) }}" id="filterForm">
                    @if(request('user_id'))
                        <input type="hidden" name="user_id" value="{{ request('user_id') }}">
                    @endif
                    <div class="row">
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Channel</label>
                                <select name="channel" class="form-control form-control-sm">
                                    <option value="">Semua</option>
                                    <option value="email" {{ request('channel') == 'email' ? 'selected' : '' }}>Email</option>
                                    <option value="whatsapp" {{ request('channel') == 'whatsapp' ? 'selected' : '' }}>WhatsApp</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Status</label>
                                <select name="status" class="form-control form-control-sm">
                                    <option value="">Semua</option>
                                    <option value="sent" {{ request('status') == 'sent' ? 'selected' : '' }}>Terkirim</option>
                                    <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Gagal</option>
                                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Template</label>
                                <select name="template_code" class="form-control form-control-sm">
                                    <option value="">Semua</option>
                                    @foreach($templateCodes as $code => $info)
                                        <option value="{{ $code }}" {{ request('template_code') == $code ? 'selected' : '' }}>
                                            {{ $info['name'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Dari Tanggal</label>
                                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Sampai Tanggal</label>
                                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Cari</label>
                                <div class="input-group input-group-sm">
                                    <input type="text" name="search" class="form-control" placeholder="Nama/Email/HP..." value="{{ request('search') }}">
                                    <div class="input-group-append">
                                        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center">
                        <button type="submit" class="btn btn-sm btn-primary mr-2"><i class="fas fa-filter mr-1"></i> Terapkan</button>
                        <a href="{{ route('admin.notification-logs.index', request()->only('user_id')) }}" class="btn btn-sm btn-secondary mr-auto"><i class="fas fa-undo mr-1"></i> Reset</a>
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-danger" onclick="cleanupLogs(30)" title="Hapus log > 30 hari">
                                <i class="fas fa-broom mr-1"></i> Bersihkan &gt; 30 hari
                            </button>
                            <button type="button" class="btn btn-outline-danger" onclick="cleanupLogs(90)" title="Hapus log > 90 hari">
                                <i class="fas fa-trash mr-1"></i> &gt; 90 hari
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Table --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-list mr-1"></i> Daftar Log Notifikasi</h3>
                <div class="card-tools">
                    <span class="badge badge-secondary">{{ $logs->total() }} log</span>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th style="width:40px">#</th>
                                <th style="width:70px">Channel</th>
                                <th style="width:70px">Status</th>
                                <th>Penerima</th>
                                <th>Template</th>
                                <th>Subject</th>
                                <th>Waktu</th>
                                <th style="width:80px">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($logs as $i => $log)
                            <tr>
                                <td class="text-muted">{{ $logs->firstItem() + $i }}</td>
                                <td>
                                    <span class="badge badge-{{ $log->channel_color }}">
                                        <i class="{{ $log->channel_icon }} mr-1"></i>{{ $log->channel_label }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-{{ $log->status_color }}">{{ $log->status_label }}</span>
                                </td>
                                <td>
                                    <div>
                                        @if($log->customer)
                                            <strong>{{ $log->customer->name }}</strong><br>
                                        @endif
                                        <small class="text-muted">{{ $log->recipient }}</small>
                                    </div>
                                </td>
                                <td><small>{{ $log->template_label }}</small></td>
                                <td>
                                    <small>{{ \Illuminate\Support\Str::limit($log->subject ?? '-', 40) }}</small>
                                </td>
                                <td>
                                    <small title="{{ $log->created_at->format('d M Y H:i:s') }}">
                                        {{ $log->created_at->diffForHumans() }}
                                    </small>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-info" onclick="viewLog('{{ $log->id }}')" title="Lihat Detail">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        @if($log->customer && $log->template_code)
                                        <button class="btn btn-outline-warning" onclick="resendLog('{{ $log->id }}')" title="Kirim Ulang">
                                            <i class="fas fa-redo"></i>
                                        </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                    Belum ada log notifikasi.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($logs->hasPages())
            <div class="card-footer clearfix">
                {{ $logs->links() }}
            </div>
            @endif
        </div>
    </div>
</div>

{{-- Detail Modal --}}
<div class="modal fade" id="logDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-info-circle mr-1"></i> Detail Notifikasi</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body" id="logDetailBody">
                <div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
@endif
@endsection

@section('scripts')
<script>
function viewLog(id) {
    $('#logDetailBody').html('<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x"></i></div>');
    $('#logDetailModal').modal('show');

    $.get(`{{ url('admin/notification-logs') }}/${id}`, function(data) {
        if (data.success) {
            const log = data.log;
            let html = `
                <div class="row mb-3">
                    <div class="col-md-6">
                        <table class="table table-sm table-borderless">
                            <tr><th width="120">Channel</th><td><span class="badge badge-info"><i class="${log.channel_icon} mr-1"></i>${log.channel_label}</span></td></tr>
                            <tr><th>Status</th><td><span class="badge badge-${log.status_color}">${log.status_label}</span></td></tr>
                            <tr><th>Template</th><td>${log.template_label || '-'}</td></tr>
                            <tr><th>Penerima</th><td>${log.recipient}</td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-sm table-borderless">
                            <tr><th width="120">Pelanggan</th><td>${log.customer_name || '-'}</td></tr>
                            <tr><th>Subject</th><td>${log.subject || '-'}</td></tr>
                            <tr><th>Terkirim</th><td>${log.sent_at || '-'}</td></tr>
                            <tr><th>Dibuat</th><td>${log.created_at}</td></tr>
                        </table>
                    </div>
                </div>`;

            if (log.error_message) {
                html += `<div class="alert alert-danger"><i class="fas fa-exclamation-triangle mr-1"></i><strong>Error:</strong> ${log.error_message}</div>`;
            }

            if (log.body) {
                html += `<div class="card"><div class="card-header py-2"><strong>Isi Pesan</strong></div><div class="card-body">`;
                if (log.channel === 'email' && log.body.includes('<html')) {
                    html += `<iframe srcdoc="${log.body.replace(/"/g, '&quot;')}" style="width:100%;min-height:400px;border:1px solid #ddd;border-radius:4px;"></iframe>`;
                } else {
                    html += `<div class="bg-light p-3 rounded" style="white-space:pre-wrap;font-size:13px;">${log.body.replace(/</g,'&lt;').replace(/>/g,'&gt;')}</div>`;
                }
                html += `</div></div>`;
            }

            $('#logDetailBody').html(html);
        }
    }).fail(function() {
        $('#logDetailBody').html('<div class="alert alert-danger">Gagal memuat detail.</div>');
    });
}

function resendLog(id) {
    Swal.fire({
        title: 'Kirim Ulang?',
        text: 'Notifikasi akan dikirim ulang ke pelanggan.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#f0ad4e',
        confirmButtonText: 'Ya, Kirim Ulang',
        cancelButtonText: 'Batal'
    }).then(result => {
        if (result.isConfirmed) {
            $.ajax({
                url: `{{ url('admin/notification-logs') }}/${id}/resend`,
                type: 'POST',
                data: { _token: '{{ csrf_token() }}' },
                success: function(data) {
                    if (data.success) {
                        toastr.success(data.message);
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        toastr.error(data.message);
                    }
                },
                error: function(xhr) {
                    toastr.error(xhr.responseJSON?.message || 'Gagal mengirim ulang.');
                }
            });
        }
    });
}

function cleanupLogs(days) {
    Swal.fire({
        title: `Hapus Log > ${days} Hari?`,
        text: `Semua log notifikasi yang lebih dari ${days} hari akan dihapus permanen.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal'
    }).then(result => {
        if (result.isConfirmed) {
            $.ajax({
                url: '{{ route("admin.notification-logs.destroy") }}',
                type: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}',
                    older_than: days,
                    @if(request('user_id'))
                    user_id: '{{ request("user_id") }}'
                    @endif
                },
                success: function(data) {
                    if (data.success) {
                        toastr.success(data.message);
                        setTimeout(() => location.reload(), 1000);
                    }
                },
                error: function(xhr) {
                    toastr.error('Gagal menghapus log.');
                }
            });
        }
    });
}
</script>
@endsection
