@extends('layouts.admin')

@section('title', 'Pengaturan Notifikasi')

@section('page-title', 'Pengaturan Notifikasi')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">Pengaturan Notifikasi</li>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-3">
        @include('admin.pop-settings.partials.sidebar')
    </div>
    <div class="col-lg-9">
        @if($popUsers && auth()->user()->hasRole('superadmin'))
        <div class="card card-outline card-info mb-3">
            <div class="card-body py-2">
                <div class="row align-items-center">
                    <div class="col-auto">
                        <i class="fas fa-user-shield text-info fa-lg"></i>
                        <strong class="ml-2">Mode Superadmin:</strong>
                    </div>
                    <div class="col">
                        <select class="form-control select2" id="selectPopUser">
                            <option value="">-- Pilih Admin POP --</option>
                            @foreach($popUsers as $popUser)
                                <option value="{{ $popUser->id }}" {{ $userId == $popUser->id ? 'selected' : '' }}>
                                    {{ $popUser->name }} ({{ $popUser->email }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Tabs -->
        <div class="card">
            <div class="card-header p-0">
                <ul class="nav nav-tabs" id="notifTabs">
                    <li class="nav-item">
                        <a class="nav-link active" data-toggle="tab" href="#tab-email">
                            <i class="fas fa-envelope mr-1"></i> Email (SMTP)
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="tab" href="#tab-whatsapp">
                            <i class="fab fa-whatsapp mr-1"></i> WhatsApp
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="tab" href="#tab-telegram">
                            <i class="fab fa-telegram mr-1"></i> Telegram
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="tab" href="#tab-events">
                            <i class="fas fa-bell mr-1"></i> Event Notifikasi
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('admin.message-templates.index') }}">
                            <i class="fas fa-file-alt mr-1"></i> Template Pesan
                            <i class="fas fa-external-link-alt ml-1 text-muted small"></i>
                        </a>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content">
                    <!-- Email Tab -->
                    <div class="tab-pane fade show active" id="tab-email">
                        <form id="emailForm">
                            @csrf
                            @if($userId)
                            <input type="hidden" name="user_id" value="{{ $userId }}">
                            @endif
                            
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="email_enabled" name="email_enabled" 
                                               value="1" {{ $setting->email_enabled ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="email_enabled">
                                            <strong>Aktifkan Notifikasi Email</strong>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div id="emailSettings">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>SMTP Host <span class="text-danger">*</span></label>
                                            <input type="text" name="smtp_host" class="form-control" 
                                                   value="{{ $setting->smtp_host }}" placeholder="smtp.gmail.com">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Port <span class="text-danger">*</span></label>
                                            <input type="number" name="smtp_port" class="form-control" 
                                                   value="{{ $setting->smtp_port ?? 587 }}">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Encryption</label>
                                            <select name="smtp_encryption" class="form-control select2">
                                                <option value="">None</option>
                                                <option value="tls" {{ $setting->smtp_encryption == 'tls' ? 'selected' : '' }}>TLS</option>
                                                <option value="ssl" {{ $setting->smtp_encryption == 'ssl' ? 'selected' : '' }}>SSL</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Username <span class="text-danger">*</span></label>
                                            <input type="text" name="smtp_username" class="form-control" 
                                                   value="{{ $setting->smtp_username }}">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Password <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <input type="password" name="smtp_password" class="form-control" 
                                                       value="{{ $setting->smtp_password }}" placeholder="Kosongkan jika tidak diubah">
                                                <div class="input-group-append">
                                                    <button type="button" class="btn btn-outline-secondary toggle-password">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Nama Pengirim</label>
                                            <input type="text" name="smtp_from_name" class="form-control" 
                                                   value="{{ $setting->smtp_from_name }}" placeholder="Nama ISP Anda">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Email Pengirim</label>
                                            <input type="email" name="smtp_from_email" class="form-control" 
                                                   value="{{ $setting->smtp_from_email }}" placeholder="noreply@domain.com">
                                        </div>
                                    </div>
                                </div>

                                <div class="alert alert-info">
                                    <h6><i class="fas fa-lightbulb mr-2"></i>Tips untuk Gmail:</h6>
                                    <ul class="mb-0">
                                        <li>Host: smtp.gmail.com, Port: 587, Encryption: TLS</li>
                                        <li>Gunakan App Password jika 2FA aktif</li>
                                        <li>Enable "Less secure apps" jika tidak menggunakan 2FA</li>
                                    </ul>
                                </div>
                            </div>

                            <div class="mt-3">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save mr-1"></i>Simpan
                                </button>
                                <button type="button" class="btn btn-info test-email">
                                    <i class="fas fa-paper-plane mr-1"></i>Kirim Email Test
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- WhatsApp Tab -->
                    <div class="tab-pane fade" id="tab-whatsapp">
                        <form id="whatsappForm">
                            @csrf
                            @if($userId)
                            <input type="hidden" name="user_id" value="{{ $userId }}">
                            @endif
                            
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="whatsapp_enabled" name="whatsapp_enabled" 
                                               value="1" {{ $setting->whatsapp_enabled ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="whatsapp_enabled">
                                            <strong>Aktifkan Notifikasi WhatsApp</strong>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div id="whatsappSettings">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Provider WhatsApp Gateway <span class="text-danger">*</span></label>
                                            <select name="whatsapp_provider" class="form-control select2" id="whatsapp_provider">
                                                <option value="">-- Pilih Provider --</option>
                                                @foreach(\App\Models\NotificationSetting::whatsappProviders() as $key => $provider)
                                                    <option value="{{ $key }}" {{ $setting->whatsapp_provider == $key ? 'selected' : '' }}>
                                                        {{ $provider['name'] }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>API Key / Token <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <input type="password" name="whatsapp_api_key" class="form-control" 
                                                       value="{{ $setting->whatsapp_api_key }}" placeholder="API Key dari provider">
                                                <div class="input-group-append">
                                                    <button type="button" class="btn btn-outline-secondary toggle-password">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Device ID / Sender Number</label>
                                            <input type="text" name="whatsapp_device_id" class="form-control" 
                                                   value="{{ $setting->whatsapp_device_id }}" placeholder="ID Device atau nomor pengirim">
                                        </div>
                                    </div>
                                </div>

                                <div class="row mt-3">
                                    @foreach(\App\Models\NotificationSetting::whatsappProviders() as $key => $provider)
                                    <div class="col-md-4 mb-3">
                                        <div class="card h-100">
                                            <div class="card-body text-center">
                                                <h6>{{ $provider['name'] }}</h6>
                                                <p class="text-muted small mb-2">{{ $provider['description'] }}</p>
                                                <a href="{{ $provider['url'] }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-external-link-alt mr-1"></i>Daftar
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="mt-3">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save mr-1"></i>Simpan
                                </button>
                                <button type="button" class="btn btn-success test-whatsapp">
                                    <i class="fab fa-whatsapp mr-1"></i>Kirim WA Test
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Telegram Tab -->
                    <div class="tab-pane fade" id="tab-telegram">
                        <form id="telegramForm">
                            @csrf
                            @if($userId)
                            <input type="hidden" name="user_id" value="{{ $userId }}">
                            @endif
                            
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="telegram_enabled" name="telegram_enabled" 
                                               value="1" {{ $setting->telegram_enabled ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="telegram_enabled">
                                            <strong>Aktifkan Notifikasi Telegram</strong>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div id="telegramSettings">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Bot Token <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <input type="password" name="telegram_bot_token" class="form-control" 
                                                       value="{{ $setting->telegram_bot_token }}" placeholder="Bot token dari @BotFather">
                                                <div class="input-group-append">
                                                    <button type="button" class="btn btn-outline-secondary toggle-password">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Chat ID Default</label>
                                            <input type="text" name="telegram_chat_id" class="form-control" 
                                                   value="{{ $setting->telegram_chat_id }}" placeholder="Chat ID untuk notifikasi admin">
                                            <small class="text-muted">Untuk notifikasi admin/internal. Chat ID pelanggan disimpan di data pelanggan.</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="alert alert-info">
                                    <h6><i class="fab fa-telegram mr-2"></i>Cara Membuat Bot Telegram:</h6>
                                    <ol class="mb-0">
                                        <li>Chat @BotFather di Telegram</li>
                                        <li>Kirim /newbot dan ikuti instruksi</li>
                                        <li>Copy Bot Token yang diberikan</li>
                                        <li>Untuk mendapat Chat ID, chat bot Anda, lalu gunakan @getidsbot</li>
                                    </ol>
                                </div>
                            </div>

                            <div class="mt-3">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save mr-1"></i>Simpan
                                </button>
                                <button type="button" class="btn btn-info test-telegram">
                                    <i class="fab fa-telegram mr-1"></i>Kirim Telegram Test
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Events Tab -->
                    <div class="tab-pane fade" id="tab-events">
                        <form id="eventsForm">
                            @csrf
                            @if($userId)
                            <input type="hidden" name="user_id" value="{{ $userId }}">
                            @endif
                            
                            <p class="text-muted mb-4">Pilih event yang akan mengirim notifikasi otomatis ke pelanggan:</p>
                            
                            <div class="row">
                                @php
                                    $events = \App\Models\NotificationSetting::availableEvents();
                                    $enabledEvents = $setting->enabled_events ?? [];
                                @endphp
                                @foreach($events as $event => $info)
                                <div class="col-md-6 mb-3">
                                    <div class="card h-100">
                                        <div class="card-body">
                                            <div class="custom-control custom-switch mb-2">
                                                <input type="checkbox" class="custom-control-input" 
                                                       id="event_{{ $event }}" name="enabled_events[]" 
                                                       value="{{ $event }}" {{ in_array($event, $enabledEvents) ? 'checked' : '' }}>
                                                <label class="custom-control-label" for="event_{{ $event }}">
                                                    <strong>{{ $info['name'] }}</strong>
                                                </label>
                                            </div>
                                            <p class="text-muted small mb-2">{{ $info['description'] }}</p>
                                            <div class="d-flex flex-wrap">
                                                @foreach($info['channels'] as $channel)
                                                    @php
                                                        $icons = ['email' => 'fas fa-envelope', 'whatsapp' => 'fab fa-whatsapp', 'telegram' => 'fab fa-telegram'];
                                                        $colors = ['email' => 'primary', 'whatsapp' => 'success', 'telegram' => 'info'];
                                                    @endphp
                                                    <span class="badge badge-{{ $colors[$channel] }} mr-1">
                                                        <i class="{{ $icons[$channel] }}"></i> {{ ucfirst($channel) }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>

                            <div class="mt-3">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save mr-1"></i>Simpan
                                </button>
                                <button type="button" class="btn btn-secondary" id="selectAllEvents">
                                    <i class="fas fa-check-square mr-1"></i>Pilih Semua
                                </button>
                                <button type="button" class="btn btn-secondary" id="deselectAllEvents">
                                    <i class="far fa-square mr-1"></i>Hapus Semua
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Templates Tab -->
                    <div class="tab-pane fade" id="tab-templates">
                        <form id="templatesForm">
                            @csrf
                            @if($userId)
                            <input type="hidden" name="user_id" value="{{ $userId }}">
                            @endif
                            
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle mr-2"></i>
                                <strong>Variabel yang tersedia:</strong> 
                                <code>{customer_name}</code>, <code>{invoice_number}</code>, <code>{amount}</code>, 
                                <code>{due_date}</code>, <code>{package_name}</code>, <code>{isp_name}</code>, 
                                <code>{payment_link}</code>, <code>{support_phone}</code>
                            </div>
                            
                            @php
                                $templates = $setting->templates ?? \App\Models\NotificationSetting::defaultTemplates();
                            @endphp

                            <div class="accordion" id="templateAccordion">
                                @foreach($templates as $key => $template)
                                <div class="card mb-2">
                                    <div class="card-header py-2" id="heading_{{ $key }}">
                                        <h6 class="mb-0">
                                            <button class="btn btn-link btn-block text-left p-0" type="button" 
                                                    data-toggle="collapse" data-target="#collapse_{{ $key }}">
                                                <i class="fas fa-chevron-down mr-2"></i>
                                                {{ ucwords(str_replace('_', ' ', $key)) }}
                                            </button>
                                        </h6>
                                    </div>
                                    <div id="collapse_{{ $key }}" class="collapse {{ $loop->first ? 'show' : '' }}" 
                                         data-parent="#templateAccordion">
                                        <div class="card-body">
                                            <div class="form-group">
                                                <label>Subject (untuk Email)</label>
                                                <input type="text" name="templates[{{ $key }}][subject]" class="form-control" 
                                                       value="{{ $template['subject'] ?? '' }}">
                                            </div>
                                            <div class="form-group">
                                                <label>Pesan WhatsApp / Telegram</label>
                                                <textarea name="templates[{{ $key }}][message]" class="form-control" 
                                                          rows="4">{{ $template['message'] ?? '' }}</textarea>
                                            </div>
                                            <div class="form-group mb-0">
                                                <label>Body Email (HTML)</label>
                                                <textarea name="templates[{{ $key }}][email_body]" class="form-control" 
                                                          rows="6">{{ $template['email_body'] ?? '' }}</textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>

                            <div class="mt-3">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save mr-1"></i>Simpan Template
                                </button>
                                <button type="button" class="btn btn-warning" id="resetTemplates">
                                    <i class="fas fa-undo mr-1"></i>Reset ke Default
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Test Modal -->
<div class="modal fade" id="testModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-paper-plane mr-2"></i>Kirim Notifikasi Test</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label id="testLabel">Alamat Tujuan</label>
                    <input type="text" class="form-control" id="testDestination" placeholder="email@example.com">
                </div>
                <div class="form-group">
                    <label>Pesan Test</label>
                    <textarea class="form-control" id="testMessage" rows="3">Ini adalah pesan test dari sistem billing ISP Anda.</textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-primary" id="btnSendTest">
                    <i class="fas fa-paper-plane mr-1"></i>Kirim
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
$(function() {
    // Select2 sudah diinisialisasi secara global di layout admin
    
    // Toggle password visibility
    $(document).on('click', '.toggle-password', function() {
        const input = $(this).closest('.input-group').find('input');
        const icon = $(this).find('i');
        
        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            input.attr('type', 'password');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });

    // POP user selector
    $('#selectPopUser').on('change', function() {
        const userId = $(this).val();
        window.location.href = '{{ route("admin.notification-settings.index") }}' + (userId ? '?user_id=' + userId : '');
    });

    // Email form
    $('#emailForm').on('submit', function(e) {
        e.preventDefault();
        submitForm($(this), '{{ route("admin.notification-settings.update-email") }}');
    });

    // WhatsApp form
    $('#whatsappForm').on('submit', function(e) {
        e.preventDefault();
        submitForm($(this), '{{ route("admin.notification-settings.update-whatsapp") }}');
    });

    // Telegram form
    $('#telegramForm').on('submit', function(e) {
        e.preventDefault();
        submitForm($(this), '{{ route("admin.notification-settings.update-telegram") }}');
    });

    // Events form
    $('#eventsForm').on('submit', function(e) {
        e.preventDefault();
        submitForm($(this), '{{ route("admin.notification-settings.update-events") }}');
    });

    // Templates form
    $('#templatesForm').on('submit', function(e) {
        e.preventDefault();
        submitForm($(this), '{{ route("admin.notification-settings.update-templates") }}');
    });

    function submitForm(form, url) {
        const btn = form.find('button[type="submit"]');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Menyimpan...');

        $.ajax({
            url: url,
            type: 'POST',
            data: form.serialize(),
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
                    Object.keys(errors).forEach(field => {
                        toastr.error(errors[field][0]);
                    });
                } else {
                    toastr.error('Terjadi kesalahan!');
                }
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fas fa-save mr-1"></i>Simpan');
            }
        });
    }

    // Test notifications
    let currentTestType = '';

    $('.test-email').on('click', function() {
        currentTestType = 'email';
        $('#testLabel').text('Email Tujuan');
        $('#testDestination').attr('placeholder', 'email@example.com');
        $('#testModal').modal('show');
    });

    $('.test-whatsapp').on('click', function() {
        currentTestType = 'whatsapp';
        $('#testLabel').text('Nomor WhatsApp');
        $('#testDestination').attr('placeholder', '081234567890');
        $('#testModal').modal('show');
    });

    $('.test-telegram').on('click', function() {
        currentTestType = 'telegram';
        $('#testLabel').text('Chat ID');
        $('#testDestination').attr('placeholder', '123456789');
        $('#testModal').modal('show');
    });

    $('#btnSendTest').on('click', function() {
        const btn = $(this);
        const destination = $('#testDestination').val();
        const message = $('#testMessage').val();

        if (!destination) {
            toastr.error('Masukkan alamat tujuan');
            return;
        }

        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Mengirim...');

        let url = '';
        switch(currentTestType) {
            case 'email': url = '{{ route("admin.notification-settings.test-email") }}'; break;
            case 'whatsapp': url = '{{ route("admin.notification-settings.test-whatsapp") }}'; break;
            case 'telegram': url = '{{ route("admin.notification-settings.test-telegram") }}'; break;
        }

        $.post(url, {
            _token: '{{ csrf_token() }}',
            destination: destination,
            message: message,
            user_id: '{{ $userId }}'
        }, function(response) {
            if (response.success) {
                toastr.success(response.message);
                $('#testModal').modal('hide');
            } else {
                toastr.error(response.message);
            }
        }).fail(function(xhr) {
            toastr.error(xhr.responseJSON?.message || 'Gagal mengirim notifikasi test');
        }).always(function() {
            btn.prop('disabled', false).html('<i class="fas fa-paper-plane mr-1"></i>Kirim');
        });
    });

    // Select/deselect all events
    $('#selectAllEvents').on('click', function() {
        $('input[name="enabled_events[]"]').prop('checked', true);
    });

    $('#deselectAllEvents').on('click', function() {
        $('input[name="enabled_events[]"]').prop('checked', false);
    });

    // Reset templates
    $('#resetTemplates').on('click', function() {
        Swal.fire({
            title: 'Reset Template?',
            text: 'Semua template akan dikembalikan ke default',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Reset!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('{{ route("admin.notification-settings.reset-templates") }}', {
                    _token: '{{ csrf_token() }}',
                    user_id: '{{ $userId }}'
                }, function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        toastr.error(response.message);
                    }
                });
            }
        });
    });
});
</script>
@endpush
