<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Invoice - {{ $customer->name }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: #333;
            background: #e9ecef;
        }
        .print-toolbar {
            max-width: 800px;
            margin: 20px auto;
            padding: 15px 20px;
            background: #343a40;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #fff;
        }
        .toolbar-btn {
            padding: 8px 20px;
            font-size: 14px;
            cursor: pointer;
            border: none;
            border-radius: 5px;
            margin-left: 10px;
        }
        .btn-print { background: #007bff; color: #fff; }
        .btn-close { background: #6c757d; color: #fff; }
        .alert {
            max-width: 800px;
            margin: 15px auto;
            padding: 12px 15px;
            border-radius: 6px;
            background: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
        }
        .invoice-page {
            max-width: 800px;
            margin: 20px auto;
            padding: 30px;
            background: #fff;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            page-break-after: always;
        }
        .invoice-page:last-of-type { page-break-after: auto; }
        .header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #007bff;
        }
        .company-logo img { max-height: 60px; margin-bottom: 10px; }
        .company-name { font-size: 18px; font-weight: bold; color: #007bff; }
        .company-details { color: #666; font-size: 11px; }
        .invoice-title { text-align: right; }
        .invoice-title h1 { font-size: 28px; color: #007bff; margin-bottom: 10px; }
        .invoice-number { font-size: 14px; font-weight: bold; }
        .info-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        .bill-to, .invoice-details { width: 48%; }
        .section-title {
            font-size: 10px;
            text-transform: uppercase;
            color: #666;
            margin-bottom: 5px;
            letter-spacing: 1px;
        }
        .customer-name { font-size: 16px; font-weight: bold; color: #333; }
        .customer-details { color: #666; font-size: 11px; }
        .invoice-details table { width: 100%; }
        .invoice-details td { padding: 3px 0; }
        .invoice-details td:first-child { color: #666; }
        .invoice-details td:last-child { text-align: right; font-weight: 500; }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .items-table th {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 10px;
            text-align: left;
            font-size: 11px;
            text-transform: uppercase;
            color: #666;
        }
        .items-table td {
            border: 1px solid #dee2e6;
            padding: 10px;
        }
        .items-table .text-right { text-align: right; }
        .items-table tfoot td { font-weight: 500; }
        .items-table tfoot tr:last-child td {
            font-size: 14px;
            font-weight: bold;
            background: #f8f9fa;
            color: #007bff;
        }
        .bank-info { margin-top: 20px; }
        .bank-info h4 { font-size: 12px; margin-bottom: 10px; color: #333; }
        .bank-card {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 8px;
            display: inline-block;
            margin-right: 10px;
        }
        .bank-name { font-weight: bold; color: #007bff; }
        .bank-number {
            font-size: 14px;
            font-weight: bold;
            letter-spacing: 1px;
        }
        .bank-holder { font-size: 10px; color: #666; }
        .notes-section {
            margin-top: 20px;
            padding: 10px 15px;
            background: #fff3cd;
            border-radius: 5px;
            font-size: 11px;
        }
        .notes-section h4 { font-size: 11px; margin-bottom: 5px; }
        .terms-section {
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px solid #dee2e6;
            font-size: 10px;
            color: #666;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            padding-top: 15px;
            border-top: 2px solid #007bff;
            color: #666;
            font-size: 10px;
        }
        @media print {
            body {
                background: #fff;
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }
            .invoice-page {
                box-shadow: none;
                margin: 0 auto;
                padding: 20px 30px;
            }
            .print-toolbar, .alert, .no-print { display: none !important; }
        }
    </style>
</head>
<body>
<div class="print-toolbar no-print">
    <div>
        <strong>{{ $selectedMonths->count() }} bulan</strong> dipilih untuk {{ $customer->name }} ({{ $selectedYear }})
    </div>
    <div>
        <button onclick="window.print()" class="toolbar-btn btn-print">Cetak Semua</button>
        <button onclick="window.close()" class="toolbar-btn btn-close">Tutup</button>
    </div>
</div>

@if($missingMonths->isNotEmpty())
<div class="alert no-print">
    Tidak ditemukan invoice untuk bulan:
    {{ $missingMonths->map(fn($m) => \Carbon\Carbon::create()->month($m)->translatedFormat('F'))->implode(', ') }}.
</div>
@endif

@foreach($printRows as $row)
@php
    $month = $row['month'];
    $invoice = $row['invoice'];
    $monthName = \Carbon\Carbon::create()->month($month)->translatedFormat('F');
@endphp
<div class="invoice-page">
    <div class="header">
        <div class="company-info">
            @if($popSetting?->isp_logo)
            <div class="company-logo">
                <img src="{{ Storage::url($popSetting->isp_logo) }}" alt="Logo">
            </div>
            @endif
            <div class="company-name">{{ $popSetting?->isp_name ?? 'ISP Provider' }}</div>
            <div class="company-details">
                {{ $popSetting?->address }}<br>
                Telp: {{ $popSetting?->phone }} | Email: {{ $popSetting?->email }}<br>
                @if($popSetting?->website)
                Website: {{ $popSetting->website }}
                @endif
            </div>
        </div>
        <div class="invoice-title">
            <h1>INVOICE</h1>
            @if($invoice)
            <div class="invoice-number">{{ $invoice->invoice_number }}</div>
            @else
            <div class="invoice-number">{{ strtoupper($monthName) }} {{ $selectedYear }}</div>
            @endif
        </div>
    </div>

    <div class="info-section">
        <div class="bill-to">
            <div class="section-title">Ditagihkan Kepada</div>
            <div class="customer-name">{{ $customer->name }}</div>
            <div class="customer-details">
                ID Pelanggan: {{ $customer->customer_id }}<br>
                {{ $customer->phone }}<br>
                {{ $customer->address }}
            </div>
        </div>
        <div class="invoice-details">
            <table>
                <tr>
                    <td>Periode Cetak</td>
                    <td>{{ $monthName }} {{ $selectedYear }}</td>
                </tr>
                @if($invoice)
                <tr>
                    <td>Tanggal Invoice</td>
                    <td>{{ $invoice->invoice_date?->format('d F Y') }}</td>
                </tr>
                <tr>
                    <td>Jatuh Tempo</td>
                    <td style="{{ $invoice->isOverdue() ? 'color: #dc3545; font-weight: bold;' : '' }}">
                        {{ $invoice->due_date?->format('d F Y') }}
                    </td>
                </tr>
                <tr>
                    <td>Periode Layanan</td>
                    <td>{{ $invoice->period_start?->format('d M Y') }} - {{ $invoice->period_end?->format('d M Y') }}</td>
                </tr>
                @endif
            </table>
        </div>
    </div>

    @if($invoice)

    <table class="items-table">
        <thead>
        <tr>
            <th style="width: 40px;">No</th>
            <th>Deskripsi</th>
            <th class="text-right" style="width: 150px;">Jumlah</th>
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
            <td class="text-right" style="color: #dc3545;">- Rp {{ number_format($invoice->discount_amount, 0, ',', '.') }}</td>
        </tr>
        @endif
        @if($invoice->tax_amount > 0)
        <tr>
            <td colspan="2" class="text-right">PPN ({{ $popSetting?->ppn_percentage ?? 11 }}%)</td>
            <td class="text-right">Rp {{ number_format($invoice->tax_amount, 0, ',', '.') }}</td>
        </tr>
        @endif
        <tr>
            <td colspan="2" class="text-right">TOTAL</td>
            <td class="text-right">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</td>
        </tr>
        </tfoot>
    </table>

    @else
    <table class="items-table">
        <thead>
        <tr>
            <th>Deskripsi</th>
            <th class="text-right" style="width: 220px;">Keterangan</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td>Invoice bulan {{ $monthName }} {{ $selectedYear }}</td>
            <td class="text-right">Belum tersedia</td>
        </tr>
        </tbody>
    </table>
    @endif

    @if($popSetting?->bank_accounts && count($popSetting->bank_accounts) > 0)
    <div class="bank-info">
        <h4>Informasi Pembayaran</h4>
        @foreach($popSetting->bank_accounts as $bank)
            <div class="bank-card">
                <div class="bank-name">{{ $bank['bank_name'] ?? '-' }}</div>
                <div class="bank-number">{{ $bank['account_number'] ?? '-' }}</div>
                <div class="bank-holder">a.n. {{ $bank['account_name'] ?? '-' }}</div>
            </div>
        @endforeach
    </div>
    @endif

    @if($invoice && $invoice->notes)
    <div class="notes-section">
        <h4>Catatan</h4>
        <p>{{ $invoice->notes }}</p>
    </div>
    @endif

    @if($popSetting?->invoice_terms)
    <div class="terms-section">
        <strong>Syarat & Ketentuan:</strong><br>
        {!! nl2br(e($popSetting->invoice_terms)) !!}
    </div>
    @endif

    <div class="footer">
        {{ $popSetting?->invoice_footer ?? 'Terima kasih atas kepercayaan Anda menggunakan layanan kami.' }}
    </div>
</div>
@endforeach

<script>
window.addEventListener('load', function () {
    // Keep manual print action from toolbar; auto-print is intentionally disabled.
});
</script>
</body>
</html>
