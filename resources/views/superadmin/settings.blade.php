@extends('layouts.app')

@section('page_title', 'Pengaturan Platform & Logo Sistem')
@section('page_subtitle', 'Kustomisasi logo resmi sistem, identitas branding, siaran pemeliharaan, dan diagnostik server')

@section('content')
<div class="w-full space-y-6 sm:space-y-8 animate-in fade-in duration-300">

    <!-- Enterprise Hero Header -->
    <x-page-header 
        title="Pengaturan Platform & Branding Sistem" 
        subtitle="Ubah logo utama sistem, identitas nama platform, siaran pemeliharaan global, serta pemantauan kesehatan server."
        badge="PLATFORM BRANDING"
    />

    <form action="{{ route('superadmin.settings.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf

        <!-- 1. Card: Logo Resmi Sistem -->
        <x-card>
            <div class="space-y-5">
                <div class="pb-3 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm sm:text-base font-extrabold text-slate-900 tracking-tight">Logo Utama Sistem</h3>
                        <p class="text-xs text-slate-500 font-medium">Logo ini digunakan secara global pada header navbar, sidebar menu, dan halaman autentikasi.</p>
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold font-mono {{ $hasCustomLogo ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : 'bg-slate-100 text-slate-600' }}">
                        {{ $hasCustomLogo ? 'Kustom Terpasang' : 'Logo Default' }}
                    </span>
                </div>

                <div class="flex flex-col md:flex-row items-start md:items-center gap-6">
                    <!-- Logo Preview Box -->
                    <div class="relative w-28 h-28 rounded-3xl bg-slate-950/90 border border-slate-800 shadow-xl flex items-center justify-center p-3 shrink-0 overflow-hidden group">
                        <img id="system-logo-preview" 
                             src="{{ $currentLogoUrl }}" 
                             alt="Logo Sistem" 
                             class="w-full h-full object-contain transition-transform group-hover:scale-105 duration-200">
                    </div>

                    <!-- Upload Controls & Guidelines -->
                    <div class="flex-1 space-y-3 w-full">
                        <div>
                            <label class="block text-xs font-bold text-slate-800 mb-1.5">Unggah Berkas Logo Baru</label>
                            <input type="file" 
                                   name="system_logo" 
                                   id="system_logo_input" 
                                   accept="image/png,image/jpeg,image/webp,image/svg+xml" 
                                   onchange="previewSystemLogo(this)" 
                                   class="w-full text-xs text-slate-600 file:mr-3 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 transition-all cursor-pointer">
                        </div>

                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-[11px] text-slate-500 font-medium">
                            <span>Format didukung: <strong>PNG, JPG, WEBP, SVG</strong></span>
                            <span>&bull;</span>
                            <span>Ukuran maksimal: <strong>2 MB</strong></span>
                            <span>&bull;</span>
                            <span>Rekomendasi: <strong>PNG Latar Belakang Transparan</strong></span>
                        </div>

                        @if($hasCustomLogo)
                            <div class="pt-2">
                                <label class="inline-flex items-center gap-2 text-xs font-bold text-rose-600 hover:text-rose-700 cursor-pointer">
                                    <input type="checkbox" name="reset_logo" value="1" class="rounded border-slate-300 text-rose-600 focus:ring-rose-500">
                                    <span>Hapus logo kustom dan kembalikan ke logo bawaan (Default VokaTrack)</span>
                                </label>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </x-card>

        <!-- 2. Card: Identitas & Teks Platform -->
        <x-card>
            <div class="space-y-4">
                <div class="pb-3 border-b border-slate-100">
                    <h3 class="text-sm sm:text-base font-extrabold text-slate-900 tracking-tight">Identitas & Penamaan Platform</h3>
                    <p class="text-xs text-slate-500 font-medium">Pengaturan teks nama sistem yang ditampilkan di banner atas dan footer aplikasi.</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Nama Aplikasi / Platform</label>
                        <input type="text" name="platform_name" value="{{ old('platform_name', $platformName) }}" required 
                               class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-bold text-slate-900">
                        <span class="text-[10px] text-slate-400 mt-1 block">Tampil di judul tab browser dan sidebar menu</span>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Tagline / Edisi Platform</label>
                        <input type="text" name="platform_tagline" value="{{ old('platform_tagline', $platformTagline) }}" 
                               class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-medium text-slate-900">
                        <span class="text-[10px] text-slate-400 mt-1 block">Contoh: Enterprise Cloud, Multi-Tenancy Edition</span>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Deskripsi Ringkas Platform</label>
                    <textarea name="app_description" rows="2" 
                              class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-medium text-slate-900 leading-relaxed">{{ old('app_description', $appDescription) }}</textarea>
                </div>
            </div>
        </x-card>

        <!-- 3. Card: Pemberitahuan Sistem Global & Siaran Pemeliharaan -->
        <x-card>
            <div class="space-y-4">
                <div class="pb-3 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm sm:text-base font-extrabold text-slate-900 tracking-tight">Siaran Pengumuman & Mode Pemeliharaan Global</h3>
                        <p class="text-xs text-slate-500 font-medium">Tampilkan pita pengumuman (banner) di seluruh portal akun (Admin Sekolah, Guru, Siswa, DU/DI).</p>
                    </div>
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="maintenance_active" value="1" {{ $maintenanceActive ? 'checked' : '' }} class="rounded text-indigo-600 focus:ring-indigo-500">
                        <span class="text-xs font-bold text-indigo-900">Aktifkan Banner Siaran</span>
                    </label>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Teks Pesan Siaran Global</label>
                    <textarea name="maintenance_notice" rows="2" placeholder="Contoh: Pemeliharaan rutin server dijadwalkan pada hari Minggu pukul 00:00 - 04:00 WIB. Sistem mungkin mengalami interupsi sementara." 
                              class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-600 font-medium text-slate-900 leading-relaxed">{{ old('maintenance_notice', $maintenanceNotice) }}</textarea>
                    <span class="text-[10px] text-slate-400 mt-1 block">Banner akan otomatis tampil tepat di bawah header atas di seluruh portal tenant saat dicentang aktif.</span>
                </div>
            </div>
        </x-card>

        <!-- 4. Card: Konfigurasi Gateway Email (SMTP Global) -->
        <x-card>
            <div class="space-y-4">
                <div class="pb-3 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm sm:text-base font-extrabold text-slate-900 tracking-tight">Konfigurasi Gateway Email (SMTP Platform)</h3>
                        <p class="text-xs text-slate-500 font-medium">Pengaturan mailer SMTP untuk pengiriman tagihan invoice, notifikasi sewa, dan reset password.</p>
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold font-mono bg-blue-50 text-blue-700 border border-blue-200">
                        SMTP Mailer
                    </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Host SMTP</label>
                        <input type="text" name="mail_host" value="{{ old('mail_host', $smtpSettings['mail_host'] ?? '') }}" placeholder="smtp.gmail.com / mail.server.com"
                               class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:ring-2 focus:ring-indigo-500/20 font-mono text-slate-900">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Port SMTP</label>
                        <input type="number" name="mail_port" value="{{ old('mail_port', $smtpSettings['mail_port'] ?? '587') }}" placeholder="587 / 465"
                               class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:ring-2 focus:ring-indigo-500/20 font-mono text-slate-900">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Enkripsi Mail</label>
                        <select name="mail_encryption" class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:ring-2 focus:ring-indigo-500/20 font-bold text-slate-900">
                            <option value="tls" {{ ($smtpSettings['mail_encryption'] ?? '') === 'tls' ? 'selected' : '' }}>TLS (Port 587)</option>
                            <option value="ssl" {{ ($smtpSettings['mail_encryption'] ?? '') === 'ssl' ? 'selected' : '' }}>SSL (Port 465)</option>
                            <option value="none" {{ ($smtpSettings['mail_encryption'] ?? '') === 'none' ? 'selected' : '' }}>Tanpa Enkripsi</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Username / Email Akun SMTP</label>
                        <input type="text" name="mail_username" value="{{ old('mail_username', $smtpSettings['mail_username'] ?? '') }}" placeholder="noreply@vokatrack.id"
                               class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:ring-2 focus:ring-indigo-500/20 font-mono text-slate-900">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Password / App Password</label>
                        <input type="password" name="mail_password" placeholder="••••••••••••"
                               class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:ring-2 focus:ring-indigo-500/20 font-mono text-slate-900">
                        <span class="text-[10px] text-slate-400 mt-1 block">Kosongkan jika tidak ingin mengubah kata sandi SMTP yang tersimpan</span>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Alamat Pengirim (From Address)</label>
                        <input type="email" name="mail_from_address" value="{{ old('mail_from_address', $smtpSettings['mail_from_address'] ?? '') }}" placeholder="billing@vokatrack.id"
                               class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:ring-2 focus:ring-indigo-500/20 text-slate-900 font-medium">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Nama Pengirim (From Name)</label>
                        <input type="text" name="mail_from_name" value="{{ old('mail_from_name', $smtpSettings['mail_from_name'] ?? '') }}" placeholder="VokaTrack Notification System"
                               class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:ring-2 focus:ring-indigo-500/20 text-slate-900 font-medium">
                    </div>
                </div>
            </div>
        </x-card>

        <!-- 5. Card: WhatsApp Gateway & Cloud Storage Multi-Tenant -->
        <x-card>
            <div class="space-y-4">
                <div class="pb-3 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm sm:text-base font-extrabold text-slate-900 tracking-tight">Integrasi WhatsApp Gateway & Storage Multi-Tenant</h3>
                        <p class="text-xs text-slate-500 font-medium">Pengaturan endpoint API WhatsApp bot dan driver penyimpanan media cloud.</p>
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold font-mono bg-emerald-50 text-emerald-700 border border-emerald-200">
                        Add-ons & Cloud
                    </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Penyedia WhatsApp</label>
                        <select name="wa_provider" class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:ring-2 focus:ring-indigo-500/20 font-bold text-slate-900">
                            <option value="fonnte" {{ ($waSettings['wa_provider'] ?? '') === 'fonnte' ? 'selected' : '' }}>Fonnte API (Indonesia)</option>
                            <option value="wablas" {{ ($waSettings['wa_provider'] ?? '') === 'wablas' ? 'selected' : '' }}>Wablas Official</option>
                            <option value="twilio" {{ ($waSettings['wa_provider'] ?? '') === 'twilio' ? 'selected' : '' }}>Twilio WhatsApp</option>
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">API Endpoint / Token WhatsApp</label>
                        <input type="password" name="wa_api_token" value="{{ old('wa_api_token', $waSettings['wa_api_token'] ?? '') }}" placeholder="API Token / Key Fonnte/Wablas"
                               class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:ring-2 focus:ring-indigo-500/20 font-mono text-slate-900">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Driver Storage Berkas</label>
                        <select name="storage_driver" class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:ring-2 focus:ring-indigo-500/20 font-bold text-slate-900">
                            <option value="public" {{ ($storageSettings['storage_driver'] ?? '') === 'public' ? 'selected' : '' }}>Local Server Public Disk</option>
                            <option value="s3" {{ ($storageSettings['storage_driver'] ?? '') === 's3' ? 'selected' : '' }}>Amazon AWS S3 Bucket</option>
                            <option value="r2" {{ ($storageSettings['storage_driver'] ?? '') === 'r2' ? 'selected' : '' }}>Cloudflare R2 Object Storage</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Nama Bucket S3 / R2</label>
                        <input type="text" name="s3_bucket" value="{{ old('s3_bucket', $storageSettings['s3_bucket'] ?? '') }}" placeholder="vokatrack-storage-prod"
                               class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:ring-2 focus:ring-indigo-500/20 font-mono text-slate-900">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Wilayah (Region)</label>
                        <input type="text" name="s3_region" value="{{ old('s3_region', $storageSettings['s3_region'] ?? 'ap-southeast-1') }}" placeholder="ap-southeast-1"
                               class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl bg-slate-50 border border-slate-200 focus:bg-white focus:ring-2 focus:ring-indigo-500/20 font-mono text-slate-900">
                    </div>
                </div>
            </div>
        </x-card>

        <!-- Submit Action Bar -->
        <div class="flex items-center justify-end gap-3 pt-2">
            <x-button type="button" variant="secondary" size="md" onclick="window.history.back()">
                Batal
            </x-button>
            <x-button type="submit" variant="primary" size="md" class="shadow-lg shadow-indigo-600/25">
                <svg class="w-4 h-4 mr-1.5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                Simpan Perubahan Pengaturan
            </x-button>
        </div>
    </form>

    <!-- 4. Card: System Health & Server Overview (Read-Only Diagnostics) -->
    <x-card>
        <div class="space-y-4">
            <div class="pb-3 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="text-sm sm:text-base font-extrabold text-slate-900 tracking-tight">Status Kesehatan Server & Diagnostik Sistem</h3>
                    <p class="text-xs text-slate-500 font-medium">Informasi lingkungan runtime aplikasi untuk kebutuhan pemeliharaan dan audit teknis.</p>
                </div>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    Sistem Berjalan Normal
                </span>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-1">
                    <span class="text-[10px] text-slate-400 uppercase font-extrabold block">Versi PHP</span>
                    <span class="font-mono font-bold text-slate-900 block text-xs sm:text-sm">PHP {{ $systemHealth['php_version'] }}</span>
                </div>
                <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-1">
                    <span class="text-[10px] text-slate-400 uppercase font-extrabold block">Versi Framework</span>
                    <span class="font-mono font-bold text-indigo-700 block text-xs sm:text-sm">Laravel {{ $systemHealth['laravel_version'] }}</span>
                </div>
                <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-1">
                    <span class="text-[10px] text-slate-400 uppercase font-extrabold block">Database Driver</span>
                    <span class="font-mono font-bold text-slate-900 block text-xs sm:text-sm uppercase">{{ $systemHealth['db_driver'] }}</span>
                </div>
                <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-1">
                    <span class="text-[10px] text-slate-400 uppercase font-extrabold block">Database Name</span>
                    <span class="font-mono font-bold text-slate-900 block text-xs truncate">{{ $systemHealth['db_name'] }}</span>
                </div>
                <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-1">
                    <span class="text-[10px] text-slate-400 uppercase font-extrabold block">Environment</span>
                    <span class="font-mono font-bold text-slate-900 block text-xs uppercase">{{ $systemHealth['app_env'] }}</span>
                </div>
                <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-1">
                    <span class="text-[10px] text-slate-400 uppercase font-extrabold block">Cache Driver</span>
                    <span class="font-mono font-bold text-slate-900 block text-xs uppercase">{{ $systemHealth['cache_driver'] }}</span>
                </div>
                <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-1">
                    <span class="text-[10px] text-slate-400 uppercase font-extrabold block">Session Driver</span>
                    <span class="font-mono font-bold text-slate-900 block text-xs uppercase">{{ $systemHealth['session_driver'] }}</span>
                </div>
                <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-1">
                    <span class="text-[10px] text-slate-400 uppercase font-extrabold block">Izin Tulis Storage</span>
                    <span class="font-bold text-emerald-600 block text-xs">
                        {{ $systemHealth['storage_writable'] ? 'Dapat Ditulis (OK)' : 'Terproteksi / Read-Only' }}
                    </span>
                </div>
            </div>
        </div>
    </x-card>

</div>

<script>
    function previewSystemLogo(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const previewImg = document.getElementById('system-logo-preview');
                if (previewImg) {
                    previewImg.src = e.target.result;
                }
            };
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>
@endsection
