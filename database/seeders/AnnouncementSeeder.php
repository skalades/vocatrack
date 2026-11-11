<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\Major;
use App\Models\School;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AnnouncementSeeder extends Seeder
{
    public function run(): void
    {
        $school = School::where('npsn', '20209203')->first();
        if (!$school) {
            return;
        }

        $admin = User::where('school_id', $school->id)->where('role', 'admin-sekolah')->first();
        $guru = User::where('school_id', $school->id)->where('role', 'guru-pembimbing')->first();
        $majorGP = Major::where('school_id', $school->id)->where('code', 'GP')->first();
        $majorRPL = Major::where('school_id', $school->id)->where('code', 'RPL')->first();

        if (!$admin || !$guru) {
            return;
        }

        // 1. Pengumuman K3 Khusus Jurusan Geologi Pertambangan
        Announcement::create([
            'school_id' => $school->id,
            'author_id' => $admin->id,
            'title' => 'Wajib Memakai APD Standar K3 di Wilayah Operasional Pertambangan PT ANTAM',
            'content' => "Diberitahukan kepada seluruh siswa Praktik Kerja Lapangan (PKL) Jurusan Teknik Geologi Pertambangan yang bertugas di PT Aneka Tambang Tbk (Unit Bisnis Geomin), wajib selalu menggunakan APD Lengkap (Safety Helmet, Safety Shoes, Rompi Reflektor, dan Kacamata Pelindung) selama berada di zona pit eksplorasi.\n\nPelanggaran terhadap SOP K3 Industri akan dikenakan sanksi disiplin dan pembatalan izin presensi.",
            'category' => 'penting',
            'image_path' => null,
            'target_role' => 'siswa',
            'target_major_id' => $majorGP ? $majorGP->id : null,
            'is_pinned' => true,
            'is_active' => true,
            'published_at' => Carbon::now()->subHours(3),
        ]);

        // 2. Pengumuman Kunjungan Monitoring Guru Pembimbing
        Announcement::create([
            'school_id' => $school->id,
            'author_id' => $guru->id,
            'title' => 'Jadwal Monitoring & Diskusi Lapangan Guru Pembimbing Pekan Ini',
            'content' => "Halo rekan-rekan siswa bimbingan PKL,\n\nSaya akan melaksanakan kunjungan supervisi ke lapangan PT Aneka Tambang Tbk pada hari Kamis pekan ini pukul 09.00 WIB. Pastikan logbook jurnal harian Anda di aplikasi VokaTrack sudah terisi lengkap hingga tanggal kemarin dan sudah divalidasi oleh mentor industri DU/DI.",
            'category' => 'kegiatan',
            'image_path' => null,
            'target_role' => 'siswa',
            'target_major_id' => null, // Semua siswa bimbingan
            'is_pinned' => false,
            'is_active' => true,
            'published_at' => Carbon::now()->subDays(1),
        ]);

        // 3. Pengumuman Evaluasi Tengah Periode (Semua Civitas)
        Announcement::create([
            'school_id' => $school->id,
            'author_id' => $admin->id,
            'title' => 'Sosialisasi Alur Evaluasi Jurnal Harian & Instrumen Penilaian PKL',
            'content' => "Disampaikan kepada seluruh siswa PKL, guru pembimbing, dan pembimbing industri bahwa mulai pekan depan sistem verifikasi jurnal bertingkat (Tingkat 1 oleh DU/DI, Tingkat 2 oleh Guru) wajib diselesaikan tepat waktu sebelum tanggal 28 setiap bulannya.",
            'category' => 'akademik',
            'image_path' => null,
            'target_role' => 'all',
            'target_major_id' => null, // Semua Jurusan & Seluruh Pengguna
            'is_pinned' => false,
            'is_active' => true,
            'published_at' => Carbon::now()->subDays(2),
        ]);

        // 4. Pengumuman Spesifik RPL (Siswa Geologi Pertambangan TIDAK BOLEH melihat ini)
        if ($majorRPL) {
            Announcement::create([
                'school_id' => $school->id,
                'author_id' => $admin->id,
                'title' => 'Workshop Git Version Control & Deployment Khusus Rekayasa Perangkat Lunak',
                'content' => "Daftar peserta workshop Git Branching Model dan CI/CD pipeline untuk siswa RPL yang sedang PKL di software house rekanan.",
                'category' => 'kegiatan',
                'image_path' => null,
                'target_role' => 'siswa',
                'target_major_id' => $majorRPL->id,
                'is_pinned' => false,
                'is_active' => true,
                'published_at' => Carbon::now()->subDays(3),
            ]);
        }
    }
}
