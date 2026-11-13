<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Journal;
use App\Models\JournalApproval;
use App\Models\School;
use App\Models\StudentPkl;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class Smkn10GarutJournalSeeder extends Seeder
{
    /**
     * Seed Jurnal Harian Kejuruan dan Absensi Siswa SMKN 10 Garut (Otomotif, TI, DKV, Akuntansi & Perbankan Syariah).
     */
    public function run(): void
    {
        $school = School::where('npsn', '20262128')->firstOrFail();

        // 7 hari kerja terakhir secara dinamis
        $workdays = [];
        $cursor = Carbon::today()->subDay();
        while (count($workdays) < 7) {
            if (!$cursor->isWeekend()) {
                $workdays[] = $cursor->copy();
            }
            $cursor->subDay();
        }

        $journalDatasets = [
            // 1. Ilham Kurniawan (TKRO @ Auto2000 Toyota Garut)
            'ilham.tkro@smkn10garut.sch.id' => [
                [
                    'day_idx' => 0,
                    'status' => 'submitted',
                    'checkin' => '07:20:00',
                    'checkout' => '16:30:00',
                    'activity' => 'Melaksanakan servis berkala 20.000 km mobil Toyota Avanza: tune up mesin, penggantian oli mesin sintetik SAE 5W-30, filter oli, pembersihan throttle body, dan pengecekan celah elektroda busi iridium.',
                    'checkout_notes' => 'Pekerjaan stall 3 selesai dan checklist servis tervalidasi.',
                    'mentor_notes' => null,
                    'guru_notes' => null,
                ],
                [
                    'day_idx' => 1,
                    'status' => 'review_guru',
                    'checkin' => '07:15:20',
                    'checkout' => '16:35:00',
                    'activity' => 'Pemeriksaan dan perawatan sistem pengereman cakram depan (disc brake) dan tromol belakang: pengukuran ketebalan brake pad dengan jangka sorong, pembersihan brake shoe, serta bleeding minyak rem DOT 4.',
                    'checkout_notes' => 'Pengereman pakem dan tidak ada kebocoran master rem.',
                    'mentor_notes' => 'Siswa teliti dan mematuhi SOP keselamatan bengkel resmi Toyota.',
                    'guru_notes' => null,
                ],
                [
                    'day_idx' => 2,
                    'status' => 'approved',
                    'checkin' => '07:18:45',
                    'checkout' => '16:40:00',
                    'activity' => 'Diagnosis sistem Electronic Fuel Injection (EFI) dan sensor ABS menggunakan Toyota Intelligent Tester/OBD-II scanner. Membaca Diagnostic Trouble Code (DTC) dan menganalisis live data sensor MAF serta O2 sensor.',
                    'checkout_notes' => 'DTC clear dan putaran idle mesin normal di 750 RPM.',
                    'mentor_notes' => 'Kemampuan membaca data scanner diagnostik sangat memuaskan.',
                    'guru_notes' => 'Penerapan teknologi diagnostik scan tool memenuhi kompetensi engine management TKRO.',
                ],
                [
                    'day_idx' => 3,
                    'status' => 'approved',
                    'checkin' => '07:22:10',
                    'checkout' => '16:30:00',
                    'activity' => 'Overhaul unit kopling manual (clutch replacement) pada Toyota Kijang Innova: memeriksa keausan kampas kopling, runout fly-wheel, dan mengganti release bearing serta pilot bearing.',
                    'checkout_notes' => 'Transmisi terpasang kembali presisi dan pedal kopling empuk.',
                    'mentor_notes' => 'Keterampilan mekanik pembongkaran transmisi rapi dan teratur.',
                    'guru_notes' => 'Kompetensi sistem pemindah tenaga (power train) tercapai sempurna.',
                ],
            ],

            // 2. Sandi Permana (TBSM @ AHASS Daya Motor Garut)
            'sandi.tbsm@smkn10garut.sch.id' => [
                [
                    'day_idx' => 0,
                    'status' => 'submitted',
                    'checkin' => '07:25:00',
                    'checkout' => '16:30:00',
                    'activity' => 'Servis sistem CVT sepeda motor matic Honda Vario 160: pembersihan pulley primary dan secondary, pengecekan ketebalan roller weight, pengukuran lebar v-belt, dan pelumasan grease high-temp.',
                    'checkout_notes' => 'Penyebab gredeg pada putaran awal teratasi.',
                    'mentor_notes' => null,
                    'guru_notes' => null,
                ],
                [
                    'day_idx' => 1,
                    'status' => 'approved',
                    'checkin' => '07:12:40',
                    'checkout' => '16:35:00',
                    'activity' => 'Melakukan reset ECU/ECM dan kalibrasi sensor Throttle Position (TP) sepeda motor Honda BeAT eSP menggunakan Honda Diagnostic Tool (HIDS) setelah pembersihan injector ultrasonic.',
                    'checkout_notes' => 'Respons akselerasi motor kembali prima dan hemat bahan bakar.',
                    'mentor_notes' => 'Memahami prosedur penggunaan diagnostic tool AHASS dengan baik.',
                    'guru_notes' => 'Kompetensi sistem injeksi PGM-FI sepeda motor terlaksana sesuai standar industri.',
                ],
                [
                    'day_idx' => 2,
                    'status' => 'approved',
                    'checkin' => '07:19:15',
                    'checkout' => '16:30:00',
                    'activity' => 'Penyetelan celah katup hisap dan buang (valve clearance) motor Honda Supra X 125 menggunakan feeler gauge pada posisi top kompresi sesuai spesifikasi standar pabrikan (0.05 mm ± 0.02 mm).',
                    'checkout_notes' => 'Suara mesin halus tanpa suara ketukan klep.',
                    'mentor_notes' => 'Pengukuran celah klep sangat presisi dan teliti.',
                    'guru_notes' => 'Kompetensi tune-up mekanisme katup motor 4-tak tercapai baik.',
                ],
            ],

            // 3. Rifki Fauzan (TJKT @ PT Telkom Unit Jaringan Garut)
            'rifki.tjkt@smkn10garut.sch.id' => [
                [
                    'day_idx' => 0,
                    'status' => 'submitted',
                    'checkin' => '07:28:10',
                    'checkout' => '16:35:00',
                    'activity' => 'Instalasi dan terminasi kabel jaringan UTP Cat6 pada patch panel server rack data room serta labeling kabel sesuai skema perkabelan ANSI/TIA-568-B.',
                    'checkout_notes' => '16 port patch panel selesai diterminasi rapi.',
                    'mentor_notes' => null,
                    'guru_notes' => null,
                ],
                [
                    'day_idx' => 1,
                    'status' => 'approved',
                    'checkin' => '07:14:30',
                    'checkout' => '16:40:00',
                    'activity' => 'Konfigurasi hotspot gateway MikroTik RouterOS: manajemen bandwidth dengan Simple Queue, pembagian segmen IP DHCP server, dan pembuatan firewall filter anti brute force.',
                    'checkout_notes' => 'Hotspot guest dan staff terpisah aman.',
                    'mentor_notes' => 'Konfigurasi router MikroTik rapi dan firewall rule bekerja optimal.',
                    'guru_notes' => 'Penerapan administrasi infrastruktur jaringan sesuai materi TJKT.',
                ],
                [
                    'day_idx' => 2,
                    'status' => 'approved',
                    'checkin' => '07:20:45',
                    'checkout' => '16:30:00',
                    'activity' => 'Pengujian performa throughput dan jitter koneksi jaringan lokal wireless menggunakan tool iperf3 dan analisis spektrum interferensi frekuensi 2.4 GHz & 5 GHz.',
                    'checkout_notes' => 'Throughput stabil di 92 Mbps tanpa packet loss.',
                    'mentor_notes' => 'Analisis konektivitas nirkabel sangat komprehensif.',
                    'guru_notes' => 'Pengujian bandwidth jaringan lokal terstruktur ilmiah.',
                ],
            ],

            // 4. Firman Syahputra (DKV @ Percetakan Duta Media Garut)
            'firman.dkv@smkn10garut.sch.id' => [
                [
                    'day_idx' => 0,
                    'status' => 'revision',
                    'checkin' => '07:30:00',
                    'checkout' => '16:30:00',
                    'activity' => 'Mendesain spanduk promosi UMKM Cilawu di CorelDraw.',
                    'checkout_notes' => 'Selesai 1 file banner.',
                    'mentor_notes' => 'Deskripsi kegiatan terlalu singkat. Mohon cantumkan ukuran media cetak, color profile CMYK yang digunakan, serta resolusi output file.',
                    'guru_notes' => 'Perbaiki jurnal dengan merinci proses pra-cetak (pre-press) yang dilakukan.',
                ],
                [
                    'day_idx' => 1,
                    'status' => 'approved',
                    'checkin' => '07:18:20',
                    'checkout' => '16:35:00',
                    'activity' => 'Pembuatan layout booklet profil instansi dan kalender dinding 2027 menggunakan Adobe InDesign dengan standar pre-press resolusi 300 DPI, margin bleed 3mm, dan color mode CMYK.',
                    'checkout_notes' => 'File siap cetak (print ready PDF) selesai tervalidasi.',
                    'mentor_notes' => 'Layout tipografi rapi dan siap masuk ke mesin cetak tanpa error trapping.',
                    'guru_notes' => 'Karya tata letak media cetak memenuhi standar industri grafika.',
                ],
                [
                    'day_idx' => 2,
                    'status' => 'approved',
                    'checkin' => '07:22:15',
                    'checkout' => '16:45:00',
                    'activity' => 'Pengoperasian mesin cetak digital outdoor format besar (large format plotter): mengatur kalibrasi printhead, alignment bahan vinyl banner, dan pengecekan akurasi densitas warna cetak.',
                    'checkout_notes' => 'Cetak 3 roll banner tuntas dengan warna tajam.',
                    'mentor_notes' => 'Terampil mengoperasikan software RIP dan mesin digital printing.',
                    'guru_notes' => 'Keterampilan produksi cetak digital melengkapi kompetensi DKV.',
                ],
            ],

            // 5. Naufal Azhar (AKL @ Bank BSI KCP Garut)
            'naufal.akl@smkn10garut.sch.id' => [
                [
                    'day_idx' => 0,
                    'status' => 'submitted',
                    'checkin' => '07:12:00',
                    'checkout' => '16:30:00',
                    'activity' => 'Verifikasi kelengkapan dokumen pembukaan rekening tabungan wadiah nasabah baru dan pencocokan data identitas e-KTP pada aplikasi front-end core banking BSI.',
                    'checkout_notes' => '18 berkas nasabah baru selesai diotorisasi.',
                    'mentor_notes' => null,
                    'guru_notes' => null,
                ],
                [
                    'day_idx' => 1,
                    'status' => 'approved',
                    'checkin' => '07:10:30',
                    'checkout' => '16:35:00',
                    'activity' => 'Rekonsiliasi harian kas teller cabang Bank BSI: menghitung fisik uang tunai pada brankas teller box, pencocokan dengan slip setoran/tarikan, dan penutupan buku kas harian.',
                    'checkout_notes' => 'Fisik kas teller balance dengan laporan sistem.',
                    'mentor_notes' => 'Siswa berintegritas tinggi, teliti, dan disiplin dalam penghitungan kas tunai.',
                    'guru_notes' => 'Praktek rekonsiliasi kas perbankan syariah terlaksana sangat akurat.',
                ],
                [
                    'day_idx' => 2,
                    'status' => 'approved',
                    'checkin' => '07:15:00',
                    'checkout' => '16:40:00',
                    'activity' => 'Pencatatan jurnal memorial bagi hasil deposito mudharabah bulanan serta verifikasi perhitungan amortisasi biaya operasional cabang.',
                    'checkout_notes' => 'Jurnal penyesuaian tersusun rapi.',
                    'mentor_notes' => 'Memahami perlakuan akuntansi transaksi syariah PSAK 105 dan 106.',
                    'guru_notes' => 'Kompetensi akuntansi lembaga keuangan syariah tercapai prima.',
                ],
            ],

            // 6. Ahmad Fauzan (LPS - Layanan Perbankan Syariah @ Bank BSI KCP Garut)
            'fauzan.lps@smkn10garut.sch.id' => [
                [
                    'day_idx' => 0,
                    'status' => 'submitted',
                    'checkin' => '07:14:00',
                    'checkout' => '16:30:00',
                    'activity' => 'Memberikan layanan konsultasi produk pembiayaan syariah kepada calon nasabah: penjelasan skema akad Murabahah (jual-beli) kepemilikan kendaraan dan simulasi tabel margin tetap.',
                    'checkout_notes' => '4 calon nasabah terlayani dengan simulasi pembiayaan.',
                    'mentor_notes' => null,
                    'guru_notes' => null,
                ],
                [
                    'day_idx' => 1,
                    'status' => 'approved',
                    'checkin' => '07:08:45',
                    'checkout' => '16:35:00',
                    'activity' => 'Verifikasi dan validasi kelengkapan berkas legalitas permohonan pembiayaan mikro usaha syariah: mengecek SIUP/NIB, rekening koran 3 bulan, dan kelayakan dokumen agunan fidusia.',
                    'checkout_notes' => 'Dokumen 3 debitur siap diajukan ke komite pembiayaan.',
                    'mentor_notes' => 'Cermat dan teliti dalam memverifikasi keabsahan dokumen perbankan syariah.',
                    'guru_notes' => 'Kompetensi analisis pembiayaan syariah dan layanan nasabah terpenuhi.',
                ],
                [
                    'day_idx' => 2,
                    'status' => 'approved',
                    'checkin' => '07:11:20',
                    'checkout' => '16:30:00',
                    'activity' => 'Penginputan data akad pembiayaan Ijarah Multijasa (sewa-menyewa manfaat) ke aplikasi pembiayaan BSI serta pengarsipan fisik dokumen perjanjian akad di ruang khasanah berkas.',
                    'checkout_notes' => 'Arsip akad tersusun aman dan bernomor warkah.',
                    'mentor_notes' => 'Rapi dalam tata kelola dokumen legal dan pemahaman rukun akad syariah.',
                    'guru_notes' => 'Penguasaan fikih muamalah dan administrasi perbankan syariah sangat baik.',
                ],
            ],
        ];

        foreach ($journalDatasets as $studentEmail => $entries) {
            $user = User::where('email', $studentEmail)->first();
            if (!$user || !$user->student) {
                continue;
            }

            $spkl = StudentPkl::where('student_id', $user->student->id)
                ->where('school_id', $school->id)
                ->first();

            if (!$spkl) {
                continue;
            }

            $supervisor = $spkl->supervisor?->user;
            $teacher = $spkl->teacher?->user;

            foreach ($entries as $item) {
                $dayDate = $workdays[$item['day_idx']] ?? Carbon::today();
                $dateStr = $dayDate->toDateString();

                // 1. Catat / Update Absensi
                $attendance = Attendance::where('student_pkl_id', $spkl->id)
                    ->where(function ($q) use ($dateStr) {
                        $q->where('date', $dateStr)
                          ->orWhere('date', 'like', $dateStr . '%');
                    })
                    ->first();

                if (!$attendance) {
                    $attendance = new Attendance();
                    $attendance->school_id = $school->id;
                    $attendance->student_pkl_id = $spkl->id;
                    $attendance->date = $dateStr;
                }

                $attendance->status = 'hadir';
                $attendance->checkin_time = $item['checkin'];
                $attendance->checkin_photo_path = null;
                $attendance->checkin_lat = $spkl->company->latitude ?? -7.2182;
                $attendance->checkin_lng = $spkl->company->longitude ?? 107.9045;
                $attendance->is_within_radius = true;
                $attendance->accuracy_meters = 15;
                $attendance->is_mock_detected = false;
                $attendance->checkout_time = $item['checkout'];
                $attendance->checkout_notes = $item['checkout_notes'];
                $attendance->save();

                // 2. Catat / Update Jurnal Kegiatan
                $journal = Journal::where('student_pkl_id', $spkl->id)
                    ->where(function ($q) use ($dateStr) {
                        $q->where('date', $dateStr)
                          ->orWhere('date', 'like', $dateStr . '%');
                    })
                    ->first();

                if (!$journal) {
                    $journal = new Journal();
                    $journal->school_id = $school->id;
                    $journal->student_pkl_id = $spkl->id;
                    $journal->date = $dateStr;
                }

                $journal->activity_description = $item['activity'];
                $journal->status = $item['status'];
                $journal->save();

                // 3. Catat Riwayat Approval
                if ($item['status'] === 'approved' && $supervisor) {
                    JournalApproval::updateOrCreate(
                        [
                            'journal_id' => $journal->id,
                            'role_level' => 'pembimbing-dudi',
                        ],
                        [
                            'approver_id' => $supervisor->id,
                            'status' => 'approved',
                            'notes' => $item['mentor_notes'] ?? 'Kegiatan PKL disetujui.',
                            'created_at' => $dayDate->copy()->setTime(17, 15),
                        ]
                    );

                    if ($teacher) {
                        JournalApproval::updateOrCreate(
                            [
                                'journal_id' => $journal->id,
                                'role_level' => 'guru-pembimbing',
                            ],
                            [
                                'approver_id' => $teacher->id,
                                'status' => 'approved',
                                'notes' => $item['guru_notes'] ?? 'Laporan kegiatan diverifikasi memenuhi kriteria kejuruan.',
                                'created_at' => $dayDate->copy()->setTime(19, 30),
                            ]
                        );
                    }
                } elseif ($item['status'] === 'review_guru' && $supervisor) {
                    JournalApproval::updateOrCreate(
                        [
                            'journal_id' => $journal->id,
                            'role_level' => 'pembimbing-dudi',
                        ],
                        [
                            'approver_id' => $supervisor->id,
                            'status' => 'approved',
                            'notes' => $item['mentor_notes'] ?? 'Disetujui oleh pembimbing industri.',
                            'created_at' => $dayDate->copy()->setTime(17, 15),
                        ]
                    );
                } elseif ($item['status'] === 'revision') {
                    if ($supervisor) {
                        JournalApproval::updateOrCreate(
                            [
                                'journal_id' => $journal->id,
                                'role_level' => 'pembimbing-dudi',
                            ],
                            [
                                'approver_id' => $supervisor->id,
                                'status' => 'revision',
                                'notes' => $item['mentor_notes'] ?? 'Mohon lengkapi data pekerjaan teknis.',
                                'created_at' => $dayDate->copy()->setTime(17, 00),
                            ]
                        );
                    }
                    if ($teacher) {
                        JournalApproval::updateOrCreate(
                            [
                                'journal_id' => $journal->id,
                                'role_level' => 'guru-pembimbing',
                            ],
                            [
                                'approver_id' => $teacher->id,
                                'status' => 'revision',
                                'notes' => $item['guru_notes'] ?? 'Silakan lakukan revisi sesuai catatan DU/DI.',
                                'created_at' => $dayDate->copy()->setTime(19, 00),
                            ]
                        );
                    }
                }
            }
        }
    }
}
