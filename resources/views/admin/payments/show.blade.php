@extends('layouts.admin')

@section('title', 'Proses Pembayaran')
@section('page-title', 'Proses Pembayaran')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.payments.index', auth()->user()->hasRole('superadmin') ? ['pop_id' => $popId] : []) }}">Pembayaran</a></li>
    <li class="breadcrumb-item active">{{ $customer->name }}</li>
@endsection

@section('content')
<div class="row">
    <div class="col-lg-8">
        <form id="paymentForm" method="POST" action="{{ route('admin.payments.store', $customer) }}">
            @csrf
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-file-invoice-dollar mr-2"></i>Tunggakan {{ $customer->name }}</h3>
                    @if($missingPeriodCount > 0)
                    <div class="card-tools">
                        @can('invoices.edit')
                        <form method="POST" action="{{ route('admin.payments.generate-missing-periods', $customer) }}" class="d-inline" onsubmit="return confirm('Buat {{ $missingPeriodCount }} invoice periode yang belum ada? Invoice akan menjadi tagihan nyata.');">
                            @csrf
                            <button class="btn btn-warning btn-sm"><i class="fas fa-calendar-plus mr-1"></i>Lengkapi {{ $missingPeriodCount }} Bulan</button>
                        </form>
                        @endcan
                    </div>
                    @endif
                </div>
                <div class="card-body p-0">
                    @if($invoices->isEmpty())
                    <div class="alert alert-success m-3 mb-0"><i class="fas fa-check-circle mr-1"></i>Tidak ada tunggakan yang dapat dibayarkan.</div>
                    @else
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="thead-light"><tr><th width="42"><input type="checkbox" id="checkAll"></th><th>Periode Tagihan</th><th>Invoice</th><th>Jatuh Tempo</th><th>Status</th><th class="text-right">Sisa Tagihan</th><th class="text-center">Cetak</th></tr></thead>
                            <tbody>
                            @foreach($invoices as $invoice)
                            <tr>
                                <td><input class="invoice-check" type="checkbox" name="invoice_ids[]" value="{{ $invoice->id }}" data-amount="{{ $invoice->remaining_amount }}"></td>
                                <td>{{ $invoice->period_start?->translatedFormat('F Y') ?? '—' }}</td>
                                <td><a href="{{ route('admin.invoices.show', $invoice) }}">{{ $invoice->invoice_number }}</a></td>
                                <td class="{{ $invoice->due_date?->isPast() ? 'text-danger font-weight-bold' : '' }}">{{ $invoice->due_date?->format('d/m/Y') }}</td>
                                <td><span class="badge badge-{{ $invoice->status_color }}">{{ $invoice->status_label }}</span></td>
                                <td class="text-right">Rp {{ number_format($invoice->remaining_amount, 0, ',', '.') }}</td>
                                <td class="text-center"><a href="{{ route('admin.invoices.print', $invoice) }}" target="_blank" class="btn btn-outline-secondary btn-sm" title="Cetak invoice"><i class="fas fa-print"></i></a></td>
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
            <div class="card card-outline card-success">
                <div class="card-header"><h3 class="card-title"><i class="fas fa-money-bill-wave mr-2"></i>Data Pembayaran</h3></div>
                <div class="card-body">
                    <div class="form-row">
                        <div class="form-group col-md-4"><label>Metode Pembayaran</label><select name="payment_method" class="form-control" required><option value="">Pilih metode</option><option>Cash</option><option>Transfer Bank</option><option>QRIS</option><option>E-Wallet</option><option>Lainnya</option></select></div>
                        <div class="form-group col-md-4"><label>Tanggal Bayar</label><input name="paid_at" type="datetime-local" value="{{ now()->format('Y-m-d\\TH:i') }}" class="form-control" required></div>
                        <div class="form-group col-md-4"><label>No. Referensi <small class="text-muted">(opsional)</small></label><input name="payment_reference" class="form-control" maxlength="100"></div>
                    </div>
                    <div class="form-group mb-0"><label>Catatan <small class="text-muted">(opsional)</small></label><textarea name="notes" class="form-control" rows="2" maxlength="500"></textarea></div>
                </div>
                <div class="card-footer d-flex justify-content-between align-items-center">
                    <strong>Total dipilih: <span id="selectedTotal" class="text-success">Rp 0</span> (<span id="selectedCount">0</span> invoice)</strong>
                    <button class="btn btn-success" type="submit"><i class="fas fa-check-circle mr-1"></i>Catat Pembayaran</button>
                </div>
            </div>
            @endcan
            @endif
        </form>
    </div>
    <div class="col-lg-4">
        <div class="card card-outline card-info"><div class="card-body">
            <h5>{{ $customer->name }}</h5><div class="text-muted">{{ $customer->customer_id }}</div><hr>
            <div><i class="fas fa-phone mr-2"></i>{{ $customer->phone ?: '—' }}</div>
            <div class="mt-2"><i class="fas fa-user-tag mr-2"></i>{{ $customer->pppoe_username ?: 'Tanpa PPPoE' }}</div>
            <div class="mt-2"><i class="fas fa-file-invoice mr-2"></i>{{ $invoices->count() }} invoice belum lunas</div>
        </div></div>
        @if($invoices->isNotEmpty())
        <form id="printForm" method="POST" action="{{ route('admin.payments.print', $customer) }}" target="_blank">@csrf<span id="printInvoiceIds"></span><button type="button" id="printSelected" class="btn btn-outline-secondary btn-block"><i class="fas fa-print mr-1"></i>Cetak Invoice Terpilih</button></form>
        @endif
        <a href="{{ route('admin.payments.index', auth()->user()->hasRole('superadmin') ? ['pop_id' => $popId] : []) }}" class="btn btn-outline-secondary btn-block mt-2"><i class="fas fa-arrow-left mr-1"></i>Kembali</a>
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
        selected().each(function () { total += Number($(this).data('amount')); });
        $('#selectedTotal').text(formatRupiah(total));
        $('#selectedCount').text(selected().length);
        $('#checkAll').prop('checked', $('.invoice-check').length === selected().length);
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
