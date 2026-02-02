@extends('layouts.pelanggan')

@section('title', 'Tagihan')

@section('page-title', 'Daftar Tagihan')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-file-invoice-dollar mr-2"></i>
                    Tagihan Anda
                </h3>
            </div>
            <div class="card-body">
                @if($invoices->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>No. Invoice</th>
                                <th>Periode</th>
                                <th>Jatuh Tempo</th>
                                <th>Jumlah</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($invoices as $invoice)
                            <tr class="{{ $invoice->status === 'overdue' ? 'table-danger' : '' }}">
                                <td><code>{{ $invoice->invoice_number }}</code></td>
                                <td>{{ $invoice->period_start?->format('M Y') ?? '-' }}</td>
                                <td>
                                    {{ $invoice->due_date?->format('d M Y') ?? '-' }}
                                    @if($invoice->status === 'overdue')
                                    <br><small class="text-danger">Terlambat {{ $invoice->due_date->diffInDays(now()) }} hari</small>
                                    @endif
                                </td>
                                <td>
                                    <strong>Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</strong>
                                    @if($invoice->late_fee > 0)
                                    <br><small class="text-danger">+ Denda Rp {{ number_format($invoice->late_fee, 0, ',', '.') }}</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge badge-{{ $invoice->status_color }}">
                                        {{ $invoice->status_label }}
                                    </span>
                                </td>
                                <td>
                                    @if(in_array($invoice->status, ['pending', 'overdue']))
                                    <a href="{{ route('pelanggan.invoice', $invoice) }}" class="btn btn-sm btn-primary">
                                        <i class="fas fa-credit-card mr-1"></i> Bayar
                                    </a>
                                    @elseif($invoice->status === 'paid')
                                    <a href="{{ route('pelanggan.invoice', $invoice) }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="fas fa-eye mr-1"></i> Lihat
                                    </a>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <div class="d-flex justify-content-center mt-4">
                    {{ $invoices->links() }}
                </div>
                @else
                <div class="text-center py-5">
                    <i class="fas fa-file-invoice fa-4x text-muted mb-3"></i>
                    <h5 class="text-muted">Belum ada tagihan</h5>
                    <p class="text-muted">Tagihan akan muncul di sini saat periode penagihan dimulai.</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
