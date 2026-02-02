@extends('layouts.admin')

@section('title', 'Edit Template - ' . $templateInfo['name'])
@section('page-title', 'Edit Template: ' . $templateInfo['name'])

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.message-templates.index', ['channel' => $channel]) }}">Template</a></li>
    <li class="breadcrumb-item active">{{ $templateInfo['name'] }}</li>
@endsection

@push('css')
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.css" rel="stylesheet">
<style>
    .variable-list {
        max-height: 400px;
        overflow-y: auto;
    }
    .variable-item {
        cursor: pointer;
        padding: 8px 12px;
        border-radius: 4px;
        margin-bottom: 5px;
        background: #f8f9fa;
        transition: all 0.2s;
    }
    .variable-item:hover {
        background: #e3f2fd;
    }
    .variable-item code {
        color: #007bff;
        font-weight: bold;
    }
    .preview-frame {
        border: 1px solid #dee2e6;
        border-radius: 8px;
        min-height: 300px;
        background: white;
    }
    .preview-header {
        background: #f8f9fa;
        padding: 10px 15px;
        border-bottom: 1px solid #dee2e6;
        border-radius: 8px 8px 0 0;
    }
    .preview-body {
        padding: 15px;
    }
    .channel-badge {
        font-size: 1rem;
        padding: 8px 15px;
    }
    .note-editor {
        border-radius: 8px;
    }
    .wa-preview {
        background: #e5ddd5;
        padding: 20px;
        border-radius: 8px;
        min-height: 300px;
    }
    .wa-bubble {
        background: white;
        padding: 10px 15px;
        border-radius: 8px;
        max-width: 80%;
        box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        white-space: pre-wrap;
    }
    .wa-time {
        font-size: 0.75rem;
        color: #999;
        text-align: right;
        margin-top: 5px;
    }
</style>
@endpush

@section('content')
<form id="templateForm" method="POST" action="{{ route('admin.message-templates.update', $code) }}">
    @csrf
    @method('PUT')
    <input type="hidden" name="channel" value="{{ $channel }}">

    <div class="row">
        <!-- Main Content -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        @if($channel === 'email')
                            <i class="fas fa-envelope mr-2"></i>
                        @else
                            <i class="fab fa-whatsapp mr-2"></i>
                        @endif
                        {{ $templateInfo['name'] }}
                        <span class="badge badge-{{ $channel === 'email' ? 'primary' : 'success' }} channel-badge ml-2">
                            {{ ucfirst($channel) }}
                        </span>
                    </h3>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-4">{{ $templateInfo['description'] }}</p>

                    @if($channel === 'email')
                        <!-- Email Subject -->
                        <div class="form-group">
                            <label>Subject Email <span class="text-danger">*</span></label>
                            <input type="text" name="email_subject" id="email_subject" 
                                   class="form-control" required
                                   value="{{ old('email_subject', $template->email_subject ?? $defaultTemplate->email_subject ?? '') }}"
                                   placeholder="Masukkan subject email...">
                            <small class="text-muted">Gunakan variabel seperti <code>@{{customer_name}}</code></small>
                        </div>

                        <!-- Email Body -->
                        <div class="form-group">
                            <label>Isi Email <span class="text-danger">*</span></label>
                            <textarea name="email_body" id="email_body" class="summernote">{{ old('email_body', $template->email_body ?? $defaultTemplate->email_body ?? '') }}</textarea>
                        </div>
                    @else
                        <!-- WhatsApp Body -->
                        <div class="form-group">
                            <label>Isi Pesan WhatsApp <span class="text-danger">*</span></label>
                            <textarea name="wa_body" id="wa_body" class="form-control" rows="12" required
                                      placeholder="Masukkan pesan WhatsApp...">{{ old('wa_body', $template->wa_body ?? $defaultTemplate->wa_body ?? '') }}</textarea>
                            <small class="text-muted">
                                <strong>Format:</strong> *bold*, _italic_, ~strikethrough~, ```monospace```
                            </small>
                        </div>
                    @endif

                    <!-- Active Status -->
                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1"
                                   {{ old('is_active', $template->is_active ?? $defaultTemplate->is_active ?? true) ? 'checked' : '' }}>
                            <label class="custom-control-label" for="is_active">Template Aktif</label>
                        </div>
                        <small class="text-muted">Template yang tidak aktif tidak akan digunakan untuk mengirim notifikasi</small>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary" id="btnSave">
                        <i class="fas fa-save mr-1"></i> Simpan Template
                    </button>
                    <button type="button" class="btn btn-info" id="btnPreview">
                        <i class="fas fa-eye mr-1"></i> Preview
                    </button>
                    <a href="{{ route('admin.message-templates.index', ['channel' => $channel]) }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left mr-1"></i> Kembali
                    </a>
                </div>
            </div>

            <!-- Preview Section -->
            <div class="card" id="previewCard" style="display:none;">
                <div class="card-header bg-info text-white">
                    <h3 class="card-title"><i class="fas fa-eye mr-2"></i>Preview Template</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-sm btn-outline-light" id="btnSendTest">
                            <i class="fas fa-paper-plane mr-1"></i> Kirim Test
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    @if($channel === 'email')
                        <div class="preview-frame">
                            <div class="preview-header">
                                <strong>Subject:</strong> <span id="previewSubject">-</span>
                            </div>
                            <div class="preview-body" id="previewBody">
                                <!-- Preview content will be inserted here -->
                            </div>
                        </div>
                    @else
                        <div class="wa-preview">
                            <div class="wa-bubble" id="previewWaBody">
                                <!-- WhatsApp preview -->
                            </div>
                            <div class="wa-time">{{ now()->format('H:i') }}</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Sidebar - Variables -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <h3 class="card-title"><i class="fas fa-code mr-2"></i>Variabel Tersedia</h3>
                </div>
                <div class="card-body p-0">
                    <div class="variable-list p-3">
                        @foreach($templateInfo['variables'] as $var => $desc)
                        <div class="variable-item" data-variable="{{ $var }}" title="Klik untuk menyalin">
                            <code>{!! '{{' . $var . '}}' !!}</code>
                            <br><small class="text-muted">{{ $desc }}</small>
                        </div>
                        @endforeach
                    </div>
                </div>
                <div class="card-footer">
                    <small class="text-muted">
                        <i class="fas fa-info-circle mr-1"></i>
                        Klik variabel untuk menyalin ke clipboard
                    </small>
                </div>
            </div>

            @if($defaultTemplate && !$template)
            <div class="alert alert-info">
                <i class="fas fa-info-circle mr-1"></i>
                <strong>Menggunakan Template Default</strong><br>
                Edit dan simpan untuk membuat template custom.
            </div>
            @endif

            @if($template && $popId)
            <div class="card">
                <div class="card-body">
                    <button type="button" class="btn btn-warning btn-block" id="btnReset">
                        <i class="fas fa-undo mr-1"></i> Reset ke Default
                    </button>
                    <small class="text-muted d-block mt-2">
                        Menghapus template custom dan kembali menggunakan template default sistem.
                    </small>
                </div>
            </div>
            @endif
        </div>
    </div>
</form>

<!-- Send Test Modal -->
<div class="modal fade" id="sendTestModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">
                    <i class="fas fa-paper-plane mr-2"></i>Kirim Test {{ ucfirst($channel) }}
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>{{ $channel === 'email' ? 'Email Penerima' : 'No. WhatsApp Penerima' }}</label>
                    <input type="text" id="testRecipient" class="form-control" 
                           placeholder="{{ $channel === 'email' ? 'email@example.com' : '628123456789' }}">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-info" id="btnConfirmSendTest">
                    <i class="fas fa-paper-plane mr-1"></i> Kirim
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.js"></script>
<script>
$(function() {
    @if($channel === 'email')
    // Initialize Summernote
    $('.summernote').summernote({
        height: 350,
        toolbar: [
            ['style', ['style']],
            ['font', ['bold', 'italic', 'underline', 'clear']],
            ['fontsize', ['fontsize']],
            ['color', ['color']],
            ['para', ['ul', 'ol', 'paragraph']],
            ['table', ['table']],
            ['insert', ['link', 'picture']],
            ['view', ['fullscreen', 'codeview', 'help']]
        ],
        callbacks: {
            onInit: function() {
                // Make sure editor is properly initialized
            }
        }
    });
    @endif

    // Copy variable to clipboard
    $('.variable-item').on('click', function() {
        const variable = String.fromCharCode(123, 123) + $(this).data('variable') + String.fromCharCode(125, 125);
        
        navigator.clipboard.writeText(variable).then(function() {
            toastr.success('Variabel disalin: ' + variable);
        });

        @if($channel === 'email')
        // Also insert at cursor in Summernote
        $('.summernote').summernote('insertText', variable);
        @else
        // Insert at cursor in textarea
        const textarea = document.getElementById('wa_body');
        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const text = textarea.value;
        textarea.value = text.substring(0, start) + variable + text.substring(end);
        textarea.selectionStart = textarea.selectionEnd = start + variable.length;
        textarea.focus();
        @endif
    });

    // Preview
    $('#btnPreview').on('click', function() {
        @if($channel === 'email')
        const subject = $('#email_subject').val();
        const body = $('.summernote').summernote('code');
        @else
        const subject = '';
        const body = $('#wa_body').val();
        @endif

        $.ajax({
            url: '{{ route("admin.message-templates.preview") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                channel: '{{ $channel }}',
                subject: subject,
                body: body
            },
            success: function(response) {
                if (response.success) {
                    @if($channel === 'email')
                    $('#previewSubject').text(response.subject);
                    $('#previewBody').html(response.body);
                    @else
                    $('#previewWaBody').text(response.body);
                    @endif
                    $('#previewCard').slideDown();
                }
            }
        });
    });

    // Send Test
    $('#btnSendTest').on('click', function() {
        $('#sendTestModal').modal('show');
    });

    $('#btnConfirmSendTest').on('click', function() {
        const btn = $(this);
        const recipient = $('#testRecipient').val();

        if (!recipient) {
            toastr.error('Masukkan penerima');
            return;
        }

        @if($channel === 'email')
        const subject = $('#previewSubject').text();
        const body = $('#previewBody').html();
        @else
        const subject = '';
        const body = $('#previewWaBody').text();
        @endif

        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Mengirim...');

        $.ajax({
            url: '{{ route("admin.message-templates.send-test") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                channel: '{{ $channel }}',
                recipient: recipient,
                subject: subject,
                body: body
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    $('#sendTestModal').modal('hide');
                } else {
                    toastr.error(response.message);
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Gagal mengirim');
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fas fa-paper-plane mr-1"></i> Kirim');
            }
        });
    });

    // Reset to default
    @if($template && $popId)
    $('#btnReset').on('click', function() {
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
                    url: '{{ route("admin.message-templates.reset", $code) }}',
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
    @endif

    // Form submit
    $('#templateForm').on('submit', function(e) {
        e.preventDefault();
        
        const btn = $('#btnSave');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...');

        @if($channel === 'email')
        // Update hidden textarea with summernote content
        $('#email_body').val($('.summernote').summernote('code'));
        @endif

        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                } else {
                    toastr.error(response.message);
                }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON.errors;
                    for (let field in errors) {
                        toastr.error(errors[field][0]);
                    }
                } else {
                    toastr.error(xhr.responseJSON?.message || 'Terjadi kesalahan');
                }
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i> Simpan Template');
            }
        });
    });
});
</script>
@endpush
