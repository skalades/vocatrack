<?php

namespace Database\Seeders;

use Illuminate\Support\Str;

class FarmasiIndustriPklSeeder extends BaseCsvMajorPklSeeder
{
    private const MALE_STUDENTS = [
        'FAHRI ALVAHREZI',
        'GAREL PUTRA ANUGRAH',
        'MOH. SOFWAN SOLEHUDIN',
        'KEMAL ISMAIL PASYA',
        'KHENZA FAHMY HERDIANSYAH',
    ];

    protected function csvRelativePath(): string
    {
        return 'SSEDER/Farmasi Industri.csv';
    }

    protected function majorCode(): string
    {
        return 'FI';
    }

    protected function majorName(): string
    {
        return 'Farmasi Industri';
    }

    protected function teacherCatalog(): array
    {
        return [
            'EUTIK SUPIATI' => [
                'name' => 'Eutik Supiati, S.Si.',
                'email' => 'eutik.supiati@smkn2garut.sch.id',
                'gender' => 'P',
            ],
            'APT. WILDANI INAYATI' => [
                'name' => 'apt. Wildani Inayati, S.Farm',
                'email' => 'wildani.inayati@smkn2garut.sch.id',
                'gender' => 'P',
            ],
            'TEMI TRI RIZKI' => [
                'name' => 'Temi Tri Rizki, S.Kep',
                'email' => 'temi.tri@smkn2garut.sch.id',
                'gender' => 'L',
            ],
            'NOVY NOFYAWATI' => [
                'name' => 'Novy Nofyawati, S.Farm',
                'email' => 'novy.nofyawati@smkn2garut.sch.id',
                'gender' => 'P',
            ],
            'HULAIMA HUSNUL HIMAH' => [
                'name' => 'Hulaima Husnul Himah, S.Farm',
                'email' => 'hulaima.husnul@smkn2garut.sch.id',
                'gender' => 'P',
            ],
            'DINNA RATNA AINI' => [
                'name' => 'Dinna Ratna Aini, S.Si.',
                'email' => 'dinna.ratna@smkn2garut.sch.id',
                'gender' => 'P',
            ],
            'APT. RISMA AGUSTINA' => [
                'name' => 'apt. Risma Agustina, S.Farm',
                'email' => 'risma.agustina@smkn2garut.sch.id',
                'gender' => 'P',
            ],
        ];
    }

    protected function resolveStudentGender(string $name, string $nis): string
    {
        return in_array(Str::upper($name), self::MALE_STUDENTS, true) ? 'L' : 'P';
    }

    protected function learningObjectivesData(): array
    {
        return [
            [
                'code' => 'FI-CP01',
                'title' => 'Penerapan K3LH, CPOB & Sanitasi Higiene Industri Farmasi',
                'element_name' => 'CPOB & K3LH Farmasi',
                'description' => 'Menerapkan Cara Pembuatan Obat yang Baik (CPOB), higiene perorangan, sanitasi ruang produksi, dan keselamatan kerja laboratorium/industri.',
                'target_hours' => 80,
                'sort_order' => 1,
            ],
            [
                'code' => 'FI-CP02',
                'title' => 'Produksi & Pengemasan Sediaan Farmasi Skala Industri',
                'element_name' => 'Teknologi Produksi Sediaan',
                'description' => 'Melaksanakan proses penimbangan bahan baku, pencampuran, pengisian, serta pengemasan primer dan sekunder sediaan obat dan kosmetika.',
                'target_hours' => 140,
                'sort_order' => 2,
            ],
            [
                'code' => 'FI-CP03',
                'title' => 'Pengujian Mutu (Quality Control) & In-Process Control',
                'element_name' => 'Pengawasan Mutu (QC)',
                'description' => 'Melakukan pemeriksaan organoleptis, uji bobot, keseragaman sediaan, pH, viskositas, dan pencatatan dokumentasi IPC sesuai standar mutu.',
                'target_hours' => 120,
                'sort_order' => 3,
            ],
            [
                'code' => 'FI-CP04',
                'title' => 'Pengelolaan Gudang Farmasi, Rantai Dingin & Pelayanan Kefarmasian',
                'element_name' => 'Logistik & Pelayanan Farmasi',
                'description' => 'Mengelola penyimpanan bahan baku/obat jadi berdasarkan prinsip FEFO/FIFO, pengendalian stok, serta pelayanan resep di apotek/klinik.',
                'target_hours' => 120,
                'sort_order' => 4,
            ],
            [
                'code' => 'FI-CP05',
                'title' => 'Dokumentasi Batch Record & Administrasi Kefarmasian',
                'element_name' => 'Dokumentasi & Regulasi',
                'description' => 'Menyusun catatan pengolahan batch, kartu stok, pelaporan harian operasional farmasi, dan ketertelusuran dokumen mutu.',
                'target_hours' => 100,
                'sort_order' => 5,
            ],
        ];
    }
}
