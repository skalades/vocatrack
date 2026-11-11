<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Profile;
use App\Models\School;
use App\Models\Supervisor;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CompanyAndSupervisorSeeder extends Seeder
{
    public function run(): void
    {
        $school = School::where('npsn', '20209203')->firstOrFail();

        // 1. PT Aneka Tambang Tbk (Antam) - Unit Bisnis Geomin & Eksplorasi
        $companyAntam = Company::create([
            'school_id' => $school->id,
            'name' => 'PT Aneka Tambang Tbk - Unit Bisnis Geomin & Eksplorasi Mineral',
            'address' => 'Gedung Antam Posko Eksplorasi Jabar, Jl. Raya Samarang No. 45, Tarogong, Garut',
            'contact_person' => 'Ir. Hendra Gunawan, S.T., M.Sc.',
            'phone' => '0262-234567',
            'gps_enabled' => true,
            'latitude' => -7.21854000,
            'longitude' => 107.89230000,
            'radius_meters' => 200,
            'override_hours' => false,
            'work_days' => '5_days',
        ]);

        $supervisorUserAntam = User::create([
            'school_id' => $school->id,
            'name' => 'Ir. Hendra Gunawan, S.T., M.Sc.',
            'email' => 'supervisor@antam.com',
            'password' => Hash::make('password'),
            'role' => 'pembimbing-dudi',
            'is_active' => true,
        ]);
        Profile::create([
            'user_id' => $supervisorUserAntam->id,
            'gender' => 'L',
            'phone' => '081234567894',
            'address' => 'Tarogong, Kabupaten Garut',
        ]);
        Supervisor::create([
            'user_id' => $supervisorUserAntam->id,
            'company_id' => $companyAntam->id,
            'position' => 'Chief Exploration Geologist & Mining Superintendent',
        ]);

        // 2. PT Timah Tbk - Divisi Eksplorasi Darat & Pemboran Tambang
        $companyTimah = Company::create([
            'school_id' => $school->id,
            'name' => 'PT Timah Tbk - Divisi Eksplorasi Darat & Pemboran Tambang',
            'address' => 'Area Operasional Pemboran & Eksplorasi, Jl. Cimanuk No. 112, Tarogong Kidul, Garut',
            'contact_person' => 'Bambang Triyono, S.T.',
            'phone' => '0262-541289',
            'gps_enabled' => true,
            'latitude' => -7.20912000,
            'longitude' => 107.90145000,
            'radius_meters' => 150,
            'override_hours' => false,
            'work_days' => '5_days',
        ]);

        $supervisorUserTimah = User::create([
            'school_id' => $school->id,
            'name' => 'Bambang Triyono, S.T.',
            'email' => 'supervisor.timah@smkn2garut.sch.id',
            'password' => Hash::make('password'),
            'role' => 'pembimbing-dudi',
            'is_active' => true,
        ]);
        Profile::create([
            'user_id' => $supervisorUserTimah->id,
            'gender' => 'L',
            'phone' => '081234567895',
            'address' => 'Garut Kota',
        ]);
        Supervisor::create([
            'user_id' => $supervisorUserTimah->id,
            'company_id' => $companyTimah->id,
            'position' => 'Senior Mine Drilling & Exploration Supervisor',
        ]);

        // 3. Pusat Sumber Daya Mineral, Batubara dan Panas Bumi (PSDMBP) - Badan Geologi Kementerian ESDM
        $companyGeologi = Company::create([
            'school_id' => $school->id,
            'name' => 'PSDMBP Badan Geologi - Kementerian ESDM',
            'address' => 'Balai Survei & Eksplorasi Geologi Wilayah Jawa Barat, Jl. Diponegoro No. 57, Bandung',
            'contact_person' => 'Dr. Ir. Asep Supriatna, M.T.',
            'phone' => '022-7203884',
            'gps_enabled' => false,
            'latitude' => -6.90120000,
            'longitude' => 107.62100000,
            'radius_meters' => 250,
            'override_hours' => false,
            'work_days' => '5_days',
        ]);

        $supervisorUserGeologi = User::create([
            'school_id' => $school->id,
            'name' => 'Dr. Ir. Asep Supriatna, M.T.',
            'email' => 'supervisor.geologi@esdm.go.id',
            'password' => Hash::make('password'),
            'role' => 'pembimbing-dudi',
            'is_active' => true,
        ]);
        Profile::create([
            'user_id' => $supervisorUserGeologi->id,
            'gender' => 'L',
            'phone' => '081234567896',
            'address' => 'Bandung',
        ]);
        Supervisor::create([
            'user_id' => $supervisorUserGeologi->id,
            'company_id' => $companyGeologi->id,
            'position' => 'Koordinator Tim Pemetaan Geologi Regional & Sumber Daya Mineral',
        ]);

        // 4. PT Geoservices Ltd. - Divisi Laboratorium Geokimia & Geoteknik Batuan
        $companyGeoservices = Company::create([
            'school_id' => $school->id,
            'name' => 'PT Geoservices Ltd. - Divisi Laboratorium Geokimia & Geoteknik Batuan',
            'address' => 'Laboratorium Preparasi Conto Mineral & Pengujian Batuan, Jl. Otista No. 88, Tarogong, Garut',
            'contact_person' => 'Rahmat Hidayat, S.Si.',
            'phone' => '0262-452110',
            'gps_enabled' => true,
            'latitude' => -7.20500000,
            'longitude' => 107.89500000,
            'radius_meters' => 120,
            'override_hours' => false,
            'work_days' => '5_days',
        ]);

        $supervisorUserGeoservices = User::create([
            'school_id' => $school->id,
            'name' => 'Rahmat Hidayat, S.Si.',
            'email' => 'supervisor.geoservices@geoservices.co.id',
            'password' => Hash::make('password'),
            'role' => 'pembimbing-dudi',
            'is_active' => true,
        ]);
        Profile::create([
            'user_id' => $supervisorUserGeoservices->id,
            'gender' => 'L',
            'phone' => '081234567897',
            'address' => 'Tarogong Kaler, Garut',
        ]);
        Supervisor::create([
            'user_id' => $supervisorUserGeoservices->id,
            'company_id' => $companyGeoservices->id,
            'position' => 'Petrographic & Mineral Laboratory Analyst',
        ]);
    }
}
