<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $settings['site_name'] ?? 'Internet35' }} - Layanan Diisolir</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <style>
        :root {
            --primary: #007bff;
            --warning: #ffc107;
            --danger: #dc3545;
            --dark: #343a40;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .isolir-card {
            max-width: 540px;
            width: 100%;
            margin: 2rem;
            border: none;
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }

        .isolir-header {
            background: linear-gradient(135deg, var(--danger), #c0392b);
            color: white;
            padding: 2.5rem 2rem 2rem;
            text-align: center;
        }

        .isolir-header .icon {
            font-size: 3.5rem;
            margin-bottom: 1rem;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.1); opacity: 0.8; }
        }

        .isolir-body {
            padding: 2rem;
            background: white;
        }

        .info-item {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            padding: 0.75rem 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-item .icon {
            color: var(--danger);
            font-size: 1.1rem;
            margin-top: 2px;
            flex-shrink: 0;
        }

        .isolir-footer {
            padding: 1.5rem 2rem;
            background: #f8f9fa;
            text-align: center;
        }

        .btn-bayar {
            background: linear-gradient(135deg, var(--primary), #0056b3);
            border: none;
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 0.5rem;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .btn-bayar:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,123,255,0.3);
            color: white;
        }

        .contact-info {
            margin-top: 1rem;
            font-size: 0.85rem;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="card isolir-card">
        <div class="isolir-header">
            <div class="icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3 class="fw-bold mb-2">Layanan Internet Anda Diisolir</h3>
            <p class="mb-0 opacity-75">Akses internet Anda sementara dibatasi</p>
        </div>

        <div class="isolir-body">
            <div class="info-item">
                <span class="icon"><i class="fas fa-info-circle"></i></span>
                <div>
                    <strong>Mengapa layanan diisolir?</strong>
                    <p class="text-muted mb-0 small">Layanan internet Anda diisolir karena terdapat tagihan yang belum dibayar melewati batas jatuh tempo.</p>
                </div>
            </div>

            <div class="info-item">
                <span class="icon"><i class="fas fa-credit-card"></i></span>
                <div>
                    <strong>Bagaimana cara mengaktifkan kembali?</strong>
                    <p class="text-muted mb-0 small">Segera lakukan pembayaran tagihan Anda. Layanan akan aktif kembali secara otomatis setelah pembayaran dikonfirmasi.</p>
                </div>
            </div>

            <div class="info-item">
                <span class="icon"><i class="fas fa-clock"></i></span>
                <div>
                    <strong>Berapa lama proses aktivasi?</strong>
                    <p class="text-muted mb-0 small">Setelah pembayaran berhasil, layanan akan aktif kembali dalam beberapa menit secara otomatis.</p>
                </div>
            </div>
        </div>

        <div class="isolir-footer">
            @php
                $portalUrl = $settings['app_url'] ?? url('/');
                $portalUrl = rtrim($portalUrl, '/') . '/pelanggan';
            @endphp
            <a href="{{ $portalUrl }}" class="btn btn-bayar">
                <i class="fas fa-sign-in-alt me-2"></i>Login & Bayar Tagihan
            </a>

            <div class="contact-info">
                <p class="mb-1">Butuh bantuan? Hubungi kami:</p>
                @if(!empty($settings['contact_phone']))
                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $settings['contact_phone']) }}" class="text-decoration-none">
                        <i class="fab fa-whatsapp me-1"></i>{{ $settings['contact_phone'] }}
                    </a>
                @endif
                @if(!empty($settings['contact_email']))
                    <span class="mx-2">|</span>
                    <a href="mailto:{{ $settings['contact_email'] }}" class="text-decoration-none">
                        <i class="fas fa-envelope me-1"></i>{{ $settings['contact_email'] }}
                    </a>
                @endif
            </div>
            <p class="text-muted small mt-2 mb-0">&copy; {{ date('Y') }} {{ $settings['site_name'] ?? 'Internet35' }}</p>
        </div>
    </div>
</body>
</html>
