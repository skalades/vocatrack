<?php

namespace App\Exports\Sheets;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class TeacherAccountsDataSheet implements FromCollection, WithTitle, WithEvents, WithColumnFormatting
{
    public function __construct(
        protected Collection $teachers,
        protected array $meta = []
    ) {}

    public function title(): string
    {
        return 'Data Akun Guru';
    }

    public function collection(): Collection
    {
        $rows = collect();
        $loginUrl = route('login');

        // Row 1: Judul Banner
        $rows->push(['DAFTAR AKUN & AKSES LOGIN GURU PEMBIMBING PKL — VOKATRACK']);

        // Row 2: Metadata Info
        $school = $this->meta['school_name'] ?? 'Sekolah';
        $majorFilter = $this->meta['major_name'] ?? 'Semua Jurusan';
        $total = $this->teachers->count();
        $date = $this->meta['downloaded_at'] ?? now()->format('d/m/Y H:i');
        $rows->push(["Sekolah: {$school}   |   Filter Jurusan: {$majorFilter}   |   Total Guru: {$total} Orang   |   Waktu Unduh: {$date}"]);

        // Row 3: Baris Pemisah Kosong
        $rows->push(['']);

        // Row 4: Header Kolom
        $rows->push([
            'No',
            'Nama Lengkap Guru',
            'NIP (Username Login)',
            'Jurusan yang Diampu',
            'Peran / Jabatan',
            'Email Akun Login',
            'Password Default',
            'Beban Bimbingan (Siswa)',
            'Status Akun',
            'Tautan Login Guru',
            'No. Telepon / WhatsApp',
        ]);

        // Row 5+: Data Guru
        $no = 1;
        foreach ($this->teachers as $teacher) {
            $user = $teacher->user;
            $name = $user?->name ?? '-';
            $nip = (string) ($teacher->nip ?? '-');
            $email = $user?->email ?? '-';

            // Majors string
            if ($teacher->majors->isNotEmpty()) {
                $majorsStr = $teacher->majors->pluck('code')->implode(', ');
            } else {
                $majorsStr = 'Lintas Jurusan';
            }

            // Role / Heading
            if ($teacher->headedMajors->isNotEmpty()) {
                $headedCodes = $teacher->headedMajors->pluck('code')->implode(', ');
                $roleTitle = "Kepala Program ({$headedCodes})";
            } else {
                $roleTitle = 'Guru Pembimbing PKL';
            }

            $defaultPassword = 'password';
            $studentCount = $teacher->studentPkls ? $teacher->studentPkls->count() . ' Siswa' : '0 Siswa';
            $status = ($user && $user->is_active) ? 'Aktif' : 'Nonaktif';
            $phone = $user?->profile?->phone ?? '-';

            $rows->push([
                $no++,
                $name,
                $nip,
                $majorsStr,
                $roleTitle,
                $email,
                $defaultPassword,
                $studentCount,
                $status,
                $loginUrl,
                $phone,
            ]);
        }

        return $rows;
    }

    public function columnFormats(): array
    {
        return [
            'C' => NumberFormat::FORMAT_TEXT, // NIP as text to avoid scientific notation
            'K' => NumberFormat::FORMAT_TEXT, // Phone as text
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $totalRows = $this->teachers->count() + 4;

                // 1. Column Widths
                $widths = [
                    'A' => 6,   // No
                    'B' => 28,  // Nama Lengkap Guru
                    'C' => 22,  // NIP
                    'D' => 26,  // Jurusan yang Diampu
                    'E' => 24,  // Peran / Jabatan
                    'F' => 30,  // Email
                    'G' => 18,  // Password Default
                    'H' => 22,  // Beban Bimbingan
                    'I' => 14,  // Status Akun
                    'J' => 36,  // Tautan Login
                    'K' => 22,  // No. Telepon
                ];
                foreach ($widths as $col => $w) {
                    $sheet->getColumnDimension($col)->setWidth($w);
                }

                // 2. Styling Row 1 (Banner Title)
                $sheet->mergeCells('A1:K1');
                $sheet->getRowDimension(1)->setRowHeight(32);
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => [
                        'name' => 'Segoe UI',
                        'bold' => true,
                        'size' => 14,
                        'color' => ['rgb' => '0B2B25'],
                    ],
                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                // 3. Styling Row 2 (Metadata Info)
                $sheet->mergeCells('A2:K2');
                $sheet->getRowDimension(2)->setRowHeight(20);
                $sheet->getStyle('A2')->applyFromArray([
                    'font' => [
                        'name' => 'Segoe UI',
                        'italic' => true,
                        'size' => 9.5,
                        'color' => ['rgb' => '475569'],
                    ],
                    'alignment' => [
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                // 3.5. Styling Row 3 (Pemisah Kosong)
                $sheet->getRowDimension(3)->setRowHeight(12);

                // 4. Styling Row 4 (Table Header)
                $sheet->getRowDimension(4)->setRowHeight(30);
                $sheet->getStyle('A4:K4')->applyFromArray([
                    'font' => [
                        'name' => 'Segoe UI',
                        'bold' => true,
                        'size' => 10.5,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '0B2B25'], // VokaTrack Deep Green
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

                // 5. Styling Data Rows (Row 5 s/d $totalRows)
                if ($this->teachers->count() > 0) {
                    for ($row = 5; $row <= $totalRows; $row++) {
                        $sheet->getRowDimension($row)->setRowHeight(24);

                        $isOdd = ($row % 2 !== 0);
                        $bgColor = $isOdd ? 'F8FAFC' : 'FFFFFF';

                        $sheet->getStyle("A{$row}:K{$row}")->applyFromArray([
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
                            'alignment' => [
                                'vertical' => Alignment::VERTICAL_CENTER,
                            ],
                        ]);

                        // Center alignments
                        $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle("C{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle("D{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle("E{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle("G{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle("H{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle("I{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                        // Highlight Password Default Box
                        $sheet->getStyle("G{$row}")->applyFromArray([
                            'font' => [
                                'bold' => true,
                                'color' => ['rgb' => '0B2B25'],
                            ],
                            'fill' => [
                                'fillType' => Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'EBF6F0'],
                            ],
                        ]);

                        // Status Badge Color
                        $statusVal = $sheet->getCell("I{$row}")->getValue();
                        $statusColor = ($statusVal === 'Aktif') ? '047857' : 'DC2626';
                        $sheet->getStyle("I{$row}")->applyFromArray([
                            'font' => [
                                'bold' => true,
                                'color' => ['rgb' => $statusColor],
                            ],
                        ]);

                        // Hyperlink Styling for URL
                        $sheet->getStyle("J{$row}")->applyFromArray([
                            'font' => ['color' => ['rgb' => '0F7A66'], 'underline' => true],
                        ]);
                    }
                }

                // 6. Freeze Panes
                $sheet->freezePane('A5');
            },
        ];
    }
}
