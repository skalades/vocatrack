<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\LearningObjective;
use App\Models\Major;
use App\Models\PklPeriod;
use App\Models\Profile;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentPkl;
use App\Models\Teacher;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TeknikAudioVideoPklSeeder extends Seeder
{
    private const CSV_RELATIVE_PATH = 'SSEDER/Teknik Audio Video.csv';
    private const SCHOOL_NPSN = '20209203';
    private const EMAIL_DOMAIN = 'smkn2garut.sch.id';
    private const ACADEMIC_YEAR = '2026/2027';

    /**
     * Alias nama guru pada kolom Guru Pembimbing vs Wali Kelas agar tidak terjadi duplikasi akun.
     */
    private const TEACHER_CATALOG = [
        'AGUS SETIYARSO' => [
            'name' => 'Agus Setiyarso, S.T.',
            'email' => 'agus.setiyarso@smkn2garut.sch.id',
            'gender' => 'L',
        ],
        'MARGO SUSILO' => [
            'name' => 'Margo Susilo Widiarto, S.ST',
            'email' => 'margo.susilo@smkn2garut.sch.id',
            'gender' => 'L',
        ],
        'ZAMZAM' => [
            'name' => 'Zamzam, S.Pd., M.Pd',
            'email' => 'zamzam@smkn2garut.sch.id',
            'gender' => 'L',
        ],
        'SRI RAHAYU NINGSIH' => [
            'name' => 'Sri Rahayu Ningsih, S.Pd',
            'email' => 'sri.rahayu@smkn2garut.sch.id',
            'gender' => 'P',
        ],
        'RAHMAT' => [
            'name' => 'Rahmat, S.Pd',
            'email' => 'rahmat@smkn2garut.sch.id',
            'gender' => 'L',
        ],
        'CECEP SUPRIATNA' => [
            'name' => 'Cecep Supriatna, S.T.',
            'email' => 'cecep.supriatna@smkn2garut.sch.id',
            'gender' => 'L',
        ],
        'NISA NURFITRIANI' => [
            'name' => 'Nisa Nurfitriani, S.T.',
            'email' => 'nisa.nurfitriani@smkn2garut.sch.id',
            'gender' => 'P',
        ],
    ];

    private const FEMALE_STUDENTS = [
        'CAMEILA ZAHWA ZIVANKA',
        'REVINA ANANDA SYABILA',
        'NESA KUSMAYANTI',
        'PUTRI NIRWANDI',
        'SAIRA NAILA ALIFAH',
        'SYABI ISNAENI',
    ];

    /**
     * Jalankan seeder modular Penempatan PKL Jurusan Teknik Audio Video (TAV) dari berkas CSV.
     * - Pembimbing DU/DI dikosongkan (`supervisor_id = null`, tanpa akun pembimbing-dudi)
     * - Verifikasi jurnal langsung oleh Guru Pembimbing (`requires_industry_approval = false`)
     * - Koordinat yang rusak akibat pemformatan Excel otomatis dinormalisasi ke derajat desimal.
     */
    public function run(): void
    {
        $csvPath = base_path(self::CSV_RELATIVE_PATH);
        if (!is_file($csvPath)) {
            if (isset($this->command)) {
                $this->command->warn("⚠️ [TeknikAudioVideoPklSeeder] Berkas CSV tidak ditemukan: {$csvPath}");
            }
            return;
        }

        $rows = $this->parseCsvRows($csvPath);
        if ($rows === []) {
            if (isset($this->command)) {
                $this->command->warn('⚠️ [TeknikAudioVideoPklSeeder] Tidak ada baris data siswa di CSV.');
            }
            return;
        }

        $school = School::where('npsn', self::SCHOOL_NPSN)->first() ?? School::firstOrFail();

        $majorTav = Major::firstOrCreate(
            [
                'school_id' => $school->id,
                'code' => 'TAV',
            ],
            [
                'name' => 'Teknik Audio Video',
            ]
        );

        $this->seedLearningObjectives($school, $majorTav);

        $teachersByCanonical = $this->syncTeachers($school, $majorTav, $rows);
        $classesByName = $this->syncClasses($school, $majorTav, $rows, $teachersByCanonical);
        $companiesByName = $this->syncCompanies($school, $majorTav, $rows);

        $period = PklPeriod::where('school_id', $school->id)->where('is_active', true)->first();
        if (!$period) {
            $period = PklPeriod::create([
                'school_id' => $school->id,
                'name' => 'PKL Teknik Audio Video TP ' . self::ACADEMIC_YEAR,
                'start_date' => Carbon::now()->subMonth()->startOfMonth()->toDateString(),
                'end_date' => Carbon::now()->addMonths(4)->endOfMonth()->toDateString(),
                'is_active' => true,
            ]);
        }

        $studentCount = $this->syncStudentsAndPlotting(
            $school,
            $period,
            $rows,
            $classesByName,
            $companiesByName,
            $teachersByCanonical
        );

        if (isset($this->command)) {
            $this->command->info('✅ [TeknikAudioVideoPklSeeder] Berhasil men-seed data Teknik Audio Video SMKN 2 Garut:');
            $this->command->info("   - Jurusan: {$majorTav->name} ({$majorTav->code})");
            $this->command->info('   - Kelas: ' . implode(', ', array_keys($classesByName)));
            $this->command->info('   - Guru Pembimbing & Wali Kelas: ' . count($teachersByCanonical) . ' guru');
            $this->command->info('   - Total Instansi / DUDI: ' . count($companiesByName) . ' perusahaan (Pembimbing DU/DI kosong, Verifikasi Jurnal oleh Guru)');
            $this->command->info("   - Total Siswa & Plotting PKL: {$studentCount} siswa aktif");
        }
    }

    /**
     * Seed Capaian Pembelajaran (CP) Kurikulum Merdeka Jurusan Teknik Audio Video.
     */
    private function seedLearningObjectives(School $school, Major $major): void
    {
        $objectives = [
            [
                'code' => 'TAV-CP01',
                'title' => 'Penerapan K3LH & Keselamatan Kerja Elektronika Audio Video',
                'element_name' => 'K3LH & Budaya Kerja Industri',
                'description' => 'Menerapkan standar keselamatan kerja kelistrikan, proteksi ESD, penggunaan alat ukur elektronik, dan tata tertib bengkel industri.',
                'target_hours' => 60,
                'sort_order' => 1,
            ],
            [
                'code' => 'TAV-CP02',
                'title' => 'Instalasi, Perawatan & Perbaikan Perangkat Elektronika Audio',
                'element_name' => 'Sistem Audio & Tata Suara',
                'description' => 'Merakit, menginstalasi, dan memperbaiki sistem amplifier, mixer audio, car audio, serta perangkat tata suara profesional.',
                'target_hours' => 140,
                'sort_order' => 2,
            ],
            [
                'code' => 'TAV-CP03',
                'title' => 'Perbaikan & Pemeliharaan Perangkat Video, Televisi & Display Digital',
                'element_name' => 'Sistem Video & Televisi',
                'description' => 'Mendiagnosis kerusakan rangkaian power supply, panel display, dan modul pemrosesan sinyal pada televisi maupun perangkat video.',
                'target_hours' => 140,
                'sort_order' => 3,
            ],
            [
                'code' => 'TAV-CP04',
                'title' => 'Instalasi Sistem CCTV, Jaringan Akses & Otomasi Elektronika',
                'element_name' => 'Instalasi Sistem Terintegrasi',
                'description' => 'Melakukan penarikan kabel, konfigurasi perangkat keamanan CCTV/akses kontrol, dan integrasi perangkat elektronika terapan.',
                'target_hours' => 120,
                'sort_order' => 4,
            ],
            [
                'code' => 'TAV-CP05',
                'title' => 'Layanan Pelanggan, Estimasi Perbaikan & Dokumentasi Teknis',
                'element_name' => 'Layanan & Administrasi Teknik',
                'description' => 'Melakukan pemeriksaan unit masuk, penyusunan laporan diagnosa kerusakan, pengujian akhir (QC), dan serah terima ke pelanggan.',
                'target_hours' => 100,
                'sort_order' => 5,
            ],
        ];

        foreach ($objectives as $cp) {
            LearningObjective::updateOrCreate(
                [
                    'school_id' => $school->id,
                    'major_id' => $major->id,
                    'code' => $cp['code'],
                ],
                [
                    'title' => $cp['title'],
                    'element_name' => $cp['element_name'],
                    'description' => $cp['description'],
                    'target_hours' => $cp['target_hours'],
                    'sort_order' => $cp['sort_order'],
                    'is_active' => true,
                ]
            );
        }
    }

    /**
     * Baca dan bersihkan seluruh baris data siswa dari CSV.
     *
     * @return array<int, array<string, mixed>>
     */
    private function parseCsvRows(string $filePath): array
    {
        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            return [];
        }

        // Lewati baris header
        fgetcsv($handle);

        $rows = [];
        while (($raw = fgetcsv($handle)) !== false) {
            $nis = $this->normalizeText((string) ($raw[0] ?? ''));
            $name = $this->normalizeText((string) ($raw[1] ?? ''));

            if ($nis === '' || $name === '') {
                continue;
            }

            $rows[] = [
                'nis' => $nis,
                'name' => $name,
                'class_name' => $this->normalizeText((string) ($raw[2] ?? '')),
                'major_code' => $this->normalizeText((string) ($raw[3] ?? 'TAV')),
                'phone' => $this->normalizePhone((string) ($raw[4] ?? '')),
                'teacher_raw' => $this->normalizeText((string) ($raw[5] ?? '')),
                'homeroom_raw' => $this->normalizeText((string) ($raw[6] ?? '')),
                'company_name' => $this->normalizeText((string) ($raw[7] ?? '')),
                'company_address' => $this->normalizeText((string) ($raw[8] ?? '')),
                'latitude' => $this->parseCoordinate((string) ($raw[9] ?? ''), true),
                'longitude' => $this->parseCoordinate((string) ($raw[10] ?? ''), false),
                'radius_meters' => max(50, (int) preg_replace('/[^0-9]/', '', (string) ($raw[11] ?? '100')) ?: 100),
            ];
        }

        fclose($handle);

        return $rows;
    }

    /**
     * Normalisasi koordinat yang rusak oleh pemformatan ribuan/titik ganda di Excel
     * (contoh: "-7,222,980" -> -7.22298, "107.550.769." -> 107.550769).
     */
    public static function parseCoordinate(string $raw, bool $isLatitude): ?float
    {
        $trimmed = trim(str_replace(["\xc2\xa0", "\xa0", ' '], '', $raw));
        if ($trimmed === '') {
            return null;
        }

        // Jika sudah berbentuk angka desimal normal (satu titik, tanpa koma, tanpa titik di akhir)
        if (preg_match('/^-?\d+\.\d+$/', $trimmed) === 1) {
            $value = (float) $trimmed;
            $limit = $isLatitude ? 90.0 : 180.0;

            return abs($value) <= $limit ? round($value, 8) : null;
        }

        // Bersihkan seluruh pemisah ribuan (koma / titik berlebih / titik di akhir) dengan mempertahankan tanda minus awal
        $digitsOnly = preg_replace('/(?!^-)[^0-9]/', '', $trimmed);
        if ($digitsOnly === null || $digitsOnly === '' || $digitsOnly === '-') {
            return null;
        }

        $value = ((float) $digitsOnly) / 1_000_000;
        $limit = $isLatitude ? 90.0 : 180.0;

        if (abs($value) > $limit) {
            return null;
        }

        return round($value, 8);
    }

    /**
     * Bersihkan spasi berlebih dan non-breaking space (0xA0 / UTF-8 NBSP) dari hasil ekspor Excel/Windows.
     */
    private function normalizeText(string $value): string
    {
        $cleaned = str_replace(["\xc2\xa0", "\xa0"], ' ', $value);
        $cleaned = preg_replace('/\s+/', ' ', $cleaned) ?? $cleaned;

        return trim($cleaned);
    }

    /**
     * Bersihkan nomor WA murid (menghapus tanda petik awal Excel seperti '0812...).
     */
    private function normalizePhone(string $raw): ?string
    {
        $digits = preg_replace('/[^0-9]/', '', ltrim(trim($raw), "'"));

        return ($digits !== null && $digits !== '') ? $digits : null;
    }

    /**
     * Normalisasi identitas guru berdasarkan kata kunci nama agar alias nama guru & wali kelas menyatu.
     *
     * @return array{key: string, name: string, email: string, gender: string}
     */
    private function resolveTeacherIdentity(string $rawName): array
    {
        $upper = Str::upper($this->normalizeText($rawName));

        foreach (self::TEACHER_CATALOG as $prefix => $meta) {
            if (str_starts_with($upper, $prefix)) {
                return [
                    'key' => $prefix,
                    'name' => $meta['name'],
                    'email' => $meta['email'],
                    'gender' => $meta['gender'],
                ];
            }
        }

        $baseName = trim(explode(',', $rawName)[0]);
        $slug = Str::slug($baseName, '.');

        return [
            'key' => $upper,
            'name' => $this->normalizeText($rawName),
            'email' => ($slug !== '' ? $slug : 'guru.tav') . '@' . self::EMAIL_DOMAIN,
            'gender' => 'L',
        ];
    }

    /**
     * Buat atau perbarui seluruh Guru Pembimbing dan Wali Kelas jurusan TAV.
     *
     * @return array<string, Teacher>
     */
    private function syncTeachers(School $school, Major $major, array $rows): array
    {
        $uniqueIdentities = [];
        foreach ($rows as $row) {
            foreach (['teacher_raw', 'homeroom_raw'] as $col) {
                if ($row[$col] !== '') {
                    $identity = $this->resolveTeacherIdentity($row[$col]);
                    $uniqueIdentities[$identity['key']] = $identity;
                }
            }
        }

        $teachersByKey = [];
        foreach ($uniqueIdentities as $key => $identity) {
            $user = User::updateOrCreate(
                ['email' => $identity['email']],
                [
                    'school_id' => $school->id,
                    'name' => $identity['name'],
                    'password' => Hash::make('password'),
                    'role' => 'guru-pembimbing',
                    'is_active' => true,
                ]
            );

            Profile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'gender' => $identity['gender'],
                    'address' => 'Garut, Jawa Barat',
                ]
            );

            $teacher = Teacher::firstOrCreate(
                ['user_id' => $user->id],
                ['nip' => null]
            );

            $teacher->majors()->syncWithoutDetaching([$major->id]);
            $teachersByKey[$key] = $teacher;
        }

        return $teachersByKey;
    }

    /**
     * Buat atau perbarui kelas XI/XII TAV beserta penetapan Wali Kelas (`homeroom_teacher_id`).
     *
     * @return array<string, SchoolClass>
     */
    private function syncClasses(School $school, Major $major, array $rows, array $teachersByKey): array
    {
        $classMeta = [];
        foreach ($rows as $row) {
            $className = $row['class_name'];
            if ($className === '') {
                continue;
            }

            if (!isset($classMeta[$className]) && $row['homeroom_raw'] !== '') {
                $homeroomKey = $this->resolveTeacherIdentity($row['homeroom_raw'])['key'];
                $classMeta[$className] = $teachersByKey[$homeroomKey] ?? null;
            } elseif (!isset($classMeta[$className])) {
                $classMeta[$className] = null;
            }
        }

        $classesByName = [];
        foreach ($classMeta as $className => $homeroomTeacher) {
            $gradeLevel = Str::startsWith($className, 'XIII') ? 'XIII'
                : (Str::startsWith($className, 'XI ') ? 'XI'
                : (Str::startsWith($className, 'X ') ? 'X' : 'XII'));

            $classesByName[$className] = SchoolClass::updateOrCreate(
                [
                    'school_id' => $school->id,
                    'name' => $className,
                ],
                [
                    'major_id' => $major->id,
                    'homeroom_teacher_id' => $homeroomTeacher?->id,
                    'grade_level' => $gradeLevel,
                    'academic_year' => self::ACADEMIC_YEAR,
                ]
            );
        }

        return $classesByName;
    }

    /**
     * Buat atau perbarui seluruh DU/DI dari CSV:
     * - Tanpa pembimbing DU/DI (`Supervisor` tidak dibuat)
     * - Verifikasi jurnal langsung oleh Guru Pembimbing (`requires_industry_approval = false`)
     * - Koordinat dipilih berdasarkan frekuensi terbanyak (mode) untuk menghindari salah salin 1 baris,
     *   dan radius diambil nilai terbesar untuk mencegah penolakan presensi siswa.
     *
     * @return array<string, Company>
     */
    private function syncCompanies(School $school, Major $major, array $rows): array
    {
        $grouped = [];
        foreach ($rows as $row) {
            $name = $row['company_name'];
            if ($name === '') {
                continue;
            }

            $grouped[$name]['address'] ??= $row['company_address'];
            $grouped[$name]['radii'][] = $row['radius_meters'];

            if ($row['latitude'] !== null && $row['longitude'] !== null) {
                $coordKey = sprintf('%.6f,%.6f', $row['latitude'], $row['longitude']);
                $grouped[$name]['coords'][$coordKey] = [
                    'count' => ($grouped[$name]['coords'][$coordKey]['count'] ?? 0) + 1,
                    'lat' => $row['latitude'],
                    'lng' => $row['longitude'],
                ];
            }
        }

        $companiesByName = [];
        foreach ($grouped as $name => $info) {
            $selectedCoord = null;
            if (!empty($info['coords'])) {
                // Pilih koordinat dengan kemunculan terbanyak (mempertahankan urutan pertama jika seri)
                $maxCount = -1;
                foreach ($info['coords'] as $candidate) {
                    if ($candidate['count'] > $maxCount) {
                        $maxCount = $candidate['count'];
                        $selectedCoord = $candidate;
                    }
                }
            }

            $radiusMeters = !empty($info['radii']) ? max($info['radii']) : 100;

            $company = Company::updateOrCreate(
                [
                    'school_id' => $school->id,
                    'name' => $name,
                ],
                [
                    'address' => $info['address'] !== '' ? $info['address'] : 'Kabupaten Garut, Jawa Barat',
                    'contact_person' => 'Pihak Instansi / DU/DI',
                    'phone' => '-',
                    'gps_enabled' => $selectedCoord !== null,
                    'latitude' => $selectedCoord['lat'] ?? null,
                    'longitude' => $selectedCoord['lng'] ?? null,
                    'radius_meters' => $radiusMeters,
                    'override_hours' => false,
                    'work_days' => '5_days',
                    'requires_industry_approval' => false,
                ]
            );

            $company->majors()->syncWithoutDetaching([$major->id]);
            $companiesByName[$name] = $company;
        }

        return $companiesByName;
    }

    /**
     * Buat atau perbarui akun siswa, profil, rekord `Student`, dan plotting `StudentPkl`
     * dengan `supervisor_id = null` (Pembimbing DU/DI dikosongkan).
     */
    private function syncStudentsAndPlotting(
        School $school,
        PklPeriod $period,
        array $rows,
        array $classesByName,
        array $companiesByName,
        array $teachersByKey
    ): int {
        $count = 0;

        foreach ($rows as $row) {
            $nis = $row['nis'];
            $targetClass = $classesByName[$row['class_name']] ?? null;
            $company = $companiesByName[$row['company_name']] ?? null;
            $teacherKey = $this->resolveTeacherIdentity($row['teacher_raw'])['key'];
            $teacher = $teachersByKey[$teacherKey] ?? null;

            if (!$targetClass || !$company || !$teacher) {
                continue;
            }

            $email = "siswa.{$nis}@" . self::EMAIL_DOMAIN;
            $gender = in_array(Str::upper($row['name']), self::FEMALE_STUDENTS, true) ? 'P' : 'L';

            $studentUser = User::updateOrCreate(
                ['email' => $email],
                [
                    'school_id' => $school->id,
                    'name' => $row['name'],
                    'password' => Hash::make('password'),
                    'role' => 'siswa',
                    'is_active' => true,
                ]
            );

            Profile::updateOrCreate(
                ['user_id' => $studentUser->id],
                [
                    'gender' => $gender,
                    'phone' => $row['phone'],
                    'address' => 'Kabupaten Garut, Jawa Barat',
                ]
            );

            $student = Student::where('user_id', $studentUser->id)->first();
            if ($student) {
                $student->update([
                    'class_id' => $targetClass->id,
                    'nis' => $nis,
                    'is_parent_portal_active' => true,
                ]);
            } else {
                $student = Student::create([
                    'user_id' => $studentUser->id,
                    'class_id' => $targetClass->id,
                    'nis' => $nis,
                    'nisn' => $nis,
                    'parent_token' => Str::random(32),
                    'is_parent_portal_active' => true,
                ]);
            }

            StudentPkl::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'pkl_period_id' => $period->id,
                ],
                [
                    'school_id' => $school->id,
                    'company_id' => $company->id,
                    'teacher_id' => $teacher->id,
                    'supervisor_id' => null,
                    'status' => 'active',
                ]
            );

            $count++;
        }

        return $count;
    }
}
