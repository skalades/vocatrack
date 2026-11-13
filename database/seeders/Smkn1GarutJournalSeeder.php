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

class Smkn1GarutJournalSeeder extends Seeder
{
    /**
     * Seed Jurnal Harian dan Absensi Siswa SMKN 1 Garut lintas kompetensi keahlian.
     * Mengikuti enum valid: 'draft', 'submitted', 'review_guru', 'approved', 'revision'.
     * Approval mengikuti enum: 'approved', 'revision'.
     */
    public function run(): void
    {
        $school = School::where('npsn', '20209201')->firstOrFail();

        // Ambil 7 hari kerja terakhir secara dinamis
        $workdays = [];
        $cursor = Carbon::today()->subDay();
        while (count($workdays) < 7) {
            if (!$cursor->isWeekend()) {
                $workdays[] = $cursor->copy();
            }
            $cursor->subDay();
        }

        // Data aktivitas jurnal kejuruan per siswa SMKN 1 Garut
        $journalDatasets = [
            // 1. Muhammad Raihan Pratama (PPLG - Diskominfo Garut)
            'raihan.pplg@smkn1garut.sch.id' => [
                [
                    'day_idx' => 0, // Hari kerja terakhir
                    'status' => 'submitted',
                    'checkin' => '07:22:10',
                    'checkout' => '16:30:15',
                    'activity' => 'Melakukan perancangan dan implementasi endpoint RESTful API layanan aduan masyarakat Garut Satu Data menggunakan Laravel 11. Menguji respons JSON, validasi request form, dan format status HTTP via Postman.',
                    'checkout_notes' => 'Selesai 3 endpoint CRUD utama.',
                    'mentor_notes' => null,
                    'guru_notes' => null,
                ],
                [
                    'day_idx' => 1,
                    'status' => 'review_guru',
                    'checkin' => '07:18:40',
                    'checkout' => '16:35:00',
                    'activity' => 'Mengimplementasikan middleware autentikasi dan penanganan otorisasi berbasis Role-Based Access Control (RBAC) pada modul sistem informasi perizinan terpadu Diskominfo.',
                    'checkout_notes' => 'Testing otorisasi admin dan operator berhasil.',
                    'mentor_notes' => 'Struktur arsitektur API rapi dan mematuhi best practice PSR-12.',
                    'guru_notes' => null,
                ],
                [
                    'day_idx' => 2,
                    'status' => 'approved',
                    'checkin' => '07:25:15',
                    'checkout' => '16:40:20',
                    'activity' => 'Optimasi query database PostgreSQL pada tabel log rekapitulasi data sektoral. Menambahkan composite index dan memangkas waktu eksekusi query dari 320ms menjadi 45ms.',
                    'checkout_notes' => 'Query explain analyze tuntas diverifikasi.',
                    'mentor_notes' => 'Kemampuan optimasi basis data sangat baik dan membantu performa server.',
                    'guru_notes' => 'Analisis query tuning sangat mendalam untuk level siswa kejuruan.',
                ],
                [
                    'day_idx' => 3,
                    'status' => 'approved',
                    'checkin' => '07:15:30',
                    'checkout' => '16:30:00',
                    'activity' => 'Membangun komponen antarmuka dasbor visualisasi data demografi penduduk Garut menggunakan Blade, Tailwind CSS, dan Chart.js dengan desain responsif mobile.',
                    'checkout_notes' => 'Komponen grafik donat dan batang selesai.',
                    'mentor_notes' => 'Desain UI clean, modern, dan ramah pengguna.',
                    'guru_notes' => 'Konsistensi styling UI dan interaktivitas chart terpenuhi sangat baik.',
                ],
                [
                    'day_idx' => 4,
                    'status' => 'approved',
                    'checkin' => '07:20:00',
                    'checkout' => '16:45:00',
                    'activity' => 'Mengikuti sprint review mingguan tim IT Diskominfo Garut, melakukan push branch git ke remote repository GitHub, serta menyelesaikan pull request (PR) modul laporan bulanan.',
                    'checkout_notes' => 'PR merged ke branch staging.',
                    'mentor_notes' => 'Kolaborasi git workflow sangat disiplin dan rapi.',
                    'guru_notes' => 'Budaya kerja tim agile dan version control terserap optimal.',
                ],
            ],

            // 2. Ilham Ramadhan (TJKT - PT Telkom Witel Garut)
            'ilham.tjkt@smkn1garut.sch.id' => [
                [
                    'day_idx' => 0,
                    'status' => 'submitted',
                    'checkin' => '07:28:15',
                    'checkout' => '16:35:00',
                    'activity' => 'Melakukan pemantauan utilisasi bandwidth uplink pelanggan enterprise segmen perbankan pada dashboard Network Operation Center (NOC) Telkom Garut.',
                    'checkout_notes' => 'Laporan status traffic harian normal.',
                    'mentor_notes' => null,
                    'guru_notes' => null,
                ],
                [
                    'day_idx' => 1,
                    'status' => 'approved',
                    'checkin' => '07:14:20',
                    'checkout' => '16:40:00',
                    'activity' => 'Konfigurasi VLAN trunking, Access Control List (ACL), dan inter-VLAN routing pada managed switch Cisco Catalyst untuk segmentasi jaringan kantor Telkom.',
                    'checkout_notes' => 'Konfigurasi switch rack B lantai 2 selesai.',
                    'mentor_notes' => 'Konfigurasi IP addressing dan trunking tepat tanpa ada looping jaringan.',
                    'guru_notes' => 'Praktik segmentasi VLAN menguatkan kompetensi infrastruktur TJKT.',
                ],
                [
                    'day_idx' => 2,
                    'status' => 'approved',
                    'checkin' => '07:22:50',
                    'checkout' => '16:45:30',
                    'activity' => 'Melakukan penyambungan kabel fiber optik (optical fusion splicing) 24 core pada Optical Distribution Cabinet (ODC) area Tarogong menggunakan splicer otomatis Sumitomo.',
                    'checkout_notes' => 'Nilai redaman splice rata-rata 0.02 dB.',
                    'mentor_notes' => 'Teknik cleaving dan splicing sangat presisi sesuai standar Telkom Akses.',
                    'guru_notes' => 'Keterampilan fisik pemotongan dan penyambungan serat optik sangat memuaskan.',
                ],
                [
                    'day_idx' => 3,
                    'status' => 'approved',
                    'checkin' => '07:19:10',
                    'checkout' => '16:30:00',
                    'activity' => 'Pengukuran kualitas redaman sinyal optik kabel drop core pelanggan menggunakan Optical Power Meter (OPM) dan pelacakan titik bending kabel dengan Visual Fault Locator (VFL).',
                    'checkout_notes' => 'Titik redaman tinggi berhasil diatasi.',
                    'mentor_notes' => 'Pemahaman troubleshooting sinyal optik sangat baik.',
                    'guru_notes' => 'Penggunaan instrumen OPM dan VFL akurat.',
                ],
            ],

            // 3. Fauzan Maulana (DKV - Berdnations Studio)
            'fauzan.dkv@smkn1garut.sch.id' => [
                [
                    'day_idx' => 0,
                    'status' => 'submitted',
                    'checkin' => '07:35:00',
                    'checkout' => '16:40:00',
                    'activity' => 'Menyusun moodboard visual, palet warna, dan wireframe layout desain kemasan (packaging design) untuk produk kopi khas Garut di Adobe Illustrator.',
                    'checkout_notes' => 'Konsep 1 dan 2 selesai dipresentasikan.',
                    'mentor_notes' => null,
                    'guru_notes' => null,
                ],
                [
                    'day_idx' => 1,
                    'status' => 'approved',
                    'checkin' => '07:20:10',
                    'checkout' => '16:30:00',
                    'activity' => 'Digital imaging dan photo retouching materi kampanye produk fesyen kulit Sukaregang Garut menggunakan Adobe Photoshop dengan teknik frequency separation.',
                    'checkout_notes' => 'Retouch 12 foto katalog komersial selesai.',
                    'mentor_notes' => 'Detail pencahayaan dan kontur tekstur kulit produk terjaga natural.',
                    'guru_notes' => 'Penerapan prinsip komposisi visual dan pewarnaan digital memenuhi standar industri.',
                ],
                [
                    'day_idx' => 2,
                    'status' => 'approved',
                    'checkin' => '07:26:45',
                    'checkout' => '16:45:00',
                    'activity' => 'Produksi animasi bumper video dan lower-third grafis 60 fps menggunakan Adobe After Effects untuk media sosial promosi pariwisata Kabupaten Garut.',
                    'checkout_notes' => 'Render format ProRes dan MP4 H.264 selesai.',
                    'mentor_notes' => 'Timing motion grafik dinamis dan tipografi selaras dengan brand identity.',
                    'guru_notes' => 'Karya motion graphics kreatif dan memiliki nilai jual profesional.',
                ],
            ],

            // 4. Rizky Wahyudi (AKL - Bank bjb Cabang Garut)
            'rizky.akl@smkn1garut.sch.id' => [
                [
                    'day_idx' => 0,
                    'status' => 'submitted',
                    'checkin' => '07:15:00',
                    'checkout' => '16:30:00',
                    'activity' => 'Melakukan rekonsiliasi harian mutasi rekening koran nasabah korporat dan verifikasi pencocokan transaksi clearing pada sistem internal perbankan.',
                    'checkout_notes' => 'Seluruh mutasi kliring klop tanpa selisih.',
                    'mentor_notes' => null,
                    'guru_notes' => null,
                ],
                [
                    'day_idx' => 1,
                    'status' => 'approved',
                    'checkin' => '07:10:30',
                    'checkout' => '16:35:00',
                    'activity' => 'Pencatatan jurnal umum debet-kredit transaksi operasional kas kecil (petty cash) cabang dan verifikasi kelengkapan bukti transaksi/kuitansi pengeluaran.',
                    'checkout_notes' => 'Rekapitulasi kas kecil minggu ke-2 selesai.',
                    'mentor_notes' => 'Siswa teliti dalam mengidentifikasi pos akun dan bukti pendukung.',
                    'guru_notes' => 'Penerapan siklus akuntansi operasional lembaga keuangan sangat tepat.',
                ],
                [
                    'day_idx' => 2,
                    'status' => 'approved',
                    'checkin' => '07:12:00',
                    'checkout' => '16:40:00',
                    'activity' => 'Verifikasi dan pengarsipan bukti potong PPh Pasal 23 serta pencocokan nomor e-Faktur pajak masukan pengadaan perlengkapan kantor cabang.',
                    'checkout_notes' => 'Arsip faktur pajak terorganisir rapi.',
                    'mentor_notes' => 'Disiplin dan memahami ketelitian administrasi perpajakan perbankan.',
                    'guru_notes' => 'Kompetensi perpajakan dan ketelitian data akuntansi tercapai.',
                ],
            ],

            // 5. Dea Ananda (MPLB - Kantor Pertanahan / BPN Garut)
            'dea.mplb@smkn1garut.sch.id' => [
                [
                    'day_idx' => 0,
                    'status' => 'revision',
                    'checkin' => '07:25:00',
                    'checkout' => '16:30:00',
                    'activity' => 'Scan dokumen warkah sertifikat tanah pemohon program PTSL.',
                    'checkout_notes' => 'Selesai 1 box berkas.',
                    'mentor_notes' => 'Deskripsi terlalu singkat. Mohon cantumkan jumlah dokumen yang berhasil divalidasi serta nomor seri buku tanah yang diindeks.',
                    'guru_notes' => 'Silakan perbaiki jurnal dengan menambahkan rincian prosedur kearsipan digital yang dilakukan.',
                ],
                [
                    'day_idx' => 1,
                    'status' => 'approved',
                    'checkin' => '07:16:40',
                    'checkout' => '16:35:00',
                    'activity' => 'Digitalisasi dan validasi 45 berkas warkah sertifikat tanah program PTSL Desa Tarogong ke dalam aplikasi Komputerisasi Kantor Pertanahan (KKP) BPN.',
                    'checkout_notes' => '45 berkas terunggah lengkap dengan metadata buku tanah.',
                    'mentor_notes' => 'Input data akurat dan teliti dalam memverifikasi nomor hak milik.',
                    'guru_notes' => 'Kompetensi kearsipan digital dan tata naskah perkantoran terpenuhi.',
                ],
                [
                    'day_idx' => 2,
                    'status' => 'approved',
                    'checkin' => '07:18:10',
                    'checkout' => '16:30:00',
                    'activity' => 'Penerimaan surat masuk kedinasan, penomoran agenda persuratan, serta penyiapan lembar disposisi pimpinan kantor pertanahan.',
                    'checkout_notes' => '18 surat masuk selesai diagendakan.',
                    'mentor_notes' => 'Sikap sopan dan cermat dalam mengelola lalu lintas surat dinas.',
                    'guru_notes' => 'Tata kelola persuratan dinas dijalankan sesuai standar SOP.',
                ],
            ],

            // 6. Fikri Haikal (TET - PT Pertamina Geothermal Energy Kamojang)
            'fikri.tet@smkn1garut.sch.id' => [
                [
                    'day_idx' => 0,
                    'status' => 'approved',
                    'checkin' => '07:10:00',
                    'checkout' => '16:30:00',
                    'activity' => 'Monitoring harian parameter operasional turbin pembangkit listrik tenaga panas bumi (PLTP) Unit 4: mencatat tekanan steam (6.5 bar), laju alir uap, dan temperatur pendingin kondensor.',
                    'checkout_notes' => 'Log sheet parameter turbin diserahkan ke control room.',
                    'mentor_notes' => 'Kepatuhan K3 lapangan panas bumi sangat tinggi dan pencatatan presisi.',
                    'guru_notes' => 'Penguasaan instrumen pembangkit energi terbarukan sangat membanggakan.',
                ],
                [
                    'day_idx' => 1,
                    'status' => 'approved',
                    'checkin' => '07:12:30',
                    'checkout' => '16:45:00',
                    'activity' => 'Inspeksi berkala separator uap dan demister pada wellhead KMJ-14, pembersihan valve blowdown, serta pengamatan visual potensi kebocoran pipa transmisi fluida geotermal.',
                    'checkout_notes' => 'Kondisi separator bersih dan beroperasi stabil.',
                    'mentor_notes' => 'Siswa tanggap terhadap potensi anomali tekanan fluida panas bumi.',
                    'guru_notes' => 'Kompetensi pemeliharaan fasilitas energi terbarukan tercapai optimal.',
                ],
            ],

            // 7. Salma Aulia (TF - PT Kimia Farma Apotek & Diagnostika Garut)
            'salma.tf@smkn1garut.sch.id' => [
                [
                    'day_idx' => 0,
                    'status' => 'approved',
                    'checkin' => '07:24:00',
                    'checkout' => '16:30:00',
                    'activity' => 'Melakukan peracikan sediaan serbuk terbagi (pulveres/puyuh) antibiotik amoxicillin sesuai resep dokter spesialis anak, penulisan etiket putih, dan pengecekan dosis maksimum.',
                    'checkout_notes' => '15 bungkus puyuh selesai diracik higienis.',
                    'mentor_notes' => 'Teknik homogenisasi serbuk mortar-stemper rapi dan perhitungan dosis akurat.',
                    'guru_notes' => 'Ketelitian formulasi farmasi klinis memenuhi syarat kelulusan kejuruan.',
                ],
                [
                    'day_idx' => 1,
                    'status' => 'approved',
                    'checkin' => '07:20:15',
                    'checkout' => '16:35:00',
                    'activity' => 'Pengecekan tanggal kedaluwarsa (expired date) obat pada kartu stok fisik dan penataan obat sistem FEFO (First Expired First Out) serta pemisahan obat kategori LASA.',
                    'checkout_notes' => 'Area rak sediaan sirup dan tablet tuntas diatur.',
                    'mentor_notes' => 'Sangat memahami standar penyimpanan obat LASA dan cold chain.',
                    'guru_notes' => 'Manajemen logistik farmasi sesuai standar BPOM dan Kemenkes.',
                ],
            ],

            // 8. Farrel Alghifari (TLM - RSUD dr. Slamet Garut)
            'farrel.tlm@smkn1garut.sch.id' => [
                [
                    'day_idx' => 0,
                    'status' => 'approved',
                    'checkin' => '07:15:40',
                    'checkout' => '16:30:00',
                    'activity' => 'Pemeriksaan hematologi lengkap (CBC: eritrosit, leukosit, hemoglobin, hematokrit) menggunakan automated hematology analyzer Sysmex di laboratorium patologi klinik RSUD.',
                    'checkout_notes' => '32 sampel darah pasien tuntas diuji dan dicetak.',
                    'mentor_notes' => 'Prosedur kalibrasi instrumen dan penanganan tabung sampel K3EDTA sangat baik.',
                    'guru_notes' => 'Keahlian laboratorium klinik analitik sangat matang dan siap kerja.',
                ],
                [
                    'day_idx' => 1,
                    'status' => 'approved',
                    'checkin' => '07:18:20',
                    'checkout' => '16:40:00',
                    'activity' => 'Pembuatan preparat apusan darah tepi (blood smear), pewarnaan Giemsa 10%, dan pengamatan mikroskopis morfologi sel darah putih di bawah perbesaran 1000x immersion oil.',
                    'checkout_notes' => 'Preparat apusan tipis rata tanpa artefak.',
                    'mentor_notes' => 'Ketrampilan membuat apusan darah tipis sangat terampil dan bersih.',
                    'guru_notes' => 'Interpretasi visual mikroskopis sel darah memenuhi standar TLM.',
                ],
            ],

            // 9. Kevin Sanjaya (PMS - Yogya Toserba Garut)
            'kevin.pms@smkn1garut.sch.id' => [
                [
                    'day_idx' => 0,
                    'status' => 'approved',
                    'checkin' => '07:30:00',
                    'checkout' => '16:35:00',
                    'activity' => 'Penataan visual merchandising produk kategori makanan ringan pada rak gondola utama promo awal bulan dan pembaruan label harga elektronik (electronic shelf labeling).',
                    'checkout_notes' => 'Display gondola A1-A4 selesai rapi.',
                    'mentor_notes' => 'Display produk menarik perhatian konsumen dan penataan rapi.',
                    'guru_notes' => 'Penerapan strategi visual merchandising ritel modern sangat baik.',
                ],
            ],

            // 10. Bayu Setiawan (TLG - Yogya Toserba Garut / Logistik)
            'bayu.tlg@smkn1garut.sch.id' => [
                [
                    'day_idx' => 0,
                    'status' => 'approved',
                    'checkin' => '07:22:30',
                    'checkout' => '16:30:00',
                    'activity' => 'Pemeriksaan penerimaan barang inbound logistik dari gudang distribusi pusat, scanning barcode koli, dan pencocokan fisik barang dengan Surat Jalan / Delivery Order.',
                    'checkout_notes' => '240 karton barang masuk tuntas diverifikasi tanpa selisih.',
                    'mentor_notes' => 'Disiplin, cepat dalam verifikasi dokumen penerimaan barang gudang.',
                    'guru_notes' => 'Manajemen operasional pergudangan ritel tercapai sesuai silabus.',
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

                // 1. Catat / Update Absensi yang sesuai (menggunakan whereDate agar kompatibel dengan SQLite & MySQL)
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
                $attendance->checkin_lat = $spkl->company->latitude ?? -7.2188;
                $attendance->checkin_lng = $spkl->company->longitude ?? 107.8967;
                $attendance->is_within_radius = true;
                $attendance->accuracy_meters = 12;
                $attendance->is_mock_detected = false;
                $attendance->checkout_time = $item['checkout'];
                $attendance->checkout_notes = $item['checkout_notes'];
                $attendance->save();

                // 2. Catat / Update Jurnal Kegiatan (menggunakan query date yang fleksibel)
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

                // 3. Catat Riwayat Approval sesuai status
                if ($item['status'] === 'approved' && $supervisor) {
                    JournalApproval::updateOrCreate(
                        [
                            'journal_id' => $journal->id,
                            'role_level' => 'pembimbing-dudi',
                        ],
                        [
                            'approver_id' => $supervisor->id,
                            'status' => 'approved',
                            'notes' => $item['mentor_notes'] ?? 'Kegiatan PKL disetujui dengan catatan memuaskan.',
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
                                'notes' => $item['guru_notes'] ?? 'Laporan jurnal harian diverifikasi dan memenuhi standar kompetensi kejuruan.',
                                'created_at' => $dayDate->copy()->setTime(19, 30),
                            ]
                        );
                    }
                } elseif ($item['status'] === 'review_guru' && $supervisor) {
                    // Approved by DU/DI, awaiting Guru
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
                                'notes' => $item['mentor_notes'] ?? 'Mohon lengkapi rincian teknis pekerjaan.',
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
                                'notes' => $item['guru_notes'] ?? 'Silakan lakukan revisi sesuai catatan pembimbing industri.',
                                'created_at' => $dayDate->copy()->setTime(19, 00),
                            ]
                        );
                    }
                }
            }
        }
    }
}
