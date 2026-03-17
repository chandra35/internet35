@extends('layouts.admin')

@section('title', 'Detail Invoice')

@section('page-title', 'Detail Invoice')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.invoices.index') }}">Invoice</a></li>
    <li class="breadcrumb-item active">{{ $invoice->invoice_number }}</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8">
        <!-- Invoice Card -->
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-0">
                        <i class="fas fa-file-invoice mr-2 text-primary"></i>
                        {{ $invoice->invoice_number }}
                    </h4>
                </div>
                <div>
                    <span class="badge badge-{{ $invoice->status_color }} px-3 py-2" style="font-size: 14px;">
                        {{ $invoice->status_label }}
                    </span>
                </div>
            </div>
            <div class="card-body">
                <!-- Header Info -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6 class="text-muted mb-2">Ditagihkan Kepada:</h6>
                        <h5>{{ $invoice->customer?->name }}</h5>
                        <p class="mb-1">ID: {{ $invoice->customer?->customer_id }}</p>
                        <p class="mb-1">{{ $invoice->customer?->phone }}</p>
                        <p class="mb-0">{{ $invoice->customer?->address }}</p>
                    </div>
                    <div class="col-md-6 text-md-right">
                        @if($popSetting?->isp_logo)
                        <img src="{{ Storage::url($popSetting->isp_logo) }}" alt="Logo" 
                             style="max-height: 60px; margin-bottom: 10px;">
                        @endif
                        <h5>{{ $popSetting?->isp_name ?? 'ISP' }}</h5>
                        <p class="mb-1">{{ $popSetting?->address }}</p>
                        <p class="mb-1">{{ $popSetting?->phone }}</p>
                        <p class="mb-0">{{ $popSetting?->email }}</p>
                    </div>
                </div>
                
                <hr>
                
                <!-- Invoice Details -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <small class="text-muted">Tanggal Invoice</small>
                        <p class="mb-0 font-weight-bold">{{ $invoice->invoice_date?->format('d F Y') }}</p>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted">Jatuh Tempo</small>
                        <p class="mb-0 font-weight-bold {{ $invoice->isOverdue() ? 'text-danger' : '' }}">
                            {{ $invoice->due_date?->format('d F Y') }}
                        </p>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted">Periode Awal</small>
                        <p class="mb-0">{{ $invoice->period_start?->format('d M Y') }}</p>
                    </div>
                    <div class="col-md-3">
                        <small class="text-muted">Periode Akhir</small>
                        <p class="mb-0">{{ $invoice->period_end?->format('d M Y') }}</p>
                    </div>
                </div>
                
                <!-- Items Table -->
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="thead-light">
                            <tr>
                                <th width="60">#</th>
                                <th>Deskripsi</th>
                                <th class="text-right" width="200">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if($invoice->items)
                                @foreach($invoice->items as $index => $item)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $item['description'] ?? '-' }}</td>
                                    <td class="text-right">Rp {{ number_format($item['amount'] ?? 0, 0, ',', '.') }}</td>
                                </tr>
                                @endforeach
                            @endif
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="2" class="text-right">Subtotal</td>
                                <td class="text-right">Rp {{ number_format($invoice->subtotal, 0, ',', '.') }}</td>
                            </tr>
                            @if($invoice->discount_amount > 0)
                            <tr>
                                <td colspan="2" class="text-right">Diskon</td>
                                <td class="text-right text-danger">- Rp {{ number_format($invoice->discount_amount, 0, ',', '.') }}</td>
                            </tr>
                            @endif
                            @if($invoice->tax_amount > 0)
                            <tr>
                                <td colspan="2" class="text-right">PPN ({{ $popSetting?->ppn_percentage ?? 11 }}%)</td>
                                <td class="text-right">Rp {{ number_format($invoice->tax_amount, 0, ',', '.') }}</td>
                            </tr>
                            @endif
                            <tr class="font-weight-bold bg-light">
                                <td colspan="2" class="text-right">Total</td>
                                <td class="text-right text-primary">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</td>
                            </tr>
                            @if($invoice->paid_amount > 0)
                            <tr>
                                <td colspan="2" class="text-right">Dibayar</td>
                                <td class="text-right text-success">Rp {{ number_format($invoice->paid_amount, 0, ',', '.') }}</td>
                            </tr>
                            @endif
                            @if($invoice->remaining_amount > 0 && $invoice->status !== 'paid')
                            <tr class="font-weight-bold">
                                <td colspan="2" class="text-right">Sisa Tagihan</td>
                                <td class="text-right text-danger">Rp {{ number_format($invoice->remaining_amount, 0, ',', '.') }}</td>
                            </tr>
                            @endif
                        </tfoot>
                    </table>
                </div>
                
                <!-- Notes -->
                @if($invoice->notes)
                <div class="mt-4">
                    <h6 class="text-muted">Catatan:</h6>
                    <p class="mb-0">{{ $invoice->notes }}</p>
                </div>
                @endif
                
                <!-- Terms -->
                @if($popSetting?->invoice_terms)
                <div class="mt-4">
                    <h6 class="text-muted">Syarat & Ketentuan:</h6>
                    <p class="mb-0 small text-muted">{!! nl2br(e($popSetting->invoice_terms)) !!}</p>
                </div>
                @endif
            </div>
        </div>
        
        <!-- Payments History -->
        @if($invoice->payments->count() > 0)
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-money-bill-wave mr-2"></i>Riwayat Pembayaran
                </h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>No. Pembayaran</th>
                            <th>Tanggal</th>
                            <th>Metode</th>
                            <th class="text-right">Jumlah</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($invoice->payments as $payment)
                        <tr>
                            <td>{{ $payment->payment_number }}</td>
                            <td>{{ $payment->paid_at?->format('d/m/Y H:i') ?? $payment->created_at->format('d/m/Y H:i') }}</td>
                            <td>{{ $payment->payment_channel ?? $payment->payment_method }}</td>
                            <td class="text-right">Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
                            <td>
                                <span class="badge badge-{{ $payment->status_color }}">
                                    {{ $payment->status_label }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>
    
    <div class="col-md-4">
        <!-- Actions -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-cogs mr-2"></i>Aksi
                </h3>
            </div>
            <div class="card-body">
                <a href="{{ route('admin.invoices.print', $invoice) }}" class="btn btn-secondary btn-block mb-2" target="_blank">
                    <i class="fas fa-print mr-1"></i> Print Invoice
                </a>
                <a href="{{ route('admin.invoices.download-pdf', $invoice) }}" class="btn btn-danger btn-block mb-2">
                    <i class="fas fa-file-pdf mr-1"></i> Download PDF
                </a>
                @if($invoice->print_count > 0)
                <div class="text-center mb-2">
                    <small class="text-muted">
                        <i class="fas fa-history mr-1"></i>Dicetak {{ $invoice->print_count }}x
                        @if($invoice->printed_at)
                        | Terakhir: {{ $invoice->printed_at->format('d/m/Y H:i') }}
                        @endif
                    </small>
                </div>
                @endif
                
                @if($invoice->status !== 'paid' && $invoice->status !== 'cancelled')
                    @can('invoices.edit')
                    <a href="{{ route('admin.invoices.edit', $invoice) }}" class="btn btn-warning btn-block mb-2">
                        <i class="fas fa-edit mr-1"></i> Edit Invoice
                    </a>
                    
                    <button type="button" class="btn btn-success btn-block mb-2" data-toggle="modal" data-target="#markPaidModal">
                        <i class="fas fa-check mr-1"></i> Tandai Lunas
                    </button>
                    @endcan
                    
                    <button type="button" class="btn btn-info btn-block mb-2" onclick="sendReminder()">
                        <i class="fas fa-bell mr-1"></i> Kirim Reminder
                    </button>
                    
                    @can('invoices.delete')
                    <form action="{{ route('admin.invoices.cancel', $invoice) }}" method="POST" 
                          onsubmit="return confirm('Yakin ingin membatalkan invoice ini?')">
                        @csrf
                        <button type="submit" class="btn btn-danger btn-block mb-2">
                            <i class="fas fa-times mr-1"></i> Batalkan Invoice
                        </button>
                    </form>
                    @endcan
                @endif
                
                <a href="{{ route('admin.invoices.index') }}" class="btn btn-outline-secondary btn-block">
                    <i class="fas fa-arrow-left mr-1"></i> Kembali
                </a>
            </div>
        </div>
        
        <!-- Info Card -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-info-circle mr-2"></i>Informasi
                </h3>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td class="text-muted">Dibuat oleh</td>
                        <td>{{ $invoice->creator?->name ?? 'System' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Tanggal dibuat</td>
                        <td>{{ $invoice->created_at->format('d/m/Y H:i') }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Terakhir update</td>
                        <td>{{ $invoice->updated_at->format('d/m/Y H:i') }}</td>
                    </tr>
                    @if($invoice->paid_at)
                    <tr>
                        <td class="text-muted">Dibayar pada</td>
                        <td>{{ $invoice->paid_at->format('d/m/Y H:i') }}</td>
                    </tr>
                    @endif
                    @if($invoice->payment_method)
                    <tr>
                        <td class="text-muted">Metode bayar</td>
                        <td>{{ $invoice->payment_method }}</td>
                    </tr>
                    @endif
                    @if($invoice->payment_reference)
                    <tr>
                        <td class="text-muted">Referensi</td>
                        <td>{{ $invoice->payment_reference }}</td>
                    </tr>
                    @endif
                </table>
            </div>
        </div>
        
        <!-- Bank Accounts -->
        @if($popSetting?->bank_accounts)
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-university mr-2"></i>Rekening Pembayaran
                </h3>
            </div>
            <div class="card-body">
                @foreach($popSetting->bank_accounts as $bank)
                <div class="border rounded p-2 mb-2">
                    <strong>{{ $bank['bank_name'] ?? '-' }}</strong><br>
                    <span>{{ $bank['account_number'] ?? '-' }}</span><br>
                    <small class="text-muted">a.n. {{ $bank['account_name'] ?? '-' }}</small>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Mark Paid Modal -->
<div class="modal fade" id="markPaidModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.invoices.mark-paid', $invoice) }}" method="POST">
                @csrf
                <div class="modal-header bg-success">
                    <h5 class="modal-title text-white">
                        <i class="fas fa-check mr-2"></i>Tandai Lunas
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-1"></i>
                        Jumlah yang akan dibayar: <strong>Rp {{ number_format($invoice->remaining_amount, 0, ',', '.') }}</strong>
                    </div>
                    
                    <div class="form-group">
                        <label>Metode Pembayaran <span class="text-danger">*</span></label>
                        <select name="payment_method" class="form-control" required>
                            <option value="transfer">Transfer Bank</option>
                            <option value="cash">Tunai</option>
                            <option value="qris">QRIS</option>
                            <option value="other">Lainnya</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>No. Referensi / Bukti</label>
                        <input type="text" name="payment_reference" class="form-control" 
                               placeholder="Contoh: No. Rekening, No. Transaksi">
                    </div>
                    
                    <div class="form-group">
                        <label>Tanggal Bayar <span class="text-danger">*</span></label>
                        <input type="datetime-local" name="paid_at" class="form-control" 
                               value="{{ now()->format('Y-m-d\TH:i') }}" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Catatan</label>
                        <textarea name="notes" class="form-control" rows="2" 
                                  placeholder="Catatan pembayaran..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check mr-1"></i> Konfirmasi Lunas
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function sendReminder() {
    if (confirm('Kirim reminder ke pelanggan?')) {
        fetch('{{ route('admin.invoices.send-reminder', $invoice) }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            alert('Reminder berhasil dikirim!');
        })
        .catch(error => {
            alert('Gagal mengirim reminder');
        });
    }
}
</script>
@endpush
