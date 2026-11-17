# Paket Materi Presentasi VokaTrack

Folder ini berisi seluruh bahan presentasi dan hasil render video VokaTrack.

- `Presentasi-VokaTrack-Bernarasi.mp4` — video presentasi MP4 berdurasi 05:47 (1600x900, 24 fps, audio AAC narator).
- `Naskah-Narasi-VokaTrack.md` — naskah narasi lengkap 14 bagian.
- `Subtitle-VokaTrack.srt` — subtitle SRT yang telah disinkronkan sesuai timing render aktual.
- `video_narasi.json` — sumber data slide dan narasi yang digunakan generator.
- `render_video.ps1` — skrip PowerShell generator slide HTML, frame capture (Edge), narasi audio (Windows Speech), dan muxing video (FFmpeg).
- `presentasi-referensi.html` — presentasi interaktif VokaTrack asli sebagai bahan pemeriksaan tampilan dan fitur.
- `logo-sistem.png` — logo proyek.

## Cakupan

Narasi mencakup Admin Sekolah, Kepala Jurusan, Kepala Sekolah, Guru Pembimbing, Pembimbing DU/DI, siswa, dan orang tua. Portal Super Admin tidak dimasukkan. Bagian SOS menyebutkan WhatsApp Gateway, dengan catatan bahwa pengiriman bergantung pada nomor kontak yang tersimpan dan gateway yang terhubung.

## Status Eksekusi

Render telah berhasil dieksekusi:
- **Video Output:** `Presentasi-VokaTrack-Bernarasi.mp4` (~9.1 MB, 14 slide, durasi 05:47.68)
- **Resolusi:** 1600x900 (16:9), H.264 High Profile, 24 fps
- **Audio:** AAC Stereo 44.1 kHz, 138 kbps (Windows Speech Desktop Voice)
- **Subtitle:** `Subtitle-VokaTrack.srt` tersinkronisasi 100% dengan timecode aktual setiap slide
