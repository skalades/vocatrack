<?php

namespace Database\Seeders;

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
        Teacher::create([
            'user_id' => $guru1->id,
            'nip' => '198702142011011003',
        ]);

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
        Teacher::create([
            'user_id' => $guru2->id,
            'nip' => '198203142008011005',
        ]);

        // 4. Siswa Kejuruan Geologi Pertambangan SMKN 2 Garut
        $classGp1 = SchoolClass::where('school_id', $school->id)->where('name', 'XII GP 1')->firstOrFail();
        $classGp2 = SchoolClass::where('school_id', $school->id)->where('name', 'XII GP 2')->firstOrFail();

        // Siswa 1 (Primary Demo Siswa)
        $siswaUser1 = User::create([
            'school_id' => $school->id,
            'name' => 'Fajar Nugraha',
            'email' => 'siswa@smkn2garut.sch.id',
            'password' => Hash::make('password'),
            'role' => 'siswa',
            'is_active' => true,
        ]);
        Profile::create([
            'user_id' => $siswaUser1->id,
            'gender' => 'L',
            'phone' => '081398765401',
            'address' => 'Jl. Otista No. 15, Tarogong Kaler, Garut',
        ]);
        Student::create([
            'user_id' => $siswaUser1->id,
            'class_id' => $classGp1->id,
            'nis' => '240101',
            'nisn' => '0067890123',
        ]);

        // Siswa 2
        $siswaUser2 = User::create([
            'school_id' => $school->id,
            'name' => 'Rendi Pratama',
            'email' => 'rendi@smkn2garut.sch.id',
            'password' => Hash::make('password'),
            'role' => 'siswa',
            'is_active' => true,
        ]);
        Profile::create([
            'user_id' => $siswaUser2->id,
            'gender' => 'L',
            'phone' => '081398765402',
            'address' => 'Jl. Cimanuk No. 89, Garut Kota',
        ]);
        Student::create([
            'user_id' => $siswaUser2->id,
            'class_id' => $classGp1->id,
            'nis' => '240102',
            'nisn' => '0067890124',
        ]);

        // Siswa 3
        $siswaUser3 = User::create([
            'school_id' => $school->id,
            'name' => 'Gina Septiani',
            'email' => 'gina@smkn2garut.sch.id',
            'password' => Hash::make('password'),
            'role' => 'siswa',
            'is_active' => true,
        ]);
        Profile::create([
            'user_id' => $siswaUser3->id,
            'gender' => 'P',
            'phone' => '081398765403',
            'address' => 'Jl. Patriot No. 12, Tarogong Kidul, Garut',
        ]);
        Student::create([
            'user_id' => $siswaUser3->id,
            'class_id' => $classGp2->id,
            'nis' => '240103',
            'nisn' => '0067890125',
        ]);

        // Siswa 4
        $siswaUser4 = User::create([
            'school_id' => $school->id,
            'name' => 'Dika Firmansyah',
            'email' => 'dika@smkn2garut.sch.id',
            'password' => Hash::make('password'),
            'role' => 'siswa',
            'is_active' => true,
        ]);
        Profile::create([
            'user_id' => $siswaUser4->id,
            'gender' => 'L',
            'phone' => '081398765404',
            'address' => 'Jl. Merdeka No. 64, Garut',
        ]);
        Student::create([
            'user_id' => $siswaUser4->id,
            'class_id' => $classGp2->id,
            'nis' => '240104',
            'nisn' => '0067890126',
        ]);
    }
}
