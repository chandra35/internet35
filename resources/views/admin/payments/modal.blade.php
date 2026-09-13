@php($outstandingTotal = $invoices->sum(fn ($invoice) => $invoice->remaining_amount))
<form method="POST" action="{{ route('admin.payments.store', ['customer' => $customer, 'pop_id' => $popId]) }}" id="modalPaymentForm">
    @csrf
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div><strong>{{ $customer->name }}</strong><br><small class="text-muted">{{ $customer->customer_id }} · {{ $customer->pppoe_username ?: 'Tanpa PPPoE' }}</small></div>
        <span class="badge badge-danger px-2 py-2">Rp {{ number_format($outstandingTotal, 0, ',', '.') }}</span>
    </div>
    <div class="table-responsive mb-3">
        <table class="table table-sm table-bordered mb-0">
            <thead class="thead-light"><tr><th width="42"><input type="checkbox" id="modalCheckAll"></th><th>Periode</th><th>Invoice</th><th>Jatuh Tempo</th><th class="text-right">Sisa</th></tr></thead>
            <tbody>
            @forelse($invoices as $invoice)
            <tr>
                <td><input class="modal-invoice-check" type="checkbox" name="invoice_ids[]" value="{{ $invoice->id }}" data-amount="{{ $invoice->remaining_amount }}"></td>
                <td>{{ $invoice->period_start?->translatedFormat('F Y') ?? '—' }}</td>
                <td>{{ $invoice->invoice_number }}</td>
                <td class="{{ $invoice->due_date?->isPast() ? 'text-danger font-weight-bold' : '' }}">{{ $invoice->due_date?->format('d/m/Y') }}</td>
                <td class="text-right font-weight-bold">Rp {{ number_format($invoice->remaining_amount, 0, ',', '.') }}</td>
            </tr>
            @empty
            <tr><td colspan="5" class="text-center text-success py-4"><i class="fas fa-check-circle mr-1"></i>Tidak ada invoice belum bayar.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($invoices->isNotEmpty())
    @can('invoices.edit')
    <div class="form-row">
        <div class="form-group col-md-4"><label>Metode</label><select name="payment_method" class="form-control" required><option value="">Pilih metode</option><option>Cash</option><option>Transfer Bank</option><option>QRIS</option><option>E-Wallet</option><option>Lainnya</option></select></div>
        <div class="form-group col-md-4"><label>Tanggal Bayar</label><input name="paid_at" type="datetime-local" value="{{ now()->format('Y-m-d\TH:i') }}" class="form-control" required></div>
        <div class="form-group col-md-4"><label>Referensi</label><input name="payment_reference" class="form-control" maxlength="100"></div>
    </div>
    <div class="form-group"><label>Catatan</label><textarea name="notes" class="form-control" maxlength="500" rows="2"></textarea></div>
    <div class="d-flex justify-content-between align-items-center"><span class="text-muted">Total dipilih: <strong id="modalSelectedTotal">Rp 0</strong></span><button type="submit" class="btn btn-success"><i class="fas fa-check-circle mr-1"></i>Catat Pembayaran</button></div>
    @endcan
    @endif
</form>
<script>
(function () {
    const checks = $('#paymentDetailModal .modal-invoice-check');
    function update() {
        let total = 0;
        checks.filter(':checked').each(function () { total += Number($(this).data('amount')); });
        $('#modalSelectedTotal').text('Rp ' + new Intl.NumberFormat('id-ID').format(total));
        $('#modalCheckAll').prop('checked', checks.length > 0 && checks.length === checks.filter(':checked').length);
    }
    $('#modalCheckAll').on('change', function () { checks.prop('checked', this.checked); update(); });
    checks.on('change', update);
    $('#modalPaymentForm').on('submit', function (event) {
        if (!checks.filter(':checked').length) { event.preventDefault(); toastr.warning('Pilih minimal satu invoice yang akan dibayar.'); }
    });
    update();
})();
</script>
