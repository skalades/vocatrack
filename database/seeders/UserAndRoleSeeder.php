<?php

namespace Database\Seeders;

use App\Models\Major;
use App\Models\Profile;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserAndRoleSeeder extends Seeder
{
    public function run(): void
    {
        $school = School::where('npsn', '20209203')->firstOrFail();

        // 1. Admin Sekolah SMKN 2 Garut
        $schoolAdmin = User::create([
            'school_id' => $school->id,
            'name' => 'Admin SMKN 2 Garut',
            'email' => 'admin@smkn2garut.sch.id',
            'password' => Hash::make('password'),
            'role' => 'admin-sekolah',
            'is_active' => true,
        ]);
        Profile::create([
            'user_id' => $schoolAdmin->id,
            'gender' => 'L',
            'phone' => '081223344550',
            'address' => 'Komplek SMKN 2 Garut, Tarogong Kaler',
        ]);

        // 2. Kepala Sekolah SMKN 2 Garut
        $kepsek = User::create([
            'school_id' => $school->id,
            'name' => 'Nur Al Purkon, S.Pd., M.Si.',
            'email' => 'kepsek@smkn2garut.sch.id',
            'password' => Hash::make('password'),
            'role' => 'kepala-sekolah',
            'is_active' => true,
        ]);
        Profile::create([
            'user_id' => $kepsek->id,
            'gender' => 'L',
            'phone' => '081223344551',
            'address' => 'Tarogong Kaler, Kabupaten Garut',
        ]);

        // 3. Guru Pembimbing Kejuruan Geologi Pertambangan
        $guru1 = User::create([
            'school_id' => $school->id,
            'name' => 'Herdi Pebryana Putra, S.T',
            'email' => 'guru@smkn2garut.sch.id',
            'password' => Hash::make('password'),
            'role' => 'guru-pembimbing',
            'is_active' => true,
        ]);
        Profile::create([
            'user_id' => $guru1->id,
            'gender' => 'L',
            'phone' => '081223344552',
            'address' => 'Jl. Pembangunan No. 42, Garut Kota',
        ]);
        $tc1 = Teacher::create([
            'user_id' => $guru1->id,
            'nip' => '198702142011011003',
        ]);
        $majorGp = Major::where('code', 'GP')->first();
        if ($majorGp) {
            $tc1->majors()->sync([$majorGp->id]);
        }

        $guru2 = User::create([
            'school_id' => $school->id,
            'name' => 'Yayan Ruhimat, S.T.',
            'email' => 'guru2@smkn2garut.sch.id',
            'password' => Hash::make('password'),
            'role' => 'guru-pembimbing',
            'is_active' => true,
        ]);
        Profile::create([
            'user_id' => $guru2->id,
            'gender' => 'L',
            'phone' => '081223344553',
            'address' => 'Jl. Samarang No. 18, Tarogong Kidul, Garut',
        ]);
        $tc2 = Teacher::create([
            'user_id' => $guru2->id,
            'nip' => '198203142008011005',
        ]);
        if ($majorGp) {
            $tc2->majors()->sync([$majorGp->id]);
        }
    }
}
