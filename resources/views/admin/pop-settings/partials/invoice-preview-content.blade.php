<div class="invoice-preview-content">
    <!-- Mini Invoice Preview -->
    <div class="invoice-mini" style="font-size: 10px; line-height: 1.4;">
        <!-- Header -->
        <div style="display: flex; justify-content: space-between; border-bottom: 2px solid #667eea; padding-bottom: 10px; margin-bottom: 15px;">
            <div>
                @if($popSetting->isp_logo)
                <img src="{{ Storage::url($popSetting->isp_logo) }}" alt="Logo" style="max-height: 40px; margin-bottom: 5px;">
                @endif
                <div style="font-size: 14px; font-weight: bold; color: #667eea;">{{ $popSetting->isp_name ?? 'Nama ISP Anda' }}</div>
                <div style="color: #666; font-size: 9px;">
                    {{ Str::limit($popSetting->address ?? 'Alamat perusahaan', 50) }}<br>
                    Telp: {{ $popSetting->phone ?? '08xx-xxxx-xxxx' }}
                </div>
            </div>
            <div style="text-align: right;">
                <div style="font-size: 18px; font-weight: bold; color: #667eea;">INVOICE</div>
                <div style="font-weight: bold;">{{ $invoice->invoice_number }}</div>
                <span style="display: inline-block; padding: 3px 10px; border-radius: 10px; font-size: 8px; background: #ffc107; color: #000; margin-top: 5px;">
                    PREVIEW
                </span>
            </div>
        </div>

        <!-- Customer Info -->
        <div style="display: flex; gap: 20px; margin-bottom: 15px;">
            <div style="flex: 1;">
                <div style="font-size: 8px; color: #999; text-transform: uppercase; letter-spacing: 1px;">Ditagihkan Kepada</div>
                <div style="font-weight: bold; margin-top: 3px;">{{ $invoice->customer->name }}</div>
                <div style="color: #666; font-size: 9px;">{{ $invoice->customer->customer_id }}</div>
            </div>
            <div style="flex: 1; background: #f8f9fa; padding: 8px; border-radius: 5px;">
                <table style="width: 100%; font-size: 9px;">
                    <tr>
                        <td style="color: #666;">Tanggal</td>
                        <td style="text-align: right; font-weight: 600;">{{ $invoice->invoice_date->format('d/m/Y') }}</td>
                    </tr>
                    <tr>
                        <td style="color: #666;">Jatuh Tempo</td>
                        <td style="text-align: right; font-weight: 600; color: #dc3545;">{{ $invoice->due_date->format('d/m/Y') }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Items -->
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 10px;">
            <thead>
                <tr style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff;">
                    <th style="padding: 6px 8px; text-align: left; font-size: 8px; border-radius: 4px 0 0 0;">Deskripsi</th>
                    <th style="padding: 6px 8px; text-align: right; font-size: 8px; border-radius: 0 4px 0 0;">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $item)
                <tr>
                    <td style="padding: 6px 8px; border-bottom: 1px solid #eee;">{{ Str::limit($item['description'], 40) }}</td>
                    <td style="padding: 6px 8px; border-bottom: 1px solid #eee; text-align: right;">Rp {{ number_format($item['amount'] ?? 0, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td style="padding: 5px 8px; text-align: right; color: #666;">Subtotal</td>
                    <td style="padding: 5px 8px; text-align: right;">Rp {{ number_format($invoice->subtotal, 0, ',', '.') }}</td>
                </tr>
                @if($invoice->tax_amount > 0)
                <tr>
                    <td style="padding: 5px 8px; text-align: right; color: #666;">PPN ({{ $popSetting->ppn_percentage ?? 11 }}%)</td>
                    <td style="padding: 5px 8px; text-align: right;">Rp {{ number_format($invoice->tax_amount, 0, ',', '.') }}</td>
                </tr>
                @endif
                <tr style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff;">
                    <td style="padding: 8px; text-align: right; font-weight: bold; border-radius: 0 0 0 4px;">TOTAL</td>
                    <td style="padding: 8px; text-align: right; font-weight: bold; font-size: 12px; border-radius: 0 0 4px 0;">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>

        <!-- Bank Info -->
        @if($popSetting->bank_accounts && count($popSetting->bank_accounts) > 0)
        <div style="background: #f8f9fa; padding: 10px; border-radius: 5px; margin-top: 10px;">
            <div style="font-size: 9px; font-weight: bold; margin-bottom: 8px;">💳 Pembayaran:</div>
            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                @foreach($popSetting->bank_accounts as $bank)
                <div style="background: #fff; padding: 6px 10px; border-radius: 4px; border-left: 3px solid #667eea; font-size: 9px;">
                    <div style="color: #667eea; font-weight: bold;">{{ $bank['bank_name'] ?? '-' }}</div>
                    <div style="font-weight: bold;">{{ $bank['account_number'] ?? '-' }}</div>
                </div>
                @endforeach
            </div>
        </div>
        @else
        <div style="background: #fff3cd; padding: 8px 10px; border-radius: 5px; margin-top: 10px; font-size: 9px; color: #856404;">
            ⚠️ Rekening bank belum dikonfigurasi
        </div>
        @endif

        <!-- Notes -->
        @if($invoice->notes)
        <div style="background: #fff3cd; padding: 8px 10px; border-radius: 5px; margin-top: 10px; border-left: 3px solid #ffc107;">
            <div style="font-size: 9px; font-weight: bold; color: #856404;">📝 Catatan:</div>
            <div style="font-size: 9px; color: #856404;">{{ Str::limit($invoice->notes, 100) }}</div>
        </div>
        @endif

        <!-- Terms -->
        @if($popSetting->invoice_terms)
        <div style="background: #e7f5ff; padding: 8px 10px; border-radius: 5px; margin-top: 10px; border-left: 3px solid #17a2b8;">
            <div style="font-size: 9px; font-weight: bold; color: #0c5460;">📋 Syarat & Ketentuan:</div>
            <div style="font-size: 8px; color: #0c5460;">{{ Str::limit($popSetting->invoice_terms, 150) }}</div>
        </div>
        @endif

        <!-- Footer -->
        <div style="text-align: center; margin-top: 15px; padding-top: 10px; border-top: 1px solid #ddd; color: #666; font-size: 9px;">
            @if($popSetting->invoice_footer)
                {{ $popSetting->invoice_footer }}
            @else
                Terima kasih atas kepercayaan Anda menggunakan layanan kami.
            @endif
        </div>
    </div>
</div>
