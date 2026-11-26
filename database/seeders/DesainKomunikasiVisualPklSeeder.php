<?php

namespace Database\Seeders;

use Illuminate\Support\Str;

class DesainKomunikasiVisualPklSeeder extends BaseCsvMajorPklSeeder
{
    private const FEMALE_STUDENTS = [
        'ALVIDIA IBRAHIM',
        'AMELINA RISMAN',
        'CINTA NUR PADILAH',
        'DESFINA JASMINE',
        'FATIMAH AZ ZAHRO',
        'KEYLA MAULIDA SALSABILA',
        'NASHA ANDARA SHAPUTRI',
        'REGINA AULIA PUTRI',
        'RESTIA APRILLIA',
        'RISA NURLITA',
        'RISTA MAHARANI',
        'SAFITRI NURHASANNAH',
        'SALWA AURELLIA PUTRI',
        'SOVI NURUL PAUZIYAH',
        'WINDI APRRILIANI',
        'ZAHRA RAHADATHUL AISY WIJAYA',
        'ZULVA OKTARIA GARNETTA',
        'ANNISA FAUZIA',
        'ARYANI',
        'AURA PUTRI AGUSTYA',
        'GESHA DWI SEFFIRA',
        'ILMI YASINTA EKADIRA',
        'KIREN ZAHRA MARISKA',
        'MUTIARA PUSPA ANZANI',
        'NIKEN ANJANI',
        'RAHMA FITRIANI',
        'RAISYA LAILA AZ ZAHRA',
        'RAYI NAZWA NATASYA',
        'SALSA RAFIKA DEWI',
        'SYALUN RIMAYANTI',
        'TIARA AINI PARHANI',
        'ZAHRA NURZAQIAH',
        'ZAQIA AZHA',
    ];

    protected function csvRelativePath(): string
    {
        return 'SSEDER/Desain Komunikasi Visual.csv';
    }

    protected function majorCode(): string
    {
        return 'DKV';
    }

    protected function majorName(): string
    {
        return 'Desain Komunikasi Visual';
    }

    protected function teacherCatalog(): array
    {
        return [
            'ASEP NASRUDIN' => [
                'name' => 'Asep Nasrudin, S.T.',
                'email' => 'asep.nasrudin@smkn2garut.sch.id',
                'gender' => 'L',
            ],
            'SEVI SOFIYANTI' => [
                'name' => 'Sevi Sofiyanti, S.Kom.',
                'email' => 'sevi.sofiyanti@smkn2garut.sch.id',
                'gender' => 'P',
            ],
            'NIA RAHMANIA' => [
                'name' => 'Nia Rahmania, S.Kom.',
                'email' => 'nia.rahmania@smkn2garut.sch.id',
                'gender' => 'P',
            ],
            'ANDRI RISWANTO' => [
                'name' => 'Andri Riswanto, S.T., M.Pd.',
                'email' => 'andri.riswanto@smkn2garut.sch.id',
                'gender' => 'L',
            ],
            'DEPI GUSTIANTI' => [
                'name' => 'Depi Gustianti, S.Pd.',
                'email' => 'depi.gustianti@smkn2garut.sch.id',
                'gender' => 'P',
            ],
            'NENG SYAMSIAH' => [
                'name' => 'Neng Syamsiah, S.Pd., M.Pd.',
                'email' => 'neng.syamsiah@smkn2garut.sch.id',
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
                'code' => 'DKV-CP01',
                'title' => 'Penerapan K3LH, Etika Profesi & Hak Kekayaan Intelektual Desain',
                'element_name' => 'K3LH & Budaya Kerja Kreatif',
                'description' => 'Menerapkan keselamatan kerja perangkat studio/desain, manajemen berkas proyek, etika komunikasi klien, dan kepatuhan lisensi aset visual.',
                'target_hours' => 60,
                'sort_order' => 1,
            ],
            [
                'code' => 'DKV-CP02',
                'title' => 'Desain Grafis, Tipografi, Branding & Tata Letak Media Cetak',
                'element_name' => 'Desain Komunikasi Grafis',
                'description' => 'Merancang identitas visual, kemasan, poster, dan layout pracetak siap produksi (pre-press) sesuai standar warna industri percetakan.',
                'target_hours' => 140,
                'sort_order' => 2,
            ],
            [
                'code' => 'DKV-CP03',
                'title' => 'Fotografi Komersial, Tata Cahaya Studio & Digital Imaging',
                'element_name' => 'Fotografi & Pengolahan Citra',
                'description' => 'Mengoperasikan kamera digital dan pencahayaan studio untuk foto produk/dokumentasi serta melakukan retouching profesional.',
                'target_hours' => 120,
                'sort_order' => 3,
            ],
            [
                'code' => 'DKV-CP04',
                'title' => 'Produksi Konten Digital, Motion Graphic & Penyuntingan Video',
                'element_name' => 'Audio Visual & Motion',
                'description' => 'Memproduksi konten media sosial, animasi grafis, serta penyuntingan video promosi dengan ritme dan pewarnaan yang konsisten.',
                'target_hours' => 140,
                'sort_order' => 4,
            ],
            [
                'code' => 'DKV-CP05',
                'title' => 'Presentasi Brief Klien, Revisi Karya & Finalisasi Output Produksi',
                'element_name' => 'Manajemen Proyek Desain',
                'description' => 'Menerjemahkan brief klien, mengelola iterasi revisi secara profesional, dan menyerahkan file master siap pakai.',
                'target_hours' => 100,
                'sort_order' => 5,
            ],
        ];
    }
}
