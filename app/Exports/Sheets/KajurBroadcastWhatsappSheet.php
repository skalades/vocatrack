<?php

namespace App\Exports\Sheets;

use App\Exports\Sheets\Concerns\AppliesVokaTrackAccountSheet;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;

class KajurBroadcastWhatsappSheet implements FromCollection, WithTitle, WithEvents
{
    use AppliesVokaTrackAccountSheet;

    public function __construct(
        protected Collection $teachers,
        protected array $meta = []
    ) {}

    public function title(): string
    {
        return 'Siap Kirim WA Kajur';
    }

    public function collection(): Collection
    {
        $rows = collect();
        $loginUrl = route('login');

        $rows->push(['FORMAT PESAN BROADCAST WHATSAPP KEPALA PROGRAM KEAHLIAN (KAJUR)']);
        $rows->push(['Salin (copy) teks pada kolom "Pesan Siap Kirim WhatsApp" untuk dibagikan langsung ke masing-masing Kepala Program Keahlian.']);
        $rows->push(['']);

        $rows->push([
            'No',
            'Nama Lengkap Kajur',
            'NIP',
            'Jurusan yang Dipimpin',
            'Pesan Siap Kirim WhatsApp (Akses Akun Kajur)',
        ]);

        $no = 1;
        foreach ($this->teachers as $teacher) {
            $user = $teacher->user;
            $name = $user?->name ?? '-';
            $nip = (string) ($teacher->nip ?? '-');
            $email = $user?->email ?? '-';
            $majorsStr = $teacher->headedMajors
                ->map(fn ($major) => trim("{$major->code} - {$major->name}", ' -'))
                ->implode(', ') ?: '-';

            $waMessage = "Yth. Bapak/Ibu {$name},\n\n"
                . "Berikut adalah informasi akses akun resmi Anda sebagai Kepala Program Keahlian (Kajur) pada sistem VokaTrack:\n\n"
                . "🔐 INFORMASI LOGIN KAJUR:\n"
                . "• Portal Login: {$loginUrl}\n"
                . "• Username / NIP: {$nip}\n"
                . "• Email Akun: {$email}\n"
                . "• Password Default: password\n"
                . "• Jurusan Binaan: {$majorsStr}\n\n"
                . "Melalui panel Kajur, Bapak/Ibu dapat mengelola mitra DU/DI jurusan, plotting siswa PKL, capaian pembelajaran (CP), serta memantau presensi, jurnal, dan rekap nilai jurusan.\n\n"
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
            AfterSheet::class => function (AfterSheet $event): void {
                $this->styleBroadcastWhatsappSheet($event->sheet->getDelegate(), $this->teachers->count());
            },
        ];
    }
}
