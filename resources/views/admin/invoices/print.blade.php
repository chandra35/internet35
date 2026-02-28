<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: #333;
            background: #fff;
        }
        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 30px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #007bff;
        }
        .company-info {
            flex: 1;
        }
        .company-logo img {
            max-height: 60px;
            margin-bottom: 10px;
        }
        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #007bff;
        }
        .company-details {
            color: #666;
            font-size: 11px;
        }
        .invoice-title {
            text-align: right;
        }
        .invoice-title h1 {
            font-size: 28px;
            color: #007bff;
            margin-bottom: 10px;
        }
        .invoice-number {
            font-size: 14px;
            font-weight: bold;
        }
        .invoice-status {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            margin-top: 10px;
        }
        .status-pending { background: #ffc107; color: #000; }
        .status-paid { background: #28a745; color: #fff; }
        .status-overdue { background: #dc3545; color: #fff; }
        .status-cancelled { background: #6c757d; color: #fff; }
        
        .info-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        .bill-to, .invoice-details {
            width: 48%;
        }
        .section-title {
            font-size: 10px;
            text-transform: uppercase;
            color: #666;
            margin-bottom: 5px;
            letter-spacing: 1px;
        }
        .customer-name {
            font-size: 16px;
            font-weight: bold;
            color: #333;
        }
        .customer-details {
            color: #666;
            font-size: 11px;
        }
        .invoice-details table {
            width: 100%;
        }
        .invoice-details td {
            padding: 3px 0;
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
        .items-table .text-right {
            text-align: right;
        }
        .items-table tfoot td {
            font-weight: 500;
        }
        .items-table tfoot tr:last-child td {
            font-size: 14px;
            font-weight: bold;
            background: #f8f9fa;
            color: #007bff;
        }
        
        .summary-section {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
        }
        .bank-info {
            width: 55%;
        }
        .bank-info h4 {
            font-size: 12px;
            margin-bottom: 10px;
            color: #333;
        }
        .bank-card {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 8px;
        }
        .bank-name {
            font-weight: bold;
            color: #007bff;
        }
        .bank-number {
            font-size: 14px;
            font-weight: bold;
            letter-spacing: 1px;
        }
        .bank-holder {
            font-size: 10px;
            color: #666;
        }
        
        .notes-section {
            margin-top: 30px;
            padding: 15px;
            background: #fff3cd;
            border-radius: 5px;
        }
        .notes-section h4 {
            font-size: 11px;
            margin-bottom: 5px;
        }
        
        .terms-section {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            font-size: 10px;
            color: #666;
        }
        
        .footer {
            margin-top: 40px;
            text-align: center;
            padding-top: 20px;
            border-top: 2px solid #007bff;
            color: #666;
            font-size: 10px;
        }
        
        @media print {
            body { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
            .invoice-container { padding: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <!-- Header -->
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
                <div class="invoice-number">{{ $invoice->invoice_number }}</div>
                <div class="invoice-status status-{{ $invoice->status }}">
                    {{ strtoupper($invoice->status_label) }}
                </div>
            </div>
        </div>
        
        <!-- Info Section -->
        <div class="info-section">
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
        
        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th width="50">No</th>
                    <th>Deskripsi</th>
                    <th width="150" class="text-right">Jumlah</th>
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
        
        <!-- Bank Info -->
        @if($popSetting?->bank_accounts && $invoice->status !== 'paid')
        <div class="summary-section">
            <div class="bank-info">
                <h4>Pembayaran dapat dilakukan melalui:</h4>
                @foreach($popSetting->bank_accounts as $bank)
                <div class="bank-card">
                    <div class="bank-name">{{ $bank['bank_name'] ?? '-' }}</div>
                    <div class="bank-number">{{ $bank['account_number'] ?? '-' }}</div>
                    <div class="bank-holder">a.n. {{ $bank['account_name'] ?? '-' }}</div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
        
        <!-- Notes -->
        @if($invoice->notes)
        <div class="notes-section">
            <h4>Catatan:</h4>
            {{ $invoice->notes }}
        </div>
        @endif
        
        <!-- Terms -->
        @if($popSetting?->invoice_terms)
        <div class="terms-section">
            <strong>Syarat & Ketentuan:</strong><br>
            {!! nl2br(e($popSetting->invoice_terms)) !!}
        </div>
        @endif
        
        <!-- Footer -->
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
    
    <!-- Print Button -->
    <div class="no-print" style="text-align: center; margin: 20px 0;">
        <button onclick="window.print()" style="padding: 10px 30px; font-size: 14px; cursor: pointer; background: #007bff; color: #fff; border: none; border-radius: 5px;">
            🖨️ Cetak Invoice
        </button>
        <button onclick="window.close()" style="padding: 10px 30px; font-size: 14px; cursor: pointer; background: #6c757d; color: #fff; border: none; border-radius: 5px; margin-left: 10px;">
            ✕ Tutup
        </button>
    </div>
</body>
</html>
