@extends('layouts.admin')

@section('title', 'Pengaturan Semua POP')

@section('page-title', 'Pengaturan Semua POP')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">Pengaturan POP</li>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-3">
        @include('admin.pop-settings.partials.sidebar')
    </div>
    <div class="col-lg-9">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-building mr-2"></i>Daftar Pengaturan POP</h3>
                <div class="card-tools">
                    <div class="input-group input-group-sm" style="width: 250px;">
                        <input type="text" name="search" class="form-control" placeholder="Cari..." id="searchInput">
                        <div class="input-group-append">
                            <button type="button" class="btn btn-default">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="popTable">
                        <thead class="bg-light">
                            <tr>
                                <th>Admin POP</th>
                                <th>Nama ISP</th>
                                <th>POP</th>
                                <th>Payment Gateway</th>
                                <th>Notifikasi</th>
                                <th class="text-center" width="100">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($popUsers as $user)
                            @php
                                $setting = $user->popSetting;
                                $gatewayCount = $user->paymentGateways()->where('is_active', true)->count();
                                $notif = $user->notificationSetting;
                            @endphp
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="{{ $user->avatar_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($user->name) }}" 
                                             class="img-circle mr-2" style="width: 32px; height: 32px;">
                                        <div>
                                            <strong>{{ $user->name }}</strong>
                                            <br><small class="text-muted">{{ $user->email }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if($setting && $setting->isp_name)
                                        @if($setting->isp_logo)
                                        <img src="{{ $setting->logo_url }}" style="height: 24px;" class="mr-1">
                                        @endif
                                        {{ $setting->isp_name }}
                                    @else
                                        <span class="text-muted">Belum diatur</span>
                                    @endif
                                </td>
                                <td>
                                    @if($setting && $setting->pop_name)
                                        <span class="badge badge-info">{{ $setting->pop_code ?? '-' }}</span>
                                        {{ $setting->pop_name }}
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    @if($gatewayCount > 0)
                                        <span class="badge badge-success">{{ $gatewayCount }} aktif</span>
                                    @else
                                        <span class="badge badge-secondary">0</span>
                                    @endif
                                </td>
                                <td>
                                    @if($notif)
                                        @if($notif->email_enabled)
                                        <span class="badge badge-primary" title="Email"><i class="fas fa-envelope"></i></span>
                                        @endif
                                        @if($notif->whatsapp_enabled)
                                        <span class="badge badge-success" title="WhatsApp"><i class="fab fa-whatsapp"></i></span>
                                        @endif
                                        @if($notif->telegram_enabled)
                                        <span class="badge badge-info" title="Telegram"><i class="fab fa-telegram"></i></span>
                                        @endif
                                        @if(!$notif->email_enabled && !$notif->whatsapp_enabled && !$notif->telegram_enabled)
                                        <span class="text-muted">-</span>
                                        @endif
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="btn-group">
                                        <a href="{{ route('admin.pop-settings.isp-info', ['user_id' => $user->id]) }}" 
                                           class="btn btn-sm btn-info" title="Lihat/Edit">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-warning copy-settings" 
                                                data-id="{{ $user->id }}" data-name="{{ $user->name }}" title="Salin ke...">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                    <h5>Tidak ada Admin POP</h5>
                                    <p class="text-muted">Belum ada user dengan role admin-pop</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($popUsers->hasPages())
            <div class="card-footer">
                {{ $popUsers->links() }}
            </div>
            @endif
        </div>

        <!-- Statistics -->
        <div class="row">
            <div class="col-md-3">
                <div class="info-box">
                    <span class="info-box-icon bg-info"><i class="fas fa-users"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Total Admin POP</span>
                        <span class="info-box-number">{{ $popUsers->total() }}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="info-box">
                    <span class="info-box-icon bg-success"><i class="fas fa-building"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">ISP Terdaftar</span>
                        <span class="info-box-number">{{ \App\Models\PopSetting::whereNotNull('isp_name')->count() }}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="info-box">
                    <span class="info-box-icon bg-warning"><i class="fas fa-credit-card"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Gateway Aktif</span>
                        <span class="info-box-number">{{ \App\Models\PaymentGateway::where('is_active', true)->count() }}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="info-box">
                    <span class="info-box-icon bg-danger"><i class="fas fa-clock"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Pending Sandbox</span>
                        <span class="info-box-number">{{ \App\Models\PaymentGateway::where('sandbox_status', 'pending')->count() }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Copy Settings Modal -->
<div class="modal fade" id="copyModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="copyForm">
                @csrf
                <input type="hidden" name="source_user_id" id="source_user_id">
                
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-copy mr-2"></i>Salin Pengaturan</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i>
                        Menyalin pengaturan dari <strong id="sourceName"></strong>
                    </div>
                    
                    <div class="form-group">
                        <label>Target User</label>
                        <select name="target_user_id" class="form-control select2" id="targetUser" required style="width: 100%;">
                            <option value="">-- Pilih Target --</option>
                            @foreach($popUsers as $user)
                            <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Yang akan disalin:</label>
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="copy_isp" name="copy_sections[]" value="isp" checked>
                            <label class="custom-control-label" for="copy_isp">Informasi ISP (tanpa logo)</label>
                        </div>
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="copy_invoice" name="copy_sections[]" value="invoice" checked>
                            <label class="custom-control-label" for="copy_invoice">Pengaturan Invoice & Pajak</label>
                        </div>
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="copy_notifications" name="copy_sections[]" value="notifications" checked>
                            <label class="custom-control-label" for="copy_notifications">Pengaturan Notifikasi (tanpa API key)</label>
                        </div>
                    </div>

                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <strong>Perhatian:</strong> Payment Gateway tidak dapat disalin karena memiliki kredensial unik per merchant.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-copy mr-1"></i>Salin Pengaturan
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
    // Reinitialize select2 inside modal with dropdownParent
    $('#copyModal').on('shown.bs.modal', function() {
        $(this).find('.select2').select2({ theme: 'bootstrap-5', dropdownParent: $('#copyModal') });
    });

    // Search
    $('#searchInput').on('keyup', function() {
        const value = $(this).val().toLowerCase();
        $('#popTable tbody tr').filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
    });

    // Copy settings
    $('.copy-settings').on('click', function() {
        const userId = $(this).data('id');
        const userName = $(this).data('name');
        
        $('#source_user_id').val(userId);
        $('#sourceName').text(userName);
        $('#targetUser').val('').trigger('change');
        $('#targetUser option[value="' + userId + '"]').prop('disabled', true);
        
        $('#copyModal').modal('show');
    });

    $('#copyModal').on('hidden.bs.modal', function() {
        $('#targetUser option').prop('disabled', false);
    });

    $('#copyForm').on('submit', function(e) {
        e.preventDefault();
        
        const btn = $(this).find('button[type="submit"]');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Menyalin...');

        $.ajax({
            url: '{{ route("admin.pop-settings.copy") }}',
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    $('#copyModal').modal('hide');
                } else {
                    toastr.error(response.message);
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Gagal menyalin pengaturan');
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="fas fa-copy mr-1"></i>Salin Pengaturan');
            }
        });
    });
});
</script>
@endpush
