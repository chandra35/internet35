<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Invoice PDF</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .invoice-page {
            padding: 20px 25px;
            page-break-after: always;
        }
        .invoice-page:last-child {
            page-break-after: auto;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0;
            padding-bottom: 12px;
            border-bottom: 2px solid #007bff;
        }
        .company-name {
            font-size: 16px;
            font-weight: bold;
            color: #007bff;
        }
        .company-details {
            color: #666;
            font-size: 10px;
            margin-top: 3px;
        }
        .invoice-title-text {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 4px;
        }
        .invoice-number-text {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 4px;
        }
        .invoice-status {
            display: inline-block;
            padding: 3px 10px;
            font-weight: bold;
            font-size: 9px;
        }
        .status-pending { background: #ffc107; color: #000; }
        .status-paid { background: #28a745; color: #fff; }
        .status-overdue { background: #dc3545; color: #fff; }
        .status-cancelled { background: #6c757d; color: #fff; }
        .status-draft { background: #e9ecef; color: #333; }
        .status-partial { background: #17a2b8; color: #fff; }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            margin-top: 15px;
        }
        .section-title {
            font-size: 9px;
            color: #666;
            margin-bottom: 3px;
        }
        .customer-name-text {
            font-size: 14px;
            font-weight: bold;
            color: #333;
            margin-bottom: 2px;
        }
        .customer-details-text {
            color: #666;
            font-size: 10px;
        }
        .detail-label {
            color: #666;
            font-size: 10px;
            padding: 2px 0;
        }
        .detail-value {
            font-size: 10px;
            padding: 2px 0;
            text-align: right;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .items-table th {
            background: #f0f0f0;
            border: 1px solid #ccc;
            padding: 7px 8px;
            text-align: left;
            font-size: 10px;
            font-weight: bold;
            color: #555;
        }
        .items-table td {
            border: 1px solid #ccc;
            padding: 7px 8px;
            font-size: 11px;
        }
        .text-right { text-align: right; }
        .items-table tfoot td {
            font-size: 10px;
            border: 1px solid #ccc;
        }
        .total-row td {
            font-size: 13px;
            font-weight: bold;
            background: #f0f0f0;
            color: #007bff;
        }
        .bank-section { margin-top: 15px; }
        .bank-section h4 { font-size: 11px; margin-bottom: 8px; }
        .bank-item {
            background: #f0f0f0;
            padding: 6px 10px;
            margin-bottom: 5px;
        }
        .bank-name { font-weight: bold; color: #007bff; font-size: 10px; }
        .bank-number { font-size: 12px; font-weight: bold; }
        .bank-holder { font-size: 9px; color: #666; }
        .notes-section {
            margin-top: 15px;
            padding: 8px 12px;
            background: #fff8e1;
            font-size: 10px;
        }
        .notes-section h4 { font-size: 10px; margin-bottom: 3px; }
        .terms-section {
            margin-top: 12px;
            padding-top: 8px;
            border-top: 1px solid #ccc;
            font-size: 9px;
            color: #666;
        }
        .footer {
            margin-top: 25px;
            text-align: center;
            padding-top: 12px;
            border-top: 2px solid #007bff;
            color: #666;
            font-size: 9px;
        }
    </style>
</head>
<body>
    @foreach($invoices as $invoice)
    <div class="invoice-page">

        {{-- Header --}}
        <table class="header-table">
            <tr>
                <td style="width:58%; vertical-align:top;">
                    @if($popSetting?->isp_logo)
                    <img src="{{ public_path('storage/' . $popSetting->isp_logo) }}" alt="Logo" style="max-height:50px; max-width:160px; margin-bottom:6px;"><br>
                    @endif
                    <div class="company-name">{{ $popSetting?->isp_name ?? 'ISP Provider' }}</div>
                    <div class="company-details">
                        {{ $popSetting?->address }}<br>
                        Telp: {{ $popSetting?->phone }}
                        @if($popSetting?->email) | Email: {{ $popSetting->email }}@endif
                        @if($popSetting?->website)<br>Website: {{ $popSetting->website }}@endif
                    </div>
                </td>
                <td style="width:42%; vertical-align:top; text-align:right;">
                    <div class="invoice-title-text">INVOICE</div>
                    <div class="invoice-number-text">{{ $invoice->invoice_number }}</div>
                    <div>
                        <span class="invoice-status status-{{ $invoice->status }}">
                            {{ strtoupper($invoice->status_label) }}
                        </span>
                    </div>
                </td>
            </tr>
        </table>

        {{-- Info Section --}}
        <table class="info-table">
            <tr>
                <td style="width:52%; vertical-align:top; padding-right:15px;">
                    <div class="section-title">Ditagihkan Kepada:</div>
                    <div class="customer-name-text">{{ $invoice->customer?->name }}</div>
                    <div class="customer-details-text">
                        ID Pelanggan: {{ $invoice->customer?->customer_id }}<br>
                        {{ $invoice->customer?->phone }}<br>
                        {{ $invoice->customer?->address }}
                    </div>
                </td>
                <td style="width:48%; vertical-align:top;">
                    <table style="width:100%; border-collapse:collapse;">
                        <tr>
                            <td class="detail-label">Tanggal Invoice</td>
                            <td class="detail-value">{{ $invoice->invoice_date?->format('d F Y') }}</td>
                        </tr>
                        <tr>
                            <td class="detail-label">Jatuh Tempo</td>
                            <td class="detail-value" @if($invoice->isOverdue()) style="color:#dc3545; font-weight:bold;" @endif>
                                {{ $invoice->due_date?->format('d F Y') }}
                            </td>
                        </tr>
                        <tr>
                            <td class="detail-label">Periode</td>
                            <td class="detail-value">{{ $invoice->period_start?->format('d M Y') }} - {{ $invoice->period_end?->format('d M Y') }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        {{-- Items Table --}}
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width:40px;">No</th>
                    <th>Deskripsi</th>
                    <th style="width:130px;" class="text-right">Jumlah</th>
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
                    <td colspan="2" class="text-right" style="color:#dc3545;">Diskon</td>
                    <td class="text-right" style="color:#dc3545;">- Rp {{ number_format($invoice->discount_amount, 0, ',', '.') }}</td>
                </tr>
                @endif
                @if($invoice->tax_amount > 0)
                <tr>
                    <td colspan="2" class="text-right">PPN ({{ $popSetting?->ppn_percentage ?? 11 }}%)</td>
                    <td class="text-right">Rp {{ number_format($invoice->tax_amount, 0, ',', '.') }}</td>
                </tr>
                @endif
                <tr class="total-row">
                    <td colspan="2" class="text-right">TOTAL</td>
                    <td class="text-right">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>

        {{-- Bank Info --}}
        @if($popSetting?->bank_accounts && $invoice->status !== 'paid')
        <div class="bank-section">
            <h4>Pembayaran dapat dilakukan melalui:</h4>
            @foreach($popSetting->bank_accounts as $bank)
            <div class="bank-item">
                <span class="bank-name">{{ $bank['bank_name'] ?? '-' }}</span> -
                <span class="bank-number">{{ $bank['account_number'] ?? '-' }}</span>
                <span class="bank-holder">a.n. {{ $bank['account_name'] ?? '-' }}</span>
            </div>
            @endforeach
        </div>
        @endif

        {{-- Notes --}}
        @if($invoice->notes)
        <div class="notes-section">
            <h4>Catatan:</h4>
            {{ $invoice->notes }}
        </div>
        @endif

        {{-- Terms --}}
        @if($popSetting?->invoice_terms)
        <div class="terms-section">
            <strong>Syarat &amp; Ketentuan:</strong><br>
            {!! nl2br(e($popSetting->invoice_terms)) !!}
        </div>
        @endif

        {{-- Footer --}}
        <div class="footer">
            @if($popSetting?->invoice_footer)
                {{ $popSetting->invoice_footer }}
            @else
                Terima kasih atas kepercayaan Anda menggunakan layanan kami.
            @endif
            <br>
            Dokumen ini dicetak secara otomatis dan sah tanpa tanda tangan.
        </div>
    </div>
    @endforeach
</body>
</html>