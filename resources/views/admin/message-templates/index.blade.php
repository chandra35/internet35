@extends('layouts.admin')

@section('title', 'Template Notifikasi')
@section('page-title', 'Template Email & WhatsApp')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.pop-settings.index') }}">Pengaturan</a></li>
    <li class="breadcrumb-item active">Template Notifikasi</li>
@endsection

@push('css')
<style>
    .template-card {
        transition: all 0.3s ease;
        border-left: 4px solid transparent;
    }
    .template-card:hover {
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    .template-card.has-custom {
        border-left-color: #28a745;
    }
    .template-card.using-default {
        border-left-color: #6c757d;
    }
    .channel-tabs .nav-link {
        border-radius: 20px;
        padding: 8px 20px;
        margin-right: 10px;
    }
    .channel-tabs .nav-link.active {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    .channel-tabs .nav-link i {
        margin-right: 5px;
    }
    .variable-badge {
        font-family: monospace;
        font-size: 0.8rem;
        cursor: pointer;
    }
    .variable-badge:hover {
        background-color: #007bff !important;
        color: white !important;
    }
    .config-status {
        padding: 10px 15px;
        border-radius: 8px;
        margin-bottom: 15px;
    }
    .config-status.configured {
        background: #d4edda;
        border: 1px solid #c3e6cb;
    }
    .config-status.not-configured {
        background: #fff3cd;
        border: 1px solid #ffeeba;
    }
</style>
@endpush

@section('content')
<div class="row">
    <div class="col-12">
        <!-- Config Status -->
        @if($channel === 'email')
            @if($popSetting && $popSetting->smtp_host)
                <div class="config-status configured">
                    <i class="fas fa-check-circle text-success mr-2"></i>
                    <strong>SMTP Dikonfigurasi:</strong> {{ $popSetting->smtp_host }}:{{ $popSetting->smtp_port }}
                </div>
            @else
                <div class="config-status not-configured">
                    <i class="fas fa-exclamation-triangle text-warning mr-2"></i>
                    <strong>SMTP Belum Dikonfigurasi.</strong> 
                    <a href="{{ route('admin.notification-settings.index') }}">Setup SMTP →</a>
                </div>
            @endif
        @else
            @if($popSetting && $popSetting->wa_api_url)
                <div class="config-status configured">
                    <i class="fas fa-check-circle text-success mr-2"></i>
                    <strong>WhatsApp API Dikonfigurasi</strong>
                </div>
            @else
                <div class="config-status not-configured">
                    <i class="fas fa-exclamation-triangle text-warning mr-2"></i>
                    <strong>WhatsApp API Belum Dikonfigurasi.</strong>
                    <a href="{{ route('admin.notification-settings.index') }}">Setup WhatsApp →</a>
                </div>
            @endif
        @endif

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-file-alt mr-2"></i>Template Notifikasi
                </h3>
            </div>
            <div class="card-body">
                <!-- Channel Tabs -->
                <ul class="nav channel-tabs mb-4">
                    <li class="nav-item">
                        <a class="nav-link {{ $channel === 'email' ? 'active' : '' }}" 
                           href="{{ route('admin.message-templates.index', ['channel' => 'email']) }}">
                            <i class="fas fa-envelope"></i> Email
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ $channel === 'whatsapp' ? 'active' : '' }}" 
                           href="{{ route('admin.message-templates.index', ['channel' => 'whatsapp']) }}">
                            <i class="fab fa-whatsapp"></i> WhatsApp
                        </a>
                    </li>
                </ul>

                <div class="alert alert-info mb-4">
                    <i class="fas fa-info-circle mr-1"></i>
                    <strong>Info:</strong> Template dengan tanda <span class="badge badge-success">Custom</span> adalah template yang sudah Anda sesuaikan. 
                    Template dengan tanda <span class="badge badge-secondary">Default</span> menggunakan template sistem.
                </div>

                <!-- Templates Grid -->
                <div class="row">
                    @foreach($templates as $tpl)
                    <div class="col-lg-6 mb-3">
                        <div class="card template-card {{ $tpl['has_custom'] ? 'has-custom' : 'using-default' }}">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h5 class="mb-1">
                                            {{ $tpl['name'] }}
                                            @if($tpl['has_custom'])
                                                <span class="badge badge-success">Custom</span>
                                            @else
                                                <span class="badge badge-secondary">Default</span>
                                            @endif
                                        </h5>
                                        <small class="text-muted">{{ $tpl['description'] }}</small>
                                    </div>
                                    <div>
                                        @if($tpl['is_active'])
                                            <span class="badge badge-success"><i class="fas fa-check"></i> Aktif</span>
                                        @else
                                            <span class="badge badge-secondary"><i class="fas fa-times"></i> Nonaktif</span>
                                        @endif
                                    </div>
                                </div>
                                
                                @if($channel === 'email' && $tpl['template'])
                                    <div class="mb-2">
                                        <small class="text-muted">Subject:</small><br>
                                        <code class="small">{{ Str::limit($tpl['template']->email_subject ?? '-', 60) }}</code>
                                    </div>
                                @elseif($channel === 'whatsapp' && $tpl['template'])
                                    <div class="mb-2">
                                        <small class="text-muted">Preview:</small><br>
                                        <code class="small">{{ Str::limit($tpl['template']->wa_body ?? '-', 80) }}</code>
                                    </div>
                                @endif

                                <div class="mt-3">
                                    <a href="{{ route('admin.message-templates.edit', ['code' => $tpl['code'], 'channel' => $channel]) }}" 
                                       class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit mr-1"></i> Edit Template
                                    </a>
                                    @if($tpl['has_custom'] && $popId)
                                        <button type="button" class="btn btn-sm btn-outline-warning btn-reset" 
                                                data-code="{{ $tpl['code'] }}">
                                            <i class="fas fa-undo mr-1"></i> Reset ke Default
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
$(function() {
    // Reset to default
    $('.btn-reset').on('click', function() {
        const code = $(this).data('code');
        
        Swal.fire({
            title: 'Reset Template?',
            text: 'Template custom akan dihapus dan kembali menggunakan template default.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ffc107',
            confirmButtonText: 'Ya, Reset!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `{{ url('admin/message-templates') }}/${code}/reset`,
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        channel: '{{ $channel }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message);
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            toastr.error(response.message);
                        }
                    },
                    error: function(xhr) {
                        toastr.error(xhr.responseJSON?.message || 'Terjadi kesalahan');
                    }
                });
            }
        });
    });
});
</script>
@endpush
