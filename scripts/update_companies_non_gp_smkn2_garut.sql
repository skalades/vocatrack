-- ============================================================================
-- Script MySQL / phpMyAdmin:
-- Update Status Perusahaan SMKN 2 Garut (NPSN: 20209203)
-- Cakupan: Semua jurusan SELAIN Geologi Pertambangan (GP)
-- Perubahan:
--   1. is_pkl_started             = 1            (PKL sudah dimulai)
--   2. default_start_date         = '2026-12-01' (Mulai tanggal 1 Desember 2026)
--   3. gps_enabled                = 1            (Geofencing ON)
--   4. requires_industry_approval = 0            (Validasi jurnal oleh guru pembimbing)
-- ============================================================================

-- 1. PREVIEW: Jalankan query ini terlebih dahulu untuk memastikan daftar perusahaan sudah sesuai
SELECT
    c.id,
    c.name AS nama_perusahaan,
    GROUP_CONCAT(DISTINCT m.code ORDER BY m.code SEPARATOR ', ') AS jurusan,
    c.is_pkl_started,
    c.default_start_date,
    c.gps_enabled,
    c.requires_industry_approval
FROM companies c
INNER JOIN schools s ON s.id = c.school_id
INNER JOIN company_major cm ON cm.company_id = c.id
INNER JOIN majors m ON m.id = cm.major_id
WHERE s.npsn = '20209203'
  AND NOT EXISTS (
      SELECT 1
      FROM company_major cm_gp
      INNER JOIN majors m_gp ON m_gp.id = cm_gp.major_id
      WHERE cm_gp.company_id = c.id
        AND (m_gp.code = 'GP' OR LOWER(m_gp.name) LIKE '%geologi pertambangan%')
  )
GROUP BY c.id, c.name, c.is_pkl_started, c.default_start_date, c.gps_enabled, c.requires_industry_approval
ORDER BY jurusan, c.name;

-- 2. UPDATE: Jalankan query ini untuk menerapkan perubahan
START TRANSACTION;

UPDATE companies c
INNER JOIN schools s ON s.id = c.school_id
SET
    c.is_pkl_started = 1,
    c.default_start_date = '2026-12-01',
    c.gps_enabled = 1,
    c.requires_industry_approval = 0,
    c.updated_at = NOW()
WHERE s.npsn = '20209203'
  AND EXISTS (
      SELECT 1
      FROM company_major cm
      INNER JOIN majors m ON m.id = cm.major_id
      WHERE cm.company_id = c.id
        AND m.code <> 'GP'
        AND LOWER(m.name) NOT LIKE '%geologi pertambangan%'
  )
  AND NOT EXISTS (
      SELECT 1
      FROM company_major cm_gp
      INNER JOIN majors m_gp ON m_gp.id = cm_gp.major_id
      WHERE cm_gp.company_id = c.id
        AND (m_gp.code = 'GP' OR LOWER(m_gp.name) LIKE '%geologi pertambangan%')
  );

COMMIT;
