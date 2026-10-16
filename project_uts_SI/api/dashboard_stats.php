<?php
/**
 * API Dashboard Stats: Menghitung total ringkasan data dan rekap untuk grafik Chart.js.
 * Header: Content-Type: application/json
 */

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

$database = new Database();
$conn = $database->getConnection();

try {
    // 1. Ringkasan Total Record
    $totalBuku = (int)$conn->query("SELECT COUNT(*) AS total FROM buku")->fetch()['total'];
    $totalAnggota = (int)$conn->query("SELECT COUNT(*) AS total FROM anggota")->fetch()['total'];
    $totalPeminjaman = (int)$conn->query("SELECT COUNT(*) AS total FROM peminjaman")->fetch()['total'];
    $totalPengembalian = (int)$conn->query("SELECT COUNT(*) AS total FROM pengembalian")->fetch()['total'];
    $totalPetugas = (int)$conn->query("SELECT COUNT(*) AS total FROM perugas")->fetch()['total'];
    $totalKategori = (int)$conn->query("SELECT COUNT(DISTINCT nama_kategori) AS total FROM kategori WHERE nama_kategori IS NOT NULL AND nama_kategori != ''")->fetch()['total'];
    $totalDenda = (int)$conn->query("SELECT COALESCE(SUM(denda_total), 0) AS total FROM pengembalian")->fetch()['total'];
    $totalPinjamAktif = (int)$conn->query("SELECT COUNT(*) AS total FROM peminjaman WHERE status = 'Dipinjam'")->fetch()['total'];
    $totalTerlambat = (int)$conn->query("SELECT COUNT(*) AS total FROM peminjaman WHERE status = 'Dipinjam' AND tgl_kembali_rencana < CURDATE()")->fetch()['total'];

    // 2. Rekap Data Tren Peminjaman per Bulan (Untuk Line/Bar Chart)
    $stmtTrend = $conn->query("
        SELECT 
            DATE_FORMAT(tgl_pinjam, '%Y-%m') AS periode,
            DATE_FORMAT(tgl_pinjam, '%b %Y') AS label_bulan,
            COUNT(*) AS total_pinjam
        FROM peminjaman
        WHERE tgl_pinjam IS NOT NULL
        GROUP BY DATE_FORMAT(tgl_pinjam, '%Y-%m'), DATE_FORMAT(tgl_pinjam, '%b %Y')
        ORDER BY periode ASC
    ");
    $trendRows = $stmtTrend->fetchAll();

    $trendLabels = [];
    $trendValues = [];
    foreach ($trendRows as $row) {
        $trendLabels[] = $row['label_bulan'];
        $trendValues[] = (int)$row['total_pinjam'];
    }

    // Jika data pinjam masih kosong, buat fallback label agar grafik tetap cantik
    if (empty($trendLabels)) {
        $trendLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun'];
        $trendValues = [0, 0, 0, 0, 0, 0];
    }

    // 3. Rekap Data Distribusi Buku per Kategori (Untuk Doughnut / Bar Chart)
    $stmtCat = $conn->query("
        SELECT 
            COALESCE(k.nama_kategori, 'Umum') AS kategori,
            COUNT(b.id_buku) AS total
        FROM buku b
        LEFT JOIN kategori k ON b.id_buku = k.id_buku
        GROUP BY COALESCE(k.nama_kategori, 'Umum')
        ORDER BY total DESC
        LIMIT 7
    ");
    $catRows = $stmtCat->fetchAll();

    $catLabels = [];
    $catValues = [];
    foreach ($catRows as $row) {
        $catLabels[] = $row['kategori'];
        $catValues[] = (int)$row['total'];
    }

    // 4. Rekap Data Distribusi Rating Buku
    $stmtRating = $conn->query("
        SELECT 
            COALESCE(r.jumlah_rating, 0) AS bintang,
            COUNT(b.id_buku) AS total
        FROM buku b
        LEFT JOIN rating r ON b.id_rating = r.id_rating
        GROUP BY COALESCE(r.jumlah_rating, 0)
        ORDER BY bintang DESC
    ");
    $ratingRows = $stmtRating->fetchAll();

    // 5. Buku Terpopuler / Terbaru untuk Ringkasan
    $stmtRecent = $conn->query("
        SELECT 
            b.id_buku,
            b.judul,
            b.penulis,
            b.penerbit,
            COALESCE(k.nama_kategori, 'Umum') AS nama_kategori,
            COALESCE(r.jumlah_rating, 0) AS jumlah_rating
        FROM buku b
        LEFT JOIN rating r ON b.id_rating = r.id_rating
        LEFT JOIN kategori k ON b.id_buku = k.id_buku
        ORDER BY b.id_buku DESC
        LIMIT 5
    ");
    $recentBooks = $stmtRecent->fetchAll();

    $responsePayload = [
        'summary' => [
            'total_buku'         => $totalBuku,
            'total_anggota'      => $totalAnggota,
            'total_peminjaman'   => $totalPeminjaman,
            'total_pengembalian' => $totalPengembalian,
            'total_petugas'      => $totalPetugas,
            'total_kategori'     => $totalKategori,
            'total_denda'        => $totalDenda,
            'total_pinjam_aktif' => $totalPinjamAktif,
            'total_terlambat'    => $totalTerlambat,
        ],
        'charts' => [
            'trend_peminjaman' => [
                'labels' => $trendLabels,
                'data'   => $trendValues,
            ],
            'distribusi_kategori' => [
                'labels' => $catLabels,
                'data'   => $catValues,
            ],
            'distribusi_rating' => $ratingRows,
        ],
        'recent_books' => $recentBooks,
    ];

    sendJsonResponse('success', 'Statistik dashboard berhasil dimuat', $responsePayload);

} catch (PDOException $e) {
    sendJsonResponse('error', 'Gagal memuat statistik dashboard: ' . $e->getMessage(), null, 500);
}
