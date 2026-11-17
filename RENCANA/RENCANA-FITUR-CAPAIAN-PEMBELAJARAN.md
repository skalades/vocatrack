# PERENCANAAN FITUR: BANK CAPAIAN PEMBELAJARAN (CP) & TUJUAN PEMBELAJARAN (TP) PER JURUSAN BERBASIS KURIKULUM MERDEKA

**Tanggal Penyusunan**: 17 November 2026  
**Status**: Dalam Eksekusi (In Progress)  
**Tujuan**: Mengintegrasikan pengelolaan Capaian Pembelajaran (CP) / Elemen Kompetensi Kurikulum Merdeka ke dalam alur kerja PKL di VokaTrack secara scalable, modular, dan konsisten.

---

## 1. Latar Belakang & Kebutuhan

Dalam Kurikulum Merdeka SMK, mata pelajaran Praktik Kerja Lapangan (PKL) memiliki elemen capaian pembelajaran resmi yang diturunkan menjadi Tujuan Pembelajaran (TP) spesifik sesuai Konsentrasi Keahlian. 

Kebutuhan utama:
1. **Pengelolaan Mandiri oleh Kepala Jurusan (Kajur) & Admin Sekolah**:
   - Kajur dapat merumuskan, menambah, mengedit, dan mengaktifkan butir-butir Capaian Pembelajaran khusus untuk jurusannya (misal: *Teknik Geologi Pertambangan*, *Produksi dan Siaran Program Televisi*).
   - Admin Sekolah memiliki kendali pengawasan lintas seluruh jurusan di sekolahnya.
2. **Tagging Capaian pada Jurnal Harian Siswa**:
   - Saat mengisi jurnal harian, siswa dapat memilih 1 atau lebih Capaian Pembelajaran terkait aktivitas kerja praktik hari itu.
3. **Transparansi Review Guru & DU/DI**:
   - Pembimbing DU/DI dan Guru Pembimbing dapat melihat tag capaian kompetensi pada jurnal saat melakukan verifikasi dua tingkat.
4. **Otomasi Transkrip E-Sertifikat Resmi**:
   - Halaman 2 E-Sertifikat PKL (Transkrip Nilai & Capaian Kompetensi) dapat menampilkan daftar capaian pembelajaran resmi dari jurusan siswa yang bersangkutan.

---

## 2. Skema & Desain Database

### A. Tabel `learning_objectives` (Bank Capaian Pembelajaran)
```sql
CREATE TABLE learning_objectives (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_id BIGINT UNSIGNED NOT NULL,
    major_id BIGINT UNSIGNED NOT NULL,
    code VARCHAR(30) NOT NULL,              -- Contoh: CP-01, TP-01, ELEMEN-1
    title VARCHAR(255) NOT NULL,            -- Judul singkat kompetensi
    description TEXT NULL,                  -- Deskripsi detail indikator capaian
    element_name VARCHAR(100) NULL,         -- Elemen Kurikulum Merdeka (Keterampilan Teknis, K3LH, Soft Skills)
    target_hours INT UNSIGNED DEFAULT 0,    -- Alokasi jam/target durasi bila relevan
    is_active BOOLEAN DEFAULT TRUE,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
    FOREIGN KEY (major_id) REFERENCES majors(id) ON DELETE CASCADE,
    INDEX idx_lo_school_major (school_id, major_id),
    INDEX idx_lo_major_active (major_id, is_active)
);
```

### B. Tabel Pivot `journal_learning_objective`
```sql
CREATE TABLE journal_learning_objective (
    journal_id BIGINT UNSIGNED NOT NULL,
    learning_objective_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (journal_id, learning_objective_id),
    FOREIGN KEY (journal_id) REFERENCES journals(id) ON DELETE CASCADE,
    FOREIGN KEY (learning_objective_id) REFERENCES learning_objectives(id) ON DELETE CASCADE
);
```

---

## 3. Matriks Hak Akses (RBAC)

| Peran Pengguna | Kelola Master CP Seluruh Jurusan | Kelola Master CP Jurusan Sendiri | Tagging CP di Jurnal | Lihat CP di Review Jurnal | Lihat CP di Transkrip Sertifikat |
| :--- | :---: | :---: | :---: | :---: | :---: |
| **Superadmin** | Monitoring | - | - | - | - |
| **Admin Sekolah** | **Full CRUD** | **Full CRUD** | - | Read | Read & Generate |
| **Kepala Jurusan (Kajur)** | - | **Full CRUD** (Major-Scoped) | - | Read (Jurusan) | Read (Jurusan) |
| **Guru Pembimbing** | - | Read-Only | - | Read (Bimbingan) | Read |
| **Pembimbing DU/DI** | - | - | - | Read (Siswa Magang)| Read |
| **Siswa PKL** | - | Read (Opsi Pilihan) | **Attach/Tagging** | - | - |

---

## 4. Alur Kerja & Implementasi Fitur

### A. Panel Kepala Jurusan (`/kajur/competencies`)
- Rute: `GET /kajur/competencies`, `POST /kajur/competencies`, `PUT /kajur/competencies/{learningObjective}`, `DELETE /kajur/competencies/{learningObjective}`.
- Tampilan antarmuka: Card daftar CP aktif, form modal tambah/edit CP dengan validasi scoped ke `major_id` milik Kajur.
- Switch toggle status aktif / non-aktif.

### B. Panel Admin Sekolah (`/adminsekolah/majors`)
- Pada kartu jurusan di [adminsekolah/majors.blade.php](file:///c:/Users/skala/OneDrive/Documents/project/pkl/resources/views/adminsekolah/majors.blade.php), ditambahkan indikator total CP dan tombol pintas **"Kelola Capaian"** untuk mengelola CP per jurusan.

### C. Input Jurnal Harian Siswa (`/student/journals`)
- Saat membuka form input jurnal atau edit jurnal:
  - Sistem memuat CP aktif dari jurusan siswa.
  - Menampilkan pilihan checklist atau selector pill Capaian Pembelajaran.
  - Sifatnya fleksibel: siswa dapat memilih 1 atau lebih CP, atau tetap dapat menyimpan jurnal jika belum ada CP yang didaftarkan (non-blocking).

### D. Tampilan Review Jurnal Guru & DU/DI
- Pada kartu jurnal siswa di `/guru/journals` dan `/dudi/journals`, ditampilkan badge pill CP yang ditargetkan pada hari tersebut.

### E. E-Sertifikat & Transkrip Nilai PDF
- Pada Halaman 2 E-Sertifikat, jika jurusan siswa memiliki butir-butir CP aktif, sistem menampilkan ringkasan capaian kompetensi kejuruan yang telah diselesaikan.

---

## 5. Rencana Pengujian (Test Suite)
- Test validasi penambahan CP oleh Admin Sekolah dan Kajur.
- Test isolasi: Kajur dilarang mengedit CP jurusan lain atau sekolah lain.
- Test penyimpanan jurnal siswa dengan tagging CP (`journal_learning_objective`).
- Test view rendering seluruh panel terkait.
