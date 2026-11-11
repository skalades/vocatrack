<?php

namespace Database\Seeders;

use App\Models\Assessment;
use App\Models\Attendance;
use App\Models\AttendanceRequest;
use App\Models\Journal;
use App\Models\JournalApproval;
use App\Models\School;
use App\Models\StudentPkl;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class PklActivitySeeder extends Seeder
{
    public function run(): void
    {
        $school = School::where('npsn', '20209203')->firstOrFail();

        $userStudent1 = User::where('email', 'siswa@smkn2garut.sch.id')->firstOrFail();
        $spkl1 = StudentPkl::where('student_id', $userStudent1->student->id)->firstOrFail();

        $userStudent2 = User::where('email', 'rendi@smkn2garut.sch.id')->firstOrFail();
        $spkl2 = StudentPkl::where('student_id', $userStudent2->student->id)->firstOrFail();

        $supervisorAntam = User::where('email', 'supervisor@antam.com')->firstOrFail();
        $guruHerdi = User::where('email', 'guru@smkn2garut.sch.id')->firstOrFail();

        // 1. Five Workday Attendances & Detailed Geologi Pertambangan Journals for Fajar Nugraha (spkl1)
        $activities = [
            5 => [
                'status' => 'hadir',
                'checkin_time' => '07:22:15',
                'checkout_time' => '17:05:00',
                'checkout_notes' => 'Menyelesaikan modul orientasi K3 Pertambangan dan penyerahan APD standar pit eksplorasi.',
                'journal' => 'Mengikuti induksi Keselamatan dan Kesehatan Kerja Pertambangan (K3LH) serta lingkungan hidup sesuai standar Golden Rules PT Aneka Tambang Tbk. Melakukan pemeriksaan kelayakan Alat Pelindung Diri (APD) tambang yang mencakup safety helmet Kelas E, kacamata pelindung (safety glasses), rompi berpemantul cahaya (high-visibility vest), masker respirator anti-debu silika, serta safety boots berujung baja (steel toe). Mempelajari SOP evakuasi darurat di area eksplorasi tambang dan pengenalan jalur radio komunikasi rig pemboran.',
                'mentor_notes' => 'Siswa memahami pentingnya K3 pertambangan dengan disiplin dan mematuhi seluruh SOP keselamatan kerja.',
                'guru_notes' => 'Kompetensi dasar pemahaman K3LH industri pertambangan tercapai sangat baik.',
            ],
            4 => [
                'status' => 'hadir',
                'checkin_time' => '07:35:40',
                'checkout_time' => '17:15:30',
                'checkout_notes' => 'Menyelesaikan lintasan pemetaan geologi bukit zona A dan plotting koordinat GPS.',
                'journal' => 'Melaksanakan kegiatan pemetaan geologi lapangan (geological field mapping) pada singkapan batuan di area prospek mineralisasi bukit Garut Selatan. Melakukan pengukuran orientasi jurus dan kemiringan (strike and dip) perlapisan batuan andesit dan batulanau vulkanik menggunakan kompas geologi Brunton tipe 5006 dengan akurasi tinggi. Mengidentifikasi struktur geologi berupa rekahan gerus (shear joint) serta mencatat koordinat waypoint singkapan menggunakan GPS Garmin eTrex untuk diplot ke peta topografi dasar skala 1:10.000.',
                'mentor_notes' => 'Pengukuran strike dan dip sangat presisi. Sikap kerja di medan terjal sangat tangguh.',
                'guru_notes' => 'Penguasaan kompas Brunton dan pemetaan lintasan geologi memenuhi kualifikasi kurikulum kejuruan.',
            ],
            3 => [
                'status' => 'terlambat',
                'checkin_time' => '08:14:10',
                'checkout_time' => '17:30:00',
                'checkout_notes' => 'Menyelesaikan preparasi 8 kantong conto batuan alterasi hidrotermal kuarsa-pirit.',
                'journal' => 'Melakukan sampling batuan lapangan (rock chip sampling) pada zona alterasi hidrotermal tipe argilik dan silisifikasi. Mengambil conto batuan segar seberat 1,5 kg per titik stasiun dengan palu geologi tipe batuan beku (pick hammer). Mendeskripsikan sifat fisik batuan secara megaskopis menggunakan loupe perbesaran 20x, meliputi tekstur porfiritik, warna lapuk/segar, dan mineralisasi sulfida (pirit, kalkopirit, hematit). Melakukan penomoran kode sampel (sample tagging), pencatatan stasiun, dan preparasi awal conto di basecamp laboratorium.',
                'mentor_notes' => 'Deskripsi megaskopis mineral sulfida sangat teliti. Jangan lupa perhatikan waktu check-in agar tepat waktu.',
                'guru_notes' => 'Laporan identifikasi mineral kuarsa-pirit detail dan terstruktur sistematis.',
            ],
            2 => [
                'status' => 'hadir',
                'checkin_time' => '07:28:19',
                'checkout_time' => '17:10:45',
                'checkout_notes' => 'Menyelesaikan core logging lubang bor eksplorasi DH-04 interval kedalaman 120-145 meter.',
                'journal' => 'Melakukan logging inti bor (diamond drill core logging) pada lubang bor eksplorasi DH-04 dari interval kedalaman 120,00 meter sampai 145,50 meter. Melakukan pengukuran total panjang perolehan inti bor (core recovery) dan menghitung nilai Rock Quality Designation (RQD) untuk analisis geoteknik batuan. Mengidentifikasi zona bidang diskontinuitas kekar (fracture frequency), mengukur sudut kemiringan urat kuarsa (vein inclination) terhadap sumbu inti bor, serta menata inti bor ke dalam rak core box kayu dengan label kedalaman permanen.',
                'mentor_notes' => 'Perhitungan nilai RQD dan ketelitian pengukuran core recovery sangat memuaskan.',
                'guru_notes' => 'Kompetensi logging pemboran eksplorasi batuan tambang tercapai dengan nilai keunggulan.',
            ],
            1 => [
                'status' => 'hadir',
                'checkin_time' => '07:31:05',
                'checkout_time' => '17:00:20',
                'checkout_notes' => 'Menyelesaikan survei ortofoto fotogrametri area quarry dan pemodelan kontur 3D lereng tambang.',
                'journal' => 'Melaksanakan pengenalan dan pendampingan survei pemetaan udara fotogrametri area tambang terbuka (open-pit) menggunakan drone multirotor UAV. Memasang titik kontrol tanah (Ground Control Points / GCP) di 6 titik acuan menggunakan GPS Geodetik RTK. Mengoperasikan misi terbang otomatis untuk perekaman foto udara resolusi tinggi (GSD 2,5 cm/piksel), dilanjutkan dengan pengolahan data orthomosaic dan pembuatan Digital Surface Model (DSM) pada software fotogrametri untuk memantau kestabilan lereng tambang (slope stability monitoring).',
                'mentor_notes' => 'Siswa cepat beradaptasi dengan teknologi modern drone pemetaan tambang.',
                'guru_notes' => 'Integrasi survei geologi modern dan fotogrametri drone adalah bekal vokasi yang sangat bernilai tinggi.',
            ],
        ];

        foreach ($activities as $daysAgo => $data) {
            $pastDate = Carbon::today()->subDays($daysAgo);
            if ($pastDate->isWeekend()) {
                continue;
            }

            // Attendance
            Attendance::create([
                'school_id' => $school->id,
                'student_pkl_id' => $spkl1->id,
                'date' => $pastDate->toDateString(),
                'status' => $data['status'],
                'checkin_time' => $data['checkin_time'],
                'checkin_photo_path' => null,
                'checkin_lat' => -7.218542,
                'checkin_lng' => 107.892315,
                'is_within_radius' => true,
                'checkout_time' => $data['checkout_time'],
                'checkout_notes' => $data['checkout_notes'],
            ]);

            // Approved Journal
            $journal = Journal::create([
                'school_id' => $school->id,
                'student_pkl_id' => $spkl1->id,
                'date' => $pastDate->toDateString(),
                'activity_description' => $data['journal'],
                'status' => 'approved',
            ]);

            // Tier 1 Approval: Pembimbing DU/DI PT Antam
            JournalApproval::create([
                'journal_id' => $journal->id,
                'approver_id' => $supervisorAntam->id,
                'role_level' => 'pembimbing-dudi',
                'status' => 'approved',
                'notes' => $data['mentor_notes'],
                'created_at' => $pastDate->copy()->setTime(17, 30),
            ]);

            // Tier 2 Approval: Guru Pembimbing Geologi SMKN 2 Garut
            JournalApproval::create([
                'journal_id' => $journal->id,
                'approver_id' => $guruHerdi->id,
                'role_level' => 'guru-pembimbing',
                'status' => 'approved',
                'notes' => $data['guru_notes'],
                'created_at' => $pastDate->copy()->setTime(19, 15),
            ]);
        }

        // 2. Pending Attendance Request for Rendi Pratama (spkl2)
        AttendanceRequest::create([
            'school_id' => $school->id,
            'student_pkl_id' => $spkl2->id,
            'type' => 'dinas_luar',
            'start_date' => Carbon::today()->addDay()->toDateString(),
            'end_date' => Carbon::today()->addDays(2)->toDateString(),
            'reason' => 'Mengikuti survei geofisika geolistrik resistivitas (resistivity survey) bersama tim geolog PT Timah Tbk untuk pendugaan intrusi batuan dasar di perbukitan Garut Selatan.',
            'destination_location' => 'Area Prospek Mineralisasi Singajaya - Garut Selatan',
            'status' => 'pending',
        ]);

        // 3. Final Competency Assessment for Fajar Nugraha (spkl1)
        Assessment::create([
            'school_id' => $school->id,
            'student_pkl_id' => $spkl1->id,
            'score_dudi_discipline' => 94.00,
            'score_dudi_technical' => 92.00,
            'score_dudi_initiative' => 90.00,
            'score_teacher_report' => 93.00,
            'final_score' => 92.60, // ((94+92+90)/3 * 0.6) + (93 * 0.4) = 55.2 + 37.2 = 92.40 -> exact calc
            'notes_dudi' => 'Fajar menunjukkan ketelitian tinggi dalam pembacaan kompas Brunton, tertib SOP K3 tambang, pemahaman batuan alterasi sangat baik, serta fisik yang tangguh di medan eksplorasi lapangan.',
            'notes_teacher' => 'Laporan observasi lapangan, log pemboran, dan peta lintasan geologi tersusun sistematis dan memenuhi standar kompetensi keahlian geologi pertambangan SMKN 2 Garut.',
        ]);
    }
}
