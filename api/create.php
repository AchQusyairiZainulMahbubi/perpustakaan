<?php
/**
 * API Create: Menambah data baru ke database.
 * Mendukung input JSON maupun POST form data.
 * Header: Content-Type: application/json
 */

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

// Cek method POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse('error', 'Metode request harus POST', null, 405);
}

// Baca input baik dari raw JSON maupun form-data
$rawInput = file_get_contents('php://input');
$inputData = json_decode($rawInput, true);
if (!is_array($inputData)) {
    $inputData = $_POST;
}

$database = new Database();
$conn = $database->getConnection();

$entity = isset($inputData['entity']) ? strtolower(trim($inputData['entity'])) : 'buku';

try {
    if ($entity === 'anggota') {
        $nama = trim($inputData['nama'] ?? '');
        $alamat = trim($inputData['alamat'] ?? '');
        $tangga_daftar = !empty($inputData['tangga_daftar']) ? $inputData['tangga_daftar'] : date('Y-m-d');

        if (empty($nama)) {
            sendJsonResponse('error', 'Nama anggota wajib diisi', null, 400);
        }

        // Generate ID jika belum auto increment
        $stmtId = $conn->query("SELECT COALESCE(MAX(id_anggota), 0) + 1 AS next_id FROM anggota");
        $nextId = (int)$stmtId->fetch()['next_id'];

        $stmt = $conn->prepare("INSERT INTO anggota (id_anggota, nama, alamat, tangga_daftar) VALUES (:id, :nama, :alamat, :tangga_daftar)");
        $stmt->execute([
            ':id'            => $nextId,
            ':nama'          => $nama,
            ':alamat'        => $alamat,
            ':tangga_daftar' => $tangga_daftar
        ]);

        sendJsonResponse('success', 'Anggota berhasil ditambahkan', ['id_anggota' => $nextId], 201);

    } else {
        // Default: Entity Buku
        $judul = trim($inputData['judul'] ?? '');
        $penulis = trim($inputData['penulis'] ?? '');
        $eidisi = trim($inputData['eidisi'] ?? 'Edisi 1');
        $penerbit = trim($inputData['penerbit'] ?? '');
        $tahun_terbit = trim($inputData['tahun_terbit'] ?? date('Y-m-d'));
        $denda = (int)($inputData['denda'] ?? 1000);
        $batas_hari_pinjam = max(1, (int)($inputData['batas_hari_pinjam'] ?? 7));
        $kategori = trim($inputData['nama_kategori'] ?? 'Umum');
        $ratingVal = max(1, min(5, (int)($inputData['jumlah_rating'] ?? 5)));

        // Validasi input wajib
        if (empty($judul)) {
            sendJsonResponse('error', 'Judul buku wajib diisi', null, 400);
        }
        if (empty($penulis)) {
            sendJsonResponse('error', 'Penulis buku wajib diisi', null, 400);
        }
        if (empty($penerbit)) {
            sendJsonResponse('error', 'Penerbit buku wajib diisi', null, 400);
        }

        // Format tanggal jika hanya input tahun (e.g. "2023" -> "2023-01-01")
        if (strlen($tahun_terbit) === 4 && is_numeric($tahun_terbit)) {
            $tahun_terbit = $tahun_terbit . '-01-01';
        }

        $conn->beginTransaction();

        // 1. Dapatkan atau buat rating
        $stmtRating = $conn->prepare("SELECT id_rating FROM rating WHERE id_rating = :r OR jumlah_rating = :r LIMIT 1");
        $stmtRating->execute([':r' => $ratingVal]);
        $ratingRow = $stmtRating->fetch();

        if ($ratingRow) {
            $idRating = (int)$ratingRow['id_rating'];
        } else {
            $stmtIdR = $conn->query("SELECT COALESCE(MAX(id_rating), 0) + 1 AS next_id FROM rating");
            $idRating = (int)$stmtIdR->fetch()['next_id'];
            $insR = $conn->prepare("INSERT INTO rating (id_rating, jumlah_rating) VALUES (:id, :jml)");
            $insR->execute([':id' => $idRating, ':jml' => $ratingVal]);
        }

        // 2. Generate ID Buku baru
        $stmtIdB = $conn->query("SELECT COALESCE(MAX(id_buku), 0) + 1 AS next_id FROM buku");
        $nextIdBuku = (int)$stmtIdB->fetch()['next_id'];

        // 3. Insert ke tabel buku
        $stmtBuku = $conn->prepare("INSERT INTO buku (id_buku, id_rating, judul, penulis, eidisi, penerbit, tahun_terbit, denda, batas_hari_pinjam) 
                                    VALUES (:id, :id_rating, :judul, :penulis, :eidisi, :penerbit, :tahun_terbit, :denda, :batas_hari)");
        $stmtBuku->execute([
            ':id'           => $nextIdBuku,
            ':id_rating'    => $idRating,
            ':judul'        => $judul,
            ':penulis'      => $penulis,
            ':eidisi'       => $eidisi,
            ':penerbit'     => $penerbit,
            ':tahun_terbit' => $tahun_terbit,
            ':denda'        => $denda,
            ':batas_hari'   => $batas_hari_pinjam
        ]);

        // 4. Insert kategori untuk buku ini
        if (!empty($kategori)) {
            $stmtIdK = $conn->query("SELECT COALESCE(MAX(id_kategori), 0) + 1 AS next_id FROM kategori");
            $nextIdKategori = (int)$stmtIdK->fetch()['next_id'];

            $stmtKat = $conn->prepare("INSERT INTO kategori (id_kategori, id_buku, nama_kategori) VALUES (:id_kat, :id_buku, :nama_kat)");
            $stmtKat->execute([
                ':id_kat'   => $nextIdKategori,
                ':id_buku'  => $nextIdBuku,
                ':nama_kat' => $kategori
            ]);
        }

        $conn->commit();

        sendJsonResponse('success', 'Buku baru berhasil ditambahkan', [
            'id_buku' => $nextIdBuku,
            'judul'   => $judul
        ], 201);
    }
} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    sendJsonResponse('error', 'Gagal menambahkan data: ' . $e->getMessage(), null, 500);
}
