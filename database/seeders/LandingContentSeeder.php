<?php

namespace Database\Seeders;

use App\Models\LandingContent;
use Illuminate\Database\Seeder;

class LandingContentSeeder extends Seeder
{
    public function run(): void
    {
        $contents = [
            // Hero Section
            [
                'section' => 'hero',
                'key' => 'hero_main',
                'title' => 'Internet Cepat & Stabil',
                'subtitle' => 'Untuk Kebutuhan Rumah & Bisnis Anda',
                'content' => 'Nikmati koneksi internet super cepat dengan harga terjangkau. Streaming, gaming, dan bekerja dari rumah tanpa hambatan.',
                'link_text' => 'Daftar Sekarang',
                'link' => '#packages',
                'is_active' => true,
            ],
            
            // About Section
            [
                'section' => 'about',
                'key' => 'about_main',
                'title' => 'Tentang Kami',
                'subtitle' => 'Provider Internet Terpercaya',
                'content' => '<p>Internet35 adalah provider layanan internet yang berkomitmen menyediakan koneksi cepat dan stabil untuk pelanggan kami.</p><p>Dengan pengalaman lebih dari 5 tahun, kami telah melayani ribuan pelanggan di berbagai wilayah.</p>',
                'is_active' => true,
            ],
            
            // Services Section
            [
                'section' => 'services',
                'key' => 'services_header',
                'title' => 'Layanan Kami',
                'subtitle' => 'Solusi Internet Lengkap',
                'is_active' => true,
            ],
            
            // Packages Section
            [
                'section' => 'packages',
                'key' => 'packages_header',
                'title' => 'Pilihan Paket',
                'subtitle' => 'Pilih Paket Sesuai Kebutuhan Anda',
                'is_active' => true,
            ],
            
            // Testimonials Section
            [
                'section' => 'testimonials',
                'key' => 'testimonials_header',
                'title' => 'Apa Kata Mereka',
                'subtitle' => 'Testimoni dari Pelanggan Kami',
                'is_active' => true,
            ],
            
            // FAQ Section
            [
                'section' => 'faq',
                'key' => 'faq_header',
                'title' => 'Pertanyaan Umum',
                'subtitle' => 'Temukan Jawaban yang Anda Cari',
                'is_active' => true,
            ],
            
            // Contact Section
            [
                'section' => 'contact',
                'key' => 'contact_header',
                'title' => 'Hubungi Kami',
                'subtitle' => 'Kami Siap Membantu Anda',
                'content' => 'Tim kami siap melayani Anda 24/7. Hubungi kami melalui telepon, WhatsApp, atau kirim pesan langsung.',
                'is_active' => true,
            ],
            
            // Footer Section
            [
                'section' => 'footer',
                'key' => 'footer_main',
                'title' => '© 2024 Internet35. All rights reserved.',
                'content' => 'Provider internet terpercaya dengan layanan berkualitas tinggi untuk rumah dan bisnis Anda.',
                'is_active' => true,
            ],
        ];

        foreach ($contents as $content) {
            LandingContent::updateOrCreate(
                ['key' => $content['key']],
                $content
            );
        }
    }
}
