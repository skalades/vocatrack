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

class WaliKelasAccountsDataSheet implements FromCollection, WithTitle, WithEvents, WithColumnFormatting
{
    use AppliesVokaTrackAccountSheet;

    public function __construct(
        protected Collection $teachers,
        protected array $meta = []
    ) {}

    public function title(): string
    {
        return 'Data Akun Wali Kelas';
    }

    public function collection(): Collection
    {
        $rows = collect();
        $loginUrl = route('login');

        $rows->push(['DAFTAR AKUN & AKSES LOGIN WALI KELAS — VOKATRACK']);

        $school = $this->meta['school_name'] ?? 'Sekolah';
        $majorFilter = $this->meta['major_name'] ?? 'Semua Jurusan';
        $total = $this->teachers->count();
        $date = $this->meta['downloaded_at'] ?? now()->format('d/m/Y H:i');
        $rows->push(["Sekolah: {$school}   |   Filter Jurusan: {$majorFilter}   |   Total Wali Kelas: {$total} Orang   |   Waktu Unduh: {$date}"]);
        $rows->push(['']);

        $rows->push([
            'No',
            'Nama Lengkap Wali Kelas',
            'NIP (Username Login)',
            'Rombel Kelas Ampuan',
            'Jurusan',
            'Total Siswa Rombel',
            'Email Akun Login',
            'Password Default',
            'Status Akun',
            'Tautan Login',
            'No. Telepon / WhatsApp',
        ]);

        $no = 1;
        foreach ($this->teachers as $teacher) {
            $user = $teacher->user;
            $classes = $teacher->homeroomClasses;

            $classNames = $classes->pluck('name')->filter()->implode(', ') ?: '-';
            $majorCodes = $classes->pluck('major.code')->filter()->unique()->implode(', ') ?: '-';
            $totalStudents = $classes->sum('students_count');

            $rows->push([
                $no++,
                $user?->name ?? '-',
                (string) ($teacher->nip ?? '-'),
                $classNames,
                $majorCodes,
                "{$totalStudents} Siswa",
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
                        'D' => 26,
                        'E' => 18,
                        'F' => 20,
                        'G' => 30,
                        'H' => 18,
                        'I' => 14,
                        'J' => 36,
                        'K' => 22,
                    ],
                    ['A', 'C', 'E', 'F', 'H', 'I'],
                    ['passwordCol' => 'H', 'statusCol' => 'I', 'urlCol' => 'J']
                );
            },
        ];
    }
}
