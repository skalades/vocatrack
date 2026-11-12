<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $isValid ? 'Terverifikasi Resmi: ' . $certificate->certificate_number : 'Verifikasi Sertifikat Tidak Valid' }} - VokaTrack</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
    </style>
</head>
<body class="bg-slate-900 text-slate-100 min-h-screen flex flex-col justify-between antialiased selection:bg-indigo-500 selection:text-white">

    <!-- Top Brand Bar -->
    <header class="border-b border-slate-800/80 bg-slate-950/70 backdrop-blur-md sticky top-0 z-30">
        <div class="max-w-4xl mx-auto px-4 py-3.5 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-indigo-600 to-indigo-500 text-white flex items-center justify-center font-black text-sm shadow-md shadow-indigo-600/30">
                    V
                </div>
                <div>
                    <span class="text-sm font-black tracking-tight text-white">VokaTrack</span>
                    <span class="text-[10px] font-bold text-slate-400 block -mt-1 tracking-widest uppercase">Public Verification Portal</span>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold {{ $isValid ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30' : 'bg-rose-500/10 text-rose-400 border border-rose-500/30' }}">
                    <span class="w-2 h-2 rounded-full {{ $isValid ? 'bg-emerald-400 animate-pulse' : 'bg-rose-400' }}"></span>
                    {{ $isValid ? 'Sistem Aktif & Terlindungi' : 'Verifikasi Gagal' }}
                </span>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 max-w-3xl w-full mx-auto px-4 py-8 sm:py-12">
        @if ($isValid)
            <!-- VALID STATE -->
            <div class="space-y-6">
                <!-- Status Header Card -->
                <div class="bg-gradient-to-b from-slate-800/90 to-slate-900 border border-emerald-500/30 rounded-3xl p-6 sm:p-8 text-center shadow-2xl relative overflow-hidden">
                    <div class="absolute -top-24 left-1/2 -translate-x-1/2 w-64 h-64 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>

                    <!-- Glowing Emerald Shield Badge -->
                    <div class="w-20 h-20 mx-auto rounded-3xl bg-emerald-500/10 border-2 border-emerald-500/40 flex items-center justify-center text-emerald-400 shadow-lg shadow-emerald-500/20 mb-4 animate-bounce-short">
                        <svg class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    </div>

                    <span class="inline-block px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 mb-2">
                        Dokumen Terverifikasi Sah & Asli
                    </span>
                    <h1 class="text-2xl sm:text-3xl font-black text-white tracking-tight">Sertifikat Resmi Praktik Kerja Lapangan</h1>
                    <p class="text-xs sm:text-sm text-slate-400 mt-2 max-w-lg mx-auto">
                        Dokumen digital ini terbit secara resmi melalui sistem penjaminan mutu VokaTrack dan ditandatangani oleh pimpinan sekolah dan mitra industri bersangkutan.
                    </p>

                    <div class="mt-4 pt-4 border-t border-slate-800 flex items-center justify-center gap-2 text-xs font-mono text-slate-400">
                        <span>Nomor Sertifikat:</span>
                        <strong class="text-emerald-400 text-sm">{{ $certificate->certificate_number }}</strong>
                    </div>
                </div>

                <!-- Identity Details Card -->
                <div class="bg-slate-800/60 border border-slate-700/60 rounded-3xl p-6 sm:p-8 space-y-6 shadow-xl backdrop-blur-sm">
                    <!-- Section: Siswa -->
                    <div>
                        <div class="text-[11px] font-extrabold uppercase tracking-widest text-indigo-400 mb-3 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            Identitas Peserta Didik
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 bg-slate-900/60 rounded-2xl p-4 border border-slate-800">
                            <div>
                                <span class="text-xs text-slate-500 block">Nama Lengkap Siswa</span>
                                <span class="text-base font-extrabold text-white mt-0.5 block">{{ $certificate->studentPkl->student->user->name ?? '-' }}</span>
                            </div>
                            <div>
                                <span class="text-xs text-slate-500 block">NISN / NIS</span>
                                <span class="text-sm font-bold text-slate-300 mt-0.5 block font-mono">{{ $certificate->studentPkl->student->nisn ?? '-' }} / {{ $certificate->studentPkl->student->nis ?? '-' }}</span>
                            </div>
                            <div>
                                <span class="text-xs text-slate-500 block">Kompetensi Keahlian (Jurusan)</span>
                                <span class="text-sm font-semibold text-slate-300 mt-0.5 block">{{ $certificate->studentPkl->student->class->major->name ?? ($certificate->studentPkl->student->class->name ?? '-') }}</span>
                            </div>
                            <div>
                                <span class="text-xs text-slate-500 block">Asal Sekolah Pelaksana</span>
                                <span class="text-sm font-semibold text-slate-300 mt-0.5 block">{{ $certificate->school->name ?? '-' }} (NPSN: {{ $certificate->school->npsn ?? '-' }})</span>
                            </div>
                        </div>
                    </div>

                    <!-- Section: Mitra DU/DI & Nilai -->
                    <div>
                        <div class="text-[11px] font-extrabold uppercase tracking-widest text-amber-400 mb-3 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                            Pelaksanaan PKL & Hasil Kompetensi
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 bg-slate-900/60 rounded-2xl p-4 border border-slate-800">
                            <div>
                                <span class="text-xs text-slate-500 block">Tempat Praktik (DU/DI)</span>
                                <span class="text-base font-extrabold text-white mt-0.5 block">{{ $certificate->studentPkl->company->name ?? '-' }}</span>
                                <span class="text-xs text-slate-400 block mt-0.5">{{ $certificate->studentPkl->company->address ?? '-' }}</span>
                            </div>
                            <div>
                                <span class="text-xs text-slate-500 block">Periode Kegiatan</span>
                                <span class="text-sm font-semibold text-slate-300 mt-0.5 block">{{ $certificate->studentPkl->pklPeriod->name ?? 'Periode PKL' }}</span>
                                <span class="text-xs text-slate-400 block mt-0.5">Tanggal Terbit: {{ $certificate->issued_date ? $certificate->issued_date->translatedFormat('d F Y') : '-' }}</span>
                            </div>
                            <div>
                                <span class="text-xs text-slate-500 block">Skor Akhir Evaluasi</span>
                                <span class="text-2xl font-black text-emerald-400 mt-0.5 block font-mono">{{ number_format($certificate->final_score, 2) }} <span class="text-xs font-normal text-slate-400">/ 100</span></span>
                            </div>
                            <div>
                                <span class="text-xs text-slate-500 block">Predikat Kelulusan</span>
                                <span class="inline-flex items-center px-3 py-1 rounded-xl text-xs font-black uppercase tracking-wider bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 mt-1">
                                    {{ $certificate->final_predicate }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Tombol Download Resmi -->
                    <div class="pt-2 flex flex-col sm:flex-row items-center justify-between gap-4 border-t border-slate-700/60">
                        <div class="text-xs text-slate-400 flex items-center gap-2">
                            <svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                            <span>Keaslian dokumen dijamin kriptografi UUID VokaTrack.</span>
                        </div>
                        <a href="{{ route('public.cert.download', $certificate->uuid) }}" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-3 rounded-2xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs tracking-wide shadow-lg shadow-indigo-600/30 transition-all">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Unduh Dokumen Asli (PDF)
                        </a>
                    </div>
                </div>
            </div>
        @else
            <!-- INVALID STATE -->
            <div class="bg-gradient-to-b from-slate-800 to-slate-900 border border-rose-500/30 rounded-3xl p-8 sm:p-12 text-center shadow-2xl relative overflow-hidden space-y-5">
                <div class="w-20 h-20 mx-auto rounded-3xl bg-rose-500/10 border-2 border-rose-500/40 flex items-center justify-center text-rose-400 shadow-lg shadow-rose-500/20">
                    <svg class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>

                <span class="inline-block px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider bg-rose-500/20 text-rose-300 border border-rose-500/30">
                    Sertifikat Tidak Ditemukan / Tidak Valid
                </span>

                <h1 class="text-2xl sm:text-3xl font-black text-white tracking-tight">Verifikasi Dokumen Gagal</h1>
                <p class="text-xs sm:text-sm text-slate-400 max-w-md mx-auto leading-relaxed">
                    Kode verifikasi unik <code class="text-rose-400 font-mono bg-slate-950 px-2 py-0.5 rounded">{{ $uuid }}</code> tidak terdaftar dalam pangkalan data resmi VokaTrack. Kemungkinan dokumen telah dicabut atau tautan QR code tidak valid.
                </p>

                <div class="pt-4">
                    <a href="{{ url('/') }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs transition-colors border border-slate-700">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Kembali ke Halaman Utama
                    </a>
                </div>
            </div>
        @endif
    </main>

    <!-- Footer -->
    <footer class="border-t border-slate-800/80 bg-slate-950/70 py-6 text-center text-xs text-slate-500">
        <div class="max-w-4xl mx-auto px-4 space-y-1">
            <p>&copy; {{ date('Y') }} VokaTrack Digital Certification System. Seluruh hak cipta dilindungi.</p>
            <p class="text-[11px] text-slate-600">Sistem Penjaminan Mutu & Verifikasi Sertifikasi Vokasi Terpadu.</p>
        </div>
    </footer>

</body>
</html>
