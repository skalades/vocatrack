<?php

namespace Database\Seeders;

use Illuminate\Support\Str;

class TeknikKendaraanRinganPklSeeder extends BaseCsvMajorPklSeeder
{
    private const FEMALE_STUDENTS = [
        'MELANY NURSALSABILAH',
    ];

    protected function csvRelativePath(): string
    {
        return 'SSEDER/Teknik Kendaraan Ringan Otomotif.csv';
    }

    protected function majorCode(): string
    {
        return 'TKR';
    }

    protected function majorName(): string
    {
        return 'Teknik Kendaraan Ringan Otomotif';
    }

    protected function teacherCatalog(): array
    {
        return [
            'DRA. HJ. ATIN PRIATIN' => [
                'name' => 'Dra. Hj. Atin Priatin Nurhaeni',
                'email' => 'atin.priatin@smkn2garut.sch.id',
                'gender' => 'P',
            ],
            'DRA. ATIN PRIATIN' => [
                'name' => 'Dra. Hj. Atin Priatin Nurhaeni',
                'email' => 'atin.priatin@smkn2garut.sch.id',
                'gender' => 'P',
            ],
            'ALI AJI SANTOSO' => [
                'name' => 'Ali Aji Santoso, S.Pd',
                'email' => 'ali.aji@smkn2garut.sch.id',
                'gender' => 'L',
            ],
            'SANTI SUSANTI' => [
                'name' => 'Santi Susanti, S.Pd., M.Si',
                'email' => 'santi.susanti@smkn2garut.sch.id',
                'gender' => 'P',
            ],
            'H. ASEP HERMAWAN' => [
                'name' => 'H. Asep Hermawan, S.Pd., M.Pd.',
                'email' => 'asep.hermawan@smkn2garut.sch.id',
                'gender' => 'L',
            ],
            'ARIS BUDI PRASETYO' => [
                'name' => 'Aris Budi Prasetyo, S.Pd.',
                'email' => 'aris.budi@smkn2garut.sch.id',
                'gender' => 'L',
            ],
            'RUSTANDI' => [
                'name' => 'Rustandi, S.Pd.',
                'email' => 'rustandi@smkn2garut.sch.id',
                'gender' => 'L',
            ],
            'HENHEN SUHENDAR' => [
                'name' => 'Henhen Suhendar, S.Pd.',
                'email' => 'henhen.suhendar@smkn2garut.sch.id',
                'gender' => 'L',
            ],
            'RINA SABARIAH' => [
                'name' => 'Rina Sabariah, S.E.',
                'email' => 'rina.sabariah@smkn2garut.sch.id',
                'gender' => 'P',
            ],
            'KHARISMA APRILIA' => [
                'name' => 'Kharisma Aprilia P., S.T.',
                'email' => 'kharisma.aprilia@smkn2garut.sch.id',
                'gender' => 'P',
            ],
            'HJ. TIA SETIAWATI' => [
                'name' => 'Hj. Tia Setiawati, S.Ag.',
                'email' => 'tia.setiawati@smkn2garut.sch.id',
                'gender' => 'P',
            ],
            'AAP SOLEHUDIN' => [
                'name' => 'Aap Solehudin, S.Pd.',
                'email' => 'aap.solehudin@smkn2garut.sch.id',
                'gender' => 'L',
            ],
            'ENENG SYAMSIAH' => [
                'name' => 'Eneng Syamsiah, S.Pd.',
                'email' => 'eneng.syamsiah@smkn2garut.sch.id',
                'gender' => 'P',
            ],
            'DR. ENUNG MULYATI' => [
                'name' => 'Dr. Enung Mulyati, S.Pd., M.Pd.',
                'email' => 'enung.mulyati@smkn2garut.sch.id',
                'gender' => 'P',
            ],
            'ZULFA FADZLU HUDIYA' => [
                'name' => 'Zulfa Fadzlu Hudiya, S.Pd.',
                'email' => 'zulfa.fadzlu@smkn2garut.sch.id',
                'gender' => 'L',
            ],
            'LIA RATNASARI' => [
                'name' => 'Lia Ratnasari, S.E.',
                'email' => 'lia.ratnasari@smkn2garut.sch.id',
                'gender' => 'P',
            ],
            'INA MARLINA PANTISA' => [
                'name' => 'Ina Marlina Pantisa, S.Pd.',
                'email' => 'ina.marlina@smkn2garut.sch.id',
                'gender' => 'P',
            ],
            'RENA SILVANA' => [
                'name' => 'Rena Silvana, S.E.',
                'email' => 'rena.silvana@smkn2garut.sch.id',
                'gender' => 'P',
            ],
            'DESY DAMAYANTY' => [
                'name' => 'Desy Damayanty, S.Pd.',
                'email' => 'desy.damayanty@smkn2garut.sch.id',
                'gender' => 'P',
            ],
        ];
    }

    protected function resolveStudentGender(string $name, string $nis): string
    {
        return in_array(Str::upper($name), self::FEMALE_STUDENTS, true) ? 'P' : 'L';
    }

    protected function learningObjectivesData(): array
    {
        return [
            [
                'code' => 'TKR-CP01',
                'title' => 'Penerapan K3LH, Peralatan Bengkel & Budaya Kerja Industri Otomotif',
                'element_name' => 'K3LH & Budaya Kerja Bengkel',
                'description' => 'Menerapkan keselamatan kerja bengkel otomotif, penggunaan APD, penanganan limbah B3/oli, serta tata tertib kerja sesuai SOP bengkel resmi.',
                'target_hours' => 60,
                'sort_order' => 1,
            ],
            [
                'code' => 'TKR-CP02',
                'title' => 'Perawatan Berkala & Servis Mesin Kendaraan Ringan',
                'element_name' => 'Perawatan Mesin & Tune Up',
                'description' => 'Melaksanakan perawatan berkala mesin bensin maupun diesel, penggantian pelumas/filter, penyetelan sistem bahan bakar EFI, serta sistem pendinginan.',
                'target_hours' => 140,
                'sort_order' => 2,
            ],
            [
                'code' => 'TKR-CP03',
                'title' => 'Pemeliharaan & Perbaikan Sasis, Suspensi, Kemudi dan Rem',
                'element_name' => 'Sasis & Pemindah Tenaga',
                'description' => 'Memeriksa dan memperbaiki sistem kopling, transmisi manual/otomatis, sistem rem ABS/konvensional, suspensi, kemudi, dan spooring-balancing.',
                'target_hours' => 140,
                'sort_order' => 3,
            ],
            [
                'code' => 'TKR-CP04',
                'title' => 'Diagnosa & Perbaikan Kelistrikan Bodi, AC serta Sistem Pengapian/Starter',
                'element_name' => 'Kelistrikan & AC Kendaraan Ringan',
                'description' => 'Melakukan pemeriksaan baterai, sistem pengisian, motor starter, kelistrikan penerangan, sistem AC mobil, serta pembacaan diagnostic scanner.',
                'target_hours' => 120,
                'sort_order' => 4,
            ],
            [
                'code' => 'TKR-CP05',
                'title' => 'Quality Control, Komunikasi Layanan Bengkel & Administrasi Pekerjaan',
                'element_name' => 'Layanan & Administrasi Bengkel',
                'description' => 'Mengisi work order, memeriksa hasil uji jalan/quality control pasca-servis, serta menyusun laporan pemeliharaan harian secara akurat.',
                'target_hours' => 100,
                'sort_order' => 5,
            ],
        ];
    }
}
