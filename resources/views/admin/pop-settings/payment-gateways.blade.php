@extends('layouts.admin')

@section('title', 'Payment Gateway')

@section('page-title', 'Pengaturan Payment Gateway')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">Payment Gateway</li>
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

        <!-- Add New Gateway -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-plus-circle mr-2"></i>Tambah Payment Gateway</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    @foreach(\App\Models\PaymentGateway::gatewayTypes() as $type => $info)
                        @php
                            $existing = $gateways->where('gateway_type', $type)->first();
                        @endphp
                        <div class="col-md-4 col-lg-2 mb-3">
                            <div class="card h-100 {{ $existing ? 'border-success' : '' }}">
                                <div class="card-body text-center p-3">
                                    @if($info['logo'])
                                    <img src="{{ asset('images/payment/' . $info['logo']) }}" alt="{{ $info['name'] }}" 
                                         style="height: 30px; max-width: 100%;" class="mb-2"
                                         onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                    <i class="fas fa-credit-card fa-2x text-muted mb-2" style="display: none;"></i>
                                    @else
                                    <i class="fas fa-credit-card fa-2x text-muted mb-2"></i>
                                    @endif
                                    <h6 class="mb-1">{{ $info['name'] }}</h6>
                                    @if($existing)
                                        <span class="badge badge-{{ $existing->is_active ? 'success' : 'secondary' }}">
                                            {{ $existing->is_active ? 'Aktif' : 'Nonaktif' }}
                                        </span>
                                        <div class="mt-2">
                                            <button type="button" class="btn btn-xs btn-outline-primary edit-gateway" 
                                                    data-id="{{ $existing->id }}">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        </div>
                                    @else
                                        <button type="button" class="btn btn-sm btn-primary add-gateway mt-2" 
                                                data-type="{{ $type }}">
                                            <i class="fas fa-plus mr-1"></i>Aktifkan
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Active Gateways -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-credit-card mr-2"></i>Payment Gateway Aktif</h3>
            </div>
            <div class="card-body p-0">
                @if($gateways->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Gateway</th>
                                <th>Mode</th>
                                <th>Status Sandbox</th>
                                <th>Status</th>
                                <th class="text-center" width="150">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($gateways as $gateway)
                            <tr>
                                <td>
                                    <strong>{{ $gateway->gateway_name }}</strong>
                                    @if($gateway->is_production)
                                    <span class="badge badge-success ml-1">LIVE</span>
                                    @endif
                                </td>
                                <td>
                                    @if($gateway->is_sandbox)
                                        <span class="badge badge-warning">Sandbox</span>
                                    @else
                                        <span class="badge badge-success">Production</span>
                                    @endif
                                </td>
                                <td>{!! $gateway->sandbox_status_badge !!}</td>
                                <td>
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input toggle-active" 
                                               id="active_{{ $gateway->id }}" 
                                               data-id="{{ $gateway->id }}"
                                               {{ $gateway->is_active ? 'checked' : '' }}
                                               {{ !$gateway->canEnable() ? 'disabled' : '' }}>
                                        <label class="custom-control-label" for="active_{{ $gateway->id }}">
                                            {{ $gateway->is_active ? 'Aktif' : 'Nonaktif' }}
                                        </label>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-info test-gateway" 
                                                data-id="{{ $gateway->id }}" title="Test Koneksi">
                                            <i class="fas fa-plug"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-primary edit-gateway" 
                                                data-id="{{ $gateway->id }}" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger delete-gateway" 
                                                data-id="{{ $gateway->id }}" title="Hapus">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-5">
                    <i class="fas fa-credit-card fa-4x text-muted mb-3"></i>
                    <h5>Belum ada Payment Gateway</h5>
                    <p class="text-muted">Aktifkan salah satu payment gateway di atas untuk menerima pembayaran online.</p>
                </div>
                @endif
            </div>
        </div>

        <!-- Info Cards -->
        <div class="row">
            <div class="col-md-6">
                <div class="card card-outline card-info">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-info-circle mr-2"></i>Tentang Sandbox</h3>
                    </div>
                    <div class="card-body">
                        <p class="mb-2"><strong>Sandbox Mode</strong> digunakan untuk pengujian sebelum go live.</p>
                        <ul class="pl-3 mb-0">
                            <li>Gunakan kredensial sandbox dari dashboard masing-masing gateway</li>
                            <li>Transaksi tidak akan diproses secara nyata</li>
                            <li>Beberapa gateway (seperti Duitku) memerlukan verifikasi sebelum bisa menggunakan mode production</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card card-outline card-warning">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-exclamation-triangle mr-2"></i>Penting</h3>
                    </div>
                    <div class="card-body">
                        <ul class="pl-3 mb-0">
                            <li>Pastikan kredensial API sudah benar sebelum mengaktifkan</li>
                            <li>Selalu test koneksi setelah menyimpan pengaturan</li>
                            <li>Jangan share kredensial API dengan siapapun</li>
                            <li>Simpan Merchant ID dan Secret Key di tempat yang aman</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Gateway Form Modal -->
<div class="modal fade" id="gatewayModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="gatewayForm">
                @csrf
                <input type="hidden" name="id" id="gateway_id">
                <input type="hidden" name="gateway_type" id="gateway_type">
                @if($userId)
                <input type="hidden" name="user_id" value="{{ $userId }}">
                @endif
                
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-credit-card mr-2"></i><span id="modal_title">Konfigurasi Payment Gateway</span></h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div id="gateway_form_fields">
                        <!-- Dynamic fields will be loaded here -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-info" id="btnTestConnection">
                        <i class="fas fa-plug mr-1"></i>Test Koneksi
                    </button>
                    <button type="submit" class="btn btn-primary" id="btnSaveGateway">
                        <i class="fas fa-save mr-1"></i>Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Sandbox Request Modal -->
<div class="modal fade" id="sandboxModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="sandboxForm" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="gateway_id" id="sandbox_gateway_id">
                
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-file-upload mr-2"></i>Request Verifikasi Sandbox</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-2"></i>
                        Upload dokumen yang diperlukan untuk verifikasi sandbox. Tim akan mereview dalam 1-3 hari kerja.
                    </div>
                    <div id="sandbox_documents">
                        <!-- Dynamic document fields -->
                    </div>
                    <div class="form-group">
                        <label>Catatan Tambahan</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Tambahkan catatan jika diperlukan"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane mr-1"></i>Kirim Request
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
    // Select2 sudah diinisialisasi secara global di layout admin

    // Gateway configurations
    const gatewayFields = {!! json_encode(\App\Models\PaymentGateway::credentialFields()) !!};
    const gatewayTypes = {!! json_encode(\App\Models\PaymentGateway::gatewayTypes()) !!};

    // POP user selector
    $('#selectPopUser').on('change', function() {
        const userId = $(this).val();
        window.location.href = '{{ route("admin.payment-gateways.index") }}' + (userId ? '?user_id=' + userId : '');
    });

    // Add new gateway
    $('.add-gateway').on('click', function() {
        const type = $(this).data('type');
        openGatewayModal(type);
    });

    // Edit gateway
    $(document).on('click', '.edit-gateway', function() {
        const id = $(this).data('id');
        loadGateway(id);
    });

    function openGatewayModal(type, data = null) {
        const info = gatewayTypes[type];
        const fields = gatewayFields[type];
        
        $('#gateway_id').val(data ? data.id : '');
        $('#gateway_type').val(type);
        $('#modal_title').text((data ? 'Edit ' : 'Konfigurasi ') + info.name);

        let html = '';
        
        // Common fields
        html += `
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Mode</label>
                        <select name="is_sandbox" class="form-control select2" id="sandbox_mode">
                            <option value="1" ${data && !data.is_sandbox ? '' : 'selected'}>Sandbox (Testing)</option>
                            <option value="0" ${data && !data.is_sandbox ? 'selected' : ''}>Production (Live)</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Status</label>
                        <select name="is_active" class="form-control select2">
                            <option value="1" ${data && data.is_active ? 'selected' : ''}>Aktif</option>
                            <option value="0" ${data && !data.is_active ? 'selected' : ''}>Nonaktif</option>
                        </select>
                    </div>
                </div>
            </div>
            <hr>
            <h6 class="mb-3"><i class="fas fa-key mr-2"></i>Kredensial API</h6>
        `;

        // Dynamic credential fields
        fields.forEach(field => {
            const value = data?.credentials?.[field.key] || '';
            const inputType = field.key.includes('secret') || field.key.includes('password') ? 'password' : 'text';
            const required = field.required ? 'required' : '';
            
            html += `
                <div class="form-group">
                    <label>${field.label} ${field.required ? '<span class="text-danger">*</span>' : ''}</label>
                    <div class="input-group">
                        <input type="${inputType}" name="credentials[${field.key}]" class="form-control" 
                               value="${value}" ${required} placeholder="${field.placeholder || ''}">
                        ${inputType === 'password' ? `
                        <div class="input-group-append">
                            <button type="button" class="btn btn-outline-secondary toggle-password" tabindex="-1">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        ` : ''}
                    </div>
                    ${field.help ? `<small class="text-muted">${field.help}</small>` : ''}
                </div>
            `;
        });

        // Callback URL info
        html += `
            <hr>
            <div class="alert alert-secondary">
                <h6><i class="fas fa-link mr-2"></i>Callback URL</h6>
                <p class="mb-1">Gunakan URL berikut di dashboard ${info.name}:</p>
                <code class="d-block p-2 bg-dark text-white rounded">{{ url('/') }}/payment/callback/${type}</code>
            </div>
        `;

        $('#gateway_form_fields').html(html);
        $('#gatewayModal').modal('show');
    }

    function loadGateway(id) {
        $.get(`{{ url('admin/payment-gateways') }}/${id}/edit`, function(data) {
            openGatewayModal(data.gateway_type, data);
        }).fail(function() {
            toastr.error('Gagal memuat data gateway');
        });
    }

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

    // Save gateway
    $('#gatewayForm').on('submit', function(e) {
        e.preventDefault();
        
        const id = $('#gateway_id').val();
        const url = id ? `{{ url('admin/payment-gateways') }}/${id}` : '{{ route("admin.payment-gateways.store") }}';
        const method = id ? 'PUT' : 'POST';
        
        const btn = $('#btnSaveGateway');
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Menyimpan...');

        $.ajax({
            url: url,
            type: method,
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    $('#gatewayModal').modal('hide');
                    setTimeout(() => location.reload(), 1000);
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
    });

    // Test connection
    $('#btnTestConnection, .test-gateway').on('click', function() {
        let id = $(this).data('id') || $('#gateway_id').val();
        
        if (!id && $('#gateway_type').val()) {
            // New gateway, save first
            toastr.warning('Simpan gateway terlebih dahulu sebelum test koneksi');
            return;
        }
        
        if (!id) {
            toastr.warning('Pilih gateway terlebih dahulu');
            return;
        }

        const btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

        $.post(`{{ url('admin/payment-gateways') }}/${id}/test`, {
            _token: '{{ csrf_token() }}'
        }, function(response) {
            if (response.success) {
                toastr.success(response.message);
            } else {
                toastr.error(response.message);
            }
        }).fail(function(xhr) {
            toastr.error(xhr.responseJSON?.message || 'Test koneksi gagal');
        }).always(function() {
            btn.prop('disabled', false);
            if (btn.hasClass('test-gateway')) {
                btn.html('<i class="fas fa-plug"></i>');
            } else {
                btn.html('<i class="fas fa-plug mr-1"></i>Test Koneksi');
            }
        });
    });

    // Toggle active
    $('.toggle-active').on('change', function() {
        const id = $(this).data('id');
        const checkbox = $(this);
        
        $.post(`{{ url('admin/payment-gateways') }}/${id}/toggle`, {
            _token: '{{ csrf_token() }}'
        }, function(response) {
            if (response.success) {
                toastr.success(response.message);
                checkbox.next('label').text(response.is_active ? 'Aktif' : 'Nonaktif');
            } else {
                toastr.error(response.message);
                checkbox.prop('checked', !checkbox.prop('checked'));
            }
        }).fail(function(xhr) {
            toastr.error(xhr.responseJSON?.message || 'Gagal mengubah status');
            checkbox.prop('checked', !checkbox.prop('checked'));
        });
    });

    // Delete gateway
    $('.delete-gateway').on('click', function() {
        const id = $(this).data('id');
        
        Swal.fire({
            title: 'Hapus Gateway?',
            text: 'Data gateway akan dihapus permanen',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `{{ url('admin/payment-gateways') }}/${id}`,
                    type: 'DELETE',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message);
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            toastr.error(response.message);
                        }
                    },
                    error: function() {
                        toastr.error('Gagal menghapus gateway');
                    }
                });
            }
        });
    });
});
</script>
@endpush
