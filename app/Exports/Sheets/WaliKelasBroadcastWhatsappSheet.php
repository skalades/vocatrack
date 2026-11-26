<?php

namespace App\Exports\Sheets;

use App\Exports\Sheets\Concerns\AppliesVokaTrackAccountSheet;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;

class WaliKelasBroadcastWhatsappSheet implements FromCollection, WithTitle, WithEvents
{
    use AppliesVokaTrackAccountSheet;

    public function __construct(
        protected Collection $teachers,
        protected array $meta = []
    ) {}

    public function title(): string
    {
        return 'Siap Kirim WA Wali Kelas';
    }

    public function collection(): Collection
    {
        $rows = collect();
        $loginUrl = route('login');

        $rows->push(['FORMAT PESAN BROADCAST WHATSAPP WALI KELAS']);
        $rows->push(['Salin (copy) teks pada kolom "Pesan Siap Kirim WhatsApp" untuk dibagikan langsung ke masing-masing Wali Kelas.']);
        $rows->push(['']);

        $rows->push([
            'No',
            'Nama Lengkap Wali Kelas',
            'NIP',
            'Rombel Kelas Ampuan',
            'Pesan Siap Kirim WhatsApp (Akses Akun Wali Kelas)',
        ]);

        $no = 1;
        foreach ($this->teachers as $teacher) {
            $user = $teacher->user;
            $name = $user?->name ?? '-';
            $nip = (string) ($teacher->nip ?? '-');
            $email = $user?->email ?? '-';
            $classNames = $teacher->homeroomClasses->pluck('name')->filter()->implode(', ') ?: '-';

            $waMessage = "Yth. Bapak/Ibu {$name},\n\n"
                . "Berikut adalah informasi akses akun resmi Anda sebagai Wali Kelas pada sistem VokaTrack:\n\n"
                . "🔐 INFORMASI LOGIN WALI KELAS:\n"
                . "• Portal Login: {$loginUrl}\n"
                . "• Username / NIP: {$nip}\n"
                . "• Email Akun: {$email}\n"
                . "• Password Default: password\n"
                . "• Rombel Ampuan: {$classNames}\n\n"
                . "Melalui panel Wali Kelas, Bapak/Ibu dapat memantau presensi, jurnal PKL, kendala lapangan, rekap nilai rapor PKL, serta membagikan tautan monitoring ke orang tua siswa.\n\n"
                . "Catatan: Mohon segera login dan perbarui kata sandi Anda di menu Profil demi keamanan akun.";

            $rows->push([
                $no++,
                $name,
                $nip,
                $classNames,
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
