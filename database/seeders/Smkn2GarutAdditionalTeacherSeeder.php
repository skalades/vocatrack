<?php

namespace Database\Seeders;

use App\Models\Profile;
use App\Models\School;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class Smkn2GarutAdditionalTeacherSeeder extends Seeder
{
    private const SCHOOL_NPSN = '20209203';

    /**
     * Seed daftar guru tambahan SMKN 2 Garut (kecuali Herdi, Cahya, Danang, Adbel, Yandi, Abdul Hakim).
     *
     * Idempoten: jika email atau nama guru sudah ada di SMKN 2 Garut, tidak membuat duplikat.
     *
     * Pemakaian:
     *   php artisan db:seed --class=Smkn2GarutAdditionalTeacherSeeder
     */
    public function run(): void
    {
        $school = School::where('npsn', self::SCHOOL_NPSN)->first();

        if (!$school) {
            $this->command?->warn('SMKN 2 Garut (NPSN 20209203) belum ditemukan. Seeder dilewati.');

            return;
        }

        $passwordHash = Hash::make('password');
        $createdCount = 0;
        $existingCount = 0;

        foreach ($this->teachersData() as $item) {
            $user = $this->findExistingTeacherUser($school->id, $item['email'], $item['base_name']);

            if ($user) {
                $existingCount++;
            } else {
                $user = User::create([
                    'school_id' => $school->id,
                    'name' => $item['name'],
                    'email' => $item['email'],
                    'password' => $passwordHash,
                    'role' => 'guru-pembimbing',
                    'is_active' => true,
                ]);
                $createdCount++;
            }

            Profile::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'gender' => $item['gender'],
                    'address' => 'Garut, Jawa Barat',
                ]
            );

            Teacher::firstOrCreate(
                ['user_id' => $user->id],
                ['nip' => null]
            );
        }

        $this->command?->info(
            "Smkn2GarutAdditionalTeacherSeeder selesai ({$school->name}): {$createdCount} guru baru dibuat, {$existingCount} guru sudah ada sebelumnya."
        );
    }

    private function findExistingTeacherUser(int $schoolId, string $email, string $baseName): ?User
    {
        $byEmail = User::where('email', $email)->first();
        if ($byEmail) {
            return $byEmail;
        }

        $normalizedTarget = Str::lower(trim($baseName));

        return User::where('school_id', $schoolId)
            ->where('role', 'guru-pembimbing')
            ->get()
            ->first(function (User $u) use ($normalizedTarget) {
                $existingBase = Str::lower(trim(explode(',', $u->name)[0]));
                $existingBase = preg_replace('/^(hj\.?|h\.?|drs\.?|dra\.?|ir\.?|dr\.?|apt\.?)\s+/i', '', $existingBase) ?? $existingBase;
                $targetClean = preg_replace('/^(hj\.?|h\.?|drs\.?|dra\.?|ir\.?|dr\.?|apt\.?)\s+/i', '', $normalizedTarget) ?? $normalizedTarget;

                return trim($existingBase) === trim($targetClean);
            });
    }

    /**
     * @return array<int, array{name: string, base_name: string, email: string, gender: string}>
     */
    private function teachersData(): array
    {
        return [
            [
                'name' => 'Nisa Dwi Nuranti, S.Pd.',
                'base_name' => 'Nisa Dwi Nuranti',
                'email' => 'nisa.dwi.nuranti@smkn2garut.sch.id',
                'gender' => 'P',
            ],
            [
                'name' => 'Erika Silviani, S.T.',
                'base_name' => 'Erika Silviani',
                'email' => 'erika.silviani@smkn2garut.sch.id',
                'gender' => 'P',
            ],
            [
                'name' => 'Yayu Sri Oktaviani, S.Pd.',
                'base_name' => 'Yayu Sri Oktaviani',
                'email' => 'yayu.sri.oktaviani@smkn2garut.sch.id',
                'gender' => 'P',
            ],
            [
                'name' => 'Ema Rosilawati Nasution, S.Pd.',
                'base_name' => 'Ema Rosilawati Nasution',
                'email' => 'ema.rosilawati.nasution@smkn2garut.sch.id',
                'gender' => 'P',
            ],
            [
                'name' => 'Anggara Agung Budiman, S.Pd.',
                'base_name' => 'Anggara Agung Budiman',
                'email' => 'anggara.agung.budiman@smkn2garut.sch.id',
                'gender' => 'L',
            ],
            [
                'name' => 'Siti Budiarti, S.H.',
                'base_name' => 'Siti Budiarti',
                'email' => 'siti.budiarti@smkn2garut.sch.id',
                'gender' => 'P',
            ],
            [
                'name' => 'Neni Setiani, S.Pd.',
                'base_name' => 'Neni Setiani',
                'email' => 'neni.setiani@smkn2garut.sch.id',
                'gender' => 'P',
            ],
            [
                'name' => 'Mustofa, M.Pd.',
                'base_name' => 'Mustofa',
                'email' => 'mustofa@smkn2garut.sch.id',
                'gender' => 'L',
            ],
            [
                'name' => 'Aap Solehudin, S.Pd.',
                'base_name' => 'Aap Solehudin',
                'email' => 'aap.solehudin@smkn2garut.sch.id',
                'gender' => 'L',
            ],
            [
                'name' => 'Hj. Aida Maryani S, S.Pd., M.Pd.',
                'base_name' => 'Hj. Aida Maryani S',
                'email' => 'aida.maryani@smkn2garut.sch.id',
                'gender' => 'P',
            ],
            [
                'name' => 'Rena Silvana, S.E.',
                'base_name' => 'Rena Silvana',
                'email' => 'rena.silvana@smkn2garut.sch.id',
                'gender' => 'P',
            ],
            [
                'name' => 'Dedeh Kurniasih, S.Pd., M.Pd.',
                'base_name' => 'Dedeh Kurniasih',
                'email' => 'dedeh.kurniasih@smkn2garut.sch.id',
                'gender' => 'P',
            ],
            [
                'name' => 'Rina Sabariah, S.E.',
                'base_name' => 'Rina Sabariah',
                'email' => 'rina.sabariah@smkn2garut.sch.id',
                'gender' => 'P',
            ],
            [
                'name' => 'Garnita Rizky Amelia S, S.Pd.',
                'base_name' => 'Garnita Rizky Amelia S',
                'email' => 'garnita.rizky@smkn2garut.sch.id',
                'gender' => 'P',
            ],
            [
                'name' => 'Rima Nurul Fajriyati, S.Pd.',
                'base_name' => 'Rima Nurul Fajriyati',
                'email' => 'rima.nurul@smkn2garut.sch.id',
                'gender' => 'P',
            ],
            [
                'name' => 'Rusli Hamzah, S.Pd.I.',
                'base_name' => 'Rusli Hamzah',
                'email' => 'rusli.hamzah@smkn2garut.sch.id',
                'gender' => 'L',
            ],
            [
                'name' => 'Aan Anggraeni, S.Pd.',
                'base_name' => 'Aan Anggraeni',
                'email' => 'aan.anggraeni@smkn2garut.sch.id',
                'gender' => 'P',
            ],
            [
                'name' => 'Lia Ratnasari, S.E.',
                'base_name' => 'Lia Ratnasari',
                'email' => 'lia.ratnasari@smkn2garut.sch.id',
                'gender' => 'P',
            ],
            [
                'name' => 'Dita Julia Sagita, S.Pd.',
                'base_name' => 'Dita Julia Sagita',
                'email' => 'dita.julia@smkn2garut.sch.id',
                'gender' => 'P',
            ],
        ];
    }
}
