<?php

namespace App\Imports;

use App\Imports\Concerns\SmartNikCleaner;
use App\Models\Resident;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

class ResidentPreviewImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    use SmartNikCleaner;
    protected int $totalRows = 0;
    protected int $validRows = 0;
    protected int $needsUpdateRows = 0;
    protected int $autoCorrectedRows = 0;
    protected int $skipRows = 0;
    protected int $existingRows = 0;
    protected int $newRows = 0;
    protected array $sampleRows = [];
    protected array $issues = [];
    protected array $headers = [];
    protected array $genderStats = ['L' => 0, 'P' => 0, 'unknown' => 0];

    public function collection(Collection $rows)
    {
        $this->totalRows = $rows->count();

        foreach ($rows as $index => $row) {
            $rowArray = array_change_key_case($row->toArray(), CASE_LOWER);

            if ($index === 0) {
                $this->headers = array_keys($rowArray);
            }

            $rawNik = trim($rowArray['nik'] ?? '');
            $nama = trim($rowArray['nama_lengkap'] ?? $rowArray['nama'] ?? '');
            $rawNoKk = trim($rowArray['no_kk'] ?? '');

            // Smart clean NIK and No KK
            [$nik, $nikModified, $nikCorrections] = $this->smartCleanNumericId($rawNik);
            [$noKk, $noKkModified, $noKkCorrections] = $this->smartCleanNumericId($rawNoKk);

            // Completely empty row (no name AND no NIK) → will be skipped
            if (empty($nik) && empty($nama)) {
                $this->skipRows++;
                continue;
            }

            // Check for issues
            $rowIssues = [];
            $rowCorrections = [];
            $rowStatus = 'valid'; // valid, auto_corrected, perlu_update, skip

            // Track corrections
            if ($nikModified && !empty($nikCorrections)) {
                $rowCorrections[] = 'NIK: ' . implode(', ', $nikCorrections) . " ({$rawNik} → {$nik})";
            }
            if ($noKkModified && !empty($noKkCorrections)) {
                $rowCorrections[] = 'No KK: ' . implode(', ', $noKkCorrections);
            }

            // Validate after cleaning
            if (empty($nik)) {
                $rowIssues[] = 'NIK kosong';
                $rowStatus = 'perlu_update';
            } elseif (!preg_match('/^\d{16}$/', $nik)) {
                $rowIssues[] = 'NIK tidak valid (' . strlen($nik) . ' digit)';
                $rowStatus = 'perlu_update';
            }
            if (!empty($noKk) && !preg_match('/^\d{16}$/', $noKk)) {
                $rowIssues[] = 'No KK tidak valid';
                $rowStatus = ($rowStatus === 'perlu_update') ? 'perlu_update' : 'perlu_update';
            }
            if (empty($nama)) {
                $rowIssues[] = 'Nama kosong';
                $rowStatus = 'skip';
            }

            // If no issues but was corrected, mark as auto_corrected
            if ($rowStatus === 'valid' && !empty($rowCorrections)) {
                $rowStatus = 'auto_corrected';
                $this->autoCorrectedRows++;
            }

            if ($rowStatus === 'skip') {
                $this->skipRows++;
            } elseif ($rowStatus === 'perlu_update') {
                $this->needsUpdateRows++;
                $this->newRows++;
                if (count($this->issues) < 10) {
                    $this->issues[] = [
                        'row' => $index + 2,
                        'nama' => $nama ?: '-',
                        'nik' => $rawNik ?: '-',
                        'nik_cleaned' => $nik ?: '-',
                        'issues' => $rowIssues,
                        'corrections' => $rowCorrections,
                    ];
                }
            } else {
                $this->validRows++;
                // Check existing only for valid/corrected NIK
                if (!empty($nik)) {
                    $exists = Resident::withTrashed()->where('nik', $nik)->exists();
                    if ($exists) {
                        $this->existingRows++;
                    } else {
                        $this->newRows++;
                    }
                }
            }

            // Gender stats
            $jk = strtoupper(trim($rowArray['jk'] ?? $rowArray['jenis_kelamin'] ?? ''));
            if (str_contains($jk, 'LAKI')) {
                $this->genderStats['L']++;
            } elseif (str_contains($jk, 'PEREM') || str_contains($jk, 'WANITA')) {
                $this->genderStats['P']++;
            } else {
                $this->genderStats['unknown']++;
            }

            // Collect sample rows (first 10)
            if (count($this->sampleRows) < 10) {
                // Parse birth date for display
                $tanggalLahir = '';
                $rawDate = trim($rowArray['anggal_lhr'] ?? $rowArray['tanggal_lahir'] ?? $rowArray['tanggal_lhr'] ?? $rowArray['tgl_lahir'] ?? '');
                if (!empty($rawDate)) {
                    try {
                        if (is_numeric($rawDate)) {
                            $tanggalLahir = Carbon::createFromTimestamp(($rawDate - 25569) * 86400)->format('d/m/Y');
                        } else {
                            $tanggalLahir = Carbon::parse($rawDate)->format('d/m/Y');
                        }
                    } catch (\Exception $e) {
                        $tanggalLahir = $rawDate;
                    }
                }

                $this->sampleRows[] = [
                    'no' => $index + 1,
                    'nik_raw' => $rawNik,
                    'nik' => $nik,
                    'nik_corrected' => $nikModified,
                    'nama' => strtoupper($nama),
                    'no_kk' => $noKk,
                    'no_kk_raw' => $rawNoKk,
                    'no_kk_corrected' => $noKkModified,
                    'jk' => str_contains($jk, 'LAKI') ? 'L' : (str_contains($jk, 'PEREM') || str_contains($jk, 'WANITA') ? 'P' : '-'),
                    'tempat_lahir' => strtoupper(trim($rowArray['tempat_lhr'] ?? $rowArray['tempat_lahir'] ?? '')),
                    'tanggal_lahir' => $tanggalLahir,
                    'alamat' => trim($rowArray['alamat'] ?? ''),
                    'is_existing' => !empty($nik) && in_array($rowStatus, ['valid', 'auto_corrected']) && Resident::withTrashed()->where('nik', $nik)->exists(),
                    'status' => $rowStatus,
                    'issues' => $rowIssues,
                    'corrections' => $rowCorrections,
                ];
            }
        }
    }

    public function getResults(): array
    {
        return [
            'total_rows' => $this->totalRows,
            'valid_rows' => $this->validRows,
            'auto_corrected_rows' => $this->autoCorrectedRows,
            'needs_update_rows' => $this->needsUpdateRows,
            'skip_rows' => $this->skipRows,
            'existing_rows' => $this->existingRows,
            'new_rows' => $this->newRows,
            'gender_stats' => $this->genderStats,
            'headers' => $this->headers,
            'sample_rows' => $this->sampleRows,
            'issues' => $this->issues,
        ];
    }
}
