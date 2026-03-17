<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 11px; color: #333; line-height: 1.5; }
        .container { padding: 30px 40px; }

        /* Header */
        .header { display: table; width: 100%; margin-bottom: 25px; border-bottom: 3px solid #007bff; padding-bottom: 15px; }
        .header-left { display: table-cell; width: 60%; vertical-align: middle; }
        .header-right { display: table-cell; width: 40%; text-align: right; vertical-align: middle; }
        .isp-name { font-size: 20px; font-weight: bold; color: #007bff; }
        .isp-info { font-size: 9px; color: #777; margin-top: 4px; }
        .invoice-title { font-size: 24px; font-weight: bold; color: #333; }
        .invoice-number { font-size: 12px; color: #666; margin-top: 2px; }

        /* Status badge */
        .status-badge { display: inline-block; padding: 3px 12px; border-radius: 3px; font-size: 10px; font-weight: bold; text-transform: uppercase; color: #fff; }
        .status-pending { background: #ffc107; color: #333; }
        .status-paid { background: #28a745; }
        .status-overdue { background: #dc3545; }
        .status-partial { background: #17a2b8; }
        .status-cancelled { background: #6c757d; }

        /* Info row */
        .info-row { display: table; width: 100%; margin-bottom: 20px; }
        .info-col { display: table-cell; width: 50%; vertical-align: top; }
        .info-label { font-size: 9px; text-transform: uppercase; color: #999; letter-spacing: 1px; margin-bottom: 4px; }
        .info-value { font-size: 11px; }

        /* Table */
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .items-table th { background: #f8f9fa; border-bottom: 2px solid #dee2e6; padding: 8px 12px; text-align: left; font-size: 10px; text-transform: uppercase; color: #666; letter-spacing: 0.5px; }
        .items-table td { padding: 10px 12px; border-bottom: 1px solid #eee; }
        .items-table .text-right { text-align: right; }

        /* Totals */
        .totals-table { width: 300px; margin-left: auto; margin-bottom: 25px; }
        .totals-table td { padding: 4px 12px; }
        .totals-table .total-row td { border-top: 2px solid #333; font-weight: bold; font-size: 14px; padding-top: 8px; }
        .totals-table .text-right { text-align: right; }
        .totals-table .text-success { color: #28a745; }
        .totals-table .text-danger { color: #dc3545; }

        /* Period info */
        .period-box { background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 4px; padding: 10px 15px; margin-bottom: 20px; }
        .period-box strong { color: #007bff; }

        /* Payment info */
        .payment-section { margin-top: 20px; border-top: 1px solid #eee; padding-top: 15px; }
        .payment-section h3 { font-size: 12px; margin-bottom: 8px; color: #333; }
        .bank-accounts { background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 4px; padding: 10px 15px; }
        .bank-item { margin-bottom: 6px; }
        .bank-item:last-child { margin-bottom: 0; }

        /* Footer */
        .footer { margin-top: 30px; border-top: 1px solid #dee2e6; padding-top: 12px; text-align: center; font-size: 9px; color: #999; }
        .notes { background: #fff3cd; border: 1px solid #ffc107; border-radius: 4px; padding: 8px 12px; font-size: 10px; margin-bottom: 15px; }

        /* Payment history */
        .payment-history { margin-top: 15px; }
        .payment-history table { width: 100%; border-collapse: collapse; font-size: 10px; }
        .payment-history th { background: #e9ecef; padding: 5px 8px; text-align: left; }
        .payment-history td { padding: 5px 8px; border-bottom: 1px solid #eee; }
    </style>
</head>
<body>
<div class="container">
    <!-- Header -->
    <div class="header">
        <div class="header-left">
            @if($popSetting?->logo_url)
            <img src="{{ public_path(str_replace('/storage', 'storage', $popSetting->logo_url)) }}" style="max-height: 50px; max-width: 180px; margin-bottom: 5px;" alt="Logo">
            @endif
            <div class="isp-name">{{ $popSetting?->isp_name ?? config('app.name') }}</div>
            <div class="isp-info">
                @if($popSetting?->address){{ $popSetting->address }}<br>@endif
                @php
                    $ispRegion = collect([$popSetting?->village?->name, $popSetting?->district?->name, $popSetting?->city?->name, $popSetting?->province?->name])->filter()->implode(', ');
                @endphp
                @if($ispRegion){{ $ispRegion }}<br>@endif
                @if($popSetting?->phone)Telp: {{ $popSetting->phone }}@endif
                @if($popSetting?->phone && $popSetting?->email) | @endif
                @if($popSetting?->email)Email: {{ $popSetting->email }}@endif
                @if($popSetting?->website)<br>{{ $popSetting->website }}@endif
            </div>
        </div>
        <div class="header-right">
            <div class="invoice-title">INVOICE</div>
            <div class="invoice-number">#{{ $invoice->invoice_number }}</div>
            <div style="margin-top: 5px;">
                <span class="status-badge status-{{ $invoice->status }}">{{ $invoice->status_label }}</span>
            </div>
        </div>
    </div>

    <!-- Customer & Invoice Info -->
    <div class="info-row">
        <div class="info-col">
            <div class="info-label">Ditagihkan Kepada</div>
            <div class="info-value">
                <strong>{{ $customer->name }}</strong><br>
                <span style="color: #666;">ID: {{ $customer->customer_id }}</span><br>
                @if($customer->address){{ $customer->address }}<br>@endif
                {{ $customer->village?->name ? $customer->village->name . ', ' : '' }}{{ $customer->district?->name ?? '' }}<br>
                {{ $customer->city?->name ? $customer->city->name . ', ' : '' }}{{ $customer->province?->name ?? '' }}
                @if($customer->phone)<br>Telp: {{ $customer->phone }}@endif
                @if($customer->email)<br>Email: {{ $customer->email }}@endif
            </div>
        </div>
        <div class="info-col" style="text-align: right;">
            <div class="info-label">Detail Invoice</div>
            <div class="info-value">
                <strong>Tanggal Invoice:</strong> {{ $invoice->invoice_date?->format('d F Y') }}<br>
                <strong>Jatuh Tempo:</strong> <span style="color: {{ $invoice->isOverdue() ? '#dc3545' : '#333' }};">{{ $invoice->due_date?->format('d F Y') }}</span><br>
                @if($invoice->paid_at)
                <strong>Dibayar:</strong> {{ $invoice->paid_at->format('d F Y') }}
                @endif
            </div>
        </div>
    </div>

    <!-- Period -->
    <div class="period-box">
        <strong>Periode Layanan:</strong> {{ $invoice->period_start?->format('d M Y') }} — {{ $invoice->period_end?->format('d M Y') }}
    </div>

    <!-- Items Table -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 65%;">Deskripsi</th>
                <th style="width: 30%;" class="text-right">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            @php $no = 1; @endphp
            @if(is_array($invoice->items))
                @foreach($invoice->items as $item)
                <tr>
                    <td>{{ $no++ }}</td>
                    <td>{{ $item['description'] ?? 'Layanan Internet' }}</td>
                    <td class="text-right">Rp {{ number_format($item['amount'] ?? 0, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            @else
                <tr>
                    <td>1</td>
                    <td>Layanan Internet {{ $customer->package?->name ?? '' }}</td>
                    <td class="text-right">Rp {{ number_format($invoice->subtotal, 0, ',', '.') }}</td>
                </tr>
            @endif
        </tbody>
    </table>

    <!-- Totals -->
    <table class="totals-table">
        <tr>
            <td>Subtotal</td>
            <td class="text-right">Rp {{ number_format($invoice->subtotal, 0, ',', '.') }}</td>
        </tr>
        @if($invoice->discount_amount > 0)
        <tr class="text-success">
            <td>Diskon</td>
            <td class="text-right">- Rp {{ number_format($invoice->discount_amount, 0, ',', '.') }}</td>
        </tr>
        @endif
        @if($invoice->tax_amount > 0)
        <tr>
            <td>PPN {{ ($popSetting?->ppn_percentage ?? 11) }}%</td>
            <td class="text-right">Rp {{ number_format($invoice->tax_amount, 0, ',', '.') }}</td>
        </tr>
        @endif
        <tr class="total-row">
            <td>Total</td>
            <td class="text-right">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</td>
        </tr>
        @if($invoice->paid_amount > 0)
        <tr>
            <td class="text-success">Dibayar</td>
            <td class="text-right text-success">- Rp {{ number_format($invoice->paid_amount, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td><strong>Sisa Tagihan</strong></td>
            <td class="text-right {{ $invoice->remaining_amount > 0 ? 'text-danger' : 'text-success' }}">
                <strong>Rp {{ number_format($invoice->remaining_amount, 0, ',', '.') }}</strong>
            </td>
        </tr>
        @endif
    </table>

    <!-- Notes -->
    @if($invoice->notes)
    <div class="notes">
        <strong>Catatan:</strong> {{ $invoice->notes }}
    </div>
    @endif

    <!-- Bank Accounts -->
    @if($popSetting?->bank_accounts && count($popSetting->bank_accounts) > 0)
    <div class="payment-section">
        <h3>Informasi Pembayaran</h3>
        <div class="bank-accounts">
            @foreach($popSetting->bank_accounts as $bank)
            <div class="bank-item">
                <strong>{{ $bank['bank_name'] ?? '' }}</strong>
                — {{ $bank['account_number'] ?? '' }}
                a.n. {{ $bank['account_name'] ?? '' }}
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Payment History -->
    @if($invoice->payments->where('status', 'success')->count() > 0)
    <div class="payment-history">
        <h3 style="font-size: 12px; margin-bottom: 8px;">Riwayat Pembayaran</h3>
        <table>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Metode</th>
                    <th>Jumlah</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->payments->where('status', 'success') as $payment)
                <tr>
                    <td>{{ $payment->paid_at?->format('d/m/Y') ?? $payment->created_at->format('d/m/Y') }}</td>
                    <td>{{ ucfirst($payment->payment_method) }}</td>
                    <td>Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
                    <td>{{ $payment->status_label }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Terms -->
    @if($popSetting?->invoice_terms)
    <div style="margin-top: 20px; font-size: 9px; color: #999;">
        <strong>Syarat & Ketentuan:</strong><br>
        {!! nl2br(e($popSetting->invoice_terms)) !!}
    </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        @if($popSetting?->invoice_footer)
            {{ $popSetting->invoice_footer }}
        @else
            {{ $popSetting?->isp_name ?? config('app.name') }} — Invoice ini digenerate secara otomatis.
        @endif
        <br>
        Dicetak pada: {{ now()->format('d F Y H:i') }}
    </div>
</div>
</body>
</html>
