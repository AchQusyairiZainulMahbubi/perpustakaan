-- ============================================================
-- Data Sampel untuk Sistem Informasi Perpustakaan
-- Database: perpustakaan_sistem_informasi
-- ============================================================

USE perpustakaan_sistem_informasi;

-- 1. Bersihkan data lama jika ada (urutan sesuai dependensi FK)
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE detail_peminajaman;
TRUNCATE TABLE pengembalian;
TRUNCATE TABLE peminjaman;
TRUNCATE TABLE kategori;
TRUNCATE TABLE buku;
TRUNCATE TABLE rating;
TRUNCATE TABLE perugas;
TRUNCATE TABLE anggota;
SET FOREIGN_KEY_CHECKS = 1;

-- 2. Insert Data ANGGOTA
INSERT INTO anggota (id_anggota, nama, alamat, no_hp, email, tangga_daftar) VALUES
(1, 'Ahmad Fauzi', 'Jl. Malioboro No. 12, Yogyakarta', '081234567890', 'ahmad.fauzi@gmail.com', '2024-01-10'),
(2, 'Siti Nurhaliza', 'Jl. Kaliurang KM 5, Sleman', '082198765432', 'siti.nur@gmail.com', '2024-01-15'),
(3, 'Budi Santoso', 'Jl. Gejayan No. 45, Sleman', '085712345678', 'budi.santoso@gmail.com', '2024-02-01'),
(4, 'Dewi Lestari', 'Jl. Kusumanegara No. 88, Yogyakarta', '087811223344', 'dewi.lestari@gmail.com', '2024-02-18'),
(5, 'Rian Hidayat', 'Jl. Bantul No. 20, Bantul', '081399887766', 'rian.h@gmail.com', '2024-03-05'),
(6, 'Annisa Rahma', 'Jl. Ring Road Utara, Sleman', '089655443322', 'annisa.r@gmail.com', '2024-03-22'),
(7, 'Fajar Pratama', 'Jl. Parangtritis KM 4, Bantul', '085244332211', 'fajar.p@gmail.com', '2024-04-10'),
(8, 'Citra Kirana', 'Jl. Solo KM 9, Sleman', '081288776655', 'citra.k@gmail.com', '2024-05-02');

-- 3. Insert Data PERUGAS (Petugas)
INSERT INTO perugas (id_petugas, nama_petugas) VALUES
(1, 'Bambang Supriyanto'),
(2, 'Endang Sri Wahyuni'),
(3, 'Teguh Wicaksono');

-- 4. Insert Data RATING (Skala 1 s.d 5 Bintang)
INSERT INTO rating (id_rating, jumlah_rating) VALUES
(1, 1),
(2, 2),
(3, 3),
(4, 4),
(5, 5);

-- 5. Insert Data BUKU (dengan variasi batas hari pinjam & tarif denda per hari)
INSERT INTO buku (id_buku, id_rating, judul, penulis, eidisi, penerbit, tahun_terbit, denda, batas_hari_pinjam) VALUES
(1, 5, 'Belajar Pemrograman PHP & MySQL', 'Budi Raharjo', 'Edisi 3', 'Informatika', '2022-01-10', 1000, 7),
(2, 5, 'Clean Code: A Handbook of Agile', 'Robert C. Martin', 'Edisi 1', 'Prentice Hall', '2020-08-15', 2000, 14),
(3, 4, 'Sistem Informasi Manajemen', 'Jogiyanto H.M.', 'Edisi 4', 'Andi Offset', '2021-03-20', 1000, 10),
(4, 4, 'Dasar-Dasar Rekayasa Perangkat Lunak', 'Roger S. Pressman', 'Edisi 7', 'McGraw-Hill', '2019-11-05', 1500, 14),
(5, 5, 'Struktur Data & Algoritma Dasar', 'Rinaldi Munir', 'Edisi 2', 'Informatika', '2022-05-14', 1000, 7),
(6, 3, 'Pengantar Basis Data Relasional', 'Abdul Kadir', 'Edisi 1', 'Andi Offset', '2020-02-28', 1000, 5),
(7, 4, 'Desain Antarmuka UI/UX Modern', 'Don Norman', 'Edisi 2', 'Elex Media', '2023-01-12', 1500, 7),
(8, 5, 'Kecerdasan Buatan & Machine Learning', 'Suyanto', 'Edisi 1', 'Informatika', '2023-06-25', 2500, 21);

-- 6. Insert Data KATEGORI
INSERT INTO kategori (id_kategori, id_buku, nama_kategori) VALUES
(1, 1, 'Teknologi Informasi'),
(2, 2, 'Software Engineering'),
(3, 3, 'Sistem Informasi'),
(4, 4, 'Software Engineering'),
(5, 5, 'Ilmu Komputer'),
(6, 6, 'Basis Data'),
(7, 7, 'Desain & UI/UX'),
(8, 8, 'Kecerdasan Buatan');

-- 7. Insert Data PENGEMBALIAN (Histori transaksi selesai)
INSERT INTO pengembalian (id_pengembalian, tgl_peminjama, id_peminjaman, tgl_kembali, denda_total, status_denda) VALUES
(1, '2024-01-20', 1, '2024-01-26', 0, 'Lunas'),
(2, '2024-02-01', 2, '2024-02-18', 6000, 'Lunas'),
(3, '2024-02-15', 3, '2024-02-23', 0, 'Lunas'),
(4, '2024-03-01', 4, '2024-03-16', 1500, 'Lunas'),
(5, '2024-03-18', 5, '2024-03-24', 0, 'Lunas');

-- 8. Insert Data PEMINJAMAN
-- Catatan: Variasi data peminjaman aktif tepat waktu & melewati batas (overdue) untuk simulasi denda real-time
INSERT INTO peminjaman (id_peminjaman, id_anggota, id_petugas, tgl_pinjam, tgl_kembali_rencana, status) VALUES
(1, 1, 1, '2024-01-20', '2024-01-27', 'Dikembalikan'),
(2, 2, 2, '2024-02-01', '2024-02-15', 'Dikembalikan'),
(3, 3, 1, '2024-02-15', '2024-02-25', 'Dikembalikan'),
(4, 4, 3, '2024-03-01', '2024-03-15', 'Dikembalikan'),
(5, 5, 2, '2024-03-18', '2024-03-25', 'Dikembalikan'),
-- Overdue 4 Hari (Budi Santoso - Clean Code)
(6, 3, 1, DATE_SUB(CURDATE(), INTERVAL 18 DAY), DATE_SUB(CURDATE(), INTERVAL 4 DAY), 'Dipinjam'),
-- Tepat Waktu / Sisa 5 Hari (Ahmad Fauzi - UI/UX Modern)
(7, 1, 2, DATE_SUB(CURDATE(), INTERVAL 2 DAY), DATE_ADD(CURDATE(), INTERVAL 5 DAY), 'Dipinjam'),
-- Overdue 9 Hari (Siti Nurhaliza - Kecerdasan Buatan, denda Rp 2.500/hari = Rp 22.500)
(8, 2, 3, DATE_SUB(CURDATE(), INTERVAL 30 DAY), DATE_SUB(CURDATE(), INTERVAL 9 DAY), 'Dipinjam'),
-- Overdue 3 Hari (Dewi Lestari - Pengantar Basis Data, denda Rp 1.000/hari = Rp 3.000)
(9, 4, 1, DATE_SUB(CURDATE(), INTERVAL 8 DAY), DATE_SUB(CURDATE(), INTERVAL 3 DAY), 'Dipinjam'),
-- Overdue 12 Hari (Rian Hidayat - Rekayasa Perangkat Lunak, denda Rp 1.500/hari = Rp 18.000)
(10, 5, 2, DATE_SUB(CURDATE(), INTERVAL 26 DAY), DATE_SUB(CURDATE(), INTERVAL 12 DAY), 'Dipinjam'),
-- Overdue 6 Hari (Annisa Rahma - PHP & MySQL, denda Rp 1.000/hari = Rp 6.000)
(11, 6, 1, DATE_SUB(CURDATE(), INTERVAL 13 DAY), DATE_SUB(CURDATE(), INTERVAL 6 DAY), 'Dipinjam'),
-- Overdue 2 Hari (Ahmad Fauzi - Struktur Data, denda Rp 1.000/hari = Rp 2.000)
(12, 1, 3, DATE_SUB(CURDATE(), INTERVAL 9 DAY), DATE_SUB(CURDATE(), INTERVAL 2 DAY), 'Dipinjam');

-- 9. Insert Data DETAIL_PEMINAJAMAN
INSERT INTO detail_peminajaman (id_detail, id_buku, id_pengembalian, id_peminjaman, jumlah_buku, tgl_kembali_rencana) VALUES
(1, 1, 1, 1, 1, '2024-01-27'),
(2, 2, 2, 2, 1, '2024-02-15'),
(3, 3, 3, 3, 1, '2024-02-25'),
(4, 4, 4, 4, 1, '2024-03-15'),
(5, 5, 5, 5, 1, '2024-03-25'),
(6, 2, NULL, 6, 1, DATE_SUB(CURDATE(), INTERVAL 4 DAY)),
(7, 7, NULL, 7, 1, DATE_ADD(CURDATE(), INTERVAL 5 DAY)),
(8, 8, NULL, 8, 1, DATE_SUB(CURDATE(), INTERVAL 9 DAY)),
(9, 6, NULL, 9, 1, DATE_SUB(CURDATE(), INTERVAL 3 DAY)),
(10, 4, NULL, 10, 1, DATE_SUB(CURDATE(), INTERVAL 12 DAY)),
(11, 1, NULL, 11, 1, DATE_SUB(CURDATE(), INTERVAL 6 DAY)),
(12, 5, NULL, 12, 1, DATE_SUB(CURDATE(), INTERVAL 2 DAY));

