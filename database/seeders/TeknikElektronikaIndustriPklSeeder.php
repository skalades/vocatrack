<?php

namespace Database\Seeders;

use Illuminate\Support\Str;

class TeknikElektronikaIndustriPklSeeder extends BaseCsvMajorPklSeeder
{
    private const FEMALE_STUDENTS = [
        'BEBY NUR FITRIA',
        'OURELL FITRISIA KANAHAYA',
        'PUTRI YASINTA',
        'REVANTI APRILIA',
        'SULIS AURELLYA VEGA',
    ];

    protected function csvRelativePath(): string
    {
        return 'SSEDER/Teknik Elektronika Industri.csv';
    }

    protected function majorCode(): string
    {
        return 'TEI';
    }

    protected function majorName(): string
    {
        return 'Teknik Elektronika Industri';
    }

    protected function teacherCatalog(): array
    {
        return [
            'H. USMAN T. NURJAMAN' => [
                'name' => 'H. Usman T. Nurjaman, S.Pd., M.M.',
                'email' => 'usman.nurjaman@smkn2garut.sch.id',
                'gender' => 'L',
            ],
            'DIAN HERDIANA' => [
                'name' => 'Dian Herdiana, S.Pd., M.Pd.',
                'email' => 'dian.herdiana@smkn2garut.sch.id',
                'gender' => 'L',
            ],
            'ANTON NUGRAHA' => [
                'name' => 'Anton Nugraha, S.T.',
                'email' => 'anton.nugraha@smkn2garut.sch.id',
                'gender' => 'L',
            ],
            'TIRAS SARASWATI' => [
                'name' => 'Tiras Saraswati, S.T.',
                'email' => 'tiras.saraswati@smkn2garut.sch.id',
                'gender' => 'P',
            ],
            'YULI AULIA ROSDIYANI' => [
                'name' => 'Yuli Aulia Rosdiyani, S.Pd.I',
                'email' => 'yuli.aulia@smkn2garut.sch.id',
                'gender' => 'P',
            ],
            'HJ. SITI HASANATY' => [
                'name' => 'Hj. Siti Hasanaty, S.Pd., M.Si',
                'email' => 'siti.hasanaty@smkn2garut.sch.id',
                'gender' => 'P',
            ],
            'ERNA YATI' => [
                'name' => 'Erna Yati, S.Pd.',
                'email' => 'erna.yati@smkn2garut.sch.id',
                'gender' => 'P',
            ],
            'DEDEH KURNIASIH' => [
                'name' => 'Dedeh Kurniasih, S.Pd., M.Pd',
                'email' => 'dedeh.kurniasih@smkn2garut.sch.id',
                'gender' => 'P',
            ],
            'DRA. HJ. ICEU SETIAWATI' => [
                'name' => 'Dra. Hj. Iceu Setiawati',
                'email' => 'iceu.setiawati@smkn2garut.sch.id',
                'gender' => 'P',
            ],
            'DRA. ICEU SETIAWATI' => [
                'name' => 'Dra. Hj. Iceu Setiawati',
                'email' => 'iceu.setiawati@smkn2garut.sch.id',
                'gender' => 'P',
            ],
            'EVIE KUNIASIH' => [
                'name' => 'Evie Kuniasih, S.Pd.',
                'email' => 'evie.kuniasih@smkn2garut.sch.id',
                'gender' => 'P',
            ],
            'GATI INTAN TAMARA' => [
                'name' => 'Gati Intan Tamara, S.Pd.',
                'email' => 'gati.intan@smkn2garut.sch.id',
                'gender' => 'P',
            ],
            'AAN ANGGRAENI' => [
                'name' => 'Aan Anggraeni, S.Pd.',
                'email' => 'aan.anggraeni@smkn2garut.sch.id',
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
                'code' => 'TEI-CP01',
                'title' => 'Penerapan K3LH, Proteksi Kelistrikan & Tata Tertib Kerja Industri',
                'element_name' => 'K3LH & Budaya Kerja Industri',
                'description' => 'Menerapkan keselamatan kerja elektronika dan kelistrikan industri, penggunaan APD, serta SOP pemeliharaan peralatan bertegangan.',
                'target_hours' => 60,
                'sort_order' => 1,
            ],
            [
                'code' => 'TEI-CP02',
                'title' => 'Sistem Kendali Industri, PLC, Sensor & Aktuator Otomasi',
                'element_name' => 'Sistem Kendali & Otomasi',
                'description' => 'Melakukan instalasi rangkaian kontrol, pemrograman dasar PLC/mikrokontroler, serta kalibrasi sensor dan aktuator industri.',
                'target_hours' => 140,
                'sort_order' => 2,
            ],
            [
                'code' => 'TEI-CP03',
                'title' => 'Pemeliharaan & Perbaikan Rangkaian Elektronika Daya dan Instrumentasi',
                'element_name' => 'Elektronika Daya & Instrumentasi',
                'description' => 'Mengukur parameter rangkaian elektronika daya, catu daya industri, panel kontrol, serta melakukan troubleshooting komponen.',
                'target_hours' => 140,
                'sort_order' => 3,
            ],
            [
                'code' => 'TEI-CP04',
                'title' => 'Instalasi Jaringan Kontrol, Telekomunikasi & Perangkat Sistem Terintegrasi',
                'element_name' => 'Integrasi Sistem Elektronika',
                'description' => 'Melaksanakan penarikan kabel kontrol/data, perakitan modul perangkat keras, dan pengujian konektivitas sistem terintegrasi.',
                'target_hours' => 120,
                'sort_order' => 4,
            ],
            [
                'code' => 'TEI-CP05',
                'title' => 'Dokumentasi Pemeliharaan, Quality Control & Pelaporan Teknis',
                'element_name' => 'Dokumentasi & Administrasi Teknik',
                'description' => 'Menyusun log pemeriksaan harian, laporan diagnosa gangguan, pengujian fungsi pasca-perbaikan, dan serah terima pekerjaan.',
                'target_hours' => 100,
                'sort_order' => 5,
            ],
        ];
    }
}
