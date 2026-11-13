<?php

namespace Database\Seeders;

use App\Models\Assessment;
use App\Models\School;
use App\Models\StudentPkl;
use App\Models\User;
use App\Services\CertificateService;
use Illuminate\Database\Seeder;

class Smkn10GarutAssessmentSeeder extends Seeder
{
    /**
     * Seed Penilaian Hasil Belajar PKL Siswa SMKN 10 Garut dan Penerbitan Sertifikat Kelulusan.
     */
    public function run(): void
    {
        $school = School::where('npsn', '20262128')->firstOrFail();

        // Ambil bobot penilaian sekolah (default: DUDI 60%, Guru 40%)
        $weightDudi = (float) ($school->getSetting('weight_dudi', 60));
        $weightGuru = (float) ($school->getSetting('weight_guru', 40));
        $totalWeight = $weightDudi + $weightGuru;
        $wDudi = $totalWeight > 0 ? ($weightDudi / $totalWeight) : 0.6;
        $wGuru = $totalWeight > 0 ? ($weightGuru / $totalWeight) : 0.4;

        $certificateService = app(CertificateService::class);

        $evaluationsData = [
            [
                'email' => 'ilham.tkro@smkn10garut.sch.id',
                'discipline' => 94.00,
                'technical' => 96.00,
                'initiative' => 92.00,
                'report' => 94.00,
                'notes_dudi' => 'Ilham menunjukkan keterampilan luar biasa dalam tune-up mesin Toyota, perawatan sistem rem cakram/tromol, dan pembongkaran transmisi manual di bengkel resmi Auto2000. Sangat disiplin terhadap SOP keselamatan kerja bengkel.',
                'notes_teacher' => 'Laporan akhir PKL dan analisis studi kasus perbaikan mesin kendaraan ringan disusun sistematis dan memenuhi kompetensi keahlian TKRO.',
            ],
            [
                'email' => 'sandi.tbsm@smkn10garut.sch.id',
                'discipline' => 92.00,
                'technical' => 95.00,
                'initiative' => 91.00,
                'report' => 93.00,
                'notes_dudi' => 'Mahir dalam perbaikan dan servis transmisi CVT matic serta penggunaan diagnostic scanner HIDS Honda di bengkel AHASS Daya Motor Garut. Cekatan dan teliti.',
                'notes_teacher' => 'Laporan tune up sistem injeksi sepeda motor lengkap dengan grafik pembacaan sensor dan standar toleransi celah katup.',
            ],
            [
                'email' => 'rifki.tjkt@smkn10garut.sch.id',
                'discipline' => 93.00,
                'technical' => 94.00,
                'initiative' => 90.00,
                'report' => 92.00,
                'notes_dudi' => 'Cepat beradaptasi dalam konfigurasi MikroTik RouterOS, manajemen bandwidth, dan terminasi kabel jaringan UTP di Telkom Garut. Inisiatif kerja tim sangat baik.',
                'notes_teacher' => 'Laporan perancangan jaringan lokal dan keamanan firewall hotspot tersusun rapi sesuai kurikulum TJKT.',
            ],
            [
                'email' => 'firman.dkv@smkn10garut.sch.id',
                'discipline' => 90.00,
                'technical' => 93.00,
                'initiative' => 92.00,
                'report' => 91.00,
                'notes_dudi' => 'Kreatif dalam penyusunan layout pra-cetak booklet InDesign dan terampil mengoperasikan mesin cetak digital large format di Percetakan Duta Media Garut.',
                'notes_teacher' => 'Portofolio grafis media cetak dan karya desain komunikasi visual memenuhi standar kelulusan kejuruan grafika.',
            ],
            [
                'email' => 'naufal.akl@smkn10garut.sch.id',
                'discipline' => 95.00,
                'technical' => 93.00,
                'initiative' => 92.00,
                'report' => 94.00,
                'notes_dudi' => 'Teliti dan berintegritas tinggi dalam rekonsiliasi kas harian teller serta pencatatan jurnal transaksi operasional di Bank BSI KCP Garut.',
                'notes_teacher' => 'Siklus akuntansi lembaga keuangan dan laporan rekonsiliasi kas bank disajikan tanpa ada selisih neraca.',
            ],
            [
                'email' => 'fauzan.lps@smkn10garut.sch.id',
                'discipline' => 96.00,
                'technical' => 95.00,
                'initiative' => 93.00,
                'report' => 95.00,
                'notes_dudi' => 'Sangat menguasai rukun dan syarat akad pembiayaan Murabahah serta Ijarah perbankan syariah di Bank BSI. Pelayanan nasabah ramah dan profesional.',
                'notes_teacher' => 'Laporan pembiayaan syariah dan analisis kelayakan akad tersusun mendalam mencerminkan keunggulan jurusan Layanan Perbankan Syariah.',
            ],
            [
                'email' => 'diki.tkro@smkn10garut.sch.id',
                'discipline' => 91.00,
                'technical' => 93.00,
                'initiative' => 90.00,
                'report' => 91.00,
                'notes_dudi' => 'Terampil dalam penggantian kampas rem, servis radiator, dan tune-up kendaraan di Bengkel Bratayudha Motor Garut.',
                'notes_teacher' => 'Laporan pemeliharaan sistem pendingin dan pelumasan kendaraan ringan tersusun baik.',
            ],
            [
                'email' => 'sindi.tjkt@smkn10garut.sch.id',
                'discipline' => 94.00,
                'technical' => 92.00,
                'initiative' => 91.00,
                'report' => 93.00,
                'notes_dudi' => 'Tanggap dalam instalasi kabel LAN dan maintenance PC workstation di lingkungan Diskominfo Garut.',
                'notes_teacher' => 'Laporan topologi jaringan komputer lokal terdokumentasi rapi dan terstruktur.',
            ],
            [
                'email' => 'siti.lps@smkn10garut.sch.id',
                'discipline' => 95.00,
                'technical' => 94.00,
                'initiative' => 92.00,
                'report' => 94.00,
                'notes_dudi' => 'Cermat dalam memeriksa kelengkapan berkas legalitas pembiayaan mikro syariah dan akad wadiah di BPRS Garut.',
                'notes_teacher' => 'Penguasaan fikih muamalah perbankan syariah dan administrasi legal sangat memuaskan.',
            ],
            [
                'email' => 'desi.akl@smkn10garut.sch.id',
                'discipline' => 93.00,
                'technical' => 92.00,
                'initiative' => 91.00,
                'report' => 92.00,
                'notes_dudi' => 'Cekatan dalam menyusun bukti memorial dan laporan pembukuan kas kecil BPRS Garut.',
                'notes_teacher' => 'Laporan pembukuan keuangan lembaga syariah disajikan rapi sesuai standar akuntansi.',
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
            Assessment::updateOrCreate(
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
