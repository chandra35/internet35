<?php

namespace App\Imports;

use App\Models\Customer;
use App\Models\Package;
use App\Models\Router;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

class CustomerImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    protected string $popId;
    protected array $results = [];
    protected int $successCount = 0;
    protected int $failedCount = 0;
    protected int $skippedCount = 0;
    protected array $errors = [];
    protected array $routerCache = [];
    protected array $packageCache = [];
    protected bool $previewMode = false;
    protected array $previewRows = [];
    protected ?string $defaultPackageId = null;

    public function __construct(string $popId, bool $previewMode = false, ?string $defaultPackageId = null)
    {
        $this->popId = $popId;
        $this->previewMode = $previewMode;
        $this->defaultPackageId = $defaultPackageId;
    }

    /**
     * Process collection of rows
     */
    public function collection(Collection $rows)
    {
        $this->preloadCache();

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;

            try {
                $this->processRow($row->toArray(), $rowNumber);
            } catch (\Exception $e) {
                $this->failedCount++;
                $this->errors[] = [
                    'row' => $rowNumber,
                    'name' => $row['nama'] ?? '-',
                    'error' => $e->getMessage(),
                ];

                if ($this->previewMode) {
                    $this->previewRows[] = [
                        'row' => $rowNumber,
                        'status' => 'error',
                        'name' => trim($row['nama'] ?? '-'),
                        'phone' => trim($row['telepon'] ?? $row['phone'] ?? ''),
                        'username' => trim($row['pppoe_username'] ?? $row['username'] ?? ''),
                        'password' => '***',
                        'router' => trim($row['router'] ?? ''),
                        'package' => trim($row['paket'] ?? ''),
                        'error' => $e->getMessage(),
                    ];
                }
            }
        }
    }

    /**
     * Preload router and package data for faster lookup
     */
    protected function preloadCache(): void
    {
        $routers = Router::where('pop_id', $this->popId)->get();
        foreach ($routers as $router) {
            $this->routerCache[strtolower($router->name)] = $router;
            $this->routerCache[$router->id] = $router;
        }

        $routerIds = $routers->pluck('id');
        $packages = Package::whereIn('router_id', $routerIds)->where('is_active', true)->get();
        foreach ($packages as $package) {
            $key = strtolower($package->name) . '|' . $package->router_id;
            $this->packageCache[$key] = $package;
            $this->packageCache[$package->id] = $package;
            if (!isset($this->packageCache[strtolower($package->name)])) {
                $this->packageCache[strtolower($package->name)] = $package;
            }
        }
    }

    /**
     * Process a single row
     */
    protected function processRow(array $row, int $rowNumber): void
    {
        $row = array_change_key_case($row, CASE_LOWER);

        $name = trim($row['nama'] ?? '');
        $phone = trim($row['telepon'] ?? $row['phone'] ?? $row['no_telepon'] ?? $row['no_hp'] ?? '');
        $pppoeUsername = trim($row['pppoe_username'] ?? $row['username'] ?? '');
        $pppoePassword = trim($row['pppoe_password'] ?? $row['password'] ?? '');

        // Skip empty rows
        if (empty($name)) {
            $this->skippedCount++;
            return;
        }

        // Validate required fields: nama, telepon
        if (empty($phone)) {
            throw new \Exception("Kolom telepon wajib diisi");
        }
        // Username & password opsional (untuk migrasi, bisa diisi nanti)
        $needsCredentials = empty($pppoeUsername) || empty($pppoePassword);

        // Optional: router & paket
        $routerName = trim($row['router'] ?? $row['router_name'] ?? '');
        $packageName = trim($row['paket'] ?? $row['package'] ?? $row['nama_paket'] ?? '');

        $router = null;
        $package = null;

        if (!empty($routerName)) {
            $router = $this->findRouter($routerName);
            if (!$router) {
                throw new \Exception("Router '{$routerName}' tidak ditemukan di POP ini");
            }
        }

        if (!empty($packageName) && $router) {
            $package = $this->findPackage($packageName, $router->id);
            if (!$package) {
                throw new \Exception("Paket '{$packageName}' tidak ditemukan untuk router '{$router->name}'");
            }
        } elseif (!empty($packageName)) {
            $package = $this->findPackage($packageName, '');
            if ($package) {
                $router = $this->routerCache[$package->router_id] ?? Router::find($package->router_id);
            }
        }

        // Apply default package if row has no package
        if (!$package && $this->defaultPackageId) {
            $package = $this->packageCache[$this->defaultPackageId] ?? Package::find($this->defaultPackageId);
            if ($package && !$router) {
                $router = $this->routerCache[$package->router_id] ?? Router::find($package->router_id);
            }
        }

        // Check duplicate PPPoE username (only when username is provided)
        if (!empty($pppoeUsername) && Customer::where('pppoe_username', $pppoeUsername)->exists()) {
            throw new \Exception("PPPoE username '{$pppoeUsername}' sudah digunakan");
        }

        // Check duplicate phone in same POP
        $existingByPhone = Customer::where('pop_id', $this->popId)
            ->where('phone', $phone)
            ->first();
        if ($existingByPhone) {
            $this->skippedCount++;
            $this->errors[] = [
                'row' => $rowNumber,
                'name' => $name,
                'error' => "Telepon '{$phone}' sudah terdaftar ({$existingByPhone->customer_id}) — dilewati",
            ];
            if ($this->previewMode) {
                $this->previewRows[] = [
                    'row' => $rowNumber,
                    'status' => 'skipped',
                    'name' => $name,
                    'phone' => $phone,
                    'username' => $pppoeUsername,
                    'password' => '***',
                    'router' => $routerName,
                    'package' => $packageName,
                    'error' => "Telepon duplikat ({$existingByPhone->customer_id})",
                ];
            }
            return;
        }

        // Username digunakan apa adanya dari Excel (tanpa menambah prefix)
        $finalUsername = $pppoeUsername;

        // Parse optional fields
        $email = trim($row['email'] ?? '');
        $address = trim($row['alamat'] ?? $row['address'] ?? '');
        $nik = trim($row['nik'] ?? $row['no_ktp'] ?? '');
        $gender = strtolower(trim($row['gender'] ?? $row['jenis_kelamin'] ?? ''));
        $serviceType = strtolower(trim($row['tipe_layanan'] ?? $row['service_type'] ?? ''));
        $monthlyFee = $row['biaya_bulanan'] ?? $row['monthly_fee'] ?? null;
        $installationFee = $row['biaya_instalasi'] ?? $row['installation_fee'] ?? null;
        $billingDay = $row['tanggal_tagihan'] ?? $row['billing_day'] ?? null;
        $installationDate = $row['tanggal_instalasi'] ?? $row['installation_date'] ?? null;
        $notes = trim($row['catatan'] ?? $row['notes'] ?? '');

        // Normalize gender
        if (in_array($gender, ['l', 'laki-laki', 'laki', 'pria', 'male', 'm'])) {
            $gender = 'male';
        } elseif (in_array($gender, ['p', 'perempuan', 'wanita', 'female', 'f'])) {
            $gender = 'female';
        } else {
            $gender = null;
        }

        // Normalize service type
        if (!in_array($serviceType, ['pppoe', 'hotspot', 'static'])) {
            $serviceType = 'pppoe';
        }

        // Parse installation date
        if ($installationDate) {
            try {
                $installationDate = \Carbon\Carbon::parse($installationDate)->toDateString();
            } catch (\Exception $e) {
                $installationDate = now()->toDateString();
            }
        } else {
            $installationDate = now()->toDateString();
        }

        // Preview mode: don't save, just collect data
        if ($this->previewMode) {
            $this->previewRows[] = [
                'row' => $rowNumber,
                'status' => 'valid',
                'name' => $name,
                'phone' => $phone,
                'username' => $finalUsername,
                'password' => '***',
                'router' => $router?->name ?? $routerName ?: '-',
                'package' => $package?->name ?? $packageName ?: '-',
                'monthly_fee' => is_numeric($monthlyFee) ? number_format($monthlyFee, 0, ',', '.') : ($package ? number_format($package->price ?? 0, 0, ',', '.') : '-'),
                'error' => null,
            ];
            $this->successCount++;
            return;
        }

        // Create customer
        $customer = Customer::create([
            'pop_id' => $this->popId,
            'customer_id' => Customer::generateCustomerId($this->popId),
            'name' => $name,
            'email' => $email ?: null,
            'phone' => $phone,
            'nik' => $nik ?: null,
            'gender' => $gender,
            'address' => $address ?: null,
            'router_id' => $router?->id,
            'package_id' => $package?->id,
            'pppoe_username' => $finalUsername ?: null,
            'pppoe_password' => $pppoePassword ?: null,
            'service_type' => $serviceType,
            'installation_date' => $installationDate,
            'monthly_fee' => is_numeric($monthlyFee) ? $monthlyFee : ($package->price ?? 0),
            'installation_fee' => is_numeric($installationFee) ? $installationFee : 0,
            'billing_day' => is_numeric($billingDay) && $billingDay >= 1 && $billingDay <= 28 ? (int)$billingDay : 1,
            'status' => 'pending',
            'notes' => $notes ?: null,
            'internal_notes' => '[IMPORT] Diimport dari file Excel pada ' . now()->format('d/m/Y H:i')
                . ($needsCredentials ? ' — ⚠️ Username/password belum diisi, perlu sync ke Mikrotik' : ''),
            'registered_by' => auth()->id(),
            'created_by' => auth()->id(),
        ]);

        $this->successCount++;
        $this->results[] = [
            'row' => $rowNumber,
            'customer_id' => $customer->customer_id,
            'name' => $customer->name,
            'pppoe_username' => $customer->pppoe_username,
        ];
    }

    /**
     * Find router by name or ID
     */
    protected function findRouter(string $nameOrId): ?Router
    {
        if (isset($this->routerCache[$nameOrId])) {
            return $this->routerCache[$nameOrId];
        }
        $key = strtolower($nameOrId);
        if (isset($this->routerCache[$key])) {
            return $this->routerCache[$key];
        }
        foreach ($this->routerCache as $cacheKey => $router) {
            if (is_string($cacheKey) && str_contains($cacheKey, $key)) {
                return $router;
            }
        }
        return null;
    }

    /**
     * Find package by name or ID
     */
    protected function findPackage(string $nameOrId, string $routerId): ?Package
    {
        if (isset($this->packageCache[$nameOrId])) {
            return $this->packageCache[$nameOrId];
        }
        if ($routerId) {
            $key = strtolower($nameOrId) . '|' . $routerId;
            if (isset($this->packageCache[$key])) {
                return $this->packageCache[$key];
            }
        }
        $key = strtolower($nameOrId);
        if (isset($this->packageCache[$key])) {
            return $this->packageCache[$key];
        }
        foreach ($this->packageCache as $cacheKey => $package) {
            if (is_string($cacheKey) && str_contains($cacheKey, $key)) {
                return $package;
            }
        }
        return null;
    }

    /**
     * Get preview rows (only in preview mode)
     */
    public function getPreviewRows(): array
    {
        return $this->previewRows;
    }

    /**
     * Get import results summary
     */
    public function getResults(): array
    {
        return [
            'success_count' => $this->successCount,
            'failed_count' => $this->failedCount,
            'skipped_count' => $this->skippedCount,
            'total_processed' => $this->successCount + $this->failedCount + $this->skippedCount,
            'imported_customers' => $this->results,
            'errors' => $this->errors,
        ];
    }
}
