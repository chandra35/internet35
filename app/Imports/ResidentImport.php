<?php

namespace App\Imports;

use App\Imports\Concerns\SmartNikCleaner;
use App\Models\Resident;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

class ResidentImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    use SmartNikCleaner;
    protected string $uploadedBy;
    protected ?string $provinceCode;
    protected ?string $cityCode;
    protected ?string $districtCode;
    protected ?string $villageCode;
    protected int $successCount = 0;
    protected int $failedCount = 0;
    protected int $skippedCount = 0;
    protected int $updatedCount = 0;
    protected int $flaggedCount = 0;
    protected int $autoCorrectedCount = 0;
    protected array $errors = [];

    public function __construct(
        string $uploadedBy,
        ?string $provinceCode = null,
        ?string $cityCode = null,
        ?string $districtCode = null,
        ?string $villageCode = null
    ) {
        $this->uploadedBy = $uploadedBy;
        $this->provinceCode = $provinceCode;
        $this->cityCode = $cityCode;
        $this->districtCode = $districtCode;
        $this->villageCode = $villageCode;
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;

            try {
                $this->processRow($row->toArray(), $rowNumber);
            } catch (\Exception $e) {
                $this->failedCount++;
                $this->errors[] = [
                    'row' => $rowNumber,
                    'nama' => $row['nama_lengkap'] ?? $row['nama'] ?? '-',
                    'error' => $e->getMessage(),
                ];
            }
        }
    }

    protected function processRow(array $row, int $rowNumber): void
    {
        $row = array_change_key_case($row, CASE_LOWER);

        // Support multiple header name variations
        $rawNik = trim($row['nik'] ?? '');
        $nama = trim($row['nama_lengkap'] ?? $row['nama'] ?? '');
        $rawNoKk = trim($row['no_kk'] ?? $row['no_kk'] ?? '');

        // Smart clean NIK and No KK
        [$nik, $nikModified, $nikCorrections] = $this->smartCleanNumericId($rawNik);
        [$noKk, $noKkModified, $noKkCorrections] = $this->smartCleanNumericId($rawNoKk);

        // Completely empty row (no name AND no NIK) → skip
        if (empty($nik) && empty($nama)) {
            $this->skippedCount++;
            return;
        }

        if (empty($nama)) {
            throw new \Exception("Kolom NAMA LENGKAP wajib diisi");
        }

        // Determine data status and notes
        $dataStatus = 'valid';
        $dataNotes = [];

        // Track auto-corrections
        if ($nikModified && !empty($nikCorrections)) {
            $dataNotes[] = 'NIK auto-koreksi: ' . implode(', ', $nikCorrections);
        }
        if ($noKkModified && !empty($noKkCorrections)) {
            $dataNotes[] = 'No KK auto-koreksi: ' . implode(', ', $noKkCorrections);
        }

        // Validate after cleaning
        if (empty($nik)) {
            $dataStatus = 'perlu_update';
            $dataNotes[] = 'NIK kosong';
        } elseif (!preg_match('/^\d{16}$/', $nik)) {
            $dataStatus = 'perlu_update';
            $dataNotes[] = 'NIK tidak valid (' . strlen($nik) . ' digit)';
        }

        if (!empty($noKk) && !preg_match('/^\d{16}$/', $noKk)) {
            $dataStatus = 'perlu_update';
            $dataNotes[] = 'No KK tidak valid (' . strlen($noKk) . ' digit)';
        }

        // If only auto-corrected but now valid, mark as 'valid' with correction note
        if ($dataStatus === 'valid' && ($nikModified || $noKkModified)) {
            $dataStatus = 'auto_corrected';
        }

        // Parse gender
        $jk = strtoupper(trim($row['jk'] ?? $row['jenis_kelamin'] ?? ''));
        $gender = null;
        if (str_contains($jk, 'LAKI')) {
            $gender = 'LAKI-LAKI';
        } elseif (str_contains($jk, 'PEREM') || str_contains($jk, 'WANITA')) {
            $gender = 'PEREMPUAN';
        }

        // Parse birth date
        $tanggalLahir = null;
        $rawDate = trim($row['anggal_lhr'] ?? $row['tanggal_lahir'] ?? $row['tanggal_lhr'] ?? $row['tgl_lahir'] ?? '');
        if (!empty($rawDate)) {
            try {
                if (is_numeric($rawDate)) {
                    // Excel serial date
                    $tanggalLahir = Carbon::createFromTimestamp(($rawDate - 25569) * 86400)->startOfDay();
                } else {
                    $tanggalLahir = Carbon::parse($rawDate);
                }
            } catch (\Exception $e) {
                // Try dd/mm/yyyy format
                try {
                    $tanggalLahir = Carbon::createFromFormat('d/m/Y', $rawDate);
                } catch (\Exception $e2) {
                    // Skip date if unparseable
                }
            }
        }

        $data = [
            'no_kk' => $noKk,
            'nama' => strtoupper($nama),
            'jenis_kelamin' => $gender,
            'tempat_lahir' => strtoupper(trim($row['tempat_lhr'] ?? $row['tempat_lahir'] ?? '')),
            'tanggal_lahir' => $tanggalLahir,
            'agama' => strtoupper(trim($row['agama'] ?? '')),
            'pendidikan' => trim($row['pendidikan'] ?? $row['didikan'] ?? ''),
            'status_perkawinan' => trim($row['status_perkawinan'] ?? $row['ds_perkawinan'] ?? ''),
            'nama_ayah' => strtoupper(trim($row['nama_ayah'] ?? '')),
            'nama_ibu' => strtoupper(trim($row['nama_ibu'] ?? '')),
            'alamat' => trim($row['alamat'] ?? ''),
            'dusun' => trim($row['dusun'] ?? ''),
            'rw' => trim($row['rw'] ?? ''),
            'rt' => trim($row['rt'] ?? ''),
            'kelurahan' => trim($row['kelurahan'] ?? $row['wonos'] ?? ''),
            'data_status' => $dataStatus,
            'data_notes' => !empty($dataNotes) ? implode('; ', $dataNotes) : null,
            'province_code' => $this->provinceCode,
            'city_code' => $this->cityCode,
            'district_code' => $this->districtCode,
            'village_code' => $this->villageCode,
            'uploaded_by' => $this->uploadedBy,
        ];

        // Upsert by NIK if valid/auto_corrected, otherwise just create
        if (!empty($nik) && in_array($dataStatus, ['valid', 'auto_corrected'])) {
            $existing = Resident::withTrashed()->where('nik', $nik)->first();
            if ($existing) {
                if ($existing->trashed()) {
                    $existing->restore();
                }
                $existing->update($data);
                $this->updatedCount++;
            } else {
                Resident::create(array_merge(['nik' => $nik], $data));
                $this->successCount++;
            }
            if ($dataStatus === 'auto_corrected') {
                $this->autoCorrectedCount++;
            }
        } else {
            // Bad/empty NIK — always create new record flagged as perlu_update
            Resident::create(array_merge(['nik' => $nik ?: null], $data));
            $this->flaggedCount++;
            $this->successCount++;
        }
    }

    public function getResults(): array
    {
        return [
            'success' => $this->successCount,
            'updated' => $this->updatedCount,
            'failed' => $this->failedCount,
            'skipped' => $this->skippedCount,
            'flagged' => $this->flaggedCount,
            'auto_corrected' => $this->autoCorrectedCount,
            'errors' => $this->errors,
        ];
    }
}
