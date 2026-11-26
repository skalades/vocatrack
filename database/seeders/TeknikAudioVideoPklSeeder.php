<?php

namespace Database\Seeders;

use Illuminate\Support\Str;

class TeknikAudioVideoPklSeeder extends BaseCsvMajorPklSeeder
{
    private const FEMALE_STUDENTS = [
        'CAMEILA ZAHWA ZIVANKA',
        'REVINA ANANDA SYABILA',
        'NESA KUSMAYANTI',
        'PUTRI NIRWANDI',
        'SAIRA NAILA ALIFAH',
        'SYABI ISNAENI',
    ];

    protected function csvRelativePath(): string
    {
        return 'SSEDER/Teknik Audio Video.csv';
    }

    protected function majorCode(): string
    {
        return 'TAV';
    }

    protected function majorName(): string
    {
        return 'Teknik Audio Video';
    }

    protected function teacherCatalog(): array
    {
        return [
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
    }

    protected function resolveStudentGender(string $name, string $nis): string
    {
        return in_array(Str::upper($name), self::FEMALE_STUDENTS, true) ? 'P' : 'L';
    }

    protected function learningObjectivesData(): array
    {
        return [
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
    }
}
