@extends('layouts.admin')

@section('title', 'Cetak Invoice Massal')
@section('page-title', 'Cetak Invoice Massal')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.invoices.index') }}">Invoice</a></li>
    <li class="breadcrumb-item active">Cetak Massal</li>
@endsection

@push('css')
<style>
    .customer-row { transition: background 0.2s; }
    .customer-row:hover { background: #f1f5ff !important; }
    .customer-row.selected { background: #e8f4fd !important; }
    .bulk-toolbar {
        position: sticky;
        bottom: 0;
        z-index: 100;
        background: #343a40;
        color: #fff;
        padding: 12px 20px;
        border-radius: 8px 8px 0 0;
        display: none;
        animation: slideUp 0.3s ease;
    }
    @keyframes slideUp {
        from { transform: translateY(100%); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
    .period-badge {
        font-size: 11px;
        padding: 3px 8px;
    }
    .table-check { width: 40px; text-align: center; }
    .badge-printed {
        background: #e8f5e9;
        color: #2e7d32;
        font-size: 10px;
    }
    .section-divider {
        padding: 8px 15px;
        background: #f8f9fa;
        font-weight: 600;
        font-size: 13px;
        border-top: 2px solid #dee2e6;
    }
    .summary-panel {
        position: sticky;
        top: 70px;
    }
</style>
@endpush

@section('content')
{{-- POP Selector for Superadmin --}}
@if($popUsers && auth()->user()->hasRole('superadmin'))
<div class="card card-outline card-info mb-3">
    <div class="card-body py-2">
        <div class="row align-items-center">
            <div class="col-auto">
                <i class="fas fa-user-shield text-info fa-lg"></i>
                <strong class="ml-2">Mode Superadmin:</strong>
            </div>
            <div class="col-md-4">
                <select class="form-control select2" onchange="changePop(this.value)">
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

<form id="bulkPrintForm" action="{{ route('admin.invoices.bulk-print') }}" method="POST">
@csrf

<div class="row">
    <div class="col-lg-9">
        {{-- Period Selector --}}
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-calendar-alt mr-2"></i>Pilih Periode</h3>
            </div>
            <div class="card-body py-3">
                <div class="row align-items-end">
                    <div class="col-md-3">
                        <label>Bulan</label>
                        <select name="filter_month" id="filterMonth" class="form-control">
                            @for($i = 1; $i <= 12; $i++)
                                <option value="{{ $i }}" {{ $month == $i ? 'selected' : '' }}>
                                    {{ \Carbon\Carbon::create()->month($i)->translatedFormat('F') }}
                                </option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label>Tahun</label>
                        <select name="filter_year" id="filterYear" class="form-control">
                            @for($y = now()->year; $y >= now()->year - 3; $y--)
                                <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="button" class="btn btn-primary btn-block" onclick="filterPeriod()">
                            <i class="fas fa-search mr-1"></i> Tampilkan
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button type="button" class="btn btn-outline-success btn-block" id="btnSelectAll">
                            <i class="fas fa-check-double mr-1"></i> Pilih Semua
                        </button>
                    </div>
                </div>
                {{-- Hidden period fields for form submission --}}
                <input type="hidden" name="period_start" id="periodStart" 
                       value="{{ \Carbon\Carbon::create($year, $month, 1)->startOfMonth()->format('Y-m-d') }}">
                <input type="hidden" name="period_end" id="periodEnd" 
                       value="{{ \Carbon\Carbon::create($year, $month, 1)->endOfMonth()->format('Y-m-d') }}">
            </div>
        </div>

        {{-- Customer List with Invoices --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-users mr-2"></i>
                    Pelanggan Aktif
                    <span class="badge badge-info ml-2">{{ $customers->count() }} pelanggan</span>
                </h3>
                <div class="card-tools">
                    <div class="input-group input-group-sm" style="width: 250px;">
                        <input type="text" class="form-control" id="searchCustomer" placeholder="Cari pelanggan...">
                        <div class="input-group-append">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="customerTable">
                        <thead class="thead-light">
                            <tr>
                                <th class="table-check">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="checkAll">
                                        <label class="custom-control-label" for="checkAll"></label>
                                    </div>
                                </th>
                                <th>Pelanggan</th>
                                <th>Paket</th>
                                <th class="text-right">Tagihan</th>
                                <th>Status Invoice</th>
                                <th class="text-center">Cetak</th>
                            </tr>
                        </thead>
                        <tbody>
                            {{-- Customers WITH existing invoices --}}
                            @if($customersWithInvoices->isNotEmpty())
                            <tr class="section-divider">
                                <td colspan="6">
                                    <i class="fas fa-file-invoice text-success mr-1"></i>
                                    Sudah Ada Invoice ({{ $customersWithInvoices->count() }})
                                </td>
                            </tr>
                            @foreach($customersWithInvoices as $customer)
                                @php $inv = $customer->invoices->first(); @endphp
                                <tr class="customer-row" data-name="{{ strtolower($customer->name) }}" data-id="{{ strtolower($customer->customer_id) }}">
                                    <td class="table-check">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input customer-check has-invoice"
                                                   name="invoice_ids[]" value="{{ $inv->id }}"
                                                   id="inv_{{ $inv->id }}">
                                            <label class="custom-control-label" for="inv_{{ $inv->id }}"></label>
                                        </div>
                                    </td>
                                    <td>
                                        <strong>{{ $customer->name }}</strong>
                                        <br><small class="text-muted">{{ $customer->customer_id }}</small>
                                    </td>
                                    <td>
                                        <span class="badge badge-primary">{{ $customer->package?->name ?? '-' }}</span>
                                    </td>
                                    <td class="text-right">
                                        <strong>Rp {{ number_format($inv->total_amount, 0, ',', '.') }}</strong>
                                    </td>
                                    <td>
                                        <span class="badge badge-{{ $inv->status_color }}">{{ $inv->status_label }}</span>
                                        <br>
                                        <small class="text-muted">{{ $inv->invoice_number }}</small>
                                    </td>
                                    <td class="text-center">
                                        @if($inv->print_count > 0)
                                            <span class="badge badge-printed" title="Dicetak {{ $inv->print_count }}x, terakhir {{ $inv->printed_at?->format('d/m/Y H:i') }}">
                                                <i class="fas fa-print mr-1"></i>{{ $inv->print_count }}x
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            @endif

                            {{-- Customers WITHOUT invoices --}}
                            @if($customersWithoutInvoices->isNotEmpty())
                            <tr class="section-divider">
                                <td colspan="6">
                                    <i class="fas fa-file-medical text-warning mr-1"></i>
                                    Belum Ada Invoice - Akan Dibuat Otomatis ({{ $customersWithoutInvoices->count() }})
                                </td>
                            </tr>
                            @foreach($customersWithoutInvoices as $customer)
                                @php
                                    $price = $customer->package?->price ?? 0;
                                    $tax = ($popSetting?->ppn_enabled && ($popSetting?->ppn_percentage ?? 0) > 0)
                                        ? ($price - ($price / (1 + (($popSetting->ppn_percentage ?? 0) / 100))))
                                        : 0;
                                    $total = $price;
                                @endphp
                                <tr class="customer-row" data-name="{{ strtolower($customer->name) }}" data-id="{{ strtolower($customer->customer_id) }}">
                                    <td class="table-check">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input customer-check no-invoice"
                                                   name="customer_ids[]" value="{{ $customer->id }}"
                                                   id="cust_{{ $customer->id }}">
                                            <label class="custom-control-label" for="cust_{{ $customer->id }}"></label>
                                        </div>
                                    </td>
                                    <td>
                                        <strong>{{ $customer->name }}</strong>
                                        <br><small class="text-muted">{{ $customer->customer_id }}</small>
                                    </td>
                                    <td>
                                        <span class="badge badge-primary">{{ $customer->package?->name ?? '-' }}</span>
                                    </td>
                                    <td class="text-right">
                                        <strong>Rp {{ number_format($total, 0, ',', '.') }}</strong>
                                        @if($tax > 0)
                                            <br><small class="text-muted">inc. PPN</small>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge badge-secondary">
                                            <i class="fas fa-plus-circle mr-1"></i>Akan Dibuat
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="text-muted">-</span>
                                    </td>
                                </tr>
                            @endforeach
                            @endif

                            @if($customers->isEmpty())
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    <i class="fas fa-inbox fa-3x mb-2"></i>
                                    <p>Tidak ada pelanggan aktif dengan paket.</p>
                                </td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Sidebar Summary --}}
    <div class="col-lg-3">
        <div class="summary-panel">
            <div class="card card-outline card-success">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-clipboard-check mr-2"></i>Ringkasan</h3>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <span class="display-4 text-primary" id="selectedCount">0</span>
                        <p class="text-muted mb-0">Invoice Dipilih</p>
                    </div>

                    <hr>

                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td class="text-muted">Sudah ada invoice:</td>
                            <td class="text-right"><strong id="existingCount">0</strong></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Baru dibuat:</td>
                            <td class="text-right"><strong id="newCount">0</strong></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Total tagihan:</td>
                            <td class="text-right"><strong id="totalAmount">Rp 0</strong></td>
                        </tr>
                    </table>

                    <hr>

                    <div class="form-group mb-3">
                        <label class="text-muted small">Periode Invoice</label>
                        <div class="font-weight-bold" id="periodDisplay">
                            {{ \Carbon\Carbon::create($year, $month, 1)->translatedFormat('F Y') }}
                        </div>
                    </div>

                    <input type="hidden" name="output" id="outputType" value="print">

                    <button type="submit" class="btn btn-primary btn-block btn-lg mb-2" id="btnPrint" disabled
                            onclick="$('#outputType').val('print')">
                        <i class="fas fa-print mr-2"></i> Cetak Invoice
                    </button>
                    <button type="submit" class="btn btn-danger btn-block" id="btnPdf" disabled
                            onclick="$('#outputType').val('pdf')">
                        <i class="fas fa-file-pdf mr-2"></i> Download PDF
                    </button>

                    <hr>

                    <a href="{{ route('admin.invoices.index') }}" class="btn btn-secondary btn-block btn-sm">
                        <i class="fas fa-arrow-left mr-1"></i> Kembali
                    </a>
                </div>
            </div>

            {{-- Quick Stats --}}
            <div class="card">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between mb-2">
                        <small class="text-muted">Total Pelanggan</small>
                        <strong>{{ $customers->count() }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <small class="text-muted">Invoice Tersedia</small>
                        <strong class="text-success">{{ $customersWithInvoices->count() }}</strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <small class="text-muted">Perlu Dibuat</small>
                        <strong class="text-warning">{{ $customersWithoutInvoices->count() }}</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

</form>

{{-- Bulk Toolbar (floating bottom bar) --}}
<div class="bulk-toolbar" id="bulkToolbar">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <i class="fas fa-check-circle mr-2"></i>
            <strong><span id="toolbarCount">0</span> invoice dipilih</strong>
        </div>
        <div>
            <button type="button" class="btn btn-light btn-sm mr-2" onclick="$('#outputType').val('print'); $('#bulkPrintForm').submit()">
                <i class="fas fa-print mr-1"></i> Cetak
            </button>
            <button type="button" class="btn btn-danger btn-sm" onclick="$('#outputType').val('pdf'); $('#bulkPrintForm').submit()">
                <i class="fas fa-file-pdf mr-1"></i> PDF
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    function updateSummary() {
        const checked = $('.customer-check:checked');
        const total = checked.length;
        const existing = $('.has-invoice:checked').length;
        const newInv = $('.no-invoice:checked').length;

        $('#selectedCount').text(total);
        $('#existingCount').text(existing);
        $('#newCount').text(newInv);
        $('#toolbarCount').text(total);

        // Calculate total amount
        let totalAmt = 0;
        checked.each(function() {
            const row = $(this).closest('tr');
            const amtText = row.find('td:eq(3) strong').text().replace(/[^0-9]/g, '');
            totalAmt += parseInt(amtText) || 0;
        });
        $('#totalAmount').text('Rp ' + totalAmt.toLocaleString('id-ID'));

        // Toggle buttons
        const hasSelection = total > 0;
        $('#btnPrint, #btnPdf').prop('disabled', !hasSelection);
        $('#bulkToolbar').toggle(hasSelection);
    }

    // Checkbox handlers
    $('#checkAll').on('change', function() {
        const checked = $(this).prop('checked');
        $('.customer-check:visible').prop('checked', checked).closest('tr').toggleClass('selected', checked);
        updateSummary();
    });

    $(document).on('change', '.customer-check', function() {
        $(this).closest('tr').toggleClass('selected', $(this).prop('checked'));
        // Update checkAll state
        const total = $('.customer-check:visible').length;
        const checked = $('.customer-check:visible:checked').length;
        $('#checkAll').prop('checked', total > 0 && total === checked).prop('indeterminate', checked > 0 && checked < total);
        updateSummary();
    });

    // Select All button
    $('#btnSelectAll').on('click', function() {
        const allChecked = $('.customer-check:visible:checked').length === $('.customer-check:visible').length;
        if (allChecked) {
            $('.customer-check:visible').prop('checked', false).closest('tr').removeClass('selected');
            $(this).html('<i class="fas fa-check-double mr-1"></i> Pilih Semua');
        } else {
            $('.customer-check:visible').prop('checked', true).closest('tr').addClass('selected');
            $(this).html('<i class="fas fa-times mr-1"></i> Batal Pilih');
        }
        updateSummary();
    });

    // Search filter
    $('#searchCustomer').on('keyup', function() {
        const val = $(this).val().toLowerCase();
        $('#customerTable tbody .customer-row').each(function() {
            const name = $(this).data('name') || '';
            const id = $(this).data('id') || '';
            $(this).toggle(name.includes(val) || id.includes(val));
        });
    });
});

function filterPeriod() {
    const month = $('#filterMonth').val();
    const year = $('#filterYear').val();
    window.location.href = '{{ route("admin.invoices.bulk-print-select") }}?month=' + month + '&year=' + year;
}

function changePop(popId) {
    if (popId) {
        window.location.href = '{{ route("admin.invoices.bulk-print-select") }}?pop_id=' + popId;
    }
}
</script>
@endpush
