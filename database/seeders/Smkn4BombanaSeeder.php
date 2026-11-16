<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class Smkn4BombanaSeeder extends Seeder
{
    /**
     * Master seeder SMK Negeri 04 Bombana (tenant Trial 7 Hari).
     * 1. Profil sekolah, pengaturan, Admin Sekolah, Kepala Sekolah, dan langganan trial
     * 2. 4 Program Keahlian (TKRO, TJKT, ATPH, AKL) dan kelas XII
     * 3. Guru Pembimbing dan pemetaan jurusan
     * 4. 5 Tempat PKL / DUDI beserta Pembimbing DUDI
     * 5. 10 siswa, periode PKL aktif, dan plotting penempatan
     *
     * Jalankan: php artisan db:seed --class=Smkn4BombanaSeeder
     */
    public function run(): void
    {
        $this->call([
            Smkn4BombanaTrialSeeder::class,
            Smkn4BombanaMajorAndClassSeeder::class,
            Smkn4BombanaTeacherSeeder::class,
            Smkn4BombanaCompanySeeder::class,
            Smkn4BombanaStudentAndPlottingSeeder::class,
        ]);
    }
}
