<?php

namespace App\Exports\Sheets;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class StudentBroadcastWhatsappSheet implements FromCollection, WithTitle, WithEvents
{
    public function __construct(
        protected Collection $students,
        protected array $meta = []
    ) {}

    public function title(): string
    {
        return 'Format Siap Kirim WA';
    }

    public function collection(): Collection
    {
        $rows = collect();
        $loginUrl = route('login');

        // Row 1: Judul
        $rows->push(['FORMAT PESAN BROADCAST WHATSAPP SISWA & ORANG TUA']);

        // Row 2: Petunjuk
        $rows->push(['Salin (copy) teks pada kolom "Pesan Siap Kirim WhatsApp" untuk dibagikan langsung ke grup WhatsApp siswa atau wali murid.']);

        // Row 3: Baris Pemisah
        $rows->push(['']);

        // Row 4: Header Kolom
        $rows->push([
            'No',
            'Nama Lengkap Siswa',
            'NISN',
            'Kelas & Jurusan',
            'Pesan Siap Kirim WhatsApp (Siswa & Orang Tua)',
        ]);

        // Row 5+: Data Siap Kirim
        $no = 1;
        foreach ($this->students as $student) {
            $user = $student->user;
            $name = $user?->name ?? '-';
            $nisn = (string) ($student->nisn ?? '-');
            $majorName = $student->class?->major?->name ?? '-';
            $className = $student->class?->name ?? '-';
            $companyName = $student->activePkl?->company?->name;
            $companyLine = $companyName ? "• Tempat PKL: {$companyName}\n" : "";
            $teacherName = $student->activePkl?->teacher?->user?->name;
            $teacherLine = $teacherName ? "• Guru Pembimbing: {$teacherName}\n" : "";
            $parentUrl = route('parent.monitor', ['token' => $student->parent_token]);

            $waMessage = "Halo {$name}, berikut adalah informasi akses sistem VokaTrack PKL Anda:\n\n"
                       . "🔐 AKSES LOGIN SISWA:\n"
                       . "• URL Login: {$loginUrl}\n"
                       . "• Username / NISN: {$nisn}\n"
                       . "• Password Default: password\n"
                       . "• Jurusan / Kelas: {$majorName} / {$className}\n"
                       . $companyLine
                       . $teacherLine . "\n"
                       . "👨‍👩‍👦 TAUTAN MONITORING ORANG TUA:\n"
                       . "• URL Portal Ortu: {$parentUrl}\n"
                       . "(Orang tua dapat memantau presensi, jurnal harian, dan penempatan PKL tanpa perlu login)\n\n"
                       . "Catatan: Segera masuk ke aplikasi dan perbarui kata sandi Anda di menu Profil demi keamanan akun.";

            $rows->push([
                $no++,
                $name,
                $nisn,
                "{$className} - {$majorName}",
                $waMessage,
            ]);
        }

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $totalRows = $this->students->count() + 4;

                // Column Widths
                $sheet->getColumnDimension('A')->setWidth(6);
                $sheet->getColumnDimension('B')->setWidth(26);
                $sheet->getColumnDimension('C')->setWidth(18);
                $sheet->getColumnDimension('D')->setWidth(28);
                $sheet->getColumnDimension('E')->setWidth(92);

                // Row 1 (Title)
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

                // Row 2 (Subtitle)
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

                // Row 3 (Pemisah Kosong)
                $sheet->getRowDimension(3)->setRowHeight(12);

                // Row 4 (Header)
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
                        'startColor' => ['rgb' => '0F7A66'], // Teal/Emerald Green
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
                    $sheet->getRowDimension($row)->setRowHeight(135);

                    $isOdd = ($row % 2 !== 0);
                    $bgColor = $isOdd ? 'F8FAFC' : 'FFFFFF';

                    $sheet->getStyle("A{$row}:E{$row}")->applyFromArray([
                        'font' => ['name' => 'Segoe UI', 'size' => 9.5],
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
                    ]);

                    // Alignments
                    $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_TOP);
                    $sheet->getStyle("B{$row}")->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
                    $sheet->getStyle("C{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_TOP);
                    $sheet->getStyle("D{$row}")->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
                    
                    // Column E: Wrap text and top aligned with soft green tint
                    $sheet->getStyle("E{$row}")->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
                    $sheet->getStyle("E{$row}")->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'F0FDF4'],
                        ],
                        'font' => [
                            'name' => 'Segoe UI',
                            'size' => 9.5,
                            'color' => ['rgb' => '064E3B'],
                        ],
                    ]);
                }

                $sheet->freezePane('A5');
            },
        ];
    }
}
