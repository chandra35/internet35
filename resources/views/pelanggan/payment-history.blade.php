@extends('layouts.pelanggan')

@section('title', 'Riwayat Pembayaran')

@section('page-title', 'Riwayat Pembayaran')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-history mr-2"></i>
                    Riwayat Pembayaran Anda
                </h3>
            </div>
            <div class="card-body">
                @if($payments->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>No. Referensi</th>
                                <th>Invoice</th>
                                <th>Metode</th>
                                <th>Jumlah</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($payments as $payment)
                            <tr>
                                <td>{{ $payment->created_at->format('d M Y H:i') }}</td>
                                <td><code>{{ $payment->external_id ?? $payment->payment_number }}</code></td>
                                <td>
                                    @if($payment->invoice)
                                    <a href="{{ route('pelanggan.invoice', $payment->invoice) }}">
                                        {{ $payment->invoice->invoice_number }}
                                    </a>
                                    @else
                                    -
                                    @endif
                                </td>
                                <td>
                                    @if($payment->paymentGateway)
                                    {{ $payment->paymentGateway->name }}
                                    @else
                                    {{ ucfirst($payment->payment_method) }}
                                    @endif
                                </td>
                                <td>
                                    <strong>Rp {{ number_format($payment->amount, 0, ',', '.') }}</strong>
                                </td>
                                <td>
                                    <span class="badge badge-{{ $payment->status_color }}">
                                        {{ $payment->status_label }}
                                    </span>
                                    @if($payment->status === 'success' && $payment->paid_at)
                                    <br><small class="text-muted">{{ $payment->paid_at->format('d M Y') }}</small>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <div class="d-flex justify-content-center mt-4">
                    {{ $payments->links() }}
                </div>
                @else
                <div class="text-center py-5">
                    <i class="fas fa-history fa-4x text-muted mb-3"></i>
                    <h5 class="text-muted">Belum ada riwayat pembayaran</h5>
                    <p class="text-muted">Riwayat pembayaran Anda akan ditampilkan di sini.</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
