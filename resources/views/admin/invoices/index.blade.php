@extends('layouts.admin')

@section('title', 'Invoice')

@section('page-title', 'Manajemen Invoice')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">Invoice</li>
@endsection

@push('css')
<style>
    .stat-card {
        transition: all 0.3s ease;
    }
    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
</style>
@endpush

@section('content')
<!-- POP Selector for Superadmin -->
@if($popUsers && auth()->user()->hasRole('superadmin'))
<div class="card card-outline card-info mb-3">
    <div class="card-body py-2">
        <div class="row align-items-center">
            <div class="col-auto">
                <i class="fas fa-user-shield text-info fa-lg"></i>
                <strong class="ml-2">Mode Superadmin:</strong>
            </div>
            <div class="col-md-4">
                <select class="form-control select2" id="selectPop" onchange="changePop(this.value)">
                    <option value="">-- Pilih POP --</option>
                    @foreach($popUsers as $pop)
                        <option value="{{ $pop->id }}" {{ $popId == $pop->id ? 'selected' : '' }}>
                            {{ $pop->name }} ({{ $pop->email }})
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
</div>
@endif

@if(!$popId && auth()->user()->hasRole('superadmin'))
<div class="alert alert-warning">
    <i class="fas fa-exclamation-triangle mr-2"></i>
    Pilih POP terlebih dahulu untuk mengelola invoice.
</div>
@else

<!-- Statistics -->
<div class="row">
    <div class="col-lg-2 col-6">
        <div class="small-box bg-info stat-card">
            <div class="inner">
                <h3>{{ number_format($stats['total'] ?? 0) }}</h3>
                <p>Total Invoice</p>
            </div>
            <div class="icon">
                <i class="fas fa-file-invoice"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-2 col-6">
        <div class="small-box bg-warning stat-card">
            <div class="inner">
                <h3>{{ number_format($stats['pending'] ?? 0) }}</h3>
                <p>Belum Dibayar</p>
            </div>
            <div class="icon">
                <i class="fas fa-clock"></i>
            </div>
            <a href="{{ route('admin.invoices.index', ['status' => 'pending']) }}" class="small-box-footer">
                Lihat <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
    <div class="col-lg-2 col-6">
        <div class="small-box bg-success stat-card">
            <div class="inner">
                <h3>{{ number_format($stats['paid'] ?? 0) }}</h3>
                <p>Lunas</p>
            </div>
            <div class="icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <a href="{{ route('admin.invoices.index', ['status' => 'paid']) }}" class="small-box-footer">
                Lihat <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
    <div class="col-lg-2 col-6">
        <div class="small-box bg-danger stat-card">
            <div class="inner">
                <h3>{{ number_format($stats['overdue'] ?? 0) }}</h3>
                <p>Jatuh Tempo</p>
            </div>
            <div class="icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <a href="{{ route('admin.invoices.index', ['status' => 'overdue']) }}" class="small-box-footer">
                Lihat <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
    <div class="col-lg-2 col-6">
        <div class="small-box bg-primary stat-card">
            <div class="inner">
                <h4>Rp {{ number_format($stats['total_pending_amount'] ?? 0, 0, ',', '.') }}</h4>
                <p>Total Belum Bayar</p>
            </div>
            <div class="icon">
                <i class="fas fa-money-bill-wave"></i>
            </div>
        </div>
    </div>
    <div class="col-lg-2 col-6">
        <div class="small-box bg-teal stat-card">
            <div class="inner">
                <h4>Rp {{ number_format($stats['total_paid_amount'] ?? 0, 0, ',', '.') }}</h4>
                <p>Pendapatan Bulan Ini</p>
            </div>
            <div class="icon">
                <i class="fas fa-wallet"></i>
            </div>
        </div>
    </div>
</div>

<!-- Filters & Actions -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-file-invoice mr-2"></i>
            Daftar Invoice
        </h3>
        <div class="card-tools">
            @can('invoices.view')
            <a href="{{ route('admin.invoices.bulk-print-select') }}" class="btn btn-info btn-sm mr-2">
                <i class="fas fa-print mr-1"></i> Cetak Massal
            </a>
            @endcan
            @can('invoices.create')
            <button type="button" class="btn btn-success btn-sm mr-2" data-toggle="modal" data-target="#generateModal">
                <i class="fas fa-magic mr-1"></i> Generate Massal
            </button>
            <a href="{{ route('admin.invoices.create') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus mr-1"></i> Buat Invoice
            </a>
            @endcan
        </div>
    </div>
    <div class="card-body">
        <!-- Filter Form -->
        <form action="{{ route('admin.invoices.index') }}" method="GET" class="mb-4">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="">Semua Status</option>
                            @foreach(\App\Models\CustomerInvoice::statusLabels() as $key => $label)
                                <option value="{{ $key }}" {{ request('status') == $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Bulan</label>
                        <select name="month" class="form-control">
                            <option value="">Semua</option>
                            @for($i = 1; $i <= 12; $i++)
                                <option value="{{ $i }}" {{ request('month') == $i ? 'selected' : '' }}>
                                    {{ \Carbon\Carbon::create()->month($i)->translatedFormat('F') }}
                                </option>
                            @endfor
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Tahun</label>
                        <select name="year" class="form-control">
                            <option value="">Semua</option>
                            @for($y = now()->year; $y >= now()->year - 5; $y--)
                                <option value="{{ $y }}" {{ request('year') == $y ? 'selected' : '' }}>
                                    {{ $y }}
                                </option>
                            @endfor
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Cari</label>
                        <input type="text" name="search" class="form-control" 
                               placeholder="No. Invoice / Nama Pelanggan" 
                               value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i>
                            </button>
                            <a href="{{ route('admin.invoices.index') }}" class="btn btn-secondary">
                                <i class="fas fa-sync"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
        
        <!-- Invoice Table -->
        <div class="table-responsive">
            <table class="table table-hover table-striped">
                <thead class="thead-light">
                    <tr>
                        <th>No. Invoice</th>
                        <th>Pelanggan</th>
                        <th>Tanggal</th>
                        <th>Jatuh Tempo</th>
                        <th class="text-right">Total</th>
                        <th class="text-right">Dibayar</th>
                        <th>Status</th>
                        <th width="120">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $invoice)
                    <tr>
                        <td>
                            <a href="{{ route('admin.invoices.show', $invoice) }}">
                                <strong>{{ $invoice->invoice_number }}</strong>
                            </a>
                        </td>
                        <td>
                            <div>{{ $invoice->customer?->name ?? '-' }}</div>
                            <small class="text-muted">{{ $invoice->customer?->customer_id }}</small>
                        </td>
                        <td>{{ $invoice->invoice_date?->format('d/m/Y') }}</td>
                        <td>
                            {{ $invoice->due_date?->format('d/m/Y') }}
                            @if($invoice->isOverdue() && $invoice->status !== 'paid')
                                <span class="badge badge-danger">Lewat!</span>
                            @endif
                        </td>
                        <td class="text-right">
                            <strong>Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</strong>
                        </td>
                        <td class="text-right">
                            Rp {{ number_format($invoice->paid_amount, 0, ',', '.') }}
                        </td>
                        <td>
                            <span class="badge badge-{{ $invoice->status_color }}">
                                {{ $invoice->status_label }}
                            </span>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('admin.invoices.show', $invoice) }}" 
                                   class="btn btn-info" title="Detail">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @can('invoices.edit')
                                @if($invoice->status !== 'paid')
                                <a href="{{ route('admin.invoices.edit', $invoice) }}" 
                                   class="btn btn-warning" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                @endif
                                @endcan
                                <a href="{{ route('admin.invoices.print', $invoice) }}" 
                                   class="btn btn-secondary" title="Print" target="_blank">
                                    <i class="fas fa-print"></i>
                                </a>
                                <a href="{{ route('admin.invoices.download-pdf', $invoice) }}" 
                                   class="btn btn-danger" title="Download PDF">
                                    <i class="fas fa-file-pdf"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-4">
                            <div class="text-muted">
                                <i class="fas fa-file-invoice fa-3x mb-3"></i>
                                <p>Belum ada invoice</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <div class="mt-3">
            {{ $invoices->appends(request()->query())->links() }}
        </div>
    </div>
</div>

@endif

<!-- Generate Modal -->
<div class="modal fade" id="generateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.invoices.generate') }}" method="POST">
                @csrf
                <div class="modal-header bg-success">
                    <h5 class="modal-title text-white">
                        <i class="fas fa-magic mr-2"></i>Generate Invoice Massal
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle mr-1"></i>
                        Akan membuat invoice untuk semua pelanggan aktif yang belum memiliki invoice di periode ini.
                    </div>
                    
                    <div class="form-group">
                        <label>Periode Awal <span class="text-danger">*</span></label>
                        <input type="date" name="period_start" class="form-control" 
                               value="{{ now()->startOfMonth()->format('Y-m-d') }}" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Periode Akhir <span class="text-danger">*</span></label>
                        <input type="date" name="period_end" class="form-control" 
                               value="{{ now()->endOfMonth()->format('Y-m-d') }}" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-magic mr-1"></i> Generate
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function changePop(popId) {
    if (popId) {
        window.location.href = '{{ route('admin.invoices.index') }}?pop_id=' + popId;
    }
}
</script>
@endpush
