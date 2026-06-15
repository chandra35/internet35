@extends('layouts.admin')

@section('title', 'Cetak Invoice Pelanggan')
@section('page-title', 'Cetak Invoice Pelanggan Multi-Bulan')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">Cetak Invoice Pelanggan</li>
@endsection

@section('content')
@include('admin.partials.pop-selector', ['popUsers' => $popUsers ?? null, 'popId' => $popId ?? null])

@if(!($popId ?? null) && auth()->user()->hasRole('superadmin'))
<div class="alert alert-warning">
    <i class="fas fa-exclamation-triangle mr-2"></i>
    Pilih POP terlebih dahulu untuk menggunakan fitur cetak invoice pelanggan.
</div>
@else
<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-print mr-2"></i>Form Cetak Invoice Pelanggan
        </h3>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.invoice-customer-print.print') }}" target="_blank">
            @csrf
            @if($popId)
                <input type="hidden" name="pop_id" value="{{ $popId }}">
            @endif

            <div class="form-group">
                <label for="customer_id">Pelanggan</label>
                <select name="customer_id" id="customer_id" class="form-control select2" required>
                    <option value="">-- Pilih Pelanggan --</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}" {{ (string) old('customer_id') === (string) $customer->id ? 'selected' : '' }}>
                            {{ $customer->name }} ({{ $customer->customer_id }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="year">Tahun Invoice</label>
                <select name="year" id="year" class="form-control" required>
                    @for($y = $currentYear; $y >= $currentYear - 5; $y--)
                        <option value="{{ $y }}" {{ (int) old('year', $currentYear) === $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>

            <div class="form-group">
                <label>Bulan (boleh pilih beberapa)</label>
                <div class="mb-2">
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="selectAllMonths()">Pilih Semua</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="clearAllMonths()">Reset</button>
                </div>
                <div class="row">
                    @for($m = 1; $m <= 12; $m++)
                        <div class="col-md-3 col-6 mb-2">
                            <div class="custom-control custom-checkbox">
                                <input
                                    class="custom-control-input month-check"
                                    type="checkbox"
                                    id="month_{{ $m }}"
                                    name="months[]"
                                    value="{{ $m }}"
                                    {{ in_array($m, old('months', [now()->month])) ? 'checked' : '' }}
                                >
                                <label class="custom-control-label" for="month_{{ $m }}">
                                    {{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}
                                </label>
                            </div>
                        </div>
                    @endfor
                </div>
                <small class="text-muted">Sistem akan mencetak semua invoice yang ditemukan sesuai bulan terpilih.</small>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-print mr-1"></i>Cetak Invoice
                </button>
                <a href="{{ route('admin.invoices.index', $popId ? ['pop_id' => $popId] : []) }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left mr-1"></i>Kembali ke Invoice
                </a>
            </div>
        </form>
    </div>
</div>
@endif
@endsection

@section('scripts')
<script>
function selectAllMonths() {
    document.querySelectorAll('.month-check').forEach(function (el) {
        el.checked = true;
    });
}

function clearAllMonths() {
    document.querySelectorAll('.month-check').forEach(function (el) {
        el.checked = false;
    });
}
</script>
@endsection
