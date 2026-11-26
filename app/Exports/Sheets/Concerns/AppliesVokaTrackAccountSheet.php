<?php

namespace App\Exports\Sheets\Concerns;

use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

trait AppliesVokaTrackAccountSheet
{
    /**
     * Terapkan tata letak standar VokaTrack pada sheet tabel akun (banner, metadata, header hijau gelap, baris zebra, freeze pane).
     *
     * @param  array<string, int>  $columnWidths
     * @param  array<int, string>  $centeredColumns
     * @param  array{passwordCol?: string, statusCol?: string, urlCol?: string}  $specialColumns
     */
    protected function styleAccountTableSheet(
        Worksheet $sheet,
        int $dataCount,
        array $columnWidths,
        array $centeredColumns = [],
        array $specialColumns = []
    ): void {
        $colCount = count($columnWidths);
        $lastCol = Coordinate::stringFromColumnIndex($colCount);
        $totalRows = $dataCount + 4;

        foreach ($columnWidths as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }

        // Row 1: Judul Banner
        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->getRowDimension(1)->setRowHeight(32);
        $sheet->getStyle('A1')->applyFromArray([
            'font' => [
                'name' => 'Segoe UI',
                'bold' => true,
                'size' => 14,
                'color' => ['rgb' => '0B2B25'],
            ],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // Row 2: Metadata
        $sheet->mergeCells("A2:{$lastCol}2");
        $sheet->getRowDimension(2)->setRowHeight(20);
        $sheet->getStyle('A2')->applyFromArray([
            'font' => [
                'name' => 'Segoe UI',
                'italic' => true,
                'size' => 9.5,
                'color' => ['rgb' => '475569'],
            ],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // Row 3: Pemisah
        $sheet->getRowDimension(3)->setRowHeight(12);

        // Row 4: Header
        $sheet->getRowDimension(4)->setRowHeight(30);
        $sheet->getStyle("A4:{$lastCol}4")->applyFromArray([
            'font' => [
                'name' => 'Segoe UI',
                'bold' => true,
                'size' => 10.5,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0B2B25'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '071915'],
                ],
            ],
        ]);

        // Data Rows
        for ($row = 5; $row <= $totalRows; $row++) {
            $sheet->getRowDimension($row)->setRowHeight(24);
            $bgColor = ($row % 2 !== 0) ? 'F8FAFC' : 'FFFFFF';

            $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
                'font' => [
                    'name' => 'Segoe UI',
                    'size' => 10,
                    'color' => ['rgb' => '1E293B'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => $bgColor],
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'E2E8F0'],
                    ],
                ],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ]);

            foreach ($centeredColumns as $col) {
                $sheet->getStyle("{$col}{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }

            if (!empty($specialColumns['passwordCol'])) {
                $pCol = $specialColumns['passwordCol'];
                $sheet->getStyle("{$pCol}{$row}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => '0B2B25']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EBF6F0']],
                ]);
            }

            if (!empty($specialColumns['statusCol'])) {
                $sCol = $specialColumns['statusCol'];
                $statusVal = $sheet->getCell("{$sCol}{$row}")->getValue();
                $statusColor = ($statusVal === 'Aktif') ? '047857' : 'DC2626';
                $sheet->getStyle("{$sCol}{$row}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => $statusColor]],
                ]);
            }

            if (!empty($specialColumns['urlCol'])) {
                $uCol = $specialColumns['urlCol'];
                $sheet->getStyle("{$uCol}{$row}")->applyFromArray([
                    'font' => ['color' => ['rgb' => '0F7A66'], 'underline' => true],
                ]);
            }
        }

        $sheet->freezePane('A5');
    }

    /**
     * Terapkan tata letak standar VokaTrack pada sheet format broadcast WhatsApp (5 kolom A-E).
     */
    protected function styleBroadcastWhatsappSheet(Worksheet $sheet, int $dataCount): void
    {
        $totalRows = $dataCount + 4;

        $widths = [
            'A' => 6,
            'B' => 28,
            'C' => 20,
            'D' => 30,
            'E' => 92,
        ];
        foreach ($widths as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }

        $sheet->mergeCells('A1:E1');
        $sheet->getRowDimension(1)->setRowHeight(32);
        $sheet->getStyle('A1')->applyFromArray([
            'font' => [
                'name' => 'Segoe UI',
                'bold' => true,
                'size' => 14,
                'color' => ['rgb' => '0B2B25'],
            ],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);

        $sheet->mergeCells('A2:E2');
        $sheet->getRowDimension(2)->setRowHeight(20);
        $sheet->getStyle('A2')->applyFromArray([
            'font' => [
                'name' => 'Segoe UI',
                'italic' => true,
                'size' => 9.5,
                'color' => ['rgb' => '475569'],
            ],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);

        $sheet->getRowDimension(3)->setRowHeight(12);
        $sheet->getRowDimension(4)->setRowHeight(30);
        $sheet->getStyle('A4:E4')->applyFromArray([
            'font' => [
                'name' => 'Segoe UI',
                'bold' => true,
                'size' => 10.5,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0F7A66'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '071915'],
                ],
            ],
        ]);

        for ($row = 5; $row <= $totalRows; $row++) {
            $bgColor = ($row % 2 !== 0) ? 'F8FAFC' : 'FFFFFF';
            $sheet->getStyle("A{$row}:E{$row}")->applyFromArray([
                'font' => [
                    'name' => 'Segoe UI',
                    'size' => 10,
                    'color' => ['rgb' => '1E293B'],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => $bgColor],
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'E2E8F0'],
                    ],
                ],
                'alignment' => ['vertical' => Alignment::VERTICAL_TOP],
            ]);

            $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("C{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("E{$row}")->getAlignment()->setWrapText(true);
        }

        $sheet->freezePane('A5');
    }

    /**
     * Format ringkasan koleksi Collection menjadi daftar string unik atau '-'.
     */
    protected function resolveAccountPhone(?\App\Models\User $user): string
    {
        return $user?->profile?->phone ?: ($user?->wa_number ?: '-');
    }
}
