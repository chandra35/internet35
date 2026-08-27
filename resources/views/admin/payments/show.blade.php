@extends('layouts.admin')

@section('title', 'Proses Pembayaran')
@section('page-title', 'Proses Pembayaran')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.payments.index', auth()->user()->hasRole('superadmin') ? ['pop_id' => $popId] : []) }}">Pembayaran</a></li>
    <li class="breadcrumb-item active">{{ $customer->name }}</li>
@endsection

@push('css')
<style>
    .payment-card { border: 0; border-radius: 14px; overflow: hidden; box-shadow: 0 5px 20px rgba(26, 53, 94, .08); }
    .payment-card .card-header { padding: 15px 20px; border: 0; background: linear-gradient(135deg, #173b72, #2861ad); color: #fff; }
    .payment-card .card-title { font-weight: 600; font-size: 1rem; }
    .payment-card .card-header .btn { box-shadow: none; }
    .payment-table { font-size: .88rem; }
    .payment-table thead th { background: #f5f7fb; color: #66768b; border-top: 0; border-bottom: 1px solid #e7ecf3; font-size: .7rem; text-transform: uppercase; letter-spacing: .45px; padding: 12px 16px; white-space: nowrap; }
    .payment-table td { padding: 14px 16px; border-top-color: #edf1f6; vertical-align: middle; }
    .payment-table tbody tr { transition: background .16s ease, box-shadow .16s ease; }
    .payment-table tbody tr:hover { background: #f7faff; }
    .payment-table tbody tr.is-selected { background: #edf8f0; box-shadow: inset 3px 0 0 #28a745; }
    .payment-checkbox { width: 18px; height: 18px; cursor: pointer; accent-color: #2577d4; }
    .period-label { font-weight: 600; color: #26384e; }
    .invoice-link { font-size: .82rem; font-weight: 600; }
    .due-overdue { color: #dc3545; font-weight: 700; }
    .amount-remaining { color: #24364c; font-weight: 700; white-space: nowrap; }
    .btn-print-mini { width: 34px; height: 34px; padding: 0; border-radius: 9px; display: inline-flex; align-items: center; justify-content: center; }
    .form-card .card-header { background: #fff; color: #26384e; border-bottom: 1px solid #edf1f6; }
    .form-card .card-title { color: #26384e; }
    .form-card .form-control { border-color: #dce4ee; border-radius: 8px; min-height: 42px; }
    .form-card textarea.form-control { min-height: 88px; }
    .form-card label { color: #34465c; font-size: .84rem; font-weight: 600; }
    .payment-summary-bar { background: linear-gradient(90deg, #f3faf5, #f8fbff); border-top: 1px solid #e5edf0; padding: 16px 20px; }
    .payment-summary-bar .summary-caption { font-size: .75rem; color: #6c7a89; text-transform: uppercase; letter-spacing: .4px; }
    .payment-summary-bar .summary-total { color: #15803d; font-size: 1.25rem; font-weight: 700; line-height: 1.15; }
    .btn-record-payment { padding: 10px 18px; border-radius: 9px; font-weight: 600; box-shadow: 0 4px 12px rgba(40,167,69,.22); }
    .customer-panel { position: sticky; top: 78px; }
    .customer-panel .card-body { padding: 22px; }
    .customer-avatar { width: 54px; height: 54px; border-radius: 15px; background: linear-gradient(135deg, #3d83dc, #173b72); color: #fff; display: inline-flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1.35rem; }
    .customer-panel .customer-id { color: #7c8b9c; font-size: .8rem; }
    .customer-fact { display: flex; gap: 11px; align-items: center; padding: 11px 0; border-bottom: 1px solid #edf1f6; color: #3d4d5f; }
    .customer-fact:last-child { border-bottom: 0; }
    .customer-fact i { width: 18px; color: #2875c5; text-align: center; }
    .quick-action { border-radius: 9px; min-height: 43px; font-weight: 600; }
    @media (max-width: 991.98px) { .customer-panel { position: static; } }
    @media (max-width: 767.98px) {
        .payment-table thead { display: none; }
        .payment-table, .payment-table tbody, .payment-table tr, .payment-table td { display: block; width: 100%; }
        .payment-table tr { padding: 10px 12px; border-bottom: 1px solid #edf1f6; }
        .payment-table td { border: 0; padding: 5px 0 5px 42%; position: relative; text-align: left !important; }
        .payment-table td::before { content: attr(data-label); position: absolute; left: 0; width: 38%; color: #7c8b9c; font-size: .72rem; font-weight: 700; text-transform: uppercase; }
        .payment-table td:first-child { padding-left: 0; }
        .payment-table td:first-child::before, .payment-table td:last-child::before { display: none; }
        .payment-table td:last-child { padding-left: 0; margin-top: 4px; }
        .payment-summary-bar { align-items: stretch !important; gap: 12px; }
        .btn-record-payment { width: 100%; }
    }
</style>
@endpush

@section('content')
@php
    $initial = strtoupper(mb_substr($customer->name, 0, 1));
    $outstandingTotal = $invoices->sum(fn ($invoice) => $invoice->remaining_amount);
@endphp
<div class="row">
    <div class="col-lg-8">
        <form id="paymentForm" method="POST" action="{{ route('admin.payments.store', $customer) }}">
            @csrf
            <div class="card payment-card mb-4">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h3 class="card-title mb-0"><i class="fas fa-file-invoice-dollar mr-2"></i>Tagihan Berjalan</h3>
                    <span class="badge badge-light px-3 py-2">{{ $invoices->count() }} invoice · Rp {{ number_format($outstandingTotal, 0, ',', '.') }}</span>
                </div>
                <div class="card-body p-0">
                    @if($invoices->isEmpty())
                    <div class="text-center py-5"><i class="fas fa-check-circle text-success fa-2x mb-2"></i><p class="mb-0 text-muted">Tidak ada tunggakan yang dapat dibayarkan.</p></div>
                    @else
                    <div class="table-responsive">
                        <table class="table payment-table mb-0">
                            <thead><tr><th width="44"><input type="checkbox" id="checkAll" class="payment-checkbox" title="Pilih semua"></th><th>Periode Tagihan</th><th>Invoice</th><th>Jatuh Tempo</th><th>Status</th><th class="text-right">Sisa Tagihan</th><th class="text-center">Cetak</th></tr></thead>
                            <tbody>
                            @foreach($invoices as $invoice)
                            <tr>
                                <td data-label="Pilih"><input class="invoice-check payment-checkbox" type="checkbox" name="invoice_ids[]" value="{{ $invoice->id }}" data-amount="{{ $invoice->remaining_amount }}" {{ $selectedPeriod && $invoice->period_start?->format('Y-m') === $selectedPeriod ? 'checked' : '' }}></td>
                                <td data-label="Periode" class="period-label">{{ $invoice->period_start?->translatedFormat('F Y') ?? '—' }}</td>
                                <td data-label="Invoice"><a href="{{ route('admin.invoices.show', $invoice) }}" class="invoice-link">{{ $invoice->invoice_number }}</a></td>
                                <td data-label="Jatuh tempo" class="{{ $invoice->due_date?->isPast() ? 'due-overdue' : '' }}">{{ $invoice->due_date?->format('d/m/Y') }}</td>
                                <td data-label="Status"><span class="badge badge-{{ $invoice->status_color }} px-2 py-1">{{ $invoice->status_label }}</span></td>
                                <td data-label="Sisa tagihan" class="text-right amount-remaining">Rp {{ number_format($invoice->remaining_amount, 0, ',', '.') }}</td>
                                <td data-label=""><a href="{{ route('admin.invoices.print', $invoice) }}" target="_blank" class="btn btn-outline-secondary btn-print-mini" title="Cetak invoice"><i class="fas fa-print"></i></a></td>
                            </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                </div>
            </div>

            @if($invoices->isNotEmpty())
            @can('invoices.edit')
            <div class="card payment-card form-card mb-4">
                <div class="card-header"><h3 class="card-title mb-0"><i class="fas fa-wallet mr-2 text-success"></i>Detail Pembayaran</h3></div>
                <div class="card-body p-4">
                    <div class="form-row">
                        <div class="form-group col-md-4"><label>Metode Pembayaran</label><select name="payment_method" class="form-control" required><option value="">Pilih metode</option><option>Cash</option><option>Transfer Bank</option><option>QRIS</option><option>E-Wallet</option><option>Lainnya</option></select></div>
                        <div class="form-group col-md-4"><label>Tanggal &amp; Waktu Bayar</label><input name="paid_at" type="datetime-local" value="{{ now()->format('Y-m-d\TH:i') }}" class="form-control" required></div>
                        <div class="form-group col-md-4"><label>No. Referensi <span class="text-muted font-weight-normal">(opsional)</span></label><input name="payment_reference" class="form-control" maxlength="100" placeholder="Contoh: TRF-001"></div>
                    </div>
                    <div class="form-group mb-0"><label>Catatan <span class="text-muted font-weight-normal">(opsional)</span></label><textarea name="notes" class="form-control" maxlength="500" placeholder="Tambahkan keterangan pembayaran bila diperlukan..."></textarea></div>
                </div>
                <div class="payment-summary-bar d-flex flex-column flex-md-row align-items-md-center justify-content-between">
                    <div><div class="summary-caption">Total yang akan dicatat</div><div class="summary-total" id="selectedTotal">Rp 0</div><small class="text-muted"><span id="selectedCount">0</span> invoice dipilih</small></div>
                    <button class="btn btn-success btn-record-payment" type="submit"><i class="fas fa-check-circle mr-1"></i>Catat Pembayaran</button>
                </div>
            </div>
            @endcan
            @endif
        </form>
    </div>
    <div class="col-lg-4">
        <div class="customer-panel">
            <div class="card payment-card mb-3"><div class="card-body">
                <div class="d-flex align-items-center mb-3"><div class="customer-avatar mr-3">{{ $initial }}</div><div><h5 class="mb-1">{{ $customer->name }}</h5><div class="customer-id">{{ $customer->customer_id }}</div></div></div>
                <div class="customer-fact"><i class="fas fa-phone"></i><span>{{ $customer->phone ?: '—' }}</span></div>
                <div class="customer-fact"><i class="fas fa-user-tag"></i><span>{{ $customer->pppoe_username ?: 'Tanpa PPPoE' }}</span></div>
                <div class="customer-fact"><i class="fas fa-file-invoice-dollar"></i><span><strong>{{ $invoices->count() }}</strong> invoice belum lunas</span></div>
            </div></div>
            @if($missingPeriodCount > 0)
            @can('invoices.edit')
            <form method="POST" action="{{ route('admin.payments.generate-missing-periods', $customer) }}" class="mb-2" onsubmit="return confirm('Buat {{ $missingPeriodCount }} invoice periode yang belum ada? Invoice akan menjadi tagihan nyata.');">@csrf<button class="btn btn-warning btn-block quick-action"><i class="fas fa-calendar-plus mr-1"></i>Lengkapi {{ $missingPeriodCount }} Bulan</button></form>
            @endcan
            @endif
            @if($invoices->isNotEmpty())
            <form id="printForm" method="POST" action="{{ route('admin.payments.print', $customer) }}" target="_blank">@csrf<span id="printInvoiceIds"></span><button type="button" id="printSelected" class="btn btn-outline-primary btn-block quick-action"><i class="fas fa-print mr-1"></i>Cetak Invoice Terpilih</button></form>
            @endif
            <a href="{{ route('admin.payments.index', auth()->user()->hasRole('superadmin') ? ['pop_id' => $popId] : []) }}" class="btn btn-light border btn-block quick-action mt-2"><i class="fas fa-arrow-left mr-1"></i>Kembali ke Pembayaran</a>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
$(function () {
    const formatRupiah = value => 'Rp ' + new Intl.NumberFormat('id-ID').format(value);
    function selected() { return $('.invoice-check:checked'); }
    function updateTotal() {
        let total = 0;
        $('.invoice-check').each(function () { $(this).closest('tr').toggleClass('is-selected', this.checked); });
        selected().each(function () { total += Number($(this).data('amount')); });
        $('#selectedTotal').text(formatRupiah(total));
        $('#selectedCount').text(selected().length);
        $('#checkAll').prop('checked', $('.invoice-check').length === selected().length && selected().length > 0);
    }
    $('#checkAll').on('change', function () { $('.invoice-check').prop('checked', this.checked); updateTotal(); });
    $('.invoice-check').on('change', updateTotal);
    $('#paymentForm').on('submit', function (event) { if (!selected().length) { event.preventDefault(); toastr.warning('Pilih minimal satu invoice yang akan dibayar.'); } });
    $('#printSelected').on('click', function () {
        if (!selected().length) { toastr.warning('Pilih minimal satu invoice yang akan dicetak.'); return; }
        $('#printInvoiceIds').empty();
        selected().each(function () { $('#printInvoiceIds').append($('<input>', {type: 'hidden', name: 'invoice_ids[]', value: this.value})); });
        $('#printForm').trigger('submit');
    });
    updateTotal();
});
</script>
@endpush
