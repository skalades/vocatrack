<?php

namespace Database\Seeders;

use App\Models\Assessment;
use App\Models\School;
use App\Models\StudentPkl;
use App\Models\User;
use App\Services\CertificateService;
use Illuminate\Database\Seeder;

class Smkn1GarutAssessmentSeeder extends Seeder
{
    /**
     * Seed Penilaian Hasil Belajar PKL Siswa SMKN 1 Garut dan Penerbitan Sertifikat Kelulusan.
     */
    public function run(): void
    {
        $school = School::where('npsn', '20209201')->firstOrFail();

        // Ambil bobot penilaian dari konfigurasi sekolah (default: DUDI 60%, Guru 40%)
        $weightDudi = (float) ($school->getSetting('weight_dudi', 60));
        $weightGuru = (float) ($school->getSetting('weight_guru', 40));
        $totalWeight = $weightDudi + $weightGuru;
        $wDudi = $totalWeight > 0 ? ($weightDudi / $totalWeight) : 0.6;
        $wGuru = $totalWeight > 0 ? ($weightGuru / $totalWeight) : 0.4;

        $certificateService = app(CertificateService::class);

        // Data Penilaian Komprehensif Siswa SMKN 1 Garut
        $evaluationsData = [
            [
                'email' => 'raihan.pplg@smkn1garut.sch.id',
                'discipline' => 95.00,
                'technical' => 96.00,
                'initiative' => 94.00,
                'report' => 95.00,
                'notes_dudi' => 'Raihan menunjukkan kecakapan istimewa dalam perancangan RESTful API Laravel, testing endpoint Postman, dan optimasi query PostgreSQL Diskominfo Garut. Komunikasi tim dan inisiatif pemecahan masalah teknis sangat memuaskan.',
                'notes_teacher' => 'Laporan akhir PKL dan dokumentasi arsitektur perangkat lunak disusun sangat komprehensif, memenuhi capaian pembelajaran kurikulum merdeka PPLG dan siap dijadikan portofolio unggulan.',
            ],
            [
                'email' => 'ilham.tjkt@smkn1garut.sch.id',
                'discipline' => 92.00,
                'technical' => 94.00,
                'initiative' => 90.00,
                'report' => 93.00,
                'notes_dudi' => 'Keahlian fiber optic fusion splicing, konfigurasi inter-VLAN routing switch Cisco Catalyst, serta pengukuran redaman OPM sangat teruji di lapangan operasional PT Telkom Witel Garut.',
                'notes_teacher' => 'Penguasaan instrumen serat optik dan laporan pemeliharaan jaringan telekomunikasi disajikan dengan analitis dan memenuhi standar kompetensi TJKT.',
            ],
            [
                'email' => 'fauzan.dkv@smkn1garut.sch.id',
                'discipline' => 90.00,
                'technical' => 94.00,
                'initiative' => 92.00,
                'report' => 92.00,
                'notes_dudi' => 'Kreativitas tinggi dalam perancangan visual identity, digital photo retouching di Photoshop, serta produksi motion graphic promosi pariwisata Garut di Adobe After Effects.',
                'notes_teacher' => 'Portofolio visual dan laporan komersial desain komunikasi visual tersusun artistik, rapi, dan memiliki nilai jual industri kreatif yang nyata.',
            ],
            [
                'email' => 'rizky.akl@smkn1garut.sch.id',
                'discipline' => 96.00,
                'technical' => 93.00,
                'initiative' => 92.00,
                'report' => 94.00,
                'notes_dudi' => 'Sangat cermat dalam rekonsiliasi mutasi rekening koran nasabah korporat, pencatatan jurnal kas kecil, dan verifikasi bukti potong pajak di Bank bjb Cabang Garut. Integritas tinggi.',
                'notes_teacher' => 'Siklus akuntansi operasional perbankan dan laporan pertanggungjawaban keuangan disusun tepat waktu dengan ketelitian neraca yang sempurna.',
            ],
            [
                'email' => 'dea.mplb@smkn1garut.sch.id',
                'discipline' => 93.00,
                'technical' => 90.00,
                'initiative' => 91.00,
                'report' => 92.00,
                'notes_dudi' => 'Cepat, cermat, dan sopan dalam melayani pemohon sertifikat tanah di loket pendaftaran BPN Garut serta terampil dalam penginputan warkah digital ke aplikasi KKP Pertanahan.',
                'notes_teacher' => 'Tata kelola kearsipan digital dan etika komunikasi perkantoran memenuhi standar mutu manajemen perkantoran dan layanan bisnis modern.',
            ],
            [
                'email' => 'kevin.pms@smkn1garut.sch.id',
                'discipline' => 91.00,
                'technical' => 90.00,
                'initiative' => 93.00,
                'report' => 90.00,
                'notes_dudi' => 'Kreatif dalam penataan visual merchandising produk di gondola promo Toserba Yogya Garut dan cekatan saat pelaksanaan stock opname barcode.',
                'notes_teacher' => 'Laporan strategi pemasaran ritel disajikan komprehensif didukung analisis tren produk terlaris yang faktual.',
            ],
            [
                'email' => 'fikri.tet@smkn1garut.sch.id',
                'discipline' => 96.00,
                'technical' => 95.00,
                'initiative' => 94.00,
                'report' => 95.00,
                'notes_dudi' => 'Kepatuhan K3 industri panas bumi PGE Kamojang sangat konsisten. Pemahaman pemantauan turbin uap PLTP dan instrumen separator kepala sumur sangat mendalam.',
                'notes_teacher' => 'Laporan observasi fasilitas pembangkit listrik energi terbarukan sangat bermutu tinggi dan membuktikan keunggulan vokasi SMKN 1 Garut.',
            ],
            [
                'email' => 'salma.tf@smkn1garut.sch.id',
                'discipline' => 95.00,
                'technical' => 94.00,
                'initiative' => 92.00,
                'report' => 93.00,
                'notes_dudi' => 'Teliti dan higienis dalam penyiapan sediaan obat racikan resep dokter di Apotek Kimia Farma serta menguasai manajemen stok obat FEFO dan LASA dengan tepat.',
                'notes_teacher' => 'Laporan farmasi klinis dan pengenalan regulasi kefarmasian memenuhi kualifikasi kompetensi asisten tenaga farmasi terampil.',
            ],
            [
                'email' => 'farrel.tlm@smkn1garut.sch.id',
                'discipline' => 94.00,
                'technical' => 95.00,
                'initiative' => 92.00,
                'report' => 93.00,
                'notes_dudi' => 'Terampil dalam teknik flebotomi sampling darah, kalibrasi instrumen hematology analyzer, dan pembuatan sediaan apusan darah tepi di lab RSUD dr. Slamet Garut.',
                'notes_teacher' => 'Laporan pengujian hematologi dan kimia darah disusun sesuai standar operasional laboratorium kesehatan patologi klinik.',
            ],
            [
                'email' => 'bayu.tlg@smkn1garut.sch.id',
                'discipline' => 92.00,
                'technical' => 91.00,
                'initiative' => 90.00,
                'report' => 91.00,
                'notes_dudi' => 'Disiplin dalam verifikasi dokumen penerimaan barang masuk (inbound), alokasi racking warehouse Yogya Garut, serta monitoring safety stock buffer.',
                'notes_teacher' => 'Laporan manajemen pergudangan dan rantai pasok ritel tersusun baik sesuai kurikulum teknik logistik.',
            ],
            [
                'email' => 'nabila.pplg@smkn1garut.sch.id',
                'discipline' => 93.00,
                'technical' => 92.00,
                'initiative' => 91.00,
                'report' => 92.00,
                'notes_dudi' => 'Membantu pengujian antarmuka sistem digital ticketing Telkom dengan teliti dan memiliki kemampuan dokumentasi bug tracking yang rapi.',
                'notes_teacher' => 'Laporan pengujian perangkat lunak dan quality assurance tersaji dengan sistematis.',
            ],
            [
                'email' => 'annisa.tjkt@smkn1garut.sch.id',
                'discipline' => 94.00,
                'technical' => 91.00,
                'initiative' => 92.00,
                'report' => 92.00,
                'notes_dudi' => 'Tanggap dalam penanganan instalasi kabel LAN dan maintenance berkala perangkat komputer jaringan command center Diskominfo Garut.',
                'notes_teacher' => 'Laporan penataan topologi jaringan komputer lokal terdokumentasi lengkap dan rapi.',
            ],
        ];

        foreach ($evaluationsData as $eval) {
            $user = User::where('email', $eval['email'])->first();
            if (!$user || !$user->student) {
                continue;
            }

            $spkl = StudentPkl::where('student_id', $user->student->id)
                ->where('school_id', $school->id)
                ->first();

            if (!$spkl) {
                continue;
            }

            // Hitung nilai akhir sesuai formula pembobotan sekolah
            $avgDudi = ($eval['discipline'] + $eval['technical'] + $eval['initiative']) / 3;
            $avgGuru = $eval['report'];
            $finalScore = round(($avgDudi * $wDudi) + ($avgGuru * $wGuru), 2);

            // Simpan / Update Penilaian
            $assessment = Assessment::updateOrCreate(
                [
                    'student_pkl_id' => $spkl->id,
                ],
                [
                    'school_id' => $school->id,
                    'score_dudi_discipline' => $eval['discipline'],
                    'score_dudi_technical' => $eval['technical'],
                    'score_dudi_initiative' => $eval['initiative'],
                    'score_teacher_report' => $eval['report'],
                    'final_score' => $finalScore,
                    'notes_dudi' => $eval['notes_dudi'],
                    'notes_teacher' => $eval['notes_teacher'],
                ]
            );

            // Generate Sertifikat Kelulusan PKL Resmi
            $certificateService->generateForStudentPkl($spkl);
        }
    }
}
