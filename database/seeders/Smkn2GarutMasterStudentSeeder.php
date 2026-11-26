<?php

namespace Database\Seeders;

use App\Models\Major;
use App\Models\Profile;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class Smkn2GarutMasterStudentSeeder extends Seeder
{
    private const CSV_RELATIVE_PATH = 'SSEDER/Master Siswa Belum Plotting SMKN 2 Garut.csv';
    private const SCHOOL_NPSN = '20209203';
    private const EMAIL_DOMAIN = 'smkn2garut.sch.id';
    private const ACADEMIC_YEAR = '2026/2027';

    private const MAJOR_NAMES = [
        'TITL' => 'Teknik Instalasi Tenaga Listrik',
        'TEI' => 'Teknik Elektronika Industri',
        'TAV' => 'Teknik Audio Video',
        'TKR' => 'Teknik Kendaraan Ringan Otomotif',
        'GP' => 'Teknik Geologi Pertambangan',
        'TL' => 'Teknik Logistik',
        'TP' => 'Teknik Pemesinan',
    ];

    private const FEMALE_STUDENTS = [
        'INTAN MUTIA PRATIWI',
        'ISMA RAESMAWATI',
        'LANI PAUJIAH',
        'MIRA RAHMA NURAENI',
        'SUSI',
        'BEBY NUR FITRIA',
        'OURELL FITRISIA KANAHAYA',
        'PUTRI YASINTA',
        'REVANTI APRILIA',
        'SULIS AURELLYA VEGA',
        'MELANY NURSALSABILAH',
        'ALLIFA ZAM-ZAM MIFTAHUL HUDA',
        'ALYA SALSA MAEMUNAH',
        'FADYA LESTARI PUTRI',
        'INDRI',
        'JULIYA APRIANTI',
        'N.SHENA NADIANA',
        'RAISYA',
        'SASKIA SITI SALWIAH',
        'SHIVA NUR PADILAH',
        'SYIFA SADIAH',
        'WULAN SARI',
        'ALIKHA MAHARANI YUSUP',
        'ISNI SYIAR WIDIARSY',
        'JELITA PUTRI',
        'KAYLA PUTRI NABILLA',
        'KEYLA NUR AISYAH',
        'ASTRIED NOVA THREEANA',
        'YULIA NUR RAHMANDA',
    ];

    /**
     * Seed seluruh data siswa, kelas, dan jurusan tambahan SMKN 2 Garut yang belum terplotting
     * tanpa mengganggu plotting atau akun siswa yang sudah ada.
     */
    public function run(): void
    {
        $csvPath = base_path(self::CSV_RELATIVE_PATH);
        if (!is_file($csvPath)) {
            if (isset($this->command)) {
                $this->command->warn("⚠️ [Smkn2GarutMasterStudentSeeder] Berkas CSV tidak ditemukan: {$csvPath}");
            }

            return;
        }

        $handle = fopen($csvPath, 'r');
        if ($handle === false) {
            return;
        }

        fgetcsv($handle);
        $rows = [];
        while (($raw = fgetcsv($handle)) !== false) {
            $nis = trim((string) ($raw[0] ?? ''));
            $name = preg_replace('/\s+/', ' ', trim((string) ($raw[1] ?? ''))) ?? '';
            $className = preg_replace('/\s+/', ' ', trim((string) ($raw[2] ?? ''))) ?? '';
            $majorCode = strtoupper(trim((string) ($raw[3] ?? '')));

            if ($nis === '' || $name === '' || $className === '' || $majorCode === '') {
                continue;
            }

            $rows[] = compact('nis', 'name', 'className', 'majorCode');
        }
        fclose($handle);

        if ($rows === []) {
            return;
        }

        $school = School::where('npsn', self::SCHOOL_NPSN)->first() ?? School::firstOrFail();

        // Pastikan jurusan tersedia (TL memetakan ke LOG jika sudah ada dari MajorAndClassSeeder)
        $majorsByCode = [];
        foreach (array_unique(array_column($rows, 'majorCode')) as $code) {
            $major = null;
            if ($code === 'TL') {
                $major = Major::where('school_id', $school->id)->whereIn('code', ['TL', 'LOG'])->first();
            } else {
                $major = Major::where('school_id', $school->id)->where('code', $code)->first();
            }

            if (!$major) {
                $major = Major::create([
                    'school_id' => $school->id,
                    'code' => $code,
                    'name' => self::MAJOR_NAMES[$code] ?? $code,
                    'program_duration_years' => ($code === 'GP') ? 4 : 3,
                ]);
            } elseif ($code === 'GP' && (int) $major->program_duration_years < 4) {
                $major->update(['program_duration_years' => 4]);
            }

            $majorsByCode[$code] = $major;
        }

        // Pastikan seluruh rombel kelas (termasuk XIII GP 1 & XIII GP 2) tersedia
        $classesByName = [];
        foreach ($rows as $row) {
            if (isset($classesByName[$row['className']])) {
                continue;
            }

            $major = $majorsByCode[$row['majorCode']];
            $gradeLevel = Str::startsWith($row['className'], 'XIII') ? 'XIII'
                : (Str::startsWith($row['className'], 'XI ') ? 'XI'
                : (Str::startsWith($row['className'], 'X ') ? 'X' : 'XII'));

            if ($gradeLevel === 'XIII' && (int) $major->program_duration_years < 4) {
                $major->update(['program_duration_years' => 4]);
            }

            $classesByName[$row['className']] = SchoolClass::firstOrCreate(
                [
                    'school_id' => $school->id,
                    'name' => $row['className'],
                ],
                [
                    'major_id' => $major->id,
                    'grade_level' => $gradeLevel,
                    'academic_year' => self::ACADEMIC_YEAR,
                ]
            );
        }

        // Kumpulan NIS yang sudah ada agar siswa yang sudah di-seed/plotted tidak tertimpa
        $existingNisMap = Student::whereHas('user', fn ($q) => $q->where('school_id', $school->id))
            ->pluck('nis')
            ->flip()
            ->all();

        $defaultPassword = Hash::make('password');
        $createdCount = 0;

        foreach ($rows as $row) {
            if (isset($existingNisMap[$row['nis']])) {
                continue;
            }

            $email = 'siswa.' . $row['nis'] . '@' . self::EMAIL_DOMAIN;
            $gender = in_array(Str::upper($row['name']), self::FEMALE_STUDENTS, true) ? 'P' : 'L';
            $targetClass = $classesByName[$row['className']];

            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'school_id' => $school->id,
                    'name' => $row['name'],
                    'password' => $defaultPassword,
                    'role' => 'siswa',
                    'is_active' => true,
                ]
            );

            Profile::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'gender' => $gender,
                    'address' => 'Kabupaten Garut, Jawa Barat',
                ]
            );

            Student::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'class_id' => $targetClass->id,
                    'nis' => $row['nis'],
                    'nisn' => $row['nis'],
                    'parent_token' => Str::random(32),
                    'is_parent_portal_active' => true,
                ]
            );

            $existingNisMap[$row['nis']] = true;
            $createdCount++;
        }

        if (isset($this->command)) {
            $this->command->info("✅ [Smkn2GarutMasterStudentSeeder] Berhasil men-seed {$createdCount} siswa baru (belum terplotting) di SMKN 2 Garut.");
        }
    }
}
