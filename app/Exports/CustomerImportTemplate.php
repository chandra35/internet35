<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class CustomerImportTemplate implements FromArray, WithHeadings, WithStyles, WithColumnWidths, WithTitle
{
    protected array $routers;
    protected array $packages;

    public function __construct(array $routers = [], array $packages = [])
    {
        $this->routers = $routers;
        $this->packages = $packages;
    }

    public function title(): string
    {
        return 'Template Import Pelanggan';
    }

    public function headings(): array
    {
        return [
            'nama',
            'telepon',
            'pppoe_username',
            'pppoe_password',
            'router',
            'paket',
            'email',
            'nik',
            'jenis_kelamin',
            'alamat',
            'tipe_layanan',
            'biaya_bulanan',
            'biaya_instalasi',
            'tanggal_instalasi',
            'tanggal_tagihan',
            'catatan',
        ];
    }

    public function array(): array
    {
        // Include sample data rows
        $rows = [];

        // First sample row
        $rows[] = [
            'John Doe',
            '081234567890',
            'john-pppoe',
            'pass123',
            !empty($this->routers) ? $this->routers[0] : 'Nama Router',
            !empty($this->packages) ? $this->packages[0] : 'Nama Paket',
            'john@email.com',
            '3201010101010001',
            'L',
            'Jl. Contoh No. 1, Jakarta',
            'pppoe',
            '',
            '0',
            date('Y-m-d'),
            '1',
            'Pelanggan baru',
        ];

        // Second sample row
        $rows[] = [
            'Jane Smith',
            '082345678901',
            'jane-pppoe',
            'secret456',
            !empty($this->routers) ? $this->routers[0] : 'Nama Router',
            !empty($this->packages) ? ($this->packages[1] ?? $this->packages[0]) : 'Nama Paket',
            '',
            '',
            'P',
            'Jl. Sampel No. 2',
            'pppoe',
            '150000',
            '100000',
            date('Y-m-d'),
            '5',
            '',
        ];

        // Third sample row - migrasi tanpa username/password
        $rows[] = [
            'Budi Migrasi',
            '085678901234',
            '',
            '',
            !empty($this->routers) ? $this->routers[0] : 'Nama Router',
            !empty($this->packages) ? $this->packages[0] : 'Nama Paket',
            '',
            '',
            'L',
            'Jl. Migrasi No. 3',
            'pppoe',
            '100000',
            '0',
            date('Y-m-d'),
            '1',
            'Migrasi - perlu sync username/password',
        ];

        return $rows;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 22,  // nama
            'B' => 18,  // telepon
            'C' => 22,  // pppoe_username
            'D' => 18,  // pppoe_password
            'E' => 22,  // router
            'F' => 22,  // paket
            'G' => 25,  // email
            'H' => 20,  // nik
            'I' => 16,  // jenis_kelamin
            'J' => 35,  // alamat
            'K' => 16,  // tipe_layanan
            'L' => 16,  // biaya_bulanan
            'M' => 16,  // biaya_instalasi
            'N' => 18,  // tanggal_instalasi
            'O' => 16,  // tanggal_tagihan
            'P' => 25,  // catatan
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $lastCol = 'P';
        $lastRow = 4; // heading + 3 sample rows

        // Header row styling
        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '007BFF'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        // Sample data rows - light background
        $sheet->getStyle("A2:{$lastCol}{$lastRow}")->applyFromArray([
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FFF3CD'], // Light yellow for sample data
            ],
            'font' => [
                'italic' => true,
                'color' => ['rgb' => '856404'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CCCCCC'],
                ],
            ],
        ]);

        // Add comment/note at the top of the sheet
        $sheet->getComment('A1')->getText()->createTextRun('WAJIB diisi');
        $sheet->getComment('B1')->getText()->createTextRun('WAJIB diisi. Format: 08xxxx');
        $sheet->getComment('C1')->getText()->createTextRun('Opsional. Username PPPoE (kosongkan jika migrasi)');
        $sheet->getComment('D1')->getText()->createTextRun('Opsional. Password PPPoE (kosongkan jika migrasi)');
        $sheet->getComment('E1')->getText()->createTextRun('Opsional. Nama router yang ada di sistem');
        $sheet->getComment('F1')->getText()->createTextRun('Opsional. Nama paket yang ada di router');
        $sheet->getComment('G1')->getText()->createTextRun('Opsional');
        $sheet->getComment('H1')->getText()->createTextRun('Opsional. 16 digit');
        $sheet->getComment('I1')->getText()->createTextRun('L/P atau Male/Female atau Laki-laki/Perempuan');
        $sheet->getComment('J1')->getText()->createTextRun('Opsional');
        $sheet->getComment('K1')->getText()->createTextRun('pppoe/hotspot/static. Default: pppoe');
        $sheet->getComment('L1')->getText()->createTextRun('Opsional. Default: harga paket');
        $sheet->getComment('M1')->getText()->createTextRun('Opsional. Default: 0');
        $sheet->getComment('N1')->getText()->createTextRun('Format: YYYY-MM-DD. Default: hari ini');
        $sheet->getComment('O1')->getText()->createTextRun('1-28. Default: 1');
        $sheet->getComment('P1')->getText()->createTextRun('Opsional');

        // Add instruction row after sample data
        $instructionRow = $lastRow + 1;
        $sheet->setCellValue("A{$instructionRow}", '⚠️ HAPUS baris contoh (kuning) di atas sebelum import. Mulai data Anda dari baris 2.');
        $sheet->mergeCells("A{$instructionRow}:{$lastCol}{$instructionRow}");
        $sheet->getStyle("A{$instructionRow}")->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'DC3545'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'F8D7DA'],
            ],
        ]);

        // Add router & package reference
        $refRow = $instructionRow + 2;
        $sheet->setCellValue("A{$refRow}", 'REFERENSI ROUTER:');
        $sheet->getStyle("A{$refRow}")->getFont()->setBold(true);
        foreach ($this->routers as $i => $router) {
            $sheet->setCellValue('A' . ($refRow + $i + 1), '  • ' . $router);
        }

        $pkgStartRow = $refRow + count($this->routers) + 2;
        $sheet->setCellValue("A{$pkgStartRow}", 'REFERENSI PAKET:');
        $sheet->getStyle("A{$pkgStartRow}")->getFont()->setBold(true);
        foreach ($this->packages as $i => $package) {
            $sheet->setCellValue('A' . ($pkgStartRow + $i + 1), '  • ' . $package);
        }

        // Freeze top row
        $sheet->freezePane('A2');

        return [];
    }
}
