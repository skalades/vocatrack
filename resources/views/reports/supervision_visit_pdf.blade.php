<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Berita Acara Supervisi - {{ $documentNumber }}</title>
    <style>
        @page {
            margin: 2cm 2cm 2cm 2cm;
            size: A4 portrait;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11pt;
            line-height: 1.4;
            color: #1e293b;
        }
        .header-table {
            width: 100%;
            border-bottom: 3px double #0f172a;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }
        .school-name {
            font-size: 15pt;
            font-weight: bold;
            text-transform: uppercase;
            color: #0f172a;
            margin: 0;
            line-height: 1.2;
        }
        .school-info {
            font-size: 8.5pt;
            color: #475569;
            margin: 2px 0;
        }
        .doc-title {
            text-align: center;
            font-size: 12.5pt;
            font-weight: bold;
            text-transform: uppercase;
            text-decoration: underline;
            margin-top: 15px;
            margin-bottom: 4px;
            color: #0f172a;
        }
        .doc-number {
            text-align: center;
            font-size: 9.5pt;
            color: #475569;
            margin-bottom: 20px;
        }
        .meta-table {
            width: 100%;
            margin-bottom: 15px;
            font-size: 10pt;
        }
        .meta-table td {
            padding: 3px 0;
            vertical-align: top;
        }
        .meta-label {
            width: 25%;
            font-weight: 600;
            color: #334155;
        }
        .meta-sep {
            width: 3%;
            text-align: center;
        }
        .meta-value {
            width: 72%;
            color: #0f172a;
        }
        .section-title {
            font-size: 10.5pt;
            font-weight: bold;
            color: #0f172a;
            margin-top: 15px;
            margin-bottom: 6px;
            border-left: 3px solid #3b82f6;
            padding-left: 8px;
        }
        .content-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 8px 12px;
            font-size: 9.5pt;
            line-height: 1.5;
            margin-bottom: 12px;
            text-align: justify;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9pt;
            margin-bottom: 14px;
        }
        .data-table th, .data-table td {
            border: 1px solid #cbd5e1;
            padding: 6px 8px;
        }
        .data-table th {
            background-color: #f1f5f9;
            font-weight: bold;
            text-align: left;
            color: #334155;
        }
        .signature-table {
            width: 100%;
            margin-top: 30px;
            page-break-inside: avoid;
        }
        .signature-table td {
            width: 50%;
            vertical-align: top;
            text-align: center;
            font-size: 10pt;
        }
        .sig-space {
            height: 65px;
        }
        .sig-name {
            font-weight: bold;
            text-decoration: underline;
            color: #0f172a;
        }
        .sig-role {
            font-size: 9pt;
            color: #64748b;
        }
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 8.5pt;
            font-weight: bold;
            background-color: #e0f2fe;
            color: #0369a1;
        }
    </style>
</head>
<body>

    <!-- KOP SURAT DINAS / SEKOLAH -->
    <table class="header-table">
        <tr>
            <td style="text-align: center; vertical-align: middle;">
                <div class="school-name">{{ $school->name ?? 'SMK PUSAT KEUNGGULAN' }}</div>
                <div class="school-info">{{ $school->address ?? 'Jalan Pendidikan Vokasi Terpadu' }}</div>
                <div class="school-info">Telp: {{ $school->phone ?? '-' }} | Email: {{ $school->email ?? '-' }} | NPSN: {{ $school->npsn ?? '-' }}</div>
            </td>
        </tr>
    </table>

    <!-- JUDUL DOKUMEN -->
    <div class="doc-title">BERITA ACARA KUNJUNGAN SUPERVISI PKL</div>
    <div class="doc-number">Nomor: {{ $documentNumber }}</div>

    <p style="font-size: 10pt; margin-bottom: 12px; text-align: justify;">
        Pada hari ini, tanggal <strong>{{ $visit->visit_date ? $visit->visit_date->translatedFormat('d F Y') : '-' }}</strong>, telah dilaksanakan kegiatan supervisi, monitoring, dan evaluasi Praktik Kerja Lapangan (PKL) dengan rincian data sebagai berikut:
    </p>

    <!-- IDENTITAS SUPERVISI -->
    <table class="meta-table">
        <tr>
            <td class="meta-label">Nama Guru Pembimbing</td>
            <td class="meta-sep">:</td>
            <td class="meta-value"><strong>{{ $visit->teacher->user->name ?? '-' }}</strong> (NIP: {{ $visit->teacher->nip ?? '-' }})</td>
        </tr>
        <tr>
            <td class="meta-label">Tempat PKL (DU/DI)</td>
            <td class="meta-sep">:</td>
            <td class="meta-value"><strong>{{ $visit->company->name ?? '-' }}</strong></td>
        </tr>
        <tr>
            <td class="meta-label">Alamat DU/DI</td>
            <td class="meta-sep">:</td>
            <td class="meta-value">{{ $visit->company->address ?? '-' }}</td>
        </tr>
        <tr>
            <td class="meta-label">Tahapan Kunjungan</td>
            <td class="meta-sep">:</td>
            <td class="meta-value"><span class="badge">{{ $visit->stage_label }}</span></td>
        </tr>
        <tr>
            <td class="meta-label">Periode PKL</td>
            <td class="meta-sep">:</td>
            <td class="meta-value">{{ $visit->pklPeriod->name ?? 'Periode Aktif' }}</td>
        </tr>
    </table>

    <!-- DAFTAR SISWA BIMBINGAN -->
    <div class="section-title">A. Daftar Peserta Didik PKL yang Disupervisi</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 6%; text-align: center;">No</th>
                <th style="width: 32%;">Nama Siswa</th>
                <th style="width: 18%;">NIS / NISN</th>
                <th style="width: 26%;">Kelas / Jurusan</th>
                <th style="width: 18%; text-align: center;">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($students as $index => $spkl)
                <tr>
                    <td style="text-align: center;">{{ $index + 1 }}</td>
                    <td><strong>{{ $spkl->student->user->name ?? '-' }}</strong></td>
                    <td>{{ $spkl->student->nis ?? '-' }} / {{ $spkl->student->nisn ?? '-' }}</td>
                    <td>{{ $spkl->student->class->name ?? '-' }}</td>
                    <td style="text-align: center;">
                        <span style="color: #059669; font-weight: bold; text-transform: capitalize;">{{ $spkl->status }}</span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align: center; color: #94a3b8; padding: 10px;">
                        Data siswa bimbingan pada DU/DI ini tidak ditemukan.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- HASIL SUPERVISI -->
    <div class="section-title">B. Rangkuman Perkembangan & Capaian Belajar Peserta Didik</div>
    <div class="content-box">
        {!! nl2br(e($visit->student_progress_summary)) !!}
    </div>

    <div class="section-title">C. Masukan & Evaluasi dari Pembimbing DU/DI</div>
    <div class="content-box">
        {!! nl2br(e($visit->company_feedback)) !!}
    </div>

    @if($visit->follow_up_agreement)
        <div class="section-title">D. Kesepakatan / Rencana Tindak Lanjut</div>
        <div class="content-box">
            {!! nl2br(e($visit->follow_up_agreement)) !!}
        </div>
    @endif

    <p style="font-size: 9.5pt; margin-top: 15px; margin-bottom: 20px;">
        Demikian Berita Acara Supervisi Kunjungan PKL ini dibuat dengan sebenar-benarnya untuk dapat dipergunakan sebagaimana mestinya sebagai bukti pelaksanaan pembimbingan lapangan dan penjaminan mutu PKL.
    </p>

    <!-- LEMBAR PENGESAHAN -->
    <table class="signature-table">
        <tr>
            <td>
                Mengetahui / Menyetujui,<br>
                <strong>Pembimbing DU/DI / Pimpinan</strong>
                <div class="sig-space"></div>
                <div class="sig-name">( {{ $visit->company->contact_person ?? 'Pembimbing Industri' }} )</div>
                <div class="sig-role">{{ $visit->company->name ?? 'Pihak Mitra DU/DI' }}</div>
            </td>
            <td>
                Dibuat oleh,<br>
                <strong>Guru Pembimbing PKL</strong>
                <div class="sig-space"></div>
                <div class="sig-name">( {{ $visit->teacher->user->name ?? 'Guru Pembimbing' }} )</div>
                <div class="sig-role">NIP. {{ $visit->teacher->nip ?? '........................' }}</div>
            </td>
        </tr>
    </table>

</body>
</html>
