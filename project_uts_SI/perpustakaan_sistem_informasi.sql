-- ============================================================
-- Konversi PDM: perpustakaan_sistem informasi.pdm
-- Target: SQL / MySQL-compatible
-- Nama tabel dan kolom dipertahankan sesuai PDM
-- ============================================================

CREATE DATABASE IF NOT EXISTS perpustakaan_sistem_informasi;
USE perpustakaan_sistem_informasi;

-- ============================================================
-- 1. Tabel ANGGOTA
-- ============================================================
CREATE TABLE anggota (
    id_anggota INT NOT NULL,
    nama CHAR(50),
    alamat CHAR(50),
    tangga_daftar DATE,
    CONSTRAINT pk_anggota PRIMARY KEY (id_anggota)
);

-- ============================================================
-- 2. Tabel PERUGAS
-- ============================================================
CREATE TABLE perugas (
    id_petugas INT NOT NULL,
    nama_petugas CHAR(50),
    CONSTRAINT pk_perugas PRIMARY KEY (id_petugas)
);

-- ============================================================
-- 3. Tabel RATING
-- ============================================================
CREATE TABLE rating (
    id_rating INT NOT NULL,
    jumlah_rating INT,
    CONSTRAINT pk_rating PRIMARY KEY (id_rating)
);

-- ============================================================
-- 4. Tabel BUKU
-- ============================================================
CREATE TABLE buku (
    id_buku INT NOT NULL,
    id_rating INT,
    judul CHAR(50),
    penulis CHAR(50),
    eidisi CHAR(50),
    penerbit CHAR(50),
    tahun_terbit DATE,
    denda INT,
    CONSTRAINT pk_buku PRIMARY KEY (id_buku),
    CONSTRAINT fk_buku_rating
        FOREIGN KEY (id_rating)
        REFERENCES rating (id_rating)
);

-- ============================================================
-- 5. Tabel KATEGORI
-- ============================================================
CREATE TABLE kategori (
    id_kategori INT NOT NULL,
    id_buku INT,
    nama_kategori CHAR(50),
    CONSTRAINT pk_kategori PRIMARY KEY (id_kategori),
    CONSTRAINT fk_kategori_buku
        FOREIGN KEY (id_buku)
        REFERENCES buku (id_buku)
);

-- ============================================================
-- 6. Tabel PEMINJAMAN
-- ============================================================
CREATE TABLE peminjaman (
    id_peminjaman INT NOT NULL,
    id_anggota INT,
    id_petugas INT,
    tgl_pinjam DATE,
    CONSTRAINT pk_peminjaman PRIMARY KEY (id_peminjaman),
    CONSTRAINT fk_peminjaman_anggota
        FOREIGN KEY (id_anggota)
        REFERENCES anggota (id_anggota),
    CONSTRAINT fk_peminjaman_perugas
        FOREIGN KEY (id_petugas)
        REFERENCES perugas (id_petugas)
);

-- ============================================================
-- 7. Tabel PENGEMBALIAN
-- ============================================================
CREATE TABLE pengembalian (
    id_pengembalian INT NOT NULL,
    tgl_peminjama DATE,
    CONSTRAINT pk_pengembalian PRIMARY KEY (id_pengembalian)
);

-- ============================================================
-- 8. Tabel DETAIL_PEMINAJAMAN
-- ============================================================
CREATE TABLE detail_peminajaman (
    id_detail INT NOT NULL,
    id_buku INT,
    id_pengembalian INT,
    id_peminjaman INT,
    jumlah_buku INT,
    CONSTRAINT pk_detail_peminajaman PRIMARY KEY (id_detail),
    CONSTRAINT fk_detail_buku
        FOREIGN KEY (id_buku)
        REFERENCES buku (id_buku),
    CONSTRAINT fk_detail_pengembalian
        FOREIGN KEY (id_pengembalian)
        REFERENCES pengembalian (id_pengembalian),
    CONSTRAINT fk_detail_peminjaman
        FOREIGN KEY (id_peminjaman)
        REFERENCES peminjaman (id_peminjaman)
);

-- ============================================================
-- Selesai
-- ============================================================
