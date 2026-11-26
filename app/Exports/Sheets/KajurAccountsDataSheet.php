<?php

namespace App\Exports\Sheets;

use App\Exports\Sheets\Concerns\AppliesVokaTrackAccountSheet;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class KajurAccountsDataSheet implements FromCollection, WithTitle, WithEvents, WithColumnFormatting
{
    use AppliesVokaTrackAccountSheet;

    public function __construct(
        protected Collection $teachers,
        protected array $meta = []
    ) {}

    public function title(): string
    {
        return 'Data Akun Kajur';
    }

    public function collection(): Collection
    {
        $rows = collect();
        $loginUrl = route('login');

        $rows->push(['DAFTAR AKUN & AKSES LOGIN KEPALA PROGRAM KEAHLIAN (KAJUR) — VOKATRACK']);

        $school = $this->meta['school_name'] ?? 'Sekolah';
        $majorFilter = $this->meta['major_name'] ?? 'Semua Jurusan';
        $total = $this->teachers->count();
        $date = $this->meta['downloaded_at'] ?? now()->format('d/m/Y H:i');
        $rows->push(["Sekolah: {$school}   |   Filter Jurusan: {$majorFilter}   |   Total Kajur: {$total} Orang   |   Waktu Unduh: {$date}"]);
        $rows->push(['']);

        $rows->push([
            'No',
            'Nama Lengkap Kajur',
            'NIP (Username Login)',
            'Kode Jurusan',
            'Jurusan yang Dipimpin',
            'Rombel & Siswa Jurusan',
            'Email Akun Login',
            'Password Default',
            'Status Akun',
            'Tautan Login',
            'No. Telepon / WhatsApp',
        ]);

        $no = 1;
        foreach ($this->teachers as $teacher) {
            $user = $teacher->user;
            $headedMajors = $teacher->headedMajors;

            $majorCodes = $headedMajors->pluck('code')->filter()->implode(', ') ?: '-';
            $majorNames = $headedMajors->pluck('name')->filter()->implode(', ') ?: '-';
            $totalClasses = $headedMajors->sum(fn ($major) => $major->classes->count());
            $totalStudents = $headedMajors->sum(fn ($major) => $major->classes->sum('students_count'));

            $rows->push([
                $no++,
                $user?->name ?? '-',
                (string) ($teacher->nip ?? '-'),
                $majorCodes,
                $majorNames,
                "{$totalClasses} Kelas / {$totalStudents} Siswa",
                $user?->email ?? '-',
                'password',
                ($user && $user->is_active) ? 'Aktif' : 'Nonaktif',
                $loginUrl,
                $this->resolveAccountPhone($user),
            ]);
        }

        return $rows;
    }

    public function columnFormats(): array
    {
        return [
            'C' => NumberFormat::FORMAT_TEXT,
            'K' => NumberFormat::FORMAT_TEXT,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $this->styleAccountTableSheet(
                    $event->sheet->getDelegate(),
                    $this->teachers->count(),
                    [
                        'A' => 6,
                        'B' => 30,
                        'C' => 22,
                        'D' => 16,
                        'E' => 34,
                        'F' => 24,
                        'G' => 30,
                        'H' => 18,
                        'I' => 14,
                        'J' => 36,
                        'K' => 22,
                    ],
                    ['A', 'C', 'D', 'F', 'H', 'I'],
                    ['passwordCol' => 'H', 'statusCol' => 'I', 'urlCol' => 'J']
                );
            },
        ];
    }
}
