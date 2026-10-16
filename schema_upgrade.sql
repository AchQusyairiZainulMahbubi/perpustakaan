-- ============================================================
-- SCHEMA UPGRADE: Perpustakaan Sistem Informasi
-- Menambah kolom yang dibutuhkan untuk sistem peminjaman & denda
-- ============================================================

USE perpustakaan_sistem_informasi;

-- Tambah kolom tgl_kembali_rencana dan status di peminjaman
ALTER TABLE peminjaman
    ADD COLUMN IF NOT EXISTS tgl_kembali_rencana DATE,
    ADD COLUMN IF NOT EXISTS status ENUM('Dipinjam','Dikembalikan') DEFAULT 'Dipinjam';

-- Upgrade tabel pengembalian
ALTER TABLE pengembalian
    ADD COLUMN IF NOT EXISTS id_peminjaman INT,
    ADD COLUMN IF NOT EXISTS tgl_kembali DATE,
    ADD COLUMN IF NOT EXISTS denda_total INT DEFAULT 0,
    ADD COLUMN IF NOT EXISTS status_denda ENUM('Lunas','Belum Lunas') DEFAULT 'Lunas';

-- Tambah no_hp di anggota untuk identifikasi
ALTER TABLE anggota
    ADD COLUMN IF NOT EXISTS no_hp CHAR(20),
    ADD COLUMN IF NOT EXISTS email CHAR(100);

-- Pastikan detail_peminajaman punya tgl_kembali_rencana
ALTER TABLE detail_peminajaman
    ADD COLUMN IF NOT EXISTS tgl_kembali_rencana DATE;
