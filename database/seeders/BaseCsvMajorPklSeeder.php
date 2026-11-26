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

abstract class BaseCsvMajorPklSeeder extends Seeder
{
    protected const SCHOOL_NPSN = '20209203';
    protected const EMAIL_DOMAIN = 'smkn2garut.sch.id';
    protected const ACADEMIC_YEAR = '2026/2027';

    private ?string $defaultPasswordHash = null;

    abstract protected function csvRelativePath(): string;

    abstract protected function majorCode(): string;

    abstract protected function majorName(): string;

    /**
     * @return array<int, array{code: string, title: string, element_name: string, description: string, target_hours: int, sort_order: int}>
     */
    abstract protected function learningObjectivesData(): array;

    /**
     * Katalog alias guru (prefix uppercase => metadata canonical).
     *
     * @return array<string, array{name: string, email: string, gender: string}>
     */
    protected function teacherCatalog(): array
    {
        return [];
    }

    /**
     * Tentukan gender siswa ('L' atau 'P') berdasarkan nama atau NIS.
     */
    abstract protected function resolveStudentGender(string $name, string $nis): string;

    /**
     * Alias kode jurusan yang sudah ada di database (misal TL -> LOG).
     *
     * @return array<int, string>
     */
    protected function majorCodeAliases(): array
    {
        return [];
    }

    /**
     * Apakah seluruh perusahaan pada seeder ini melewati persetujuan Pembimbing DU/DI.
     */
    protected function requiresIndustryApproval(): bool
    {
        return false;
    }

    /**
     * Jalankan seeder modular berbasis CSV untuk satu kompetensi keahlian.
     */
    public function run(): void
    {
        $csvPath = base_path($this->csvRelativePath());
        if (!is_file($csvPath)) {
            if (isset($this->command)) {
                $this->command->warn('⚠️ [' . static::class . "] Berkas CSV tidak ditemukan: {$csvPath}");
            }

            return;
        }

        $rows = $this->parseCsvRows($csvPath);
        if ($rows === []) {
            if (isset($this->command)) {
                $this->command->warn('⚠️ [' . static::class . '] Tidak ada baris data siswa di CSV.');
            }

            return;
        }

        $school = School::where('npsn', static::SCHOOL_NPSN)->first() ?? School::firstOrFail();

        $major = $this->resolveMajor($school);

        $this->seedLearningObjectives($school, $major);

        $teachersByKey = $this->syncTeachers($school, $major, $rows);
        $classesByName = $this->syncClasses($school, $major, $rows, $teachersByKey);
        $companiesByName = $this->syncCompanies($school, $major, $rows);

        $period = PklPeriod::where('school_id', $school->id)->where('is_active', true)->first();
        if (!$period) {
            $period = PklPeriod::create([
                'school_id' => $school->id,
                'name' => 'PKL ' . $this->majorName() . ' TP ' . static::ACADEMIC_YEAR,
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
            $teachersByKey
        );

        if (isset($this->command)) {
            $modeLabel = $this->requiresIndustryApproval()
                ? 'Alur DU/DI & Guru'
                : 'Pembimbing DU/DI kosong, Verifikasi Jurnal oleh Guru';

            $this->command->info('✅ [' . class_basename(static::class) . "] Berhasil men-seed data {$this->majorName()} ({$school->name}):");
            $this->command->info("   - Jurusan: {$major->name} ({$major->code})");
            $this->command->info('   - Kelas: ' . implode(', ', array_keys($classesByName)));
            $this->command->info('   - Guru Pembimbing & Wali Kelas: ' . count($teachersByKey) . ' guru');
            $this->command->info('   - Total Instansi / DUDI: ' . count($companiesByName) . " perusahaan ({$modeLabel})");
            $this->command->info("   - Total Siswa & Plotting PKL: {$studentCount} siswa aktif");
        }
    }

    /**
     * Normalisasi koordinat baik format desimal standar maupun format ribuan/titik ganda rusak dari Excel
     * (contoh: "-7,222,980" -> -7.22298, "107.550.769." -> 107.550769, "'107.90707464" -> 107.90707464).
     */
    public static function parseCoordinate(string $raw, bool $isLatitude): ?float
    {
        $trimmed = ltrim(trim(str_replace(["\xc2\xa0", "\xa0", ' '], '', $raw)), "'");
        if ($trimmed === '') {
            return null;
        }

        if (preg_match('/^-?\d+\.\d+$/', $trimmed) === 1) {
            $value = (float) $trimmed;
            $limit = $isLatitude ? 90.0 : 180.0;

            return abs($value) <= $limit ? round($value, 8) : null;
        }

        $digitsOnly = preg_replace('/(?!^-)[^0-9]/', '', $trimmed);
        if ($digitsOnly === null || $digitsOnly === '' || $digitsOnly === '-') {
            return null;
        }

        $value = ((float) $digitsOnly) / 1_000_000;

        // Pulihkan bujur Indonesia (95..141) jika nol di belakang terpotong Excel sebelum pemisahan desimal (misal: "1078696" -> 107.8696)
        if (!$isLatitude && $value > 0 && $value < 90.0) {
            $unsigned = ltrim($digitsOnly, '-');
            $intDigits = str_starts_with($unsigned, '1') ? 3 : 2;
            if (strlen($unsigned) > $intDigits) {
                $value = ((float) $digitsOnly) / (10 ** (strlen($unsigned) - $intDigits));
            }
        }

        $limit = $isLatitude ? 90.0 : 180.0;

        return abs($value) <= $limit ? round($value, 8) : null;
    }

    protected function resolveMajor(School $school): Major
    {
        $codes = array_values(array_unique(array_merge([$this->majorCode()], $this->majorCodeAliases())));
        $existing = Major::where('school_id', $school->id)->whereIn('code', $codes)->first();
        if ($existing) {
            return $existing;
        }

        return Major::create([
            'school_id' => $school->id,
            'code' => $this->majorCode(),
            'name' => $this->majorName(),
        ]);
    }

    private function seedLearningObjectives(School $school, Major $major): void
    {
        foreach ($this->learningObjectivesData() as $cp) {
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
     * @return array<int, array<string, mixed>>
     */
    private function parseCsvRows(string $filePath): array
    {
        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            return [];
        }

        fgetcsv($handle);

        $rows = [];
        while (($raw = fgetcsv($handle)) !== false) {
            $nis = $this->normalizeText((string) ($raw[0] ?? ''));
            $name = $this->normalizeText((string) ($raw[1] ?? ''));

            if ($nis === '' || $name === '') {
                continue;
            }

            $checkinStart = $this->normalizeTime((string) ($raw[12] ?? ''));
            $checkinLate = $this->normalizeTime((string) ($raw[13] ?? ''));
            $checkoutStart = $this->normalizeTime((string) ($raw[14] ?? ''));
            $startDate = $this->normalizeDate((string) ($raw[15] ?? ''));
            $endDate = $this->normalizeDate((string) ($raw[16] ?? ''));

            $rows[] = [
                'nis' => $nis,
                'name' => $name,
                'class_name' => $this->normalizeText((string) ($raw[2] ?? '')),
                'major_code' => $this->normalizeText((string) ($raw[3] ?? $this->majorCode())),
                'phone' => $this->normalizePhone((string) ($raw[4] ?? '')),
                'teacher_raw' => $this->normalizeText((string) ($raw[5] ?? '')),
                'homeroom_raw' => $this->normalizeText((string) ($raw[6] ?? '')),
                'company_name' => $this->normalizeText((string) ($raw[7] ?? '')),
                'company_address' => $this->normalizeText((string) ($raw[8] ?? '')),
                'latitude' => static::parseCoordinate((string) ($raw[9] ?? ''), true),
                'longitude' => static::parseCoordinate((string) ($raw[10] ?? ''), false),
                'radius_meters' => max(50, (int) preg_replace('/[^0-9]/', '', (string) ($raw[11] ?? '100')) ?: 100),
                'checkin_start' => $checkinStart,
                'checkin_late' => $checkinLate,
                'checkout_start' => $checkoutStart,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ];
        }

        fclose($handle);

        return $rows;
    }

    private function normalizeText(string $value): string
    {
        $cleaned = str_replace(["\xc2\xa0", "\xa0"], ' ', $value);
        $cleaned = preg_replace('/\s+/', ' ', $cleaned) ?? $cleaned;

        return trim($cleaned);
    }

    private function normalizePhone(string $raw): ?string
    {
        $digits = preg_replace('/[^0-9]/', '', ltrim(trim($raw), "'"));
        if ($digits === null || $digits === '') {
            return null;
        }

        // Pulihkan angka 0 di depan yang hilang akibat format angka Excel (misal: 8212765812 -> 08212765812)
        if (str_starts_with($digits, '8') && strlen($digits) >= 8) {
            $digits = '0' . $digits;
        }

        return $digits;
    }

    private function normalizeTime(string $raw): ?string
    {
        $trimmed = trim($raw);
        if ($trimmed === '') {
            return null;
        }

        if (preg_match('/^(\d{1,2})[:.](\d{2})(?:[:.](\d{2}))?$/', $trimmed, $m) !== 1) {
            return null;
        }

        return sprintf('%02d:%02d:%02d', (int) $m[1], (int) $m[2], (int) ($m[3] ?? 0));
    }

    private function normalizeDate(string $raw): ?string
    {
        $trimmed = trim($raw);
        if ($trimmed === '') {
            return null;
        }

        try {
            return Carbon::parse($trimmed)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return array{key: string, name: string, email: string, gender: string}
     */
    private function resolveTeacherIdentity(string $rawName): array
    {
        $normalized = $this->normalizeText($rawName);
        $upper = Str::upper($normalized);

        foreach ($this->teacherCatalog() as $prefix => $meta) {
            if (str_starts_with($upper, $prefix)) {
                return [
                    'key' => $meta['email'],
                    'name' => $meta['name'],
                    'email' => $meta['email'],
                    'gender' => $meta['gender'],
                ];
            }
        }

        $baseName = trim(explode(',', $normalized)[0]);
        $slug = Str::slug($baseName, '.');
        $email = ($slug !== '' ? $slug : 'guru.' . Str::lower($this->majorCode())) . '@' . static::EMAIL_DOMAIN;

        return [
            'key' => $email,
            'name' => $normalized,
            'email' => $email,
            'gender' => 'L',
        ];
    }

    protected function defaultPasswordHash(): string
    {
        return $this->defaultPasswordHash ??= Hash::make('password');
    }

    /**
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
                    'password' => $this->defaultPasswordHash(),
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
                    'academic_year' => static::ACADEMIC_YEAR,
                ]
            );
        }

        return $classesByName;
    }

    /**
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

            if (
                !isset($grouped[$name]['work_hours'])
                && $row['checkin_start'] !== null
                && $row['checkin_late'] !== null
                && $row['checkout_start'] !== null
            ) {
                $alfaCutoff = $row['checkin_late'] >= '11:00:00'
                    ? Carbon::createFromTimeString($row['checkin_late'])->addHour()->format('H:i:s')
                    : '11:00:00';

                $grouped[$name]['work_hours'] = [
                    'checkin_start' => $row['checkin_start'],
                    'checkin_late' => $row['checkin_late'],
                    'checkin_alfa' => $alfaCutoff,
                    'checkout_start' => $row['checkout_start'],
                ];
            }
            if (!isset($grouped[$name]['default_start_date']) && $row['start_date'] !== null) {
                $grouped[$name]['default_start_date'] = $row['start_date'];
            }
            if (!isset($grouped[$name]['default_end_date']) && $row['end_date'] !== null) {
                $grouped[$name]['default_end_date'] = $row['end_date'];
            }
        }

        $companiesByName = [];
        foreach ($grouped as $name => $info) {
            $selectedCoord = null;
            if (!empty($info['coords'])) {
                $maxCount = -1;
                foreach ($info['coords'] as $candidate) {
                    if ($candidate['count'] > $maxCount) {
                        $maxCount = $candidate['count'];
                        $selectedCoord = $candidate;
                    }
                }
            }

            $radiusMeters = !empty($info['radii']) ? max($info['radii']) : 100;
            $customWorkHours = $info['work_hours'] ?? null;

            $companyAttributes = [
                'address' => $info['address'] !== '' ? $info['address'] : 'Kabupaten Garut, Jawa Barat',
                'contact_person' => 'Pihak Instansi / DU/DI',
                'phone' => '-',
                'gps_enabled' => $selectedCoord !== null,
                'latitude' => $selectedCoord['lat'] ?? null,
                'longitude' => $selectedCoord['lng'] ?? null,
                'radius_meters' => $radiusMeters,
                'override_hours' => $customWorkHours !== null,
                'custom_work_hours' => $customWorkHours,
                'work_days' => '5_days',
                'requires_industry_approval' => $this->requiresIndustryApproval(),
            ];

            if (!empty($info['default_start_date'])) {
                $companyAttributes['default_start_date'] = $info['default_start_date'];
            }
            if (!empty($info['default_end_date'])) {
                $companyAttributes['default_end_date'] = $info['default_end_date'];
            }

            $company = Company::updateOrCreate(
                [
                    'school_id' => $school->id,
                    'name' => $name,
                ],
                $companyAttributes
            );

            $company->majors()->syncWithoutDetaching([$major->id]);
            $companiesByName[$name] = $company;
        }

        return $companiesByName;
    }

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

            $email = "siswa.{$nis}@" . static::EMAIL_DOMAIN;
            $gender = $this->resolveStudentGender($row['name'], $nis);

            $studentUser = User::updateOrCreate(
                ['email' => $email],
                [
                    'school_id' => $school->id,
                    'name' => $row['name'],
                    'password' => $this->defaultPasswordHash(),
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

            $spklAttributes = [
                'school_id' => $school->id,
                'company_id' => $company->id,
                'teacher_id' => $teacher->id,
                'supervisor_id' => null,
                'status' => 'active',
            ];
            if ($row['start_date'] !== null) {
                $spklAttributes['start_date'] = $row['start_date'];
            }
            if ($row['end_date'] !== null) {
                $spklAttributes['end_date'] = $row['end_date'];
            }

            StudentPkl::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'pkl_period_id' => $period->id,
                ],
                $spklAttributes
            );

            $count++;
        }

        return $count;
    }
}
