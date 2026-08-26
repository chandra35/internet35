@extends('layouts.admin')

@section('title', 'Pembayaran')
@section('page-title', 'Pembayaran Pelanggan')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">Pembayaran</li>
@endsection

@section('content')
@if($popUsers && auth()->user()->hasRole('superadmin'))
<div class="card card-outline card-info mb-3">
    <div class="card-body py-2">
        <form method="GET" class="form-inline">
            <label class="mr-2"><i class="fas fa-user-shield text-info mr-1"></i>POP:</label>
            <select name="pop_id" class="form-control select2" style="min-width:280px" onchange="this.form.submit()">
                <option value="">-- Pilih POP --</option>
                @foreach($popUsers as $pop)
                <option value="{{ $pop->id }}" {{ $popId === $pop->id ? 'selected' : '' }}>{{ $pop->name }}</option>
                @endforeach
            </select>
        </form>
    </div>
</div>
@endif

@if(!$popId)
<div class="alert alert-warning"><i class="fas fa-exclamation-triangle mr-2"></i>Pilih POP terlebih dahulu.</div>
@else
<div class="card card-outline card-success">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-cash-register mr-2"></i>Daftar Tunggakan Pelanggan</h3>
    </div>
    <div class="card-body pb-2">
        <form method="GET" class="form-row">
            @if(auth()->user()->hasRole('superadmin'))<input type="hidden" name="pop_id" value="{{ $popId }}">@endif
            <div class="col-md-6 mb-2">
                <input name="search" value="{{ request('search') }}" class="form-control" autofocus
                    placeholder="Cari nama, ID pelanggan, telepon, atau PPPoE...">
            </div>
            <div class="col-auto mb-2">
                <button class="btn btn-primary"><i class="fas fa-search mr-1"></i>Cari</button>
                @if(request('search'))<a href="{{ route('admin.payments.index', auth()->user()->hasRole('superadmin') ? ['pop_id' => $popId] : []) }}" class="btn btn-outline-secondary">Reset</a>@endif
            </div>
        </form>
        <p class="text-muted small mb-0">Pilih pelanggan untuk melihat bulan-bulan invoice yang belum lunas dan mencatat pembayaran sekaligus.</p>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="thead-light">
                <tr><th>Pelanggan</th><th>Kontak</th><th>Jumlah Invoice</th><th>Jatuh Tempo Terdekat</th><th class="text-right">Total Tunggakan</th><th class="text-right">Aksi</th></tr>
            </thead>
            <tbody>
                @forelse($customers as $customer)
                @php
                    $firstInvoice = $customer->invoices->first();
                    $totalOutstanding = $customer->invoices->sum(fn ($invoice) => $invoice->remaining_amount);
                @endphp
                <tr>
                    <td><strong>{{ $customer->name }}</strong><br><small class="text-muted">{{ $customer->customer_id }}</small></td>
                    <td>{{ $customer->phone ?: '—' }}<br><small class="text-muted">{{ $customer->pppoe_username ?: '—' }}</small></td>
                    <td><span class="badge badge-warning">{{ $customer->invoices->count() }} invoice</span></td>
                    <td class="{{ $firstInvoice?->due_date?->isPast() ? 'text-danger font-weight-bold' : '' }}">{{ $firstInvoice?->due_date?->format('d/m/Y') ?? '—' }}</td>
                    <td class="text-right font-weight-bold text-danger">Rp {{ number_format($totalOutstanding, 0, ',', '.') }}</td>
                    <td class="text-right"><a class="btn btn-success btn-sm" href="{{ route('admin.payments.show', $customer) }}"><i class="fas fa-cash-register mr-1"></i>Proses Bayar</a></td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-muted py-4">Tidak ada invoice yang belum lunas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($customers->hasPages())<div class="card-footer">{{ $customers->links() }}</div>@endif
</div>
@endif
@endsection
