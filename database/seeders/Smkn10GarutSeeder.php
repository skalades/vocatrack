<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class Smkn10GarutSeeder extends Seeder
{
    /**
     * Master Modular Seeder untuk SMK Negeri 10 Garut (Sekolah ke-3).
     * Mengatur urutan eksekusi seeder:
     * 1. Profil Sekolah, Pengaturan, Admin Sekolah, dan Kepala Sekolah
     * 2. 6 Program Keahlian Resmi (TKRO, TBSM, TJKT, DKV, AKL, LPS) dan Kelas XII
     * 3. Guru Pembimbing Produktif dan Pemetaan Jurusan
     * 4. 10 Tempat PKL / DUDI Mitra Industri, GPS, Jam Kerja, dan Pembimbing DUDI
     * 5. Siswa Lengkap, Periode PKL, dan Plotting Penempatan PKL Aktif
     * 6. Jurnal Harian Kejuruan, Absensi Terkait, dan Approval Bertingkat
     * 7. Penilaian Akhir Komprehensif dan Penerbitan Sertifikat Kelulusan
     */
    public function run(): void
    {
        $this->call([
            Smkn10GarutSchoolSeeder::class,
            Smkn10GarutMajorAndClassSeeder::class,
            Smkn10GarutTeacherSeeder::class,
            Smkn10GarutCompanySeeder::class,
            Smkn10GarutStudentAndPlottingSeeder::class,
            Smkn10GarutJournalSeeder::class,
            Smkn10GarutAssessmentSeeder::class,
        ]);
    }
}
