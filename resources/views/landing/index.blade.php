<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $settings['site_name'] ?? 'Internet35' }} - Internet Provider Terpercaya</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <style>
        :root {
            --primary: #007bff;
            --secondary: #6c757d;
            --success: #28a745;
            --dark: #343a40;
        }

        body {
            font-family: 'Poppins', sans-serif;
            overflow-x: hidden;
        }

        /* Navbar */
        .navbar {
            padding: 15px 0;
            transition: all 0.3s;
        }

        .navbar.scrolled {
            background: rgba(255, 255, 255, 0.98) !important;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            color: var(--primary) !important;
        }

        .nav-link {
            font-weight: 500;
            padding: 10px 20px !important;
            color: var(--dark) !important;
            transition: color 0.3s;
        }

        .nav-link:hover {
            color: var(--primary) !important;
        }

        /* Hero Section */
        .hero-section {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%23ffffff" fill-opacity="0.1" d="M0,192L48,181.3C96,171,192,149,288,154.7C384,160,480,192,576,213.3C672,235,768,245,864,234.7C960,224,1056,192,1152,176C1248,160,1344,160,1392,160L1440,160L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>');
            background-position: bottom;
            background-repeat: no-repeat;
            background-size: cover;
        }

        .hero-content {
            position: relative;
            z-index: 1;
        }

        .hero-title {
            font-size: 3.5rem;
            font-weight: 700;
            color: white;
            margin-bottom: 20px;
        }

        .hero-subtitle {
            font-size: 1.2rem;
            color: rgba(255,255,255,0.9);
            margin-bottom: 30px;
        }

        .btn-hero {
            padding: 15px 40px;
            font-weight: 600;
            border-radius: 50px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Sections */
        section {
            padding: 100px 0;
        }

        .section-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 15px;
            color: var(--dark);
        }

        .section-subtitle {
            color: var(--secondary);
            margin-bottom: 50px;
        }

        /* Services */
        .service-card {
            padding: 40px 30px;
            border-radius: 20px;
            background: white;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            height: 100%;
        }

        .service-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }

        .service-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 25px;
        }

        .service-icon i {
            font-size: 2rem;
            color: white;
        }

        /* Packages */
        .package-card {
            border-radius: 20px;
            background: white;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.3s;
            height: 100%;
        }

        .package-card:hover {
            transform: translateY(-10px);
        }

        .package-card.featured {
            border: 3px solid var(--primary);
            transform: scale(1.05);
        }

        .package-card.featured:hover {
            transform: scale(1.05) translateY(-10px);
        }

        .package-header {
            padding: 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-align: center;
        }

        .package-name {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .package-price {
            font-size: 2.5rem;
            font-weight: 700;
        }

        .package-price small {
            font-size: 1rem;
            font-weight: 400;
        }

        .package-body {
            padding: 30px;
        }

        .package-feature {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }

        .package-feature:last-child {
            border-bottom: none;
        }

        .package-feature i {
            color: var(--success);
            margin-right: 10px;
        }

        /* Testimonials */
        .testimonial-card {
            padding: 40px;
            border-radius: 20px;
            background: white;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .testimonial-text {
            font-style: italic;
            font-size: 1.1rem;
            color: var(--secondary);
            margin-bottom: 25px;
        }

        .testimonial-author {
            display: flex;
            align-items: center;
        }

        .testimonial-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 15px;
        }

        .testimonial-name {
            font-weight: 600;
            margin-bottom: 0;
        }

        .testimonial-position {
            color: var(--secondary);
            font-size: 0.9rem;
        }

        /* FAQ */
        .faq-section {
            background: #f8f9fa;
        }

        .accordion-button {
            font-weight: 600;
            padding: 20px 25px;
        }

        .accordion-button:not(.collapsed) {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .accordion-body {
            padding: 25px;
        }

        /* CTA */
        .cta-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        /* Footer */
        footer {
            background: var(--dark);
            color: white;
            padding: 60px 0 20px;
        }

        footer h5 {
            font-weight: 600;
            margin-bottom: 25px;
        }

        footer a {
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            transition: color 0.3s;
        }

        footer a:hover {
            color: white;
        }

        .social-links a {
            width: 40px;
            height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: rgba(255,255,255,0.1);
            margin-right: 10px;
            transition: all 0.3s;
        }

        .social-links a:hover {
            background: var(--primary);
            transform: translateY(-3px);
        }

        /* Back to Top */
        .back-to-top {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 50px;
            height: 50px;
            background: var(--primary);
            color: white;
            border-radius: 50%;
            display: none;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            transition: all 0.3s;
            z-index: 1000;
        }

        .back-to-top:hover {
            transform: translateY(-5px);
        }

        .back-to-top.show {
            display: flex;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }
            
            .section-title {
                font-size: 2rem;
            }
            
            .package-card.featured {
                transform: scale(1);
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-transparent fixed-top">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-wifi me-2"></i>{{ $settings['site_name'] ?? 'Internet35' }}
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="#home">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="#services">Layanan</a></li>
                    <li class="nav-item"><a class="nav-link" href="#packages">Paket</a></li>
                    <li class="nav-item"><a class="nav-link" href="#testimonials">Testimoni</a></li>
                    <li class="nav-item"><a class="nav-link" href="#faq">FAQ</a></li>
                    <li class="nav-item"><a class="nav-link btn btn-primary text-white ms-2 px-4" href="{{ route('login') }}">Login</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section" id="home">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 hero-content" data-aos="fade-right">
                    <h1 class="hero-title">{{ $contents['hero']['hero_main']->title ?? 'Internet Cepat untuk Kebutuhan Anda' }}</h1>
                    <p class="hero-subtitle">{{ $contents['hero']['hero_main']->content ?? 'Nikmati koneksi internet super cepat dengan harga terjangkau. Streaming, gaming, dan bekerja tanpa hambatan.' }}</p>
                    <a href="{{ $contents['hero']['hero_main']->link ?? '#packages' }}" class="btn btn-light btn-hero me-3">
                        {{ $contents['hero']['hero_main']->link_text ?? 'Lihat Paket' }}
                    </a>
                    <a href="https://wa.me/{{ $settings['contact_whatsapp'] ?? '' }}" class="btn btn-outline-light btn-hero" target="_blank">
                        <i class="fab fa-whatsapp me-2"></i>Hubungi Kami
                    </a>
                </div>
                <div class="col-lg-6" data-aos="fade-left">
                    @if(isset($contents['hero']['hero_main']->image) && $contents['hero']['hero_main']->image)
                        <img src="{{ asset('storage/contents/' . $contents['hero']['hero_main']->image) }}" alt="Hero" class="img-fluid">
                    @else
                        <img src="https://illustrations.popsy.co/amber/remote-work.svg" alt="Internet" class="img-fluid">
                    @endif
                </div>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section id="services">
        <div class="container">
            <div class="text-center" data-aos="fade-up">
                <h2 class="section-title">{{ $contents['services']['services_header']->title ?? 'Layanan Kami' }}</h2>
                <p class="section-subtitle">{{ $contents['services']['services_header']->subtitle ?? 'Solusi internet terbaik untuk rumah dan bisnis Anda' }}</p>
            </div>
            <div class="row g-4">
                @forelse($services as $service)
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="{{ $loop->index * 100 }}">
                    <div class="service-card">
                        <div class="service-icon">
                            <i class="{{ $service->icon }}"></i>
                        </div>
                        <h4>{{ $service->title }}</h4>
                        <p class="text-muted">{{ $service->description }}</p>
                    </div>
                </div>
                @empty
                <div class="col-lg-4 col-md-6" data-aos="fade-up">
                    <div class="service-card">
                        <div class="service-icon"><i class="fas fa-bolt"></i></div>
                        <h4>Internet Super Cepat</h4>
                        <p class="text-muted">Kecepatan hingga 1 Gbps untuk pengalaman internet tanpa buffering</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="100">
                    <div class="service-card">
                        <div class="service-icon"><i class="fas fa-clock"></i></div>
                        <h4>Uptime 99.9%</h4>
                        <p class="text-muted">Jaminan koneksi stabil sepanjang waktu dengan backup sistem</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="200">
                    <div class="service-card">
                        <div class="service-icon"><i class="fas fa-headset"></i></div>
                        <h4>Support 24/7</h4>
                        <p class="text-muted">Tim support siap membantu Anda kapanpun dibutuhkan</p>
                    </div>
                </div>
                @endforelse
            </div>
        </div>
    </section>

    <!-- Packages Section -->
    <section id="packages" class="bg-light">
        <div class="container">
            <div class="text-center" data-aos="fade-up">
                <h2 class="section-title">{{ $contents['packages']['packages_header']->title ?? 'Pilihan Paket' }}</h2>
                <p class="section-subtitle">{{ $contents['packages']['packages_header']->subtitle ?? 'Pilih paket yang sesuai dengan kebutuhan Anda' }}</p>
            </div>
            <div class="row g-4 justify-content-center">
                @forelse($packages as $package)
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="{{ $loop->index * 100 }}">
                    <div class="package-card {{ $package->is_featured ? 'featured' : '' }}">
                        <div class="package-header">
                            @if($package->is_featured)
                            <span class="badge bg-warning mb-2">POPULER</span>
                            @endif
                            <h4 class="package-name">{{ $package->name }}</h4>
                            <p class="package-price">
                                Rp {{ number_format($package->price, 0, ',', '.') }}
                                <small>/bulan</small>
                            </p>
                        </div>
                        <div class="package-body">
                            <div class="package-feature">
                                <i class="fas fa-tachometer-alt"></i> {{ $package->speed }} Mbps
                            </div>
                            @php
                                $features = is_array($package->features) ? $package->features : (is_string($package->features) ? explode("\n", $package->features) : []);
                            @endphp
                            @foreach($features as $feature)
                            <div class="package-feature">
                                <i class="fas fa-check"></i> {{ $feature }}
                            </div>
                            @endforeach
                            <div class="text-center mt-4">
                                <a href="https://wa.me/{{ $settings['whatsapp'] ?? '' }}?text=Saya%20tertarik%20dengan%20paket%20{{ urlencode($package->name) }}" 
                                   class="btn btn-primary btn-lg w-100" target="_blank">
                                    Pilih Paket
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                @empty
                <div class="col-lg-4 col-md-6" data-aos="fade-up">
                    <div class="package-card">
                        <div class="package-header">
                            <h4 class="package-name">Basic</h4>
                            <p class="package-price">Rp 150.000<small>/bulan</small></p>
                        </div>
                        <div class="package-body">
                            <div class="package-feature"><i class="fas fa-tachometer-alt"></i> 10 Mbps</div>
                            <div class="package-feature"><i class="fas fa-check"></i> Unlimited Kuota</div>
                            <div class="package-feature"><i class="fas fa-check"></i> Free Installation</div>
                            <div class="text-center mt-4">
                                <a href="#" class="btn btn-primary btn-lg w-100">Pilih Paket</a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="100">
                    <div class="package-card featured">
                        <div class="package-header">
                            <span class="badge bg-warning mb-2">POPULER</span>
                            <h4 class="package-name">Standard</h4>
                            <p class="package-price">Rp 250.000<small>/bulan</small></p>
                        </div>
                        <div class="package-body">
                            <div class="package-feature"><i class="fas fa-tachometer-alt"></i> 30 Mbps</div>
                            <div class="package-feature"><i class="fas fa-check"></i> Unlimited Kuota</div>
                            <div class="package-feature"><i class="fas fa-check"></i> Free Installation</div>
                            <div class="package-feature"><i class="fas fa-check"></i> Free Router</div>
                            <div class="text-center mt-4">
                                <a href="#" class="btn btn-primary btn-lg w-100">Pilih Paket</a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="200">
                    <div class="package-card">
                        <div class="package-header">
                            <h4 class="package-name">Premium</h4>
                            <p class="package-price">Rp 450.000<small>/bulan</small></p>
                        </div>
                        <div class="package-body">
                            <div class="package-feature"><i class="fas fa-tachometer-alt"></i> 100 Mbps</div>
                            <div class="package-feature"><i class="fas fa-check"></i> Unlimited Kuota</div>
                            <div class="package-feature"><i class="fas fa-check"></i> Free Installation</div>
                            <div class="package-feature"><i class="fas fa-check"></i> Free Router Premium</div>
                            <div class="package-feature"><i class="fas fa-check"></i> Priority Support</div>
                            <div class="text-center mt-4">
                                <a href="#" class="btn btn-primary btn-lg w-100">Pilih Paket</a>
                            </div>
                        </div>
                    </div>
                </div>
                @endforelse
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section id="testimonials">
        <div class="container">
            <div class="text-center" data-aos="fade-up">
                <h2 class="section-title">{{ $contents['testimonials']['testimonials_header']->title ?? 'Apa Kata Pelanggan' }}</h2>
                <p class="section-subtitle">{{ $contents['testimonials']['testimonials_header']->subtitle ?? 'Testimoni dari pelanggan setia kami' }}</p>
            </div>
            <div class="row g-4">
                @forelse($testimonials as $testimonial)
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="{{ $loop->index * 100 }}">
                    <div class="testimonial-card">
                        <div class="mb-3">
                            @for($i = 0; $i < $testimonial->rating; $i++)
                            <i class="fas fa-star text-warning"></i>
                            @endfor
                        </div>
                        <p class="testimonial-text">"{{ $testimonial->content }}"</p>
                        <div class="testimonial-author">
                            <img src="{{ $testimonial->avatar ?? 'https://ui-avatars.com/api/?name=' . urlencode($testimonial->name) }}" 
                                 alt="{{ $testimonial->name }}" class="testimonial-avatar">
                            <div>
                                <h6 class="testimonial-name">{{ $testimonial->name }}</h6>
                                <span class="testimonial-position">{{ $testimonial->position }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                @empty
                <div class="col-lg-4 col-md-6" data-aos="fade-up">
                    <div class="testimonial-card">
                        <div class="mb-3">
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                        </div>
                        <p class="testimonial-text">"Internet sangat stabil, cocok untuk WFH. Sudah berlangganan 2 tahun tanpa masalah berarti."</p>
                        <div class="testimonial-author">
                            <img src="https://ui-avatars.com/api/?name=Budi+Santoso" alt="Budi" class="testimonial-avatar">
                            <div>
                                <h6 class="testimonial-name">Budi Santoso</h6>
                                <span class="testimonial-position">Programmer</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="100">
                    <div class="testimonial-card">
                        <div class="mb-3">
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                        </div>
                        <p class="testimonial-text">"Harga terjangkau dengan kualitas premium. Support juga sangat responsif!"</p>
                        <div class="testimonial-author">
                            <img src="https://ui-avatars.com/api/?name=Siti+Rahayu" alt="Siti" class="testimonial-avatar">
                            <div>
                                <h6 class="testimonial-name">Siti Rahayu</h6>
                                <span class="testimonial-position">Ibu Rumah Tangga</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="200">
                    <div class="testimonial-card">
                        <div class="mb-3">
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                        </div>
                        <p class="testimonial-text">"Untuk gaming online, internetnya sangat recommended. Ping rendah dan stabil."</p>
                        <div class="testimonial-author">
                            <img src="https://ui-avatars.com/api/?name=Ahmad+Fauzi" alt="Ahmad" class="testimonial-avatar">
                            <div>
                                <h6 class="testimonial-name">Ahmad Fauzi</h6>
                                <span class="testimonial-position">Gamer</span>
                            </div>
                        </div>
                    </div>
                </div>
                @endforelse
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section id="faq" class="faq-section">
        <div class="container">
            <div class="text-center" data-aos="fade-up">
                <h2 class="section-title">{{ $contents['faq']['faq_header']->title ?? 'Pertanyaan Umum' }}</h2>
                <p class="section-subtitle">{{ $contents['faq']['faq_header']->subtitle ?? 'Temukan jawaban untuk pertanyaan yang sering diajukan' }}</p>
            </div>
            <div class="row justify-content-center">
                <div class="col-lg-8" data-aos="fade-up">
                    <div class="accordion" id="faqAccordion">
                        @forelse($faqs as $index => $faq)
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button {{ $index > 0 ? 'collapsed' : '' }}" type="button" 
                                        data-bs-toggle="collapse" data-bs-target="#faq{{ $index }}">
                                    {{ $faq->question }}
                                </button>
                            </h2>
                            <div id="faq{{ $index }}" class="accordion-collapse collapse {{ $index == 0 ? 'show' : '' }}" 
                                 data-bs-parent="#faqAccordion">
                                <div class="accordion-body">{{ $faq->answer }}</div>
                            </div>
                        </div>
                        @empty
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq0">
                                    Bagaimana cara berlangganan?
                                </button>
                            </h2>
                            <div id="faq0" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Anda dapat menghubungi kami melalui WhatsApp atau datang langsung ke kantor kami. Tim kami akan membantu proses pendaftaran dan instalasi.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                    Berapa lama proses instalasi?
                                </button>
                            </h2>
                            <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Proses instalasi biasanya memakan waktu 1-3 hari kerja setelah pendaftaran, tergantung lokasi dan ketersediaan jadwal teknisi.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                    Apakah ada biaya instalasi?
                                </button>
                            </h2>
                            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Untuk pelanggan baru, kami memberikan gratis biaya instalasi. Anda hanya perlu membayar biaya langganan bulan pertama.
                                </div>
                            </div>
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container text-center" data-aos="fade-up">
            <h2 class="mb-4">{{ $contents['contact']['contact_header']->title ?? 'Siap untuk Internet Lebih Cepat?' }}</h2>
            <p class="mb-4">{{ $contents['contact']['contact_header']->content ?? 'Hubungi kami sekarang dan dapatkan penawaran terbaik!' }}</p>
            <a href="https://wa.me/{{ $settings['contact_whatsapp'] ?? '' }}" class="btn btn-light btn-hero" target="_blank">
                <i class="fab fa-whatsapp me-2"></i>Hubungi via WhatsApp
            </a>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4">
                    <h5><i class="fas fa-wifi me-2"></i>{{ $settings['site_name'] ?? 'Internet35' }}</h5>
                    <p class="text-muted">{{ $contents['footer']['footer_main']->content ?? 'Provider internet terpercaya dengan layanan berkualitas tinggi untuk rumah dan bisnis Anda.' }}</p>
                    <div class="social-links">
                        @if(!empty($settings['social_facebook']))
                        <a href="{{ $settings['social_facebook'] }}" target="_blank"><i class="fab fa-facebook-f"></i></a>
                        @endif
                        @if(!empty($settings['social_instagram']))
                        <a href="{{ $settings['social_instagram'] }}" target="_blank"><i class="fab fa-instagram"></i></a>
                        @endif
                        @if(!empty($settings['social_twitter']))
                        <a href="{{ $settings['social_twitter'] }}" target="_blank"><i class="fab fa-twitter"></i></a>
                        @endif
                        @if(!empty($settings['social_youtube']))
                        <a href="{{ $settings['social_youtube'] }}" target="_blank"><i class="fab fa-youtube"></i></a>
                        @endif
                        @if(!empty($settings['social_tiktok']))
                        <a href="{{ $settings['social_tiktok'] }}" target="_blank"><i class="fab fa-tiktok"></i></a>
                        @endif
                    </div>
                </div>
                <div class="col-lg-2">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#home">Home</a></li>
                        <li class="mb-2"><a href="#services">Layanan</a></li>
                        <li class="mb-2"><a href="#packages">Paket</a></li>
                        <li class="mb-2"><a href="#faq">FAQ</a></li>
                    </ul>
                </div>
                <div class="col-lg-3">
                    <h5>Layanan</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#">Internet Rumah</a></li>
                        <li class="mb-2"><a href="#">Internet Bisnis</a></li>
                        <li class="mb-2"><a href="#">Dedicated Line</a></li>
                        <li class="mb-2"><a href="#">Wifi Hotspot</a></li>
                    </ul>
                </div>
                <div class="col-lg-3">
                    <h5>Kontak</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><i class="fas fa-map-marker-alt me-2"></i>{{ $settings['contact_address'] ?? 'Jl. Contoh No. 123, Kota' }}</li>
                        <li class="mb-2"><i class="fas fa-phone me-2"></i>{{ $settings['contact_phone'] ?? '021-1234567' }}</li>
                        <li class="mb-2"><i class="fas fa-envelope me-2"></i>{{ $settings['contact_email'] ?? 'info@internet35.com' }}</li>
                    </ul>
                </div>
            </div>
            <hr class="my-4" style="border-color: rgba(255,255,255,0.1);">
            <div class="text-center text-muted">
                <p class="mb-0">&copy; {{ date('Y') }} {{ $settings['site_name'] ?? 'Internet35' }}. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Back to Top -->
    <div class="back-to-top" id="backToTop">
        <i class="fas fa-chevron-up"></i>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        // Initialize AOS
        AOS.init({
            duration: 800,
            once: true
        });

        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            const backToTop = document.getElementById('backToTop');
            
            if (window.scrollY > 100) {
                navbar.classList.add('scrolled');
                backToTop.classList.add('show');
            } else {
                navbar.classList.remove('scrolled');
                backToTop.classList.remove('show');
            }
        });

        // Smooth scroll
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Back to top
        document.getElementById('backToTop').addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    </script>
</body>
</html>
