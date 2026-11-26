<?php

namespace Database\Seeders;

use Illuminate\Support\Str;

class TeknikInstalasiTenagaListrikPklSeeder extends BaseCsvMajorPklSeeder
{
    private const FEMALE_STUDENTS = [
        'INTAN MUTIA PRATIWI',
        'ISMA RAESMAWATI',
        'LANI PAUJIAH',
        'MIRA RAHMA NURAENI',
        'SUSI',
    ];

    protected function csvRelativePath(): string
    {
        return 'SSEDER/Teknik Instalasi Tenaga Listrik.csv';
    }

    protected function majorCode(): string
    {
        return 'TITL';
    }

    protected function majorName(): string
    {
        return 'Teknik Instalasi Tenaga Listrik';
    }

    protected function teacherCatalog(): array
    {
        return [
            'IMAN ABDUL FATAH' => [
                'name' => 'Iman Abdul Fatah, S.ST., S.T.',
                'email' => 'iman.abdul@smkn2garut.sch.id',
                'gender' => 'L',
            ],
            'GIN GIN JENI PURNAMA' => [
                'name' => 'Gin Gin Jeni Purnama, S.Pd.',
                'email' => 'gin.gin@smkn2garut.sch.id',
                'gender' => 'L',
            ],
            'CHANDRA MAULIDYA' => [
                'name' => 'Chandra Maulidya, S.Pd.',
                'email' => 'chandra.maulidya@smkn2garut.sch.id',
                'gender' => 'P',
            ],
            'KUSTIAWAN' => [
                'name' => 'Kustiawan, S.Pd.',
                'email' => 'kustiawan@smkn2garut.sch.id',
                'gender' => 'L',
            ],
            'ELLA RAHMI FATAH' => [
                'name' => 'Ella Rahmi Fatah, M.Pd.',
                'email' => 'ella.rahmi@smkn2garut.sch.id',
                'gender' => 'P',
            ],
            'EPA APIPAH' => [
                'name' => 'Epa Apipah, S.Pd.',
                'email' => 'epa.apipah@smkn2garut.sch.id',
                'gender' => 'P',
            ],
            'MUANA' => [
                'name' => 'Muana, S.Pd.',
                'email' => 'muana@smkn2garut.sch.id',
                'gender' => 'L',
            ],
            'RAHMAN DWI' => [
                'name' => 'Rahman Dwi S., S.Pd.',
                'email' => 'rahman.dwi@smkn2garut.sch.id',
                'gender' => 'L',
            ],
            'DENI HIDAYAT' => [
                'name' => 'Deni Hidayat, S.Pd.',
                'email' => 'deni.hidayat@smkn2garut.sch.id',
                'gender' => 'L',
            ],
            'HIDAYAT ROHMATULLOH' => [
                'name' => 'Hidayat Rohmatulloh, S.Pd.I',
                'email' => 'hidayat.rohmatulloh@smkn2garut.sch.id',
                'gender' => 'L',
            ],
            'EMPONG SAODAH' => [
                'name' => 'Empong Saodah, S.Pd., M.M.',
                'email' => 'empong.saodah@smkn2garut.sch.id',
                'gender' => 'P',
            ],
            'N. DEDE YUNINGSIH' => [
                'name' => 'N. Dede Yuningsih, S.Pd.',
                'email' => 'dede.yuningsih@smkn2garut.sch.id',
                'gender' => 'P',
            ],
            'INDRA LESMANA' => [
                'name' => 'Indra Lesmana, S.Pd.',
                'email' => 'indra.lesmana@smkn2garut.sch.id',
                'gender' => 'L',
            ],
            'SUTANTO' => [
                'name' => 'Sutanto, S.Pd., M.Pd.',
                'email' => 'sutanto@smkn2garut.sch.id',
                'gender' => 'L',
            ],
            'EVIE KUNIASIH' => [
                'name' => 'Evie Kuniasih, S.Pd.',
                'email' => 'evie.kuniasih@smkn2garut.sch.id',
                'gender' => 'P',
            ],
            'AAN ANGGRAENI' => [
                'name' => 'Aan Anggraeni, S.Pd.',
                'email' => 'aan.anggraeni@smkn2garut.sch.id',
                'gender' => 'P',
            ],
            'TIAGITA TISTIYANTI' => [
                'name' => 'Tiagita Tistiyanti, S.Pd.',
                'email' => 'tiagita.tistiyanti@smkn2garut.sch.id',
                'gender' => 'P',
            ],
            'AHMAD YANI' => [
                'name' => 'Ahmad Yani, S.Pd., M.M.',
                'email' => 'ahmad.yani@smkn2garut.sch.id',
                'gender' => 'L',
            ],
            'AZIZ ZULHAKIM' => [
                'name' => 'Aziz Zulhakim, S.Pd.',
                'email' => 'aziz.zulhakim@smkn2garut.sch.id',
                'gender' => 'L',
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
                'code' => 'TITL-CP01',
                'title' => 'Penerapan K3LH Kelistrikan, APD & Keselamatan Kerja Tegangan',
                'element_name' => 'K3LH & Budaya Kerja Kelistrikan',
                'description' => 'Menerapkan standar keselamatan kerja kelistrikan (PUIL/SOP), penggunaan APD terisolasi, lockout-tagout (LOTO), dan tata tertib industri.',
                'target_hours' => 60,
                'sort_order' => 1,
            ],
            [
                'code' => 'TITL-CP02',
                'title' => 'Pemasangan & Pemeliharaan Instalasi Penerangan dan Tenaga Listrik',
                'element_name' => 'Instalasi Penerangan & Tenaga',
                'description' => 'Melaksanakan pemasangan pengawatan instalasi penerangan 1 fasa/3 fasa, PHB/panel distribusi, sistem pentanahan, dan penangkal petir.',
                'target_hours' => 140,
                'sort_order' => 2,
            ],
            [
                'code' => 'TITL-CP03',
                'title' => 'Instalasi & Pemeliharaan Panel Kontrol, Motor Listrik serta Otomasi PLC',
                'element_name' => 'Instalasi Motor & Sistem Kontrol',
                'description' => 'Merakit rangkaian kontrol magnetik kontaktor, sistem start-delta/VFD motor listrik, serta pemrograman dasar PLC untuk pengendalian tenaga.',
                'target_hours' => 140,
                'sort_order' => 3,
            ],
            [
                'code' => 'TITL-CP04',
                'title' => 'Perawatan Sistem Utilitas Gedung, Gardu Distribusi & Jaringan Listrik',
                'element_name' => 'Pemeliharaan Utilitas & Jaringan',
                'description' => 'Melakukan pemeriksaan berkala peralatan mekanikal-elektrikal gedung/pabrik, pengukuran tahanan isolasi/grounding, dan perbaikan gangguan jaringan.',
                'target_hours' => 120,
                'sort_order' => 4,
            ],
            [
                'code' => 'TITL-CP05',
                'title' => 'Pengujian Kelistrikan, Quality Control & Pelaporan Teknis Industri',
                'element_name' => 'Pengujian & Administrasi Teknik',
                'description' => 'Melakukan komisioning hasil instalasi/perbaikan, pencatatan hasil ukur multimeter/megger/clamp meter, dan penyusunan laporan harian PKL.',
                'target_hours' => 100,
                'sort_order' => 5,
            ],
        ];
    }
}
