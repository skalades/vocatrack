@extends('layouts.app')

@section('page_title', 'Pengaturan Platform & Logo Sistem')
@section('page_subtitle', 'Kustomisasi logo resmi sistem, identitas branding, dan konfigurasi platform SaaS')

@section('content')
<div class="w-full space-y-6 sm:space-y-8 animate-in fade-in duration-300">

    <!-- Enterprise Hero Header -->
    <x-page-header 
        title="Pengaturan Branding & Logo Sistem" 
        subtitle="Ubah logo utama sistem yang tampil di sidebar menu, layar login, favicon, serta portal seluruh sekolah."
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

        <!-- Submit Action Bar -->
        <div class="flex items-center justify-end gap-3 pt-2">
            <x-button type="button" variant="secondary" size="md" onclick="window.history.back()">
                Batal
            </x-button>
            <x-button type="submit" variant="primary" size="md" class="shadow-lg shadow-indigo-600/25">
                <svg class="w-4 h-4 mr-1.5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                Simpan Perubahan Logo & Branding
            </x-button>
        </div>
    </form>

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
