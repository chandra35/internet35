<?php

namespace Database\Seeders;

use App\Models\LandingContent;
use App\Models\LandingFaq;
use App\Models\LandingPackage;
use App\Models\LandingService;
use App\Models\LandingSlider;
use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

class LandingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Site Settings
        $settings = [
            ['key' => 'site_name', 'group' => 'general', 'label' => 'Nama Website', 'value' => 'Internet35', 'type' => 'text'],
            ['key' => 'site_tagline', 'group' => 'general', 'label' => 'Tagline', 'value' => 'Internet Cepat & Stabil', 'type' => 'text'],
            ['key' => 'site_description', 'group' => 'general', 'label' => 'Deskripsi', 'value' => 'Provider internet terbaik dengan layanan 24/7', 'type' => 'textarea'],
            ['key' => 'site_logo', 'group' => 'general', 'label' => 'Logo', 'value' => null, 'type' => 'image'],
            ['key' => 'site_favicon', 'group' => 'general', 'label' => 'Favicon', 'value' => null, 'type' => 'image'],
            ['key' => 'contact_email', 'group' => 'contact', 'label' => 'Email', 'value' => 'info@internet35.com', 'type' => 'email'],
            ['key' => 'contact_phone', 'group' => 'contact', 'label' => 'Telepon', 'value' => '+62 812 3456 7890', 'type' => 'text'],
            ['key' => 'contact_whatsapp', 'group' => 'contact', 'label' => 'WhatsApp', 'value' => '6281234567890', 'type' => 'text'],
            ['key' => 'contact_address', 'group' => 'contact', 'label' => 'Alamat', 'value' => 'Jl. Internet No. 35, Jakarta', 'type' => 'textarea'],
            ['key' => 'social_facebook', 'group' => 'social', 'label' => 'Facebook', 'value' => 'https://facebook.com/internet35', 'type' => 'url'],
            ['key' => 'social_instagram', 'group' => 'social', 'label' => 'Instagram', 'value' => 'https://instagram.com/internet35', 'type' => 'url'],
            ['key' => 'social_twitter', 'group' => 'social', 'label' => 'Twitter', 'value' => 'https://twitter.com/internet35', 'type' => 'url'],
            ['key' => 'social_youtube', 'group' => 'social', 'label' => 'YouTube', 'value' => null, 'type' => 'url'],
        ];

        foreach ($settings as $index => $setting) {
            SiteSetting::firstOrCreate(
                ['key' => $setting['key']],
                array_merge($setting, ['order' => $index])
            );
        }

        // Landing Contents
        $contents = [
            [
                'section' => 'hero',
                'key' => 'hero_main',
                'title' => 'Internet Cepat & Stabil untuk Kebutuhan Anda',
                'subtitle' => 'Nikmati koneksi internet berkualitas tinggi dengan harga terjangkau',
                'content' => 'Internet35 hadir memberikan solusi internet terbaik untuk rumah dan bisnis Anda. Dengan teknologi fiber optic terkini, kami menjamin koneksi yang stabil dan cepat.',
                'link' => '#packages',
                'link_text' => 'Lihat Paket',
            ],
            [
                'section' => 'about',
                'key' => 'about_main',
                'title' => 'Tentang Internet35',
                'subtitle' => 'Provider Internet Terpercaya',
                'content' => 'Internet35 adalah penyedia layanan internet yang berkomitmen memberikan koneksi berkualitas tinggi dengan dukungan teknis 24/7. Dengan pengalaman lebih dari 10 tahun, kami telah melayani ribuan pelanggan di seluruh Indonesia.',
            ],
            [
                'section' => 'cta',
                'key' => 'cta_main',
                'title' => 'Siap Bergabung?',
                'subtitle' => 'Daftar sekarang dan dapatkan promo menarik!',
                'link' => '#contact',
                'link_text' => 'Hubungi Kami',
            ],
        ];

        foreach ($contents as $index => $content) {
            LandingContent::firstOrCreate(
                ['key' => $content['key']],
                array_merge($content, ['order' => $index, 'is_active' => true])
            );
        }

        // Landing Services
        $services = [
            ['title' => 'Internet Rumah', 'description' => 'Koneksi internet cepat untuk kebutuhan rumah tangga dengan harga terjangkau.', 'icon' => 'fas fa-home'],
            ['title' => 'Internet Bisnis', 'description' => 'Solusi internet dedicated untuk bisnis dengan SLA dan support 24/7.', 'icon' => 'fas fa-building'],
            ['title' => 'Fiber Optic', 'description' => 'Teknologi fiber optic dengan kecepatan hingga 1 Gbps.', 'icon' => 'fas fa-bolt'],
            ['title' => 'Support 24/7', 'description' => 'Tim support siap membantu Anda kapan saja.', 'icon' => 'fas fa-headset'],
        ];

        foreach ($services as $index => $service) {
            LandingService::firstOrCreate(
                ['title' => $service['title']],
                array_merge($service, ['order' => $index, 'is_active' => true])
            );
        }

        // Landing Packages
        $packages = [
            [
                'name' => 'Basic',
                'description' => 'Paket untuk penggunaan ringan',
                'price' => 150000,
                'period' => 'bulan',
                'speed' => '10 Mbps',
                'features' => ['Unlimited Quota', 'Free Installation', 'Router WiFi', 'Support 24/7'],
                'is_popular' => false,
            ],
            [
                'name' => 'Standard',
                'description' => 'Paket populer untuk keluarga',
                'price' => 250000,
                'period' => 'bulan',
                'speed' => '30 Mbps',
                'features' => ['Unlimited Quota', 'Free Installation', 'Router WiFi Dual Band', 'Support 24/7', 'Free 1 Bulan'],
                'is_popular' => true,
            ],
            [
                'name' => 'Premium',
                'description' => 'Paket untuk pengguna berat',
                'price' => 450000,
                'period' => 'bulan',
                'speed' => '100 Mbps',
                'features' => ['Unlimited Quota', 'Free Installation', 'Router WiFi Mesh', 'Priority Support 24/7', 'Free 2 Bulan', 'Static IP'],
                'is_popular' => false,
            ],
        ];

        foreach ($packages as $index => $package) {
            LandingPackage::firstOrCreate(
                ['name' => $package['name']],
                array_merge($package, ['order' => $index, 'is_active' => true])
            );
        }

        // Landing FAQs
        $faqs = [
            ['question' => 'Berapa lama proses pemasangan?', 'answer' => 'Proses pemasangan biasanya memakan waktu 1-3 hari kerja setelah survey lokasi.', 'category' => 'Instalasi'],
            ['question' => 'Apakah ada biaya instalasi?', 'answer' => 'Tidak ada biaya instalasi untuk semua paket berlangganan.', 'category' => 'Biaya'],
            ['question' => 'Bagaimana cara pembayaran?', 'answer' => 'Kami menerima pembayaran via transfer bank, e-wallet, dan minimarket.', 'category' => 'Pembayaran'],
            ['question' => 'Apakah ada garansi perangkat?', 'answer' => 'Ya, semua perangkat mendapat garansi 1 tahun dari kerusakan pabrik.', 'category' => 'Garansi'],
        ];

        foreach ($faqs as $index => $faq) {
            LandingFaq::firstOrCreate(
                ['question' => $faq['question']],
                array_merge($faq, ['order' => $index, 'is_active' => true])
            );
        }

        $this->command->info('Landing content seeded successfully!');
    }
}
