<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

class SiteSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // General Settings
            [
                'key' => 'site_name',
                'label' => 'Nama Situs',
                'value' => 'Internet35',
                'type' => 'text',
                'group' => 'general',
            ],
            [
                'key' => 'site_tagline',
                'label' => 'Tagline',
                'value' => 'Internet Cepat & Stabil untuk Kebutuhan Anda',
                'type' => 'text',
                'group' => 'general',
            ],
            [
                'key' => 'site_description',
                'label' => 'Deskripsi',
                'value' => 'Provider internet terpercaya dengan layanan berkualitas tinggi',
                'type' => 'textarea',
                'group' => 'general',
            ],
            [
                'key' => 'site_logo',
                'label' => 'Logo',
                'value' => null,
                'type' => 'image',
                'group' => 'general',
            ],
            [
                'key' => 'site_favicon',
                'label' => 'Favicon',
                'value' => null,
                'type' => 'image',
                'group' => 'general',
            ],
            
            // Contact Settings
            [
                'key' => 'contact_phone',
                'label' => 'Nomor Telepon',
                'value' => '+62 812-3456-7890',
                'type' => 'text',
                'group' => 'contact',
            ],
            [
                'key' => 'contact_whatsapp',
                'label' => 'WhatsApp',
                'value' => '6281234567890',
                'type' => 'text',
                'group' => 'contact',
                'description' => 'Format: 62xxx tanpa + atau 0',
            ],
            [
                'key' => 'contact_email',
                'label' => 'Email',
                'value' => 'info@internet35.com',
                'type' => 'email',
                'group' => 'contact',
            ],
            [
                'key' => 'contact_address',
                'label' => 'Alamat',
                'value' => 'Jl. Contoh No. 123, Kota, Indonesia',
                'type' => 'textarea',
                'group' => 'contact',
            ],
            [
                'key' => 'contact_map_lat',
                'label' => 'Latitude',
                'value' => '-6.200000',
                'type' => 'text',
                'group' => 'contact',
            ],
            [
                'key' => 'contact_map_lng',
                'label' => 'Longitude',
                'value' => '106.816666',
                'type' => 'text',
                'group' => 'contact',
            ],
            
            // Social Media
            [
                'key' => 'social_facebook',
                'label' => 'Facebook',
                'value' => 'https://facebook.com/internet35',
                'type' => 'url',
                'group' => 'social',
            ],
            [
                'key' => 'social_instagram',
                'label' => 'Instagram',
                'value' => 'https://instagram.com/internet35',
                'type' => 'url',
                'group' => 'social',
            ],
            [
                'key' => 'social_twitter',
                'label' => 'Twitter/X',
                'value' => 'https://twitter.com/internet35',
                'type' => 'url',
                'group' => 'social',
            ],
            [
                'key' => 'social_youtube',
                'label' => 'YouTube',
                'value' => '',
                'type' => 'url',
                'group' => 'social',
            ],
            [
                'key' => 'social_tiktok',
                'label' => 'TikTok',
                'value' => '',
                'type' => 'url',
                'group' => 'social',
            ],
            
            // SEO Settings
            [
                'key' => 'seo_title',
                'label' => 'Meta Title',
                'value' => 'Internet35 - Provider Internet Cepat & Terpercaya',
                'type' => 'text',
                'group' => 'seo',
            ],
            [
                'key' => 'seo_description',
                'label' => 'Meta Description',
                'value' => 'Internet35 menyediakan layanan internet cepat dan stabil untuk rumah dan bisnis. Berbagai paket dengan harga terjangkau.',
                'type' => 'textarea',
                'group' => 'seo',
            ],
            [
                'key' => 'seo_keywords',
                'label' => 'Meta Keywords',
                'value' => 'internet, provider, wifi, fiber optic, broadband',
                'type' => 'text',
                'group' => 'seo',
            ],
            [
                'key' => 'seo_og_image',
                'label' => 'OG Image',
                'value' => null,
                'type' => 'image',
                'group' => 'seo',
            ],
            
            // Appearance
            [
                'key' => 'appearance_primary_color',
                'label' => 'Warna Utama',
                'value' => '#667eea',
                'type' => 'color',
                'group' => 'appearance',
            ],
            [
                'key' => 'appearance_secondary_color',
                'label' => 'Warna Sekunder',
                'value' => '#764ba2',
                'type' => 'color',
                'group' => 'appearance',
            ],
        ];

        foreach ($settings as $setting) {
            SiteSetting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
