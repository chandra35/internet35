<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Invoice PDF</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #333;
        }
        .invoice-page {
            padding: 20px 25px;
            page-break-after: always;
        }
        .invoice-page:last-child {
            page-break-after: auto;
        }
        .header {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #007bff;
            overflow: hidden;
        }
        .company-info {
            float: left;
            width: 55%;
        }
        .company-logo img {
            max-height: 50px;
            margin-bottom: 8px;
        }
        .company-name {
            font-size: 16px;
            font-weight: bold;
            color: #007bff;
        }
        .company-details {
            color: #666;
            font-size: 10px;
        }
        .invoice-title {
            float: right;
            text-align: right;
            width: 40%;
        }
        .invoice-title h1 {
            font-size: 24px;
            color: #007bff;
            margin-bottom: 5px;
        }
        .invoice-number {
            font-size: 12px;
            font-weight: bold;
        }
        .invoice-status {
            display: inline-block;
            padding: 3px 12px;
            border-radius: 10px;
            font-weight: bold;
            font-size: 9px;
            margin-top: 5px;
        }
        .status-pending { background: #ffc107; color: #000; }
        .status-paid { background: #28a745; color: #fff; }
        .status-overdue { background: #dc3545; color: #fff; }
        .status-cancelled { background: #6c757d; color: #fff; }
        .status-draft { background: #e9ecef; color: #333; }
        .status-partial { background: #17a2b8; color: #fff; }

        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }

        .info-section {
            margin-bottom: 20px;
            overflow: hidden;
        }
        .bill-to {
            float: left;
            width: 50%;
        }
        .invoice-details {
            float: right;
            width: 45%;
        }
        .section-title {
            font-size: 9px;
            text-transform: uppercase;
            color: #666;
            margin-bottom: 3px;
            letter-spacing: 1px;
        }
        .customer-name {
            font-size: 14px;
            font-weight: bold;
            color: #333;
        }
        .customer-details {
            color: #666;
            font-size: 10px;
        }
        .invoice-details table {
            width: 100%;
        }
        .invoice-details td {
            padding: 2px 0;
            font-size: 10px;
        }
        .invoice-details td:first-child {
            color: #666;
        }
        .invoice-details td:last-child {
            text-align: right;
            font-weight: 500;
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
            text-transform: uppercase;
            color: #555;
        }
        .items-table td {
            border: 1px solid #ccc;
            padding: 7px 8px;
        }
        .items-table .text-right {
            text-align: right;
        }
        .items-table tfoot td {
            font-weight: 500;
            font-size: 10px;
        }
        .items-table tfoot tr:last-child td {
            font-size: 13px;
            font-weight: bold;
            background: #f0f0f0;
            color: #007bff;
        }

        .bank-section {
            margin-top: 15px;
        }
        .bank-section h4 {
            font-size: 11px;
            margin-bottom: 8px;
        }
        .bank-item {
            background: #f0f0f0;
            padding: 6px 10px;
            margin-bottom: 5px;
            border-radius: 3px;
        }
        .bank-name {
            font-weight: bold;
            color: #007bff;
            font-size: 10px;
        }
        .bank-number {
            font-size: 12px;
            font-weight: bold;
            letter-spacing: 1px;
        }
        .bank-holder {
            font-size: 9px;
            color: #666;
        }

        .notes-section {
            margin-top: 15px;
            padding: 8px 12px;
            background: #fff8e1;
            border-radius: 3px;
            font-size: 10px;
        }
        .notes-section h4 {
            font-size: 10px;
            margin-bottom: 3px;
        }

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
        <div class="header clearfix">
            <div class="company-info">
                @if($popSetting?->isp_logo)
                <div class="company-logo">
                    <img src="{{ public_path('storage/' . $popSetting->isp_logo) }}" alt="Logo">
                </div>
                @endif
                <div class="company-name">{{ $popSetting?->isp_name ?? 'ISP Provider' }}</div>
                <div class="company-details">
                    {{ $popSetting?->address }}<br>
                    Telp: {{ $popSetting?->phone }} | Email: {{ $popSetting?->email }}
                    @if($popSetting?->website)
                    <br>Website: {{ $popSetting->website }}
                    @endif
                </div>
            </div>
            <div class="invoice-title">
                <h1>INVOICE</h1>
                <div class="invoice-number">{{ $invoice->invoice_number }}</div>
                <div class="invoice-status status-{{ $invoice->status }}">
                    {{ strtoupper($invoice->status_label) }}
                </div>
            </div>
        </div>

        {{-- Info Section --}}
        <div class="info-section clearfix">
            <div class="bill-to">
                <div class="section-title">Ditagihkan Kepada</div>
                <div class="customer-name">{{ $invoice->customer?->name }}</div>
                <div class="customer-details">
                    ID Pelanggan: {{ $invoice->customer?->customer_id }}<br>
                    {{ $invoice->customer?->phone }}<br>
                    {{ $invoice->customer?->address }}
                </div>
            </div>
            <div class="invoice-details">
                <table>
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
                        <td>Periode</td>
                        <td>{{ $invoice->period_start?->format('d M Y') }} - {{ $invoice->period_end?->format('d M Y') }}</td>
                    </tr>
                </table>
            </div>
        </div>

        {{-- Items Table --}}
        <table class="items-table">
            <thead>
                <tr>
                    <th width="40">No</th>
                    <th>Deskripsi</th>
                    <th width="130" class="text-right">Jumlah</th>
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
            <strong>Syarat & Ketentuan:</strong><br>
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
