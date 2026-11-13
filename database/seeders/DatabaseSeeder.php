<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with modular multi-school seeders:
     * - SMKN 2 GARUT (Pionir Geologi Pertambangan & PSPT)
     * - SMKN 1 GARUT (Pusat Keunggulan Bisnis Manajemen, TI, Farmasi, & Energi Terbarukan)
     * - SMKN 10 GARUT (Pusat Keunggulan Otomotif, TI, DKV, & Perbankan Syariah)
     */
    public function run(): void
    {
        $this->call([
            // Sekolah 1: SMKN 2 Garut
            SchoolSeeder::class,
            MajorAndClassSeeder::class,
            UserAndRoleSeeder::class,
            CompanyAndSupervisorSeeder::class,
            PklPeriodAndPlottingSeeder::class,
            PklActivitySeeder::class,
            AnnouncementSeeder::class,
            PsptPklSeeder::class,

            // Sekolah 2: SMKN 1 Garut
            Smkn1GarutSeeder::class,

            // Sekolah 3: SMKN 10 Garut
            Smkn10GarutSeeder::class,
        ]);
    }
}
