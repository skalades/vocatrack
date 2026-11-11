<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Laporan Individual PKL — {{ $spkl->student->user->name }}</title>
    <style>
        @page {
            margin: 1.5cm 1.5cm 1.5cm 1.5cm;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 10pt;
            color: #1e293b;
            line-height: 1.4;
        }
        /* Kop Surat */
        .kop-table {
            width: 100%;
            border-bottom: 2.5px solid #0f172a;
            padding-bottom: 8px;
            margin-bottom: 14px;
        }
        .kop-logo {
            width: 70px;
            text-align: center;
        }
        .kop-text {
            text-align: center;
        }
        .kop-line1 {
            font-size: 10pt;
            font-weight: bold;
            color: #334155;
            text-transform: uppercase;
        }
        .kop-line2 {
            font-size: 11pt;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
        }
        .kop-line3 {
            font-size: 13pt;
            font-weight: 800;
            color: #1e1b4b;
            text-transform: uppercase;
            margin-top: 2px;
        }
        .kop-sub {
            font-size: 8pt;
            color: #64748b;
            margin-top: 3px;
        }
        /* Heading */
        .title {
            text-align: center;
            font-size: 12pt;
            font-weight: bold;
            text-transform: uppercase;
            margin: 12px 0 16px 0;
            color: #0f172a;
            letter-spacing: 0.5px;
        }
        /* Info Tables */
        .info-table {
            width: 100%;
            margin-bottom: 12px;
            border-collapse: collapse;
        }
        .info-table td {
            padding: 3px 4px;
            font-size: 9.5pt;
            vertical-align: top;
        }
        .section-title {
            font-size: 10pt;
            font-weight: bold;
            color: #1e1b4b;
            border-bottom: 1.5px solid #cbd5e1;
            padding-bottom: 3px;
            margin: 14px 0 8px 0;
            text-transform: uppercase;
        }
        /* Data Tables */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        .data-table th, .data-table td {
            border: 1px solid #cbd5e1;
            padding: 5px 6px;
            font-size: 8.5pt;
        }
        .data-table th {
            background-color: #f1f5f9;
            color: #1e293b;
            font-weight: bold;
            text-align: left;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        /* Signatures */
        .sig-table {
            width: 100%;
            margin-top: 25px;
            page-break-inside: avoid;
        }
        .sig-table td {
            width: 33.3%;
            text-align: center;
            font-size: 9pt;
            vertical-align: top;
        }
        .sig-space {
            height: 60px;
        }
    </style>
</head>
<body>

    <!-- Dynamic School Kop Surat -->
    <table class="kop-table">
        <tr>
            @if(!empty($schoolLogoBase64))
                <td class="kop-logo" style="width: 75px; vertical-align: middle; text-align: center;">
                    <img src="{{ $schoolLogoBase64 }}" alt="Logo Sekolah" style="max-height: 70px; max-width: 70px;">
                </td>
            @endif
            <td class="kop-text" style="vertical-align: middle;">
                <div class="kop-line1">{{ $settings['kop_header_line1'] ?? 'PEMERINTAH DAERAH' }}</div>
                <div class="kop-line2">{{ $settings['kop_header_line2'] ?? 'DINAS PENDIDIKAN' }}</div>
                <div class="kop-line3">{{ $settings['kop_header_line3'] ?? strtoupper($spkl->school->name) }}</div>
                <div class="kop-sub">
                    {{ $spkl->school->address }} &bull; Telp: {{ $spkl->school->phone }} &bull; Email: {{ $spkl->school->email }}
                </div>
            </td>
            @if(!empty($schoolLogoBase64))
                <td style="width: 75px;"></td>
            @endif
        </tr>
    </table>

    <div class="title">
        LAPORAN REKAPITULASI PRAKTIK KERJA LAPANGAN (PKL)<br>
        <span style="font-size: 9.5pt; font-weight: normal; color: #475569;">Periode: {{ $spkl->pklPeriod->name }}</span>
    </div>

    <!-- Student & DU/DI Identity -->
    <table class="info-table">
        <tr>
            <td style="width: 20%; font-weight: bold;">Nama Siswa</td>
            <td style="width: 30%;">: {{ $spkl->student->user->name }}</td>
            <td style="width: 22%; font-weight: bold;">Perusahaan Mitra</td>
            <td style="width: 28%;">: {{ $spkl->company->name }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">NISN / NIS</td>
            <td>: {{ $spkl->student->nisn }} / {{ $spkl->student->nis }}</td>
            <td style="font-weight: bold;">Pembimbing DU/DI</td>
            <td>: {{ $spkl->supervisor->user->name ?? '-' }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold;">Kelas / Jurusan</td>
            <td>: {{ $spkl->student->class->name }} ({{ $spkl->student->class->major->name }})</td>
            <td style="font-weight: bold;">Guru Pembimbing</td>
            <td>: {{ $spkl->teacher->user->name }}</td>
        </tr>
    </table>

    <!-- 1. Attendance Summary -->
    <div class="section-title">I. Rekapitulasi Kehadiran Siswa</div>
    @php
        $att = $spkl->attendances;
        $totalDays = $att->count();
        $hadir = $att->where('status', 'hadir')->count();
        $terlambat = $att->where('status', 'terlambat')->count();
        $izin = $att->where('status', 'izin')->count();
        $sakit = $att->where('status', 'sakit')->count();
        $dinas = $att->where('status', 'dinas_luar')->count();
        $wfh = $att->where('status', 'luar_kantor')->count();
        $alfa = $att->where('status', 'alfa')->count();
        $rate = $totalDays > 0 ? round((($hadir + $terlambat + $dinas + $wfh) / $totalDays) * 100, 1) : 0;
    @endphp

    <table class="data-table">
        <thead>
            <tr>
                <th class="text-center">Total Hari Rekam</th>
                <th class="text-center">Hadir</th>
                <th class="text-center">Terlambat</th>
                <th class="text-center">Izin</th>
                <th class="text-center">Sakit</th>
                <th class="text-center">Dinas Luar</th>
                <th class="text-center">WFH</th>
                <th class="text-center">Alfa</th>
                <th class="text-center">Tingkat Disiplin</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="text-center font-bold">{{ $totalDays }}</td>
                <td class="text-center">{{ $hadir }}</td>
                <td class="text-center">{{ $terlambat }}</td>
                <td class="text-center">{{ $izin }}</td>
                <td class="text-center">{{ $sakit }}</td>
                <td class="text-center">{{ $dinas }}</td>
                <td class="text-center">{{ $wfh }}</td>
                <td class="text-center" style="color: #b91c1c;">{{ $alfa }}</td>
                <td class="text-center font-bold" style="color: #1e1b4b;">{{ $rate }}%</td>
            </tr>
        </tbody>
    </table>

    <!-- 2. Integrated Assessment -->
    <div class="section-title">II. Lembar Penilaian Terpadu Kompetensi PKL</div>
    @if($spkl->assessment)
        @php $a = $spkl->assessment; @endphp
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 50%;">Komponen Evaluasi</th>
                    <th class="text-center" style="width: 25%;">Skor (0–100)</th>
                    <th class="text-center" style="width: 25%;">Bobot Nilai</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Kedisiplinan & Etos Kerja (DU/DI)</td>
                    <td class="text-center font-bold">{{ number_format($a->score_dudi_discipline, 1) }}</td>
                    <td class="text-center" rowspan="3">60% (Industri)</td>
                </tr>
                <tr>
                    <td>Keterampilan & Penguasaan Teknis (DU/DI)</td>
                    <td class="text-center font-bold">{{ number_format($a->score_dudi_technical, 1) }}</td>
                </tr>
                <tr>
                    <td>Inisiatif & Tanggung Jawab (DU/DI)</td>
                    <td class="text-center font-bold">{{ number_format($a->score_dudi_initiative, 1) }}</td>
                </tr>
                <tr>
                    <td>Laporan Akhir & Bimbingan Akademik (Sekolah)</td>
                    <td class="text-center font-bold">{{ number_format($a->score_teacher_report, 1) }}</td>
                    <td class="text-center">40% (Sekolah)</td>
                </tr>
                <tr style="background-color: #f8fafc;">
                    <td class="font-bold">NILAI AKHIR TERPADU</td>
                    <td class="text-center font-bold" style="font-size: 11pt; color: #1e1b4b;">
                        {{ number_format($a->final_score, 1) }}
                    </td>
                    <td class="text-center font-bold">100%</td>
                </tr>
            </tbody>
        </table>
    @else
        <p style="font-size: 8.5pt; color: #64748b; font-style: italic; margin-bottom: 12px;">
            Penilaian akhir belum selesai diisi oleh pembimbing industri atau guru sekolah.
        </p>
    @endif

    <!-- 3. Approved Journals Extract -->
    <div class="section-title">III. Ringkasan Jurnal Kerja Industri (Terverifikasi)</div>
    @if($spkl->journals->isEmpty())
        <p style="font-size: 8.5pt; color: #64748b; font-style: italic;">Belum ada entri jurnal yang disetujui penuh.</p>
    @else
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 15%;" class="text-center">Tanggal</th>
                    <th style="width: 60%;">Uraian Capaian Kegiatan & Pekerjaan</th>
                    <th style="width: 25%;">Status Verifikasi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($spkl->journals as $j)
                    <tr>
                        <td class="text-center font-bold" style="font-size: 8pt;">
                            {{ \Carbon\Carbon::parse($j->date)->format('d/m/Y') }}
                        </td>
                        <td style="font-size: 8pt; line-height: 1.3;">
                            {{ $j->activity_description }}
                        </td>
                        <td style="font-size: 7.5pt; color: #047857;">
                            Disetujui DU/DI & Guru
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <!-- Signatures -->
    <table class="sig-table">
        <tr>
            <td>
                Mengetahui,<br>
                <strong>Pembimbing Industri DU/DI</strong>
                <div class="sig-space"></div>
                <strong>{{ $spkl->supervisor->user->name ?? '............................' }}</strong><br>
                <span>{{ $spkl->company->name }}</span>
            </td>
            <td>
                Mengetahui,<br>
                <strong>Guru Pembimbing Sekolah</strong>
                <div class="sig-space"></div>
                <strong>{{ $spkl->teacher->user->name }}</strong><br>
                <span>NIP. {{ $spkl->teacher->nip ?? '-' }}</span>
            </td>
            <td>
                Mengesahkan,<br>
                <strong>Kepala Sekolah</strong>
                <div class="sig-space"></div>
                <strong>{{ $settings['kepsek_name'] ?? 'Dr. H. Bambang Sudarsono, M.Pd.' }}</strong><br>
                <span>NIP. {{ $settings['kepsek_nip'] ?? '-' }}</span>
            </td>
        </tr>
    </table>

</body>
</html>
