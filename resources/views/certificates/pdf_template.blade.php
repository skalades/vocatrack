<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Sertifikat PKL - {{ $certificate->certificate_number }}</title>
    <style>
        @page {
            size: a4 landscape;
            margin: 8mm 10mm 8mm 10mm;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #0f172a;
            margin: 0;
            padding: 0;
            line-height: 1.25;
        }

        /* ================= HALAMAN 1: SERTIFIKAT ================= */
        .cert-outer {
            border: 3.5px solid #1e3a8a;
            padding: 3px;
            height: 508pt;
            position: relative;
        }
        .cert-inner {
            border: 1.5px solid #d97706;
            padding: 10px 18px 10px 18px;
            height: 496pt;
            position: relative;
            text-align: center;
        }

        /* Kop Sekolah */
        .kop-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2px;
        }
        .kop-table td {
            vertical-align: middle;
            padding: 0;
        }
        .kop-logo {
            width: 56px;
            height: 56px;
            object-fit: contain;
            display: block;
        }
        .school-gov-line {
            font-size: 8.5pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #334155;
            margin-bottom: 1px;
        }
        .school-gov-sub {
            font-size: 7.5pt;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #475569;
            margin-bottom: 2px;
        }
        .school-title {
            font-size: 14.5pt;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            color: #1e3a8a;
            margin: 0;
        }
        .school-subtitle {
            font-size: 7.5pt;
            color: #64748b;
            margin-top: 2px;
        }
        .header-divider {
            border-top: 2px solid #1e3a8a;
            border-bottom: 1px solid #d97706;
            height: 2px;
            margin-top: 5px;
            margin-bottom: 8px;
        }

        /* Judul Sertifikat */
        .cert-title-box {
            margin-top: 3px;
            margin-bottom: 4px;
        }
        .cert-main-title {
            font-size: 24pt;
            font-weight: 900;
            letter-spacing: 4px;
            color: #0f172a;
            text-transform: uppercase;
            margin: 0;
            line-height: 1.1;
        }
        .cert-sub-title {
            font-size: 11pt;
            font-weight: 800;
            letter-spacing: 2px;
            color: #d97706;
            text-transform: uppercase;
            margin-top: 2px;
        }
        .cert-number {
            font-size: 9pt;
            font-weight: 600;
            color: #475569;
            margin-top: 2px;
        }

        /* Keterangan & Nama Siswa */
        .cert-intro {
            font-size: 9pt;
            color: #334155;
            margin-top: 8px;
            margin-bottom: 3px;
        }
        .student-name {
            font-size: 21pt;
            font-weight: 900;
            color: #1e3a8a;
            text-transform: uppercase;
            letter-spacing: 1px;
            text-decoration: underline;
            margin: 3px 0 2px 0;
        }
        .student-meta {
            font-size: 9pt;
            color: #475569;
            margin-bottom: 8px;
        }
        .cert-body-text {
            font-size: 9.5pt;
            line-height: 1.4;
            color: #1e293b;
            max-width: 92%;
            margin: 0 auto 8px auto;
        }
        .company-name {
            font-weight: bold;
            color: #0f172a;
            font-size: 10.5pt;
        }
        .period-text {
            font-size: 8.5pt;
            color: #475569;
        }

        /* Predikat Badge */
        .badge-box {
            margin-top: 3px;
            margin-bottom: 8px;
        }
        .predicate-badge {
            display: inline-block;
            background-color: #fef3c7;
            border: 1.5px solid #f59e0b;
            color: #92400e;
            font-size: 10.5pt;
            font-weight: 800;
            padding: 4px 20px;
            border-radius: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Pengesahan / Footer Halaman 1 */
        .footer-table {
            position: absolute;
            top: 400pt;
            left: 15pt;
            right: 15pt;
            width: 96%;
            border-collapse: collapse;
        }
        .footer-table td {
            vertical-align: top;
            font-size: 8.5pt;
            text-align: center;
            padding: 0;
        }
        .sig-space {
            height: 38px;
        }
        .sig-name {
            font-weight: bold;
            text-decoration: underline;
            color: #0f172a;
            font-size: 9pt;
        }
        .sig-role {
            font-size: 8pt;
            color: #64748b;
            margin-top: 2px;
        }
        .qr-box {
            text-align: center;
        }
        .qr-img {
            width: 62px;
            height: 62px;
            display: block;
            margin: 0 auto;
        }
        .qr-caption {
            font-size: 6.5pt;
            font-weight: bold;
            color: #64748b;
            margin-top: 2px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }
        .qr-uuid {
            font-size: 6pt;
            color: #94a3b8;
            margin-top: 1px;
            font-family: monospace;
        }

        /* ================= HALAMAN 2: TRANSKRIP ================= */
        .page-break {
            page-break-after: always;
        }
        .transcript-outer {
            border: 2px solid #334155;
            padding: 12px 16px;
            height: 508pt;
            position: relative;
        }
        .transcript-title {
            text-align: center;
            font-size: 13pt;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            color: #0f172a;
            margin: 0;
        }
        .transcript-sub {
            text-align: center;
            font-size: 8.5pt;
            color: #64748b;
            margin-top: 2px;
            margin-bottom: 10px;
        }
        .info-grid {
            width: 100%;
            margin-bottom: 10px;
            font-size: 8.5pt;
            border-collapse: collapse;
        }
        .info-grid td {
            padding: 2.5px 0;
            vertical-align: top;
        }
        .grade-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.5pt;
            margin-bottom: 8px;
        }
        .grade-table th, .grade-table td {
            border: 1px solid #94a3b8;
            padding: 4.5px 7px;
        }
        .grade-table th {
            background-color: #f1f5f9;
            font-weight: bold;
            color: #1e293b;
            text-align: center;
        }
        .eval-box {
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 5px 8px;
            font-size: 7.5pt;
            margin-bottom: 8px;
            line-height: 1.35;
        }
        .transcript-footer-table {
            position: absolute;
            top: 418pt;
            left: 15pt;
            right: 15pt;
            width: 96%;
            border-collapse: collapse;
        }
        .transcript-footer-table td {
            vertical-align: top;
            font-size: 8.5pt;
            text-align: center;
            padding: 0;
        }
    </style>
</head>
<body>

    <!-- ========================================== -->
    <!-- HALAMAN 1: SERTIFIKAT KELULUSAN (LANDSCAPE)-->
    <!-- ========================================== -->
    <div class="cert-outer">
        <div class="cert-inner">

            <!-- Header Sekolah dengan Logo & Kop Resmi -->
            <table class="kop-table">
                <tr>
                    <td style="width: 65px; vertical-align: middle; text-align: left;">
                        @if(!empty($schoolLogoBase64))
                            <img src="{{ $schoolLogoBase64 }}" class="kop-logo" alt="Logo Sekolah">
                        @else
                            <div style="width: 56px; height: 56px;"></div>
                        @endif
                    </td>
                    <td style="text-align: center; vertical-align: middle;">
                        @if(!empty($kopLine1))
                            <div class="school-gov-line">{{ $kopLine1 }}</div>
                        @endif
                        @if(!empty($kopLine2))
                            <div class="school-gov-sub">{{ $kopLine2 }}</div>
                        @endif
                        <div class="school-title">{{ $school->name ?? 'SMK NEGERI 2 GARUT' }}</div>
                        <div class="school-subtitle">
                            NPSN: {{ $school->npsn ?? '-' }} &bull; {{ $school->address ?? 'Pendidikan Vokasi Terpadu' }} &bull; Telp: {{ $school->phone ?? '-' }}
                        </div>
                    </td>
                    <td style="width: 65px; vertical-align: middle; text-align: right;">
                        <div style="width: 56px; height: 56px;"></div>
                    </td>
                </tr>
            </table>

            <div class="header-divider"></div>

            <!-- Judul Sertifikat -->
            <div class="cert-title-box">
                <div class="cert-main-title">SERTIFIKAT</div>
                <div class="cert-sub-title">PRAKTIK KERJA LAPANGAN (PKL)</div>
                <div class="cert-number">Nomor: {{ $certificate->certificate_number }}</div>
            </div>

            <!-- Keterangan Pembuka -->
            <div class="cert-intro">Kepala Sekolah dan Pimpinan Dunia Usaha / Dunia Industri menerangkan bahwa:</div>

            <!-- Nama Siswa -->
            <div class="student-name">{{ $student->user->name ?? 'NAMA PESERTA DIDIK' }}</div>
            <div class="student-meta">
                NIS: <strong>{{ $student->nis ?? '-' }}</strong> &bull;
                NISN: <strong>{{ $student->nisn ?? '-' }}</strong> &bull;
                Kompetensi Keahlian: <strong>{{ $student->class->major->name ?? ($student->class->name ?? 'Vokasi Terapan') }}</strong>
            </div>

            <!-- Isi Keterangan PKL -->
            <div class="cert-body-text">
                Telah melaksanakan dan menyelesaikan seluruh rangkaian kegiatan Praktik Kerja Lapangan (PKL)<br>
                pada <span class="company-name">{{ $company->name ?? 'DU/DI Mitra Industri' }}</span><br>
                @if(!empty($spkl->pklPeriod))
                    <span class="period-text">Periode: <strong>{{ $spkl->pklPeriod->name }}</strong></span><br>
                @endif
                dengan hasil evaluasi kompetensi akhir:
            </div>

            <!-- Predikat & Nilai -->
            <div class="badge-box">
                <span class="predicate-badge">Predikat: {{ $certificate->final_predicate }} (Skor: {{ number_format($certificate->final_score, 1) }})</span>
            </div>

            <!-- Pengesahan & QR Code -->
            <table class="footer-table">
                <tr>
                    <td style="width: 36%;">
                        Mengetahui,<br>
                        <strong>Pimpinan / Pembimbing DU/DI</strong>
                        <div class="sig-space"></div>
                        <div class="sig-name">( {{ $spkl->supervisor->user->name ?? ($company->contact_person ?? 'Pimpinan Industri') }} )</div>
                        <div class="sig-role">{{ $company->name ?? 'Mitra DU/DI' }}</div>
                    </td>
                    <td style="width: 28%;">
                        <div class="qr-box">
                            @if(!empty($qrCodeBase64))
                                <img src="{{ $qrCodeBase64 }}" class="qr-img" alt="QR Verifikasi">
                            @else
                                <div style="width: 62px; height: 62px; margin: 0 auto; border: 1px dashed #94a3b8; line-height: 60px; font-size: 7pt; color: #64748b; text-align: center;">
                                    TERVERIFIKASI
                                </div>
                            @endif
                            <div class="qr-caption">Pindai Untuk Verifikasi Keaslian</div>
                            <div class="qr-uuid">ID: {{ substr($certificate->uuid, 0, 8) }}...</div>
                        </div>
                    </td>
                    <td style="width: 36%;">
                        {{ $schoolCity ?? 'Garut' }}, {{ $certificate->issued_date ? $certificate->issued_date->translatedFormat('d F Y') : date('d F Y') }}<br>
                        <strong>Kepala Sekolah</strong>
                        <div class="sig-space"></div>
                        <div class="sig-name">( {{ $kepsekName ?? 'Kepala Sekolah' }} )</div>
                        <div class="sig-role">NIP. {{ $kepsekNip ?? '-' }}</div>
                    </td>
                </tr>
            </table>

        </div>
    </div>

    <!-- PISAH HALAMAN KE HALAMAN 2 -->
    <div class="page-break"></div>

    <!-- ========================================== -->
    <!-- HALAMAN 2: TRANSKRIP CAPAIAN KOMPETENSI    -->
    <!-- ========================================== -->
    <div class="transcript-outer">
        
        <div class="transcript-title">DAFTAR NILAI & CAPAIAN KOMPETENSI PKL</div>
        <div class="transcript-sub">Lampiran Sertifikat Nomor: {{ $certificate->certificate_number }}</div>

        <!-- Grid Data Siswa -->
        <table class="info-grid">
            <tr>
                <td style="width: 18%; font-weight: bold;">Nama Peserta Didik</td>
                <td style="width: 2%;">:</td>
                <td style="width: 30%;">{{ $student->user->name ?? '-' }}</td>
                <td style="width: 18%; font-weight: bold;">Tempat PKL (DU/DI)</td>
                <td style="width: 2%;">:</td>
                <td style="width: 30%;">{{ $company->name ?? '-' }}</td>
            </tr>
            <tr>
                <td style="font-weight: bold;">NIS / NISN</td>
                <td>:</td>
                <td>{{ $student->nis ?? '-' }} / {{ $student->nisn ?? '-' }}</td>
                <td style="font-weight: bold;">Alamat DU/DI</td>
                <td>:</td>
                <td>{{ $company->address ?? '-' }}</td>
            </tr>
            <tr>
                <td style="font-weight: bold;">Kompetensi Keahlian</td>
                <td>:</td>
                <td>{{ $student->class->major->name ?? ($student->class->name ?? '-') }}</td>
                <td style="font-weight: bold;">Periode PKL</td>
                <td>:</td>
                <td>{{ $spkl->pklPeriod->name ?? 'Semester Aktif' }}</td>
            </tr>
        </table>

        <!-- Tabel Nilai Rinci -->
        <table class="grade-table">
            <thead>
                <tr>
                    <th style="width: 6%;">No</th>
                    <th style="width: 50%; text-align: left;">Komponen / Aspek Penilaian</th>
                    <th style="width: 14%;">Skor Angka (0-100)</th>
                    <th style="width: 14%;">Huruf Mutu</th>
                    <th style="width: 16%;">Predikat</th>
                </tr>
            </thead>
            <tbody>
                <tr style="background-color: #f8fafc; font-weight: bold;">
                    <td colspan="5">A. ASPEK PENILAIAN DUNIA USAHA / DUNIA INDUSTRI (DU/DI)</td>
                </tr>
                <tr>
                    <td style="text-align: center;">1</td>
                    <td>Kedisiplinan, Tanggung Jawab & Kepatuhan SOP Industri</td>
                    <td style="text-align: center; font-weight: bold;">{{ number_format($gradeDiscipline['score'], 1) }}</td>
                    <td style="text-align: center;">{{ $gradeDiscipline['letter'] }}</td>
                    <td style="text-align: center;">{{ $gradeDiscipline['predicate'] }}</td>
                </tr>
                <tr>
                    <td style="text-align: center;">2</td>
                    <td>Keterampilan Teknis & Penguasaan Kompetensi Kejuruan</td>
                    <td style="text-align: center; font-weight: bold;">{{ number_format($gradeTechnical['score'], 1) }}</td>
                    <td style="text-align: center;">{{ $gradeTechnical['letter'] }}</td>
                    <td style="text-align: center;">{{ $gradeTechnical['predicate'] }}</td>
                </tr>
                <tr>
                    <td style="text-align: center;">3</td>
                    <td>Inisiatif, Kreativitas Kerja & Kerjasama Tim (Teamwork)</td>
                    <td style="text-align: center; font-weight: bold;">{{ number_format($gradeInitiative['score'], 1) }}</td>
                    <td style="text-align: center;">{{ $gradeInitiative['letter'] }}</td>
                    <td style="text-align: center;">{{ $gradeInitiative['predicate'] }}</td>
                </tr>
                <tr style="background-color: #f8fafc; font-weight: bold;">
                    <td colspan="5">B. ASPEK PENILAIAN SEKOLAH</td>
                </tr>
                <tr>
                    <td style="text-align: center;">4</td>
                    <td>Penulisan Laporan Akhir PKL & Presentasi Sidang</td>
                    <td style="text-align: center; font-weight: bold;">{{ number_format($gradeReport['score'], 1) }}</td>
                    <td style="text-align: center;">{{ $gradeReport['letter'] }}</td>
                    <td style="text-align: center;">{{ $gradeReport['predicate'] }}</td>
                </tr>
                <tr style="background-color: #f1f5f9; font-weight: bold;">
                    <td colspan="2" style="text-align: right; padding-right: 15px;">NILAI AKHIR RATA-RATA:</td>
                    <td style="text-align: center; color: #1e3a8a;">{{ number_format($certificate->final_score, 2) }}</td>
                    <td style="text-align: center;">{{ $finalGradeLetter }}</td>
                    <td style="text-align: center; color: #059669;">{{ $certificate->final_predicate }}</td>
                </tr>
            </tbody>
        </table>

        <!-- Ulasan DU/DI -->
        @if(!empty($assessment->notes_dudi))
            <div class="eval-box">
                <strong>Catatan / Saran Dunia Usaha & Industri (DU/DI):</strong><br>
                {{ $assessment->notes_dudi }}
            </div>
        @endif

        <!-- Pengesahan Transkrip -->
        <table class="transcript-footer-table">
            <tr>
                <td style="width: 50%;">
                    Pembimbing Lapangan DU/DI,
                    <div class="sig-space" style="height: 38px;"></div>
                    <div class="sig-name">( {{ $spkl->supervisor->user->name ?? ($company->contact_person ?? 'Pembimbing DU/DI') }} )</div>
                    <div class="sig-role">{{ $company->name ?? '-' }}</div>
                </td>
                <td style="width: 50%;">
                    Guru Pembimbing PKL,
                    <div class="sig-space" style="height: 38px;"></div>
                    <div class="sig-name">( {{ $spkl->teacher->user->name ?? 'Guru Pembimbing' }} )</div>
                    <div class="sig-role">NIP. {{ $spkl->teacher->nip ?? '........................' }}</div>
                </td>
            </tr>
        </table>

    </div>

</body>
</html>
