<?php

namespace Database\Seeders;

use Illuminate\Support\Str;

class TeknikPermesinanPklSeeder extends BaseCsvMajorPklSeeder
{
    private const FEMALE_STUDENTS = [
        'WULAN SARI',
    ];

    protected function csvRelativePath(): string
    {
        return 'SSEDER/Teknik Permesinan.csv';
    }

    protected function majorCode(): string
    {
        return 'TP';
    }

    protected function majorName(): string
    {
        return 'Teknik Pemesinan';
    }

    protected function teacherCatalog(): array
    {
        return [
            'TAUFIQ ISMAIL' => [
                'name' => 'Taufiq Ismail, S.Tr.T.',
                'email' => 'taufiq.ismail@smkn2garut.sch.id',
                'gender' => 'L',
            ],
            'TAUFIK ISMAIL' => [
                'name' => 'Taufiq Ismail, S.Tr.T.',
                'email' => 'taufiq.ismail@smkn2garut.sch.id',
                'gender' => 'L',
            ],
            'DENDEN NURJAMAN' => [
                'name' => 'Denden Nurjaman, S.Pd.',
                'email' => 'denden.nurjaman@smkn2garut.sch.id',
                'gender' => 'L',
            ],
            'ASEP ROSADI' => [
                'name' => 'Asep Rosadi, S.T.',
                'email' => 'asep.rosadi@smkn2garut.sch.id',
                'gender' => 'L',
            ],
            'KHARISMA APRILIA' => [
                'name' => 'Kharisma Aprilia P., S.T.',
                'email' => 'kharisma.aprilia@smkn2garut.sch.id',
                'gender' => 'P',
            ],
            'ZAFAR SIDIQ' => [
                'name' => 'Zafar Sidiq, S.Pd.',
                'email' => 'zafar.sidiq@smkn2garut.sch.id',
                'gender' => 'L',
            ],
            'HJ. NANIEN DIANI' => [
                'name' => 'Hj. Nanien Diani, M.Pd.',
                'email' => 'nanien.diani@smkn2garut.sch.id',
                'gender' => 'P',
            ],
            'ULFAH ROJIAH' => [
                'name' => 'Ulfah Rojiah, S.Pd.',
                'email' => 'ulfah.rojiah@smkn2garut.sch.id',
                'gender' => 'P',
            ],
            'DR. ENUNG MULYATI' => [
                'name' => 'Dr. Enung Mulyati, S.Pd., M.Pd.',
                'email' => 'enung.mulyati@smkn2garut.sch.id',
                'gender' => 'P',
            ],
            'AHMAD YANI' => [
                'name' => 'Ahmad Yani, S.Pd., M.M.',
                'email' => 'ahmad.yani@smkn2garut.sch.id',
                'gender' => 'L',
            ],
            'LIA RATNASARI' => [
                'name' => 'Lia Ratnasari, S.E.',
                'email' => 'lia.ratnasari@smkn2garut.sch.id',
                'gender' => 'P',
            ],
            'RINA WIDANINGSIH' => [
                'name' => 'Rina Widaningsih, S.Pd.',
                'email' => 'rina.widaningsih@smkn2garut.sch.id',
                'gender' => 'P',
            ],
            'SUCI PUTRI NUR' => [
                'name' => 'Suci Putri Nur, S.Pd.',
                'email' => 'suci.putri@smkn2garut.sch.id',
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
                'code' => 'TP-CP01',
                'title' => 'Penerapan K3LH Bengkel Permesinan & Penggunaan Alat Ukur Presisi',
                'element_name' => 'K3LH & Metrologi Industri',
                'description' => 'Menerapkan keselamatan kerja permesinan, penggunaan APD, serta pengukuran presisi menggunakan jangka sorong, mikrometer, dan dial indicator.',
                'target_hours' => 60,
                'sort_order' => 1,
            ],
            [
                'code' => 'TP-CP02',
                'title' => 'Membaca Gambar Teknik Manufaktur & Penyiapan Bahan Kerja',
                'element_name' => 'Gambar Teknik & Persiapan Kerja',
                'description' => 'Membaca simbol proyeksi, toleransi ukuran, kekasaran permukaan gambar kerja serta melaksanakan penandaan dan pemotongan benda kerja.',
                'target_hours' => 100,
                'sort_order' => 2,
            ],
            [
                'code' => 'TP-CP03',
                'title' => 'Pengoperasian Mesin Pemesinan Konvensional (Bubut, Frais & Gerinda)',
                'element_name' => 'Pemesinan Konvensional',
                'description' => 'Melaksanakan pembubutan rata/bertingkat/ulir, pengefraisan rata/alur, serta penggerindaan datar maupun silindris sesuai gambar kerja.',
                'target_hours' => 160,
                'sort_order' => 3,
            ],
            [
                'code' => 'TP-CP04',
                'title' => 'Pemrograman & Pengoperasian Mesin CNC (Turning & Milling)',
                'element_name' => 'Pemesinan NC/CNC & CAM',
                'description' => 'Menyusun kode G/M dasar, setting titik nol pahat dan benda kerja, simulasi lintasan pahat, dan eksekusi pemesinan CNC presisi.',
                'target_hours' => 140,
                'sort_order' => 4,
            ],
            [
                'code' => 'TP-CP05',
                'title' => 'Perawatan Mesin Perkakas, Quality Control & Pelaporan Produksi',
                'element_name' => 'Quality Control & Administrasi Bengkel',
                'description' => 'Melakukan pemeriksaan dimensi akhir produk manufaktur, perawatan preventif mesin perkakas, dan dokumentasi pekerjaan harian PKL.',
                'target_hours' => 100,
                'sort_order' => 5,
            ],
        ];
    }
}
