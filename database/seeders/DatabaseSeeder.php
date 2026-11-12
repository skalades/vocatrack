<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with modular seeders for SMKN 2 GARUT.
     */
    public function run(): void
    {
        $this->call([
            SchoolSeeder::class,
            MajorAndClassSeeder::class,
            UserAndRoleSeeder::class,
            CompanyAndSupervisorSeeder::class,
            PklPeriodAndPlottingSeeder::class,
            PklActivitySeeder::class,
            AnnouncementSeeder::class,
            PsptPklSeeder::class,
        ]);
    }
}
