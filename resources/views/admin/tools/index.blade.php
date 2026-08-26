@extends('layouts.admin')

@section('title', 'Tools - Manajemen Data')
@section('page-title', 'Tools')

@section('breadcrumb')
    <li class="breadcrumb-item active">Tools</li>
@endsection

@push('css')
<style>
    .tool-card {
        border: none;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        transition: box-shadow 0.2s, transform 0.2s;
        height: 100%;
    }
    .tool-card:hover {
        box-shadow: 0 6px 20px rgba(0,0,0,0.13);
        transform: translateY(-2px);
    }
    .tool-card .card-body { padding: 20px; }
    .tool-icon {
        width: 52px; height: 52px; border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 22px; color: #fff; flex-shrink: 0;
        margin-bottom: 14px;
    }
    .ti-red    { background: linear-gradient(135deg, #dc3545, #c82333); }
    .ti-orange { background: linear-gradient(135deg, #e0871a, #f4a721); }
    .ti-teal   { background: linear-gradient(135deg, #00838f, #0097a7); }
    .ti-indigo { background: linear-gradient(135deg, #3949ab, #5c6bc0); }
    .ti-blue   { background: linear-gradient(135deg, #1565c0, #1976d2); }
    .tool-card h6 { font-weight: 700; font-size: 0.95rem; margin-bottom: 6px; }
    .tool-card p  { font-size: 0.82rem; color: #6c757d; margin-bottom: 14px; min-height: 44px; }
    .count-badge {
        display: inline-block; background: #f8f9fa; border: 1px solid #dee2e6;
        border-radius: 20px; padding: 3px 12px; font-size: 0.78rem;
        font-weight: 600; color: #495057; margin-bottom: 14px;
    }
    /* Modal confirm steps */
    .confirm-step { display: none; }
    .confirm-step.active { display: block; }
    .confirm-phrase {
        background: #fff3cd; border: 1px solid #ffc107; border-radius: 6px;
        padding: 8px 14px; font-weight: 700; font-size: 1rem;
        letter-spacing: 1px; display: inline-block; margin: 6px 0;
    }
    .warning-header {
        background: linear-gradient(135deg, #dc3545, #c82333);
        color: #fff; border-radius: 10px 10px 0 0;
        padding: 16px 20px;
    }
    /* Page header gradient */
    .tools-header {
        background: linear-gradient(135deg, #495057, #6c757d);
        border-radius: 10px; padding: 18px 22px; color: #fff; margin-bottom: 20px;
    }
</style>
@endpush

@section('content_header')
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1><i class="fas fa-tools"></i> Tools</h1>
        </div>
        <div class="col-sm-6">
            <div class="float-sm-right">
                <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
                </a>
            </div>
        </div>
    </div>
@endsection

@section('content')

    {{-- Alert success --}}
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif

    {{-- Info Banner --}}
    <div class="tools-header mb-4">
        <div class="d-flex align-items-start">
            <i class="fas fa-exclamation-triangle fa-2x mr-3 mt-1" style="opacity:0.7;"></i>
            <div>
                <strong style="font-size:1rem;">Area Manajemen Data</strong>
                <p class="mb-0 mt-1" style="font-size:0.83rem; opacity:0.9;">
                    Halaman ini digunakan untuk membersihkan data transaksional ketika ingin memulai dari awal.
                    <strong>Data pelanggan tidak akan dihapus.</strong>
                    Semua tindakan di sini <strong>bersifat permanen dan tidak dapat dibatalkan</strong>.
                    Diperlukan konfirmasi ketat sebelum setiap operasi dijalankan.
                </p>
            </div>
        </div>
    </div>

    <div class="row">

        {{-- ── 1. Hapus Semua Invoice + Pembayaran ─────────────────── --}}
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card tool-card">
                <div class="card-body">
                    <div class="tool-icon ti-red"><i class="fas fa-file-invoice-dollar"></i></div>
                    <h6>Hapus Semua Invoice &amp; Pembayaran</h6>
                    <p>Menghapus seluruh data invoice dan riwayat pembayaran secara permanen. Data pelanggan tetap utuh.</p>
                    <div class="count-badge">
                        <i class="fas fa-database mr-1"></i>
                        {{ number_format($counts['invoices']) }} invoice &bull;
                        {{ number_format($counts['payments']) }} pembayaran
                    </div>
                    <br>
                    <button class="btn btn-danger btn-block btn-sm mt-2"
                            data-toggle="modal" data-target="#modal-clear-invoices">
                        <i class="fas fa-trash mr-1"></i> Hapus Invoice &amp; Pembayaran
                    </button>
                </div>
            </div>
        </div>

        {{-- ── 2. Hapus Riwayat Pembayaran Saja ───────────────────── --}}
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card tool-card">
                <div class="card-body">
                    <div class="tool-icon ti-orange"><i class="fas fa-receipt"></i></div>
                    <h6>Hapus Riwayat Pembayaran Saja</h6>
                    <p>Menghapus hanya data pembayaran. Invoice tetap ada namun status pembayarannya akan hilang.</p>
                    <div class="count-badge">
                        <i class="fas fa-database mr-1"></i>
                        {{ number_format($counts['payments']) }} riwayat pembayaran
                    </div>
                    <br>
                    <button class="btn btn-warning btn-block btn-sm mt-2"
                            data-toggle="modal" data-target="#modal-clear-payments">
                        <i class="fas fa-trash mr-1"></i> Hapus Pembayaran
                    </button>
                </div>
            </div>
        </div>

        {{-- ── 3. Hapus Log Notifikasi ─────────────────────────────── --}}
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card tool-card">
                <div class="card-body">
                    <div class="tool-icon ti-teal"><i class="fas fa-bell-slash"></i></div>
                    <h6>Hapus Log Notifikasi</h6>
                    <p>Menghapus seluruh riwayat log pengiriman notifikasi (email, WhatsApp, Telegram).</p>
                    <div class="count-badge">
                        <i class="fas fa-database mr-1"></i>
                        {{ number_format($counts['notification_logs']) }} log notifikasi
                    </div>
                    <br>
                    <button class="btn btn-info btn-block btn-sm mt-2"
                            data-toggle="modal" data-target="#modal-clear-notif-logs">
                        <i class="fas fa-trash mr-1"></i> Hapus Log Notifikasi
                    </button>
                </div>
            </div>
        </div>

        {{-- ── 4. Hapus Activity Log ───────────────────────────────── --}}
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card tool-card">
                <div class="card-body">
                    <div class="tool-icon ti-indigo"><i class="fas fa-history"></i></div>
                    <h6>Hapus Activity Log</h6>
                    <p>Menghapus seluruh riwayat aktivitas akun Anda. Log akun lain tidak terpengaruh.</p>
                    <div class="count-badge">
                        <i class="fas fa-database mr-1"></i>
                        {{ number_format($counts['activity_logs']) }} log aktivitas
                    </div>
                    <br>
                    <button class="btn btn-secondary btn-block btn-sm mt-2"
                            data-toggle="modal" data-target="#modal-clear-activity-logs">
                        <i class="fas fa-trash mr-1"></i> Hapus Activity Log
                    </button>
                </div>
            </div>
        </div>

        {{-- ── 5. Reset Status Billing Pelanggan ───────────────────── --}}
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card tool-card">
                <div class="card-body">
                    <div class="tool-icon ti-blue"><i class="fas fa-users-cog"></i></div>
                    <h6>Reset Status Billing Pelanggan</h6>
                    <p>Mengatur ulang <code>active_until</code>, <code>due_date</code> menjadi kosong dan status semua pelanggan menjadi <strong>aktif</strong>.</p>
                    <div class="count-badge">
                        <i class="fas fa-database mr-1"></i>
                        {{ number_format($counts['customers_billing']) }} pelanggan dengan tanggal aktif
                    </div>
                    <br>
                    <button class="btn btn-primary btn-block btn-sm mt-2"
                            data-toggle="modal" data-target="#modal-reset-billing">
                        <i class="fas fa-sync-alt mr-1"></i> Reset Billing
                    </button>
                </div>
            </div>
        </div>

        {{-- Reset data transaksi untuk go-live (superadmin only) --}}
        @if(auth()->user()->hasRole('superadmin'))
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card tool-card border border-danger">
                <div class="card-body">
                    <div class="tool-icon ti-red"><i class="fas fa-power-off"></i></div>
                    <h6>Reset Data Transaksi (Siap Operasional)</h6>
                    <p>Mengosongkan invoice, pembayaran, log notifikasi, dan counter scheduler agar penomoran transaksi dimulai kembali dari awal.</p>
                    <div class="count-badge">
                        <i class="fas fa-database mr-1"></i>
                        {{ number_format($counts['invoices']) }} invoice &bull;
                        {{ number_format($counts['payments']) }} pembayaran &bull;
                        {{ number_format($counts['scheduler_logs']) }} log task
                    </div>
                    <br>
                    <button class="btn btn-danger btn-block btn-sm mt-2"
                            data-toggle="modal" data-target="#modal-reset-transactional">
                        <i class="fas fa-broom mr-1"></i> Reset Data Transaksi
                    </button>
                </div>
            </div>
        </div>
        @endif

    </div>{{-- end .row --}}


    {{-- ================================================================
         MODALS
    ================================================================= --}}

    {{-- Modal: Hapus Invoice + Pembayaran --}}
    <div class="modal fade" id="modal-clear-invoices" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content" style="border-radius:10px; overflow:hidden;">
                <div class="warning-header">
                    <h5 class="mb-1"><i class="fas fa-exclamation-triangle mr-2"></i>Konfirmasi Hapus Invoice &amp; Pembayaran</h5>
                    <small style="opacity:.85;">Tindakan ini tidak dapat dibatalkan</small>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger py-2 mb-3">
                        <strong>Yang akan dihapus secara permanen:</strong>
                        <ul class="mb-0 mt-1 pl-3" style="font-size:.85rem;">
                            <li>{{ number_format($counts['invoices']) }} invoice</li>
                            <li>{{ number_format($counts['payments']) }} riwayat pembayaran</li>
                        </ul>
                    </div>
                    <form id="form-clear-invoices" method="POST" action="{{ route('admin.tools.clear-invoices') }}">
                        @csrf
                        <p class="mb-2" style="font-size:.85rem;">Ketik <span class="confirm-phrase">HAPUS DATA</span> untuk mengkonfirmasi:</p>
                        <input type="text" name="confirm_text" id="inp-clear-invoices"
                               class="form-control form-control-sm mb-3" placeholder="Ketik: HAPUS DATA"
                               autocomplete="off" spellcheck="false">
                        <div class="form-check mb-3">
                            <input type="checkbox" class="form-check-input" id="chk-clear-invoices">
                            <label class="form-check-label" for="chk-clear-invoices" style="font-size:.82rem;">
                                Saya memahami tindakan ini <strong>permanen dan tidak dapat dibatalkan</strong>
                            </label>
                        </div>
                        <div class="d-flex justify-content-between">
                            <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Batal</button>
                            <button type="submit" id="btn-clear-invoices" class="btn btn-sm btn-danger" disabled>
                                <i class="fas fa-trash mr-1"></i> Ya, Hapus Sekarang
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal: Hapus Pembayaran Saja --}}
    <div class="modal fade" id="modal-clear-payments" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content" style="border-radius:10px; overflow:hidden;">
                <div class="warning-header">
                    <h5 class="mb-1"><i class="fas fa-exclamation-triangle mr-2"></i>Konfirmasi Hapus Riwayat Pembayaran</h5>
                    <small style="opacity:.85;">Tindakan ini tidak dapat dibatalkan</small>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning py-2 mb-3">
                        <strong>Yang akan dihapus secara permanen:</strong>
                        <ul class="mb-0 mt-1 pl-3" style="font-size:.85rem;">
                            <li>{{ number_format($counts['payments']) }} riwayat pembayaran</li>
                        </ul>
                        <small class="text-muted">Invoice tidak akan dihapus.</small>
                    </div>
                    <form id="form-clear-payments" method="POST" action="{{ route('admin.tools.clear-payments') }}">
                        @csrf
                        <p class="mb-2" style="font-size:.85rem;">Ketik <span class="confirm-phrase">HAPUS DATA</span> untuk mengkonfirmasi:</p>
                        <input type="text" name="confirm_text" id="inp-clear-payments"
                               class="form-control form-control-sm mb-3" placeholder="Ketik: HAPUS DATA"
                               autocomplete="off" spellcheck="false">
                        <div class="form-check mb-3">
                            <input type="checkbox" class="form-check-input" id="chk-clear-payments">
                            <label class="form-check-label" for="chk-clear-payments" style="font-size:.82rem;">
                                Saya memahami tindakan ini <strong>permanen dan tidak dapat dibatalkan</strong>
                            </label>
                        </div>
                        <div class="d-flex justify-content-between">
                            <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Batal</button>
                            <button type="submit" id="btn-clear-payments" class="btn btn-sm btn-warning" disabled>
                                <i class="fas fa-trash mr-1"></i> Ya, Hapus Sekarang
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal: Hapus Log Notifikasi --}}
    <div class="modal fade" id="modal-clear-notif-logs" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content" style="border-radius:10px; overflow:hidden;">
                <div class="warning-header">
                    <h5 class="mb-1"><i class="fas fa-exclamation-triangle mr-2"></i>Konfirmasi Hapus Log Notifikasi</h5>
                    <small style="opacity:.85;">Tindakan ini tidak dapat dibatalkan</small>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info py-2 mb-3">
                        <strong>Yang akan dihapus:</strong>
                        <ul class="mb-0 mt-1 pl-3" style="font-size:.85rem;">
                            <li>{{ number_format($counts['notification_logs']) }} log notifikasi</li>
                        </ul>
                    </div>
                    <form id="form-clear-notif-logs" method="POST" action="{{ route('admin.tools.clear-notification-logs') }}">
                        @csrf
                        <p class="mb-2" style="font-size:.85rem;">Ketik <span class="confirm-phrase">HAPUS DATA</span> untuk mengkonfirmasi:</p>
                        <input type="text" name="confirm_text" id="inp-clear-notif-logs"
                               class="form-control form-control-sm mb-3" placeholder="Ketik: HAPUS DATA"
                               autocomplete="off" spellcheck="false">
                        <div class="form-check mb-3">
                            <input type="checkbox" class="form-check-input" id="chk-clear-notif-logs">
                            <label class="form-check-label" for="chk-clear-notif-logs" style="font-size:.82rem;">
                                Saya memahami tindakan ini <strong>permanen dan tidak dapat dibatalkan</strong>
                            </label>
                        </div>
                        <div class="d-flex justify-content-between">
                            <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Batal</button>
                            <button type="submit" id="btn-clear-notif-logs" class="btn btn-sm btn-info" disabled>
                                <i class="fas fa-trash mr-1"></i> Ya, Hapus Sekarang
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal: Hapus Activity Log --}}
    <div class="modal fade" id="modal-clear-activity-logs" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content" style="border-radius:10px; overflow:hidden;">
                <div class="warning-header">
                    <h5 class="mb-1"><i class="fas fa-exclamation-triangle mr-2"></i>Konfirmasi Hapus Activity Log</h5>
                    <small style="opacity:.85;">Tindakan ini tidak dapat dibatalkan</small>
                </div>
                <div class="modal-body">
                    <div class="alert alert-secondary py-2 mb-3">
                        <strong>Yang akan dihapus:</strong>
                        <ul class="mb-0 mt-1 pl-3" style="font-size:.85rem;">
                            <li>{{ number_format($counts['activity_logs']) }} log aktivitas akun Anda</li>
                        </ul>
                        <small class="text-muted">Log aktivitas akun lain tidak terpengaruh.</small>
                    </div>
                    <form id="form-clear-activity-logs" method="POST" action="{{ route('admin.tools.clear-activity-logs') }}">
                        @csrf
                        <p class="mb-2" style="font-size:.85rem;">Ketik <span class="confirm-phrase">HAPUS DATA</span> untuk mengkonfirmasi:</p>
                        <input type="text" name="confirm_text" id="inp-clear-activity-logs"
                               class="form-control form-control-sm mb-3" placeholder="Ketik: HAPUS DATA"
                               autocomplete="off" spellcheck="false">
                        <div class="form-check mb-3">
                            <input type="checkbox" class="form-check-input" id="chk-clear-activity-logs">
                            <label class="form-check-label" for="chk-clear-activity-logs" style="font-size:.82rem;">
                                Saya memahami tindakan ini <strong>permanen dan tidak dapat dibatalkan</strong>
                            </label>
                        </div>
                        <div class="d-flex justify-content-between">
                            <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Batal</button>
                            <button type="submit" id="btn-clear-activity-logs" class="btn btn-sm btn-dark" disabled>
                                <i class="fas fa-trash mr-1"></i> Ya, Hapus Sekarang
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal: Reset Status Billing --}}
    <div class="modal fade" id="modal-reset-billing" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content" style="border-radius:10px; overflow:hidden;">
                <div class="warning-header" style="background: linear-gradient(135deg, #1565c0, #1976d2);">
                    <h5 class="mb-1"><i class="fas fa-exclamation-triangle mr-2"></i>Konfirmasi Reset Status Billing</h5>
                    <small style="opacity:.85;">Tindakan ini tidak dapat dibatalkan</small>
                </div>
                <div class="modal-body">
                    <div class="alert alert-primary py-2 mb-3">
                        <strong>Yang akan direset untuk semua pelanggan:</strong>
                        <ul class="mb-0 mt-1 pl-3" style="font-size:.85rem;">
                            <li><code>active_until</code> → <em>kosong</em></li>
                            <li><code>due_date</code> → <em>kosong</em></li>
                            <li><code>status</code> → <em>active</em></li>
                        </ul>
                        <small class="text-muted">Data identitas &amp; koneksi pelanggan tidak berubah.</small>
                    </div>
                    <form id="form-reset-billing" method="POST" action="{{ route('admin.tools.reset-billing') }}">
                        @csrf
                        <p class="mb-2" style="font-size:.85rem;">Ketik <span class="confirm-phrase">HAPUS DATA</span> untuk mengkonfirmasi:</p>
                        <input type="text" name="confirm_text" id="inp-reset-billing"
                               class="form-control form-control-sm mb-3" placeholder="Ketik: HAPUS DATA"
                               autocomplete="off" spellcheck="false">
                        <div class="form-check mb-3">
                            <input type="checkbox" class="form-check-input" id="chk-reset-billing">
                            <label class="form-check-label" for="chk-reset-billing" style="font-size:.82rem;">
                                Saya memahami tindakan ini <strong>permanen dan tidak dapat dibatalkan</strong>
                            </label>
                        </div>
                        <div class="d-flex justify-content-between">
                            <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Batal</button>
                            <button type="submit" id="btn-reset-billing" class="btn btn-sm btn-primary" disabled>
                                <i class="fas fa-sync-alt mr-1"></i> Ya, Reset Sekarang
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal: Reset Data Transaksi --}}
    @if(auth()->user()->hasRole('superadmin'))
    <div class="modal fade" id="modal-reset-transactional" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content" style="border-radius:10px; overflow:hidden;">
                <div class="warning-header">
                    <h5 class="mb-1"><i class="fas fa-radiation-alt mr-2"></i>Konfirmasi Reset Data Transaksi</h5>
                    <small style="opacity:.85;">Hanya lakukan sebelum aplikasi mulai digunakan</small>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger py-2 mb-3">
                        <strong>Yang akan dihapus atau direset:</strong>
                        <ul class="mb-0 mt-1 pl-3" style="font-size:.85rem;">
                            <li>Invoice dan pembayaran</li>
                            <li>Log notifikasi dan log eksekusi scheduler</li>
                            <li>Counter invoice/pembayaran dan counter task scheduler</li>
                            <li>Tanggal <code>active_until</code> dan <code>due_date</code></li>
                        </ul>
                        <small class="d-block mt-2"><strong>Tidak diubah:</strong> pelanggan, user, paket, router, OLT/ONU, jaringan, PPP secret MikroTik, dan status layanan pelanggan.</small>
                    </div>
                    <form id="form-reset-transactional" method="POST" action="{{ route('admin.tools.reset-transactional-data') }}">
                        @csrf
                        <p class="mb-2" style="font-size:.85rem;">Ketik <span class="confirm-phrase">RESET TRANSAKSI</span> untuk mengkonfirmasi:</p>
                        <input type="text" name="confirm_text" id="inp-reset-transactional"
                               class="form-control form-control-sm mb-3" placeholder="Ketik: RESET TRANSAKSI"
                               autocomplete="off" spellcheck="false">
                        <div class="form-check mb-3">
                            <input type="checkbox" class="form-check-input" id="chk-reset-transactional">
                            <label class="form-check-label" for="chk-reset-transactional" style="font-size:.82rem;">
                                Saya sudah memiliki backup dan memahami transaksi tidak dapat dipulihkan dari aplikasi
                            </label>
                        </div>
                        <div class="d-flex justify-content-between">
                            <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Batal</button>
                            <button type="submit" id="btn-reset-transactional" class="btn btn-sm btn-danger" disabled>
                                <i class="fas fa-broom mr-1"></i> Ya, Reset Transaksi
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endif

@endsection

@push('js')
<script>
$(function () {
    // Generic confirm-gate: enable submit button only when phrase matches + checkbox checked
    var gates = [
        { inp: '#inp-clear-invoices',     chk: '#chk-clear-invoices',     btn: '#btn-clear-invoices' },
        { inp: '#inp-clear-payments',     chk: '#chk-clear-payments',     btn: '#btn-clear-payments' },
        { inp: '#inp-clear-notif-logs',   chk: '#chk-clear-notif-logs',   btn: '#btn-clear-notif-logs' },
        { inp: '#inp-clear-activity-logs',chk: '#chk-clear-activity-logs',btn: '#btn-clear-activity-logs' },
        { inp: '#inp-reset-billing',      chk: '#chk-reset-billing',      btn: '#btn-reset-billing' },
        { inp: '#inp-reset-transactional',chk: '#chk-reset-transactional',btn: '#btn-reset-transactional', phrase: 'RESET TRANSAKSI' },
    ];

    gates.forEach(function (g) {
        function check() {
            var phraseOk = $(g.inp).val() === (g.phrase || 'HAPUS DATA');
            var chkOk    = $(g.chk).is(':checked');
            $(g.btn).prop('disabled', !(phraseOk && chkOk));
        }
        $(g.inp).on('input', check);
        $(g.chk).on('change', check);
    });

    // Reset modal state when closed
    $('.modal').on('hidden.bs.modal', function () {
        $(this).find('input[type=text]').val('');
        $(this).find('input[type=checkbox]').prop('checked', false);
        $(this).find('button[type=submit]').prop('disabled', true);
    });
});
</script>
@endpush
