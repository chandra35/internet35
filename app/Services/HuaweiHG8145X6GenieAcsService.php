<?php

namespace App\Services;

/**
 * Alias untuk GenieAcsService — dipertahankan agar kode controller/job yang sudah
 * dirilis tidak perlu diubah. Penanganan device ID dengan karakter '%' (seperti
 * HG8145X6%2D10) sudah ditangani langsung di GenieAcsService::safeDeviceId().
 *
 * Class ini tidak perlu override apapun.
 */
class HuaweiHG8145X6GenieAcsService extends GenieAcsService
{
    // Semua logika ada di parent. Tidak perlu override.
}
