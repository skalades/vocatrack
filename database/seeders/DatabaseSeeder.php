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
        $seeders = [
            // Sekolah 1: SMKN 2 Garut
            SchoolSeeder::class,
            MajorAndClassSeeder::class,
            UserAndRoleSeeder::class,
            PsptPklSeeder::class,
            AnnouncementSeeder::class,

            // Sekolah 2: SMKN 1 Garut
            Smkn1GarutSeeder::class,

            // Sekolah 3: SMKN 10 Garut
            Smkn10GarutSeeder::class,

            // SaaS Subscription Plans & Seed Data
            SubscriptionPlanSeeder::class,

            // Sekolah 4: SMKN 04 Bombana (tenant baru, Trial 7 Hari mulai hari ini)
            Smkn4BombanaSeeder::class,
        ];

        // Seeder modular SMKN 2 Garut (GP, TAV, FI, DKV, TL, TEI, TKR, TITL, TP, & master siswa belum terplotting) opsional jika berkas tersedia
        $optionalSmkn2Seeders = [
            \Database\Seeders\GeologiPertambanganPklSeeder::class,
            \Database\Seeders\TeknikAudioVideoPklSeeder::class,
            \Database\Seeders\FarmasiIndustriPklSeeder::class,
            \Database\Seeders\DesainKomunikasiVisualPklSeeder::class,
            \Database\Seeders\TeknikLogistikPklSeeder::class,
            \Database\Seeders\TeknikElektronikaIndustriPklSeeder::class,
            \Database\Seeders\TeknikKendaraanRinganPklSeeder::class,
            \Database\Seeders\TeknikInstalasiTenagaListrikPklSeeder::class,
            \Database\Seeders\TeknikPermesinanPklSeeder::class,
            \Database\Seeders\Smkn2GarutMasterStudentSeeder::class,
        ];
        $insertAt = 3;
        foreach ($optionalSmkn2Seeders as $optionalSeeder) {
            if (class_exists($optionalSeeder)) {
                array_splice($seeders, $insertAt, 0, [$optionalSeeder]);
                $insertAt++;
            }
        }

        $this->call($seeders);
    }
}
