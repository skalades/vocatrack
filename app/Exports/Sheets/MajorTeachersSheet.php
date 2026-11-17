<?php

namespace App\Exports\Sheets;

use App\Models\Major;
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

class MajorTeachersSheet implements FromCollection, WithTitle, WithEvents, WithColumnFormatting
{
    public function __construct(
        protected ?Major $major,
        protected Collection $teachers,
        protected string $sheetTitle,
        protected array $meta = []
    ) {}

    public function title(): string
    {
        return $this->sheetTitle;
    }

    public function collection(): Collection
    {
        $rows = collect();
        $loginUrl = route('login');

        // Row 1: Judul Banner
        if ($this->major) {
            $rows->push(['DAFTAR GURU PEMBIMBING PKL — JURUSAN ' . mb_strtoupper($this->major->name)]);
        } else {
            $rows->push(['DAFTAR GURU PEMBIMBING PKL — LINTAS JURUSAN']);
        }

        // Row 2: Metadata Info
        $school = $this->meta['school_name'] ?? 'Sekolah';
        $date = $this->meta['downloaded_at'] ?? now()->format('d/m/Y H:i');
        $total = $this->teachers->count();

        if ($this->major) {
            $headTeacher = $this->major->headTeacher?->user?->name ?? 'Belum Ditunjuk';
            $metaParts = [
                "Sekolah: {$school}",
                "Jurusan: {$this->major->name} ({$this->major->code})",
                "Kepala Program Keahlian: {$headTeacher}",
                "Total Guru: {$total} Orang",
                "Waktu Unduh: {$date}",
            ];
            $rows->push([implode('   |   ', $metaParts)]);
        } else {
            $rows->push(["Sekolah: {$school}   |   Kategori: Guru Pembimbing Lintas Jurusan   |   Total Guru: {$total} Orang   |   Waktu Unduh: {$date}"]);
        }

        // Row 3: Baris Pemisah Kosong
        $rows->push(['']);

        // Row 4: Header Kolom
        $rows->push([
            'No',
            'Nama Lengkap Guru',
            'NIP (Username Login)',
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

            $isHead = $this->major ? $teacher->isMajorHead($this->major->id) : $teacher->headedMajors->isNotEmpty();
            if ($isHead) {
                $code = $this->major?->code ?? $teacher->headedMajors->first()?->code;
                $roleTitle = "Kepala Program ({$code})";
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
            'J' => NumberFormat::FORMAT_TEXT, // Phone as text
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
                    'D' => 24,  // Peran / Jabatan
                    'E' => 30,  // Email
                    'F' => 18,  // Password Default
                    'G' => 22,  // Beban Bimbingan
                    'H' => 14,  // Status Akun
                    'I' => 36,  // Tautan Login
                    'J' => 22,  // No. Telepon
                ];
                foreach ($widths as $col => $w) {
                    $sheet->getColumnDimension($col)->setWidth($w);
                }

                // 2. Styling Row 1 (Banner Title)
                $sheet->mergeCells('A1:J1');
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
                $sheet->mergeCells('A2:J2');
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
                $headerColor = $this->major ? '0B2B25' : '334155';
                $sheet->getRowDimension(4)->setRowHeight(30);
                $sheet->getStyle('A4:J4')->applyFromArray([
                    'font' => [
                        'name' => 'Segoe UI',
                        'bold' => true,
                        'size' => 10.5,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => $headerColor],
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

                        $sheet->getStyle("A{$row}:J{$row}")->applyFromArray([
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
                        $sheet->getStyle("F{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle("G{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle("H{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                        // Highlight Password Default Box
                        $sheet->getStyle("F{$row}")->applyFromArray([
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
                        $statusVal = $sheet->getCell("H{$row}")->getValue();
                        $statusColor = ($statusVal === 'Aktif') ? '047857' : 'DC2626';
                        $sheet->getStyle("H{$row}")->applyFromArray([
                            'font' => [
                                'bold' => true,
                                'color' => ['rgb' => $statusColor],
                            ],
                        ]);

                        // Hyperlink Styling for URL
                        $sheet->getStyle("I{$row}")->applyFromArray([
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
