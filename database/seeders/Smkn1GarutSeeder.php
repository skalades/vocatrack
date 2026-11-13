<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class Smkn1GarutSeeder extends Seeder
{
    /**
     * Master Modular Seeder untuk SMK Negeri 1 Garut.
     * Mengatur urutan eksekusi seeder:
     * 1. Profil Sekolah, Pengaturan, Admin Sekolah, dan Kepala Sekolah
     * 2. 10 Jurusan Resmi dan Kelas-kelas Tingkat XII
     * 3. Guru Pembimbing Produktif dan Pemetaan Jurusan
     * 4. 10 Tempat PKL / DUDI Mitra Industri, GPS, Jam Kerja, Pemetaan Jurusan, dan Pembimbing DUDI
     * 5. Siswa Lengkap, Periode PKL, dan Plotting Penempatan PKL Aktif
     * 6. Jurnal Harian Kejuruan, Absensi Terkait, dan Approval Bertingkat (DU/DI & Guru)
     * 7. Penilaian Akhir Komprehensif (Aspek DU/DI & Guru) dan Penerbitan Sertifikat
     */
    public function run(): void
    {
        $this->call([
            Smkn1GarutSchoolSeeder::class,
            Smkn1GarutMajorAndClassSeeder::class,
            Smkn1GarutTeacherSeeder::class,
            Smkn1GarutCompanySeeder::class,
            Smkn1GarutStudentAndPlottingSeeder::class,
            Smkn1GarutJournalSeeder::class,
            Smkn1GarutAssessmentSeeder::class,
        ]);
    }
}
