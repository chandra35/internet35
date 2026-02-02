<?php

namespace Database\Seeders;

use App\Models\MessageTemplate;
use Illuminate\Database\Seeder;

class MessageTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = $this->getDefaultTemplates();

        foreach ($templates as $template) {
            MessageTemplate::updateOrCreate(
                [
                    'code' => $template['code'],
                    'channel' => $template['channel'],
                    'pop_id' => null, // Global templates
                ],
                array_merge($template, [
                    'is_default' => true,
                    'is_active' => true,
                ])
            );
        }
    }

    /**
     * Get default templates
     */
    protected function getDefaultTemplates(): array
    {
        return [
            // ==================== EMAIL TEMPLATES ====================
            
            // Customer Welcome
            [
                'code' => 'customer_welcome',
                'channel' => 'email',
                'name' => 'Selamat Datang Pelanggan',
                'description' => 'Dikirim saat pelanggan baru didaftarkan',
                'email_subject' => 'Selamat Datang di {{isp_name}} - Informasi Akun Anda',
                'email_body' => $this->customerWelcomeEmail(),
                'available_variables' => MessageTemplate::getTemplateInfo('customer_welcome')['variables'],
            ],

            // User Created (Portal Account)
            [
                'code' => 'user_created',
                'channel' => 'email',
                'name' => 'Akun Portal Dibuat',
                'description' => 'Dikirim saat akun portal pelanggan dibuat',
                'email_subject' => 'Akun Portal {{isp_name}} Telah Dibuat',
                'email_body' => $this->userCreatedEmail(),
                'available_variables' => MessageTemplate::getTemplateInfo('user_created')['variables'],
            ],

            // Forgot Password
            [
                'code' => 'forgot_password',
                'channel' => 'email',
                'name' => 'Reset Password',
                'description' => 'Dikirim saat pelanggan request reset password',
                'email_subject' => 'Reset Password Akun {{isp_name}}',
                'email_body' => $this->forgotPasswordEmail(),
                'available_variables' => MessageTemplate::getTemplateInfo('forgot_password')['variables'],
            ],

            // Invoice Created
            [
                'code' => 'invoice_created',
                'channel' => 'email',
                'name' => 'Invoice Dibuat',
                'description' => 'Dikirim saat invoice baru dibuat',
                'email_subject' => 'Invoice #{{invoice_number}} - {{isp_name}}',
                'email_body' => $this->invoiceCreatedEmail(),
                'available_variables' => MessageTemplate::getTemplateInfo('invoice_created')['variables'],
            ],

            // Invoice Reminder
            [
                'code' => 'invoice_reminder',
                'channel' => 'email',
                'name' => 'Pengingat Jatuh Tempo',
                'description' => 'Dikirim H-3, H-1 sebelum jatuh tempo',
                'email_subject' => '⏰ Pengingat: Tagihan Anda Jatuh Tempo {{days_left}} Hari Lagi',
                'email_body' => $this->invoiceReminderEmail(),
                'available_variables' => MessageTemplate::getTemplateInfo('invoice_reminder')['variables'],
            ],

            // Invoice Overdue
            [
                'code' => 'invoice_overdue',
                'channel' => 'email',
                'name' => 'Tagihan Terlambat',
                'description' => 'Dikirim saat tagihan melewati jatuh tempo',
                'email_subject' => '⚠️ Tagihan Terlambat - Segera Lakukan Pembayaran',
                'email_body' => $this->invoiceOverdueEmail(),
                'available_variables' => MessageTemplate::getTemplateInfo('invoice_overdue')['variables'],
            ],

            // Payment Success
            [
                'code' => 'payment_success',
                'channel' => 'email',
                'name' => 'Pembayaran Berhasil',
                'description' => 'Dikirim setelah pembayaran dikonfirmasi',
                'email_subject' => '✅ Pembayaran Berhasil - {{isp_name}}',
                'email_body' => $this->paymentSuccessEmail(),
                'available_variables' => MessageTemplate::getTemplateInfo('payment_success')['variables'],
            ],

            // Service Isolated
            [
                'code' => 'service_isolated',
                'channel' => 'email',
                'name' => 'Layanan Diisolir',
                'description' => 'Dikirim saat layanan pelanggan diisolir',
                'email_subject' => '🔴 Layanan Internet Anda Telah Diisolir',
                'email_body' => $this->serviceIsolatedEmail(),
                'available_variables' => MessageTemplate::getTemplateInfo('service_isolated')['variables'],
            ],

            // Service Activated
            [
                'code' => 'service_activated',
                'channel' => 'email',
                'name' => 'Layanan Diaktifkan',
                'description' => 'Dikirim saat layanan diaktifkan kembali',
                'email_subject' => '🟢 Layanan Internet Anda Telah Aktif Kembali',
                'email_body' => $this->serviceActivatedEmail(),
                'available_variables' => MessageTemplate::getTemplateInfo('service_activated')['variables'],
            ],

            // Service Expired
            [
                'code' => 'service_expired',
                'channel' => 'email',
                'name' => 'Layanan Kedaluwarsa',
                'description' => 'Dikirim saat masa aktif layanan habis',
                'email_subject' => '⚠️ Masa Aktif Layanan Internet Anda Telah Habis',
                'email_body' => $this->serviceExpiredEmail(),
                'available_variables' => MessageTemplate::getTemplateInfo('service_expired')['variables'],
            ],

            // ==================== WHATSAPP TEMPLATES ====================

            // Customer Welcome - WA
            [
                'code' => 'customer_welcome',
                'channel' => 'whatsapp',
                'name' => 'Selamat Datang Pelanggan',
                'description' => 'Dikirim saat pelanggan baru didaftarkan',
                'wa_body' => $this->customerWelcomeWA(),
                'available_variables' => MessageTemplate::getTemplateInfo('customer_welcome')['variables'],
            ],

            // User Created - WA
            [
                'code' => 'user_created',
                'channel' => 'whatsapp',
                'name' => 'Akun Portal Dibuat',
                'description' => 'Dikirim saat akun portal pelanggan dibuat',
                'wa_body' => $this->userCreatedWA(),
                'available_variables' => MessageTemplate::getTemplateInfo('user_created')['variables'],
            ],

            // Forgot Password - WA
            [
                'code' => 'forgot_password',
                'channel' => 'whatsapp',
                'name' => 'Reset Password',
                'description' => 'Dikirim saat pelanggan request reset password',
                'wa_body' => $this->forgotPasswordWA(),
                'available_variables' => MessageTemplate::getTemplateInfo('forgot_password')['variables'],
            ],

            // Invoice Created - WA
            [
                'code' => 'invoice_created',
                'channel' => 'whatsapp',
                'name' => 'Invoice Dibuat',
                'description' => 'Dikirim saat invoice baru dibuat',
                'wa_body' => $this->invoiceCreatedWA(),
                'available_variables' => MessageTemplate::getTemplateInfo('invoice_created')['variables'],
            ],

            // Invoice Reminder - WA
            [
                'code' => 'invoice_reminder',
                'channel' => 'whatsapp',
                'name' => 'Pengingat Jatuh Tempo',
                'description' => 'Dikirim H-3, H-1 sebelum jatuh tempo',
                'wa_body' => $this->invoiceReminderWA(),
                'available_variables' => MessageTemplate::getTemplateInfo('invoice_reminder')['variables'],
            ],

            // Invoice Overdue - WA
            [
                'code' => 'invoice_overdue',
                'channel' => 'whatsapp',
                'name' => 'Tagihan Terlambat',
                'description' => 'Dikirim saat tagihan melewati jatuh tempo',
                'wa_body' => $this->invoiceOverdueWA(),
                'available_variables' => MessageTemplate::getTemplateInfo('invoice_overdue')['variables'],
            ],

            // Payment Success - WA
            [
                'code' => 'payment_success',
                'channel' => 'whatsapp',
                'name' => 'Pembayaran Berhasil',
                'description' => 'Dikirim setelah pembayaran dikonfirmasi',
                'wa_body' => $this->paymentSuccessWA(),
                'available_variables' => MessageTemplate::getTemplateInfo('payment_success')['variables'],
            ],

            // Service Isolated - WA
            [
                'code' => 'service_isolated',
                'channel' => 'whatsapp',
                'name' => 'Layanan Diisolir',
                'description' => 'Dikirim saat layanan pelanggan diisolir',
                'wa_body' => $this->serviceIsolatedWA(),
                'available_variables' => MessageTemplate::getTemplateInfo('service_isolated')['variables'],
            ],

            // Service Activated - WA
            [
                'code' => 'service_activated',
                'channel' => 'whatsapp',
                'name' => 'Layanan Diaktifkan',
                'description' => 'Dikirim saat layanan diaktifkan kembali',
                'wa_body' => $this->serviceActivatedWA(),
                'available_variables' => MessageTemplate::getTemplateInfo('service_activated')['variables'],
            ],

            // Service Expired - WA
            [
                'code' => 'service_expired',
                'channel' => 'whatsapp',
                'name' => 'Layanan Kedaluwarsa',
                'description' => 'Dikirim saat masa aktif layanan habis',
                'wa_body' => $this->serviceExpiredWA(),
                'available_variables' => MessageTemplate::getTemplateInfo('service_expired')['variables'],
            ],
        ];
    }

    // ==================== EMAIL TEMPLATE BODIES ====================

    protected function customerWelcomeEmail(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
        .info-box { background: white; padding: 20px; border-radius: 8px; margin: 15px 0; border-left: 4px solid #667eea; }
        .credentials { background: #fff3cd; padding: 15px; border-radius: 8px; margin: 15px 0; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
        .btn { display: inline-block; padding: 12px 30px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎉 Selamat Datang!</h1>
            <p>Terima kasih telah bergabung dengan {{isp_name}}</p>
        </div>
        <div class="content">
            <p>Halo <strong>{{customer_name}}</strong>,</p>
            
            <p>Selamat! Anda telah resmi terdaftar sebagai pelanggan {{isp_name}}. Berikut adalah informasi akun Anda:</p>
            
            <div class="info-box">
                <h3>📋 Informasi Pelanggan</h3>
                <table style="width:100%">
                    <tr><td><strong>ID Pelanggan:</strong></td><td>{{customer_id}}</td></tr>
                    <tr><td><strong>Nama:</strong></td><td>{{customer_name}}</td></tr>
                    <tr><td><strong>Paket:</strong></td><td>{{package_name}}</td></tr>
                    <tr><td><strong>Harga:</strong></td><td>{{package_price}}/bulan</td></tr>
                </table>
            </div>
            
            <div class="credentials">
                <h3>🔐 Login Portal Pelanggan</h3>
                <p><strong>ID Pelanggan:</strong> {{customer_id}}</p>
                <p><strong>Password:</strong> {{password}}</p>
                <p><em>Gunakan ID Pelanggan atau Email untuk login ke portal pelanggan. Simpan informasi ini dengan aman!</em></p>
            </div>
            
            <p>Anda dapat mengakses portal pelanggan untuk melihat tagihan dan melakukan pembayaran:</p>
            <p style="text-align:center">
                <a href="{{login_url}}" class="btn">Akses Portal Pelanggan</a>
            </p>
            
            <p>Jika Anda memiliki pertanyaan, jangan ragu untuk menghubungi kami.</p>
        </div>
        <div class="footer">
            <p><strong>{{isp_name}}</strong></p>
            <p>📞 {{isp_phone}} | ✉️ {{isp_email}}</p>
            <p>{{isp_address}}</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    protected function userCreatedEmail(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
        .credentials { background: #d4edda; padding: 20px; border-radius: 8px; margin: 15px 0; border: 1px solid #c3e6cb; }
        .btn { display: inline-block; padding: 12px 30px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔐 Akun Portal Telah Dibuat</h1>
        </div>
        <div class="content">
            <p>Halo <strong>{{customer_name}}</strong>,</p>
            
            <p>Akun portal pelanggan Anda telah berhasil dibuat. Gunakan informasi berikut untuk login:</p>
            
            <div class="credentials">
                <h3>Informasi Login</h3>
                <p><strong>ID Pelanggan / Email:</strong> {{customer_id}} atau {{email}}</p>
                <p><strong>Password:</strong> {{password}}</p>
            </div>
            
            <p style="text-align:center">
                <a href="{{login_url}}" class="btn">Login ke Portal</a>
            </p>
            
            <p><strong>Penting:</strong> Segera ubah password Anda setelah login pertama kali untuk keamanan akun.</p>
        </div>
        <div class="footer">
            <p><strong>{{isp_name}}</strong></p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    protected function forgotPasswordEmail(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #dc3545; color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
        .btn { display: inline-block; padding: 15px 40px; background: #dc3545; color: white; text-decoration: none; border-radius: 5px; font-size: 16px; }
        .warning { background: #fff3cd; padding: 15px; border-radius: 8px; margin: 15px 0; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔑 Reset Password</h1>
        </div>
        <div class="content">
            <p>Halo <strong>{{customer_name}}</strong>,</p>
            
            <p>Kami menerima permintaan untuk mereset password akun Anda. Klik tombol di bawah untuk membuat password baru:</p>
            
            <p style="text-align:center; margin: 30px 0;">
                <a href="{{reset_url}}" class="btn">Reset Password</a>
            </p>
            
            <div class="warning">
                <strong>⚠️ Perhatian:</strong>
                <ul>
                    <li>Link ini akan kedaluwarsa dalam {{expire_minutes}} menit</li>
                    <li>Jika Anda tidak meminta reset password, abaikan email ini</li>
                    <li>Jangan bagikan link ini kepada siapapun</li>
                </ul>
            </div>
        </div>
        <div class="footer">
            <p><strong>{{isp_name}}</strong></p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    protected function invoiceCreatedEmail(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #17a2b8; color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
        .invoice-box { background: white; padding: 20px; border-radius: 8px; margin: 15px 0; border: 2px solid #17a2b8; }
        .amount { font-size: 32px; color: #17a2b8; font-weight: bold; text-align: center; margin: 20px 0; }
        .btn { display: inline-block; padding: 12px 30px; background: #28a745; color: white; text-decoration: none; border-radius: 5px; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📄 Invoice Baru</h1>
            <p>#{{invoice_number}}</p>
        </div>
        <div class="content">
            <p>Halo <strong>{{customer_name}}</strong>,</p>
            
            <p>Invoice untuk periode {{period}} telah dibuat. Berikut detailnya:</p>
            
            <div class="invoice-box">
                <table style="width:100%">
                    <tr><td>No. Invoice:</td><td><strong>{{invoice_number}}</strong></td></tr>
                    <tr><td>Tanggal Invoice:</td><td>{{invoice_date}}</td></tr>
                    <tr><td>Jatuh Tempo:</td><td><strong style="color:#dc3545">{{due_date}}</strong></td></tr>
                    <tr><td>Paket:</td><td>{{package_name}}</td></tr>
                    <tr><td>Periode:</td><td>{{period}}</td></tr>
                </table>
                <div class="amount">{{amount}}</div>
            </div>
            
            <p>Metode Pembayaran:</p>
            <div style="background:#e9ecef;padding:15px;border-radius:5px;">
                {{bank_accounts}}
            </div>
            
            <p style="text-align:center; margin-top: 20px;">
                <a href="{{payment_url}}" class="btn">Bayar Sekarang</a>
            </p>
        </div>
        <div class="footer">
            <p><strong>{{isp_name}}</strong></p>
            <p>📞 {{isp_phone}}</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    protected function invoiceReminderEmail(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #ffc107; color: #333; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
        .alert { background: #fff3cd; padding: 20px; border-radius: 8px; margin: 15px 0; border: 1px solid #ffc107; text-align: center; }
        .days-left { font-size: 48px; color: #ffc107; font-weight: bold; }
        .btn { display: inline-block; padding: 12px 30px; background: #28a745; color: white; text-decoration: none; border-radius: 5px; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⏰ Pengingat Pembayaran</h1>
        </div>
        <div class="content">
            <p>Halo <strong>{{customer_name}}</strong>,</p>
            
            <div class="alert">
                <p>Tagihan Anda akan jatuh tempo dalam:</p>
                <div class="days-left">{{days_left}} Hari</div>
            </div>
            
            <table style="width:100%; margin: 20px 0;">
                <tr><td>No. Invoice:</td><td><strong>{{invoice_number}}</strong></td></tr>
                <tr><td>Jatuh Tempo:</td><td><strong>{{due_date}}</strong></td></tr>
                <tr><td>Jumlah:</td><td><strong style="color:#28a745">{{amount}}</strong></td></tr>
            </table>
            
            <p>Segera lakukan pembayaran untuk menghindari pemutusan layanan.</p>
            
            <p style="text-align:center">
                <a href="{{payment_url}}" class="btn">Bayar Sekarang</a>
            </p>
        </div>
        <div class="footer">
            <p><strong>{{isp_name}}</strong> | 📞 {{isp_phone}}</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    protected function invoiceOverdueEmail(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #dc3545; color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
        .alert { background: #f8d7da; padding: 20px; border-radius: 8px; margin: 15px 0; border: 1px solid #f5c6cb; }
        .total { font-size: 32px; color: #dc3545; font-weight: bold; text-align: center; margin: 20px 0; }
        .btn { display: inline-block; padding: 15px 40px; background: #dc3545; color: white; text-decoration: none; border-radius: 5px; font-size: 16px; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⚠️ TAGIHAN TERLAMBAT</h1>
        </div>
        <div class="content">
            <p>Halo <strong>{{customer_name}}</strong>,</p>
            
            <div class="alert">
                <h3>🔴 Tagihan Anda telah melewati jatuh tempo {{days_overdue}} hari!</h3>
            </div>
            
            <table style="width:100%; margin: 20px 0;">
                <tr><td>No. Invoice:</td><td><strong>{{invoice_number}}</strong></td></tr>
                <tr><td>Jatuh Tempo:</td><td><strong style="color:#dc3545">{{due_date}}</strong></td></tr>
                <tr><td>Tagihan:</td><td>{{amount}}</td></tr>
                <tr><td>Denda Keterlambatan:</td><td style="color:#dc3545">{{late_fee}}</td></tr>
            </table>
            
            <div class="total">Total: {{total_amount}}</div>
            
            <p><strong>⚠️ PERINGATAN:</strong> Layanan internet Anda akan diisolir jika pembayaran tidak segera dilakukan!</p>
            
            <p style="text-align:center">
                <a href="{{payment_url}}" class="btn">BAYAR SEKARANG</a>
            </p>
        </div>
        <div class="footer">
            <p><strong>{{isp_name}}</strong> | 📞 {{isp_phone}}</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    protected function paymentSuccessEmail(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #28a745; color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
        .success-box { background: #d4edda; padding: 20px; border-radius: 8px; margin: 15px 0; border: 1px solid #c3e6cb; text-align: center; }
        .amount { font-size: 32px; color: #28a745; font-weight: bold; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>✅ Pembayaran Berhasil!</h1>
        </div>
        <div class="content">
            <p>Halo <strong>{{customer_name}}</strong>,</p>
            
            <div class="success-box">
                <h2>Terima Kasih!</h2>
                <p>Pembayaran Anda telah kami terima</p>
                <div class="amount">{{amount}}</div>
            </div>
            
            <table style="width:100%; margin: 20px 0;">
                <tr><td>No. Invoice:</td><td><strong>{{invoice_number}}</strong></td></tr>
                <tr><td>Tanggal Bayar:</td><td>{{payment_date}}</td></tr>
                <tr><td>Metode:</td><td>{{payment_method}}</td></tr>
                <tr><td>Layanan Aktif Hingga:</td><td><strong style="color:#28a745">{{active_until}}</strong></td></tr>
            </table>
            
            <p>Terima kasih telah menjadi pelanggan setia kami! 🙏</p>
        </div>
        <div class="footer">
            <p><strong>{{isp_name}}</strong></p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    protected function serviceIsolatedEmail(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #dc3545; color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
        .alert { background: #f8d7da; padding: 20px; border-radius: 8px; margin: 15px 0; border: 2px solid #dc3545; }
        .btn { display: inline-block; padding: 15px 40px; background: #28a745; color: white; text-decoration: none; border-radius: 5px; font-size: 16px; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔴 Layanan Diisolir</h1>
        </div>
        <div class="content">
            <p>Halo <strong>{{customer_name}}</strong>,</p>
            
            <div class="alert">
                <h3>Layanan internet Anda telah diisolir</h3>
                <p><strong>Alasan:</strong> {{isolate_reason}}</p>
            </div>
            
            <p>Untuk mengaktifkan kembali layanan, segera lakukan pembayaran tagihan yang tertunggak:</p>
            
            <table style="width:100%; margin: 20px 0;">
                <tr><td>No. Invoice:</td><td><strong>{{invoice_number}}</strong></td></tr>
                <tr><td>Jumlah:</td><td><strong style="color:#dc3545">{{amount}}</strong></td></tr>
            </table>
            
            <p style="text-align:center">
                <a href="{{payment_url}}" class="btn">Bayar & Aktifkan Kembali</a>
            </p>
            
            <p>Setelah pembayaran dikonfirmasi, layanan Anda akan aktif kembali secara otomatis.</p>
        </div>
        <div class="footer">
            <p><strong>{{isp_name}}</strong> | 📞 {{isp_phone}}</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    protected function serviceActivatedEmail(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #28a745; color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
        .success { background: #d4edda; padding: 20px; border-radius: 8px; margin: 15px 0; text-align: center; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🟢 Layanan Aktif Kembali!</h1>
        </div>
        <div class="content">
            <p>Halo <strong>{{customer_name}}</strong>,</p>
            
            <div class="success">
                <h2>🎉 Selamat!</h2>
                <p>Layanan internet Anda telah aktif kembali</p>
            </div>
            
            <table style="width:100%; margin: 20px 0;">
                <tr><td>Paket:</td><td><strong>{{package_name}}</strong></td></tr>
                <tr><td>Aktif Hingga:</td><td><strong style="color:#28a745">{{active_until}}</strong></td></tr>
            </table>
            
            <p>Terima kasih telah melakukan pembayaran. Selamat berselancar! 🌐</p>
        </div>
        <div class="footer">
            <p><strong>{{isp_name}}</strong></p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    protected function serviceExpiredEmail(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #ffc107; color: #333; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
        .alert { background: #fff3cd; padding: 20px; border-radius: 8px; margin: 15px 0; }
        .btn { display: inline-block; padding: 12px 30px; background: #28a745; color: white; text-decoration: none; border-radius: 5px; }
        .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⚠️ Masa Aktif Habis</h1>
        </div>
        <div class="content">
            <p>Halo <strong>{{customer_name}}</strong>,</p>
            
            <div class="alert">
                <p>Masa aktif layanan internet Anda telah berakhir pada <strong>{{expired_date}}</strong>.</p>
            </div>
            
            <p>Paket: <strong>{{package_name}}</strong></p>
            
            <p>Untuk melanjutkan penggunaan layanan, silakan perpanjang langganan Anda:</p>
            
            <p style="text-align:center">
                <a href="{{renewal_url}}" class="btn">Perpanjang Sekarang</a>
            </p>
        </div>
        <div class="footer">
            <p><strong>{{isp_name}}</strong> | 📞 {{isp_phone}}</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    // ==================== WHATSAPP TEMPLATE BODIES ====================

    protected function customerWelcomeWA(): string
    {
        return <<<'TEXT'
🎉 *Selamat Datang di {{isp_name}}!*

Halo *{{customer_name}}*,

Terima kasih telah bergabung dengan kami. Berikut informasi akun Anda:

📋 *Data Pelanggan*
• ID: {{customer_id}}
• Paket: {{package_name}}
• Harga: {{package_price}}/bulan

🔐 *Login Portal Pelanggan*
• ID Pelanggan: `{{customer_id}}`
• Password: `{{password}}`

🌐 Login Portal: {{login_url}}

Hubungi kami jika ada pertanyaan:
📞 {{isp_phone}}
✉️ {{isp_email}}

Terima kasih! 🙏
TEXT;
    }

    protected function userCreatedWA(): string
    {
        return <<<'TEXT'
🔐 *Akun Portal Dibuat*

Halo *{{customer_name}}*,

Akun portal Anda telah dibuat.

*Informasi Login:*
• ID/Email: {{customer_id}}
• Password: {{password}}

🌐 Login: {{login_url}}

Segera ubah password setelah login pertama.

_{{isp_name}}_
TEXT;
    }

    protected function forgotPasswordWA(): string
    {
        return <<<'TEXT'
🔑 *Reset Password*

Halo *{{customer_name}}*,

Klik link berikut untuk reset password:
{{reset_url}}

⚠️ Link berlaku {{expire_minutes}} menit.

Jika Anda tidak meminta reset password, abaikan pesan ini.

_{{isp_name}}_
TEXT;
    }

    protected function invoiceCreatedWA(): string
    {
        return <<<'TEXT'
📄 *Invoice Baru*

Halo *{{customer_name}}*,

Invoice untuk periode *{{period}}* telah dibuat:

• No. Invoice: *{{invoice_number}}*
• Jatuh Tempo: *{{due_date}}*
• Jumlah: *{{amount}}*

💳 Pembayaran:
{{bank_accounts}}

🌐 Bayar Online: {{payment_url}}

_{{isp_name}}_ | 📞 {{isp_phone}}
TEXT;
    }

    protected function invoiceReminderWA(): string
    {
        return <<<'TEXT'
⏰ *Pengingat Pembayaran*

Halo *{{customer_name}}*,

Tagihan Anda akan jatuh tempo dalam *{{days_left}} hari*!

• Invoice: *{{invoice_number}}*
• Jatuh Tempo: *{{due_date}}*
• Jumlah: *{{amount}}*

Segera bayar untuk menghindari pemutusan layanan.

🌐 Bayar: {{payment_url}}

_{{isp_name}}_ | 📞 {{isp_phone}}
TEXT;
    }

    protected function invoiceOverdueWA(): string
    {
        return <<<'TEXT'
⚠️ *TAGIHAN TERLAMBAT*

Halo *{{customer_name}}*,

Tagihan Anda telah *terlambat {{days_overdue}} hari*!

• Invoice: *{{invoice_number}}*
• Jatuh Tempo: {{due_date}}
• Tagihan: {{amount}}
• Denda: {{late_fee}}
• *TOTAL: {{total_amount}}*

🔴 Layanan akan diisolir jika tidak segera dibayar!

🌐 Bayar Sekarang: {{payment_url}}

_{{isp_name}}_ | 📞 {{isp_phone}}
TEXT;
    }

    protected function paymentSuccessWA(): string
    {
        return <<<'TEXT'
✅ *Pembayaran Berhasil!*

Halo *{{customer_name}}*,

Terima kasih! Pembayaran Anda telah kami terima.

• Invoice: {{invoice_number}}
• Jumlah: *{{amount}}*
• Tanggal: {{payment_date}}
• Metode: {{payment_method}}

🟢 Layanan aktif hingga: *{{active_until}}*

Terima kasih telah menjadi pelanggan setia! 🙏

_{{isp_name}}_
TEXT;
    }

    protected function serviceIsolatedWA(): string
    {
        return <<<'TEXT'
🔴 *LAYANAN DIISOLIR*

Halo *{{customer_name}}*,

Layanan internet Anda telah diisolir.
Alasan: *{{isolate_reason}}*

Untuk mengaktifkan kembali, segera bayar tagihan:
• Invoice: {{invoice_number}}
• Jumlah: *{{amount}}*

🌐 Bayar: {{payment_url}}

Setelah pembayaran dikonfirmasi, layanan akan aktif otomatis.

_{{isp_name}}_ | 📞 {{isp_phone}}
TEXT;
    }

    protected function serviceActivatedWA(): string
    {
        return <<<'TEXT'
🟢 *LAYANAN AKTIF KEMBALI!*

Halo *{{customer_name}}*,

Selamat! Layanan internet Anda telah aktif kembali. 🎉

• Paket: {{package_name}}
• Aktif hingga: *{{active_until}}*

Terima kasih telah melakukan pembayaran!
Selamat berselancar! 🌐

_{{isp_name}}_
TEXT;
    }

    protected function serviceExpiredWA(): string
    {
        return <<<'TEXT'
⚠️ *Masa Aktif Habis*

Halo *{{customer_name}}*,

Masa aktif layanan internet Anda telah habis pada *{{expired_date}}*.

Paket: {{package_name}}

Untuk melanjutkan layanan, silakan perpanjang:
🌐 {{renewal_url}}

_{{isp_name}}_ | 📞 {{isp_phone}}
TEXT;
    }
}
