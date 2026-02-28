<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview Template Invoice</title>
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
            background: #e9ecef;
        }
        .preview-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            padding: 15px 30px;
            position: sticky;
            top: 0;
            z-index: 100;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        .preview-header h3 {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .preview-header .badge {
            background: rgba(255,255,255,0.2);
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 500;
        }
        .preview-actions {
            display: flex;
            gap: 10px;
        }
        .preview-actions button {
            padding: 8px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.3s;
        }
        .btn-print {
            background: #fff;
            color: #667eea;
        }
        .btn-print:hover {
            background: #f8f9fa;
            transform: translateY(-2px);
        }
        .btn-close-preview {
            background: rgba(255,255,255,0.2);
            color: #fff;
        }
        .btn-close-preview:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .invoice-wrapper {
            max-width: 850px;
            margin: 30px auto;
            padding: 0 20px;
        }
        .invoice-container {
            background: #fff;
            padding: 40px;
            box-shadow: 0 5px 30px rgba(0,0,0,0.1);
            border-radius: 10px;
        }
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-30deg);
            font-size: 80px;
            color: rgba(102, 126, 234, 0.08);
            font-weight: bold;
            pointer-events: none;
            white-space: nowrap;
        }
        .invoice-container {
            position: relative;
            overflow: hidden;
        }
        .header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #667eea;
        }
        .company-info {
            flex: 1;
        }
        .company-logo img {
            max-height: 70px;
            margin-bottom: 10px;
        }
        .company-name {
            font-size: 20px;
            font-weight: bold;
            color: #667eea;
        }
        .company-details {
            color: #666;
            font-size: 11px;
            margin-top: 5px;
        }
        .invoice-title {
            text-align: right;
        }
        .invoice-title h1 {
            font-size: 32px;
            color: #667eea;
            margin-bottom: 10px;
            letter-spacing: 2px;
        }
        .invoice-number {
            font-size: 14px;
            font-weight: bold;
            color: #333;
        }
        .invoice-status {
            display: inline-block;
            padding: 6px 18px;
            border-radius: 25px;
            font-weight: bold;
            margin-top: 10px;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .status-pending { background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%); color: #000; }
        .status-paid { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: #fff; }
        .status-overdue { background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); color: #fff; }
        .status-cancelled { background: linear-gradient(135deg, #6c757d 0%, #495057 100%); color: #fff; }
        
        .info-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            gap: 30px;
        }
        .bill-to, .invoice-details {
            flex: 1;
        }
        .section-title {
            font-size: 10px;
            text-transform: uppercase;
            color: #999;
            margin-bottom: 8px;
            letter-spacing: 2px;
            font-weight: 600;
        }
        .customer-name {
            font-size: 16px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        .customer-details {
            color: #666;
            font-size: 11px;
            line-height: 1.6;
        }
        .invoice-details {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
        }
        .invoice-details table {
            width: 100%;
        }
        .invoice-details td {
            padding: 5px 0;
        }
        .invoice-details td:first-child {
            color: #666;
            font-size: 11px;
        }
        .invoice-details td:last-child {
            text-align: right;
            font-weight: 600;
            font-size: 12px;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        .items-table th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            padding: 12px 15px;
            text-align: left;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .items-table th:first-child {
            border-radius: 8px 0 0 0;
        }
        .items-table th:last-child {
            border-radius: 0 8px 0 0;
        }
        .items-table td {
            border-bottom: 1px solid #eee;
            padding: 12px 15px;
        }
        .items-table tbody tr:hover {
            background: #f8f9fa;
        }
        .items-table .text-right {
            text-align: right;
        }
        .items-table tfoot td {
            font-weight: 500;
            padding: 10px 15px;
        }
        .items-table tfoot tr:last-child {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .items-table tfoot tr:last-child td {
            font-size: 14px;
            font-weight: bold;
            color: #fff;
            border-radius: 0 0 8px 8px;
        }
        
        .payment-info {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-top: 25px;
        }
        .payment-info h4 {
            font-size: 12px;
            margin-bottom: 15px;
            color: #333;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .bank-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 12px;
        }
        .bank-card {
            background: #fff;
            padding: 12px;
            border-radius: 8px;
            border-left: 4px solid #667eea;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .bank-name {
            font-weight: bold;
            color: #667eea;
            font-size: 12px;
        }
        .bank-number {
            font-size: 14px;
            font-weight: bold;
            letter-spacing: 1px;
            margin: 5px 0;
        }
        .bank-holder {
            font-size: 10px;
            color: #666;
        }
        
        .notes-section {
            margin-top: 25px;
            padding: 15px;
            background: #fff3cd;
            border-radius: 8px;
            border-left: 4px solid #ffc107;
        }
        .notes-section h4 {
            font-size: 11px;
            margin-bottom: 8px;
            color: #856404;
        }
        .notes-section p {
            color: #856404;
            font-size: 11px;
        }
        
        .terms-section {
            margin-top: 20px;
            padding: 15px;
            background: #e7f5ff;
            border-radius: 8px;
            border-left: 4px solid #17a2b8;
        }
        .terms-section h4 {
            font-size: 11px;
            margin-bottom: 8px;
            color: #0c5460;
        }
        .terms-section p {
            color: #0c5460;
            font-size: 10px;
            line-height: 1.6;
        }
        
        .footer {
            margin-top: 35px;
            text-align: center;
            padding-top: 20px;
            border-top: 2px solid #667eea;
            color: #666;
            font-size: 11px;
        }
        .footer-note {
            font-size: 10px;
            color: #999;
            margin-top: 5px;
        }
        
        @media print {
            body { 
                background: #fff;
                print-color-adjust: exact; 
                -webkit-print-color-adjust: exact; 
            }
            .preview-header { display: none; }
            .invoice-wrapper { 
                margin: 0;
                padding: 0;
            }
            .invoice-container { 
                box-shadow: none;
                border-radius: 0;
            }
            .watermark { display: none; }
        }
    </style>
</head>
<body>
    <div class="preview-header">
        <h3>
            <svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zm-1 2l5 5h-5V4zM6 20V4h6v6h6v10H6z"/>
            </svg>
            Preview Template Invoice
            <span class="badge">📋 Data Contoh</span>
        </h3>
        <div class="preview-actions">
            <button class="btn-print" onclick="window.print()">
                🖨️ Cetak Preview
            </button>
            <button class="btn-close-preview" onclick="window.close()">
                ✕ Tutup
            </button>
        </div>
    </div>

    <div class="invoice-wrapper">
        <div class="invoice-container">
            <div class="watermark">PREVIEW</div>
            
            <!-- Header -->
            <div class="header">
                <div class="company-info">
                    @if($popSetting->isp_logo)
                    <div class="company-logo">
                        <img src="{{ Storage::url($popSetting->isp_logo) }}" alt="Logo">
                    </div>
                    @endif
                    <div class="company-name">{{ $popSetting->isp_name ?? 'Nama ISP Anda' }}</div>
                    <div class="company-details">
                        {{ $popSetting->address ?? 'Alamat perusahaan akan muncul di sini' }}<br>
                        Telp: {{ $popSetting->phone ?? '08xx-xxxx-xxxx' }} | Email: {{ $popSetting->email ?? 'email@domain.com' }}<br>
                        @if($popSetting->website)
                        Website: {{ $popSetting->website }}
                        @endif
                    </div>
                </div>
                <div class="invoice-title">
                    <h1>INVOICE</h1>
                    <div class="invoice-number">{{ $invoice->invoice_number }}</div>
                    <div class="invoice-status status-{{ $invoice->status }}">
                        {{ $invoice->status_label }}
                    </div>
                </div>
            </div>
            
            <!-- Info Section -->
            <div class="info-section">
                <div class="bill-to">
                    <div class="section-title">Ditagihkan Kepada</div>
                    <div class="customer-name">{{ $invoice->customer->name }}</div>
                    <div class="customer-details">
                        ID Pelanggan: {{ $invoice->customer->customer_id }}<br>
                        {{ $invoice->customer->phone }}<br>
                        {{ $invoice->customer->address }}
                    </div>
                </div>
                <div class="invoice-details">
                    <table>
                        <tr>
                            <td>Tanggal Invoice</td>
                            <td>{{ $invoice->invoice_date->format('d M Y') }}</td>
                        </tr>
                        <tr>
                            <td>Jatuh Tempo</td>
                            <td style="color: #dc3545; font-weight: bold;">{{ $invoice->due_date->format('d M Y') }}</td>
                        </tr>
                        <tr>
                            <td>Periode Layanan</td>
                            <td>{{ $invoice->period_start->format('d M') }} - {{ $invoice->period_end->format('d M Y') }}</td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <!-- Items Table -->
            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">No</th>
                        <th>Deskripsi</th>
                        <th style="width: 150px;" class="text-right">Jumlah</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoice->items as $index => $item)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $item['description'] ?? '-' }}</td>
                        <td class="text-right">Rp {{ number_format($item['amount'] ?? 0, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
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
                        <td colspan="2" class="text-right">PPN ({{ $popSetting->ppn_percentage ?? 11 }}%)</td>
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
            @if($popSetting->bank_accounts && count($popSetting->bank_accounts) > 0)
            <div class="payment-info">
                <h4>
                    <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M4 10h16v2H4v-2zm0 4h16v2H4v-2zm0-8h16v2H4V6zm16-4H4c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/>
                    </svg>
                    Pembayaran dapat dilakukan melalui:
                </h4>
                <div class="bank-grid">
                    @foreach($popSetting->bank_accounts as $bank)
                    <div class="bank-card">
                        <div class="bank-name">{{ $bank['bank_name'] ?? '-' }}</div>
                        <div class="bank-number">{{ $bank['account_number'] ?? '-' }}</div>
                        <div class="bank-holder">a.n. {{ $bank['account_name'] ?? '-' }}</div>
                    </div>
                    @endforeach
                </div>
            </div>
            @else
            <div class="payment-info" style="background: #fff3cd; border-left: 4px solid #ffc107;">
                <h4 style="color: #856404;">
                    ⚠️ Rekening bank belum dikonfigurasi
                </h4>
                <p style="color: #856404; font-size: 11px; margin: 0;">
                    Tambahkan rekening bank di pengaturan invoice untuk menampilkan informasi pembayaran.
                </p>
            </div>
            @endif
            
            <!-- Notes -->
            @if($invoice->notes)
            <div class="notes-section">
                <h4>📝 Catatan:</h4>
                <p>{{ $invoice->notes }}</p>
            </div>
            @endif
            
            <!-- Terms -->
            @if($popSetting->invoice_terms)
            <div class="terms-section">
                <h4>📋 Syarat & Ketentuan:</h4>
                <p>{!! nl2br(e($popSetting->invoice_terms)) !!}</p>
            </div>
            @endif
            
            <!-- Footer -->
            <div class="footer">
                @if($popSetting->invoice_footer)
                    {{ $popSetting->invoice_footer }}
                @else
                    Terima kasih atas kepercayaan Anda menggunakan layanan kami.
                @endif
                <div class="footer-note">
                    Dokumen ini dicetak secara otomatis dan sah tanpa tanda tangan.
                </div>
            </div>
        </div>
    </div>
</body>
</html>
