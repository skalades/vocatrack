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

class TeacherBroadcastWhatsappSheet implements FromCollection, WithTitle, WithEvents
{
    public function __construct(
        protected Collection $teachers,
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
        $rows->push(['FORMAT PESAN BROADCAST WHATSAPP GURU PEMBIMBING PKL']);

        // Row 2: Petunjuk
        $rows->push(['Salin (copy) teks pada kolom "Pesan Siap Kirim WhatsApp" untuk dibagikan langsung secara privat atau ke grup WhatsApp guru pembimbing.']);

        // Row 3: Baris Pemisah
        $rows->push(['']);

        // Row 4: Header Kolom
        $rows->push([
            'No',
            'Nama Lengkap Guru',
            'NIP',
            'Jurusan yang Diampu',
            'Pesan Siap Kirim WhatsApp (Akses Akun Guru)',
        ]);

        // Row 5+: Data Siap Kirim
        $no = 1;
        foreach ($this->teachers as $teacher) {
            $user = $teacher->user;
            $name = $user?->name ?? '-';
            $nip = (string) ($teacher->nip ?? '-');
            $email = $user?->email ?? '-';

            if ($teacher->majors->isNotEmpty()) {
                $majorsStr = $teacher->majors->pluck('name')->implode(', ');
            } else {
                $majorsStr = 'Lintas Jurusan';
            }

            $waMessage = "Yth. Bapak/Ibu {$name},\n\n"
                       . "Berikut adalah informasi akses akun resmi Anda sebagai Guru Pembimbing PKL pada sistem VokaTrack:\n\n"
                       . "🔐 INFORMASI LOGIN GURU:\n"
                       . "• Portal Login: {$loginUrl}\n"
                       . "• Username / NIP: {$nip}\n"
                       . "• Email Akun: {$email}\n"
                       . "• Password Default: password\n"
                       . "• Jurusan Binaan: {$majorsStr}\n\n"
                       . "Melalui portal ini, Bapak/Ibu dapat memantau presensi harian siswa, memvalidasi jurnal mingguan, mencatat supervisi kunjungan industri, dan menginput penilaian akhir PKL.\n\n"
                       . "Catatan: Mohon segera login dan perbarui kata sandi Anda di menu Profil demi keamanan akun.";

            $rows->push([
                $no++,
                $name,
                $nip,
                $majorsStr,
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
                $totalRows = $this->teachers->count() + 4;

                // Column Widths
                $sheet->getColumnDimension('A')->setWidth(6);
                $sheet->getColumnDimension('B')->setWidth(28);
                $sheet->getColumnDimension('C')->setWidth(20);
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

                // Row 5+: Data Rows
                if ($this->teachers->count() > 0) {
                    for ($row = 5; $row <= $totalRows; $row++) {
                        $isOdd = ($row % 2 !== 0);
                        $bgColor = $isOdd ? 'F8FAFC' : 'FFFFFF';

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
                            'alignment' => [
                                'vertical' => Alignment::VERTICAL_TOP,
                            ],
                        ]);

                        $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        $sheet->getStyle("C{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                        // Wrap text on WA message column
                        $sheet->getStyle("E{$row}")->getAlignment()->setWrapText(true);
                    }
                }

                $sheet->freezePane('A5');
            },
        ];
    }
}
