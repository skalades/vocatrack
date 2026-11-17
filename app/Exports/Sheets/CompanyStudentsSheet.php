<?php

namespace App\Exports\Sheets;

use App\Models\Company;
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

class CompanyStudentsSheet implements FromCollection, WithTitle, WithEvents, WithColumnFormatting
{
    public function __construct(
        protected ?Company $company,
        protected Collection $students,
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
        if ($this->company) {
            $rows->push(['DAFTAR SISWA PKL — ' . mb_strtoupper($this->company->name)]);
        } else {
            $rows->push(['DAFTAR SISWA PKL — BELUM DITEMPATKAN']);
        }

        // Row 2: Metadata Info
        $school = $this->meta['school_name'] ?? 'Sekolah';
        $date = $this->meta['downloaded_at'] ?? now()->format('d/m/Y H:i');
        $total = $this->students->count();

        if ($this->company) {
            $metaParts = [
                "Sekolah: {$school}",
                "Tempat PKL: {$this->company->name}",
            ];
            if (!empty($this->company->contact_person)) {
                $metaParts[] = "Kontak: {$this->company->contact_person}";
            }
            if (!empty($this->company->phone)) {
                $metaParts[] = "Telp: {$this->company->phone}";
            }
            if (!empty($this->company->address)) {
                $metaParts[] = "Alamat: {$this->company->address}";
            }
            $metaParts[] = "Total Siswa: {$total} Orang";
            $metaParts[] = "Waktu Unduh: {$date}";
            $rows->push([implode('   |   ', $metaParts)]);
        } else {
            $rows->push(["Sekolah: {$school}   |   Status: Belum Memiliki Penempatan PKL (Belum Diplot)   |   Total Siswa: {$total} Orang   |   Waktu Unduh: {$date}"]);
        }

        // Row 3: Baris Pemisah Kosong
        $rows->push(['']);

        // Row 4: Header Kolom
        $rows->push([
            'No',
            'Nama Lengkap Siswa',
            'NISN (Username Login)',
            'NIS',
            'Jurusan',
            'Kelas / Rombel',
            'Pembimbing DU/DI',
            'Guru Pembimbing',
            'Email Akun',
            'Password Default',
            'Status Akun',
            'Tautan Login Siswa',
            'Tautan Monitoring Orang Tua',
        ]);

        // Row 5+: Data Siswa
        $no = 1;
        foreach ($this->students as $student) {
            $user = $student->user;
            $nisn = (string) ($student->nisn ?? '-');
            $nis = (string) ($student->nis ?? '-');
            $name = $user?->name ?? '-';
            $email = $user?->email ?? '-';
            $majorName = $student->class?->major?->name ?? '-';
            $className = $student->class?->name ?? '-';

            $supervisor = $student->activePkl?->supervisor;
            $supervisorName = $supervisor?->user?->name ?? '-';
            if ($supervisor && !empty($supervisor->position) && $supervisorName !== '-') {
                $supervisorName .= " ({$supervisor->position})";
            }

            $teacher = $student->activePkl?->teacher;
            $teacherName = $teacher?->user?->name ?? '-';

            $status = ($user && $user->is_active) ? 'Aktif' : 'Nonaktif';
            $defaultPassword = 'password';

            $parentUrl = route('parent.monitor', ['token' => $student->parent_token]);

            $rows->push([
                $no++,
                $name,
                $nisn,
                $nis,
                $majorName,
                $className,
                $supervisorName,
                $teacherName,
                $email,
                $defaultPassword,
                $status,
                $loginUrl,
                $parentUrl,
            ]);
        }

        return $rows;
    }

    public function columnFormats(): array
    {
        return [
            'C' => NumberFormat::FORMAT_TEXT, // NISN as text to prevent scientific notation
            'D' => NumberFormat::FORMAT_TEXT, // NIS as text
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $totalRows = $this->students->count() + 4;

                // 1. Column Widths
                $widths = [
                    'A' => 6,   // No
                    'B' => 28,  // Nama Lengkap
                    'C' => 20,  // NISN
                    'D' => 14,  // NIS
                    'E' => 28,  // Jurusan
                    'F' => 16,  // Kelas
                    'G' => 26,  // Pembimbing DU/DI
                    'H' => 26,  // Guru Pembimbing
                    'I' => 30,  // Email
                    'J' => 18,  // Password Default
                    'K' => 14,  // Status
                    'L' => 36,  // Tautan Login
                    'M' => 48,  // Tautan Monitoring Ortu
                ];
                foreach ($widths as $col => $w) {
                    $sheet->getColumnDimension($col)->setWidth($w);
                }

                // 2. Styling Row 1 (Banner Title)
                $sheet->mergeCells('A1:M1');
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
                $sheet->mergeCells('A2:M2');
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
                $headerColor = $this->company ? '0B2B25' : '334155'; // Deep green for company, slate for unassigned
                $sheet->getRowDimension(4)->setRowHeight(30);
                $sheet->getStyle('A4:M4')->applyFromArray([
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
                if ($this->students->count() > 0) {
                    for ($row = 5; $row <= $totalRows; $row++) {
                        $sheet->getRowDimension($row)->setRowHeight(24);

                        $isOdd = ($row % 2 !== 0);
                        $bgColor = $isOdd ? 'F8FAFC' : 'FFFFFF';

                        $sheet->getStyle("A{$row}:M{$row}")->applyFromArray([
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

                        // Center alignments for specific columns
                        $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle("C{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle("D{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle("F{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle("J{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle("K{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                        // Highlight Password Default Box
                        $sheet->getStyle("J{$row}")->applyFromArray([
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
                        $statusVal = $sheet->getCell("K{$row}")->getValue();
                        $statusColor = ($statusVal === 'Aktif') ? '047857' : 'DC2626';
                        $sheet->getStyle("K{$row}")->applyFromArray([
                            'font' => [
                                'bold' => true,
                                'color' => ['rgb' => $statusColor],
                            ],
                        ]);

                        // Hyperlink Styling for URLs
                        $sheet->getStyle("L{$row}")->applyFromArray([
                            'font' => ['color' => ['rgb' => '0F7A66'], 'underline' => true],
                        ]);
                        $sheet->getStyle("M{$row}")->applyFromArray([
                            'font' => ['color' => ['rgb' => '4F46E5'], 'underline' => true],
                        ]);
                    }
                }

                // 6. Freeze Panes di Row 4 agar Header tetap terlihat saat scroll ke bawah
                $sheet->freezePane('A5');
            },
        ];
    }
}
