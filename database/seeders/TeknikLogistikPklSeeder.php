<?php

namespace Database\Seeders;

use Illuminate\Support\Str;

class TeknikLogistikPklSeeder extends BaseCsvMajorPklSeeder
{
    private const FEMALE_STUDENTS = [
        'ALLIFA ZAM-ZAM MIFTAHUL HUDA',
        'ALYA SALSA MAEMUNAH',
        'FADYA LESTARI PUTRI',
        'INDRI',
        'JULIYA APRIANTI',
        'N.SHENA NADIANA',
        'RAISYA',
        'SASKIA SITI SALWIAH',
        'SHIVA NUR PADILAH',
        'SYIFA SADIAH',
    ];

    protected function csvRelativePath(): string
    {
        return 'SSEDER/Teknik Logistik.csv';
    }

    protected function majorCode(): string
    {
        return 'TL';
    }

    protected function majorCodeAliases(): array
    {
        return ['LOG'];
    }

    protected function majorName(): string
    {
        return 'Teknik Logistik';
    }

    protected function teacherCatalog(): array
    {
        return [
            'SRI MALADEWI' => [
                'name' => 'Sri Maladewi, S.T.',
                'email' => 'sri.maladewi@smkn2garut.sch.id',
                'gender' => 'P',
            ],
            'AUREL SURYA RAMADHANTY' => [
                'name' => 'Aurel Surya Ramadhanty, S.T.',
                'email' => 'aurel.surya@smkn2garut.sch.id',
                'gender' => 'P',
            ],
            'LUKMAN NURHAKIM' => [
                'name' => 'Lukman Nurhakim, S.T.',
                'email' => 'lukman.nurhakim@smkn2garut.sch.id',
                'gender' => 'L',
            ],
            'MEGA LESTARI' => [
                'name' => 'Mega Lestari, S.E.',
                'email' => 'mega.lestari@smkn2garut.sch.id',
                'gender' => 'P',
            ],
            'HJ. NANIEN DIANI' => [
                'name' => 'Hj. Nanien Diani, M.Pd.',
                'email' => 'nanien.diani@smkn2garut.sch.id',
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
                'code' => 'TL-CP01',
                'title' => 'Penerapan K3LH, SOP Pergudangan & Tata Tertib Operasional Logistik',
                'element_name' => 'K3LH & Keselamatan Kerja Gudang',
                'description' => 'Menerapkan keselamatan dan kesehatan kerja di area gudang/ritel, penggunaan APD, penanganan material (material handling), serta kedisiplinan SOP industri.',
                'target_hours' => 60,
                'sort_order' => 1,
            ],
            [
                'code' => 'TL-CP02',
                'title' => 'Penerimaan Barang (Receiving), Put-Away & Pengendalian Stok Gudang',
                'element_name' => 'Manajemen Pergudangan & Inventory',
                'description' => 'Melaksanakan pemeriksaan barang masuk sesuai surat jalan, pelabelan/barcoding, penataan rak (FIFO/FEFO), dan stock opname berkala.',
                'target_hours' => 140,
                'sort_order' => 2,
            ],
            [
                'code' => 'TL-CP03',
                'title' => 'Order Picking, Pengemasan (Packing) & Display Barang Ritel/Industri',
                'element_name' => 'Penyiapan Pesanan & Pengemasan',
                'description' => 'Melakukan pengambilan barang sesuai picking list, pengemasan aman sesuai karakteristik produk, serta penataan dan pengecekan stok area display.',
                'target_hours' => 120,
                'sort_order' => 3,
            ],
            [
                'code' => 'TL-CP04',
                'title' => 'Operasional Distribusi, Pengiriman & Layanan Ekspedisi/Kurir',
                'element_name' => 'Distribusi & Transportasi',
                'description' => 'Mengelola penyortiran paket/muatan berdasarkan rute tujuan, pemuatan (loading), pelacakan pengiriman, dan pelayanan serah terima barang.',
                'target_hours' => 140,
                'sort_order' => 4,
            ],
            [
                'code' => 'TL-CP05',
                'title' => 'Administrasi Dokumen Logistik, Surat Jalan & Sistem Informasi Gudang',
                'element_name' => 'Administrasi & Sistem Logistik',
                'description' => 'Menginput transaksi barang masuk/keluar pada sistem inventori/WMS, memverifikasi faktur dan surat jalan, serta menyusun laporan harian.',
                'target_hours' => 100,
                'sort_order' => 5,
            ],
        ];
    }
}
