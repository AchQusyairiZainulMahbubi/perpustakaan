<?php
/**
 * API Read: Mengambil semua data dengan dukungan search dan filter.
 * Header: Content-Type: application/json
 */

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

$database = new Database();
$conn = $database->getConnection();

$entity = isset($_GET['entity']) ? strtolower(trim($_GET['entity'])) : 'buku';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$kategori = isset($_GET['kategori']) ? trim($_GET['kategori']) : '';
$tahun = isset($_GET['tahun']) ? trim($_GET['tahun']) : '';

try {
    if ($entity === 'anggota') {
        $query = "SELECT id_anggota, nama, alamat, tangga_daftar FROM anggota WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $query .= " AND (nama LIKE :search OR alamat LIKE :search)";
            $params[':search'] = "%{$search}%";
        }

        $query .= " ORDER BY id_anggota DESC";
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        $data = $stmt->fetchAll();

        sendJsonResponse('success', 'Data anggota berhasil diambil', [
            'entity' => 'anggota',
            'count'  => count($data),
            'items'  => $data
        ]);

    } elseif ($entity === 'peminjaman') {
        $query = "SELECT 
                    p.id_peminjaman,
                    p.tgl_pinjam,
                    a.nama AS nama_anggota,
                    pet.nama_petugas,
                    b.judul AS judul_buku,
                    dp.jumlah_buku,
                    pg.tgl_peminjama AS tgl_kembali,
                    CASE 
                        WHEN pg.tgl_peminjama IS NOT NULL THEN 'Dikembalikan'
                        ELSE 'Dipinjam'
                    END AS status_pinjam
                  FROM peminjaman p
                  LEFT JOIN anggota a ON p.id_anggota = a.id_anggota
                  LEFT JOIN perugas pet ON p.id_petugas = pet.id_petugas
                  LEFT JOIN detail_peminajaman dp ON p.id_peminjaman = dp.id_peminjaman
                  LEFT JOIN buku b ON dp.id_buku = b.id_buku
                  LEFT JOIN pengembalian pg ON dp.id_pengembalian = pg.id_pengembalian
                  WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $query .= " AND (a.nama LIKE :search OR b.judul LIKE :search OR pet.nama_petugas LIKE :search)";
            $params[':search'] = "%{$search}%";
        }

        $query .= " ORDER BY p.id_peminjaman DESC";
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        $data = $stmt->fetchAll();

        sendJsonResponse('success', 'Data peminjaman berhasil diambil', [
            'entity' => 'peminjaman',
            'count'  => count($data),
            'items'  => $data
        ]);

    } else {
        // Default: Entity Buku
        $query = "SELECT 
                    b.id_buku,
                    b.judul,
                    b.penulis,
                    b.eidisi,
                    b.penerbit,
                    b.tahun_terbit,
                    b.denda,
                    COALESCE(b.batas_hari_pinjam, 7) AS batas_hari_pinjam,
                    b.id_rating,
                    COALESCE(r.jumlah_rating, 0) AS jumlah_rating,
                    k.id_kategori,
                    COALESCE(k.nama_kategori, 'Umum') AS nama_kategori
                  FROM buku b
                  LEFT JOIN rating r ON b.id_rating = r.id_rating
                  LEFT JOIN kategori k ON b.id_buku = k.id_buku
                  WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $query .= " AND (b.judul LIKE :search OR b.penulis LIKE :search OR b.penerbit LIKE :search)";
            $params[':search'] = "%{$search}%";
        }

        if (!empty($kategori)) {
            $query .= " AND k.nama_kategori = :kategori";
            $params[':kategori'] = $kategori;
        }

        if (!empty($tahun)) {
            $query .= " AND YEAR(b.tahun_terbit) = :tahun";
            $params[':tahun'] = $tahun;
        }

        $query .= " ORDER BY b.id_buku DESC";
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        $data = $stmt->fetchAll();

        // Ambil daftar kategori unik untuk dropdown filter
        $catStmt = $conn->query("SELECT DISTINCT nama_kategori FROM kategori WHERE nama_kategori IS NOT NULL AND nama_kategori != '' ORDER BY nama_kategori ASC");
        $categories = $catStmt->fetchAll(PDO::FETCH_COLUMN);

        // Ambil daftar tahun unik untuk filter
        $yearStmt = $conn->query("SELECT DISTINCT YEAR(tahun_terbit) AS thn FROM buku WHERE tahun_terbit IS NOT NULL ORDER BY thn DESC");
        $years = $yearStmt->fetchAll(PDO::FETCH_COLUMN);

        sendJsonResponse('success', 'Data buku berhasil diambil', [
            'entity'     => 'buku',
            'count'      => count($data),
            'items'      => $data,
            'categories' => $categories,
            'years'      => $years
        ]);
    }
} catch (PDOException $e) {
    sendJsonResponse('error', 'Gagal mengambil data: ' . $e->getMessage(), null, 500);
}
