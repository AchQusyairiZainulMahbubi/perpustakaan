<?php
/**
 * API Update: Mengubah data berdasarkan ID.
 * Mendukung input JSON dan form data via PUT atau POST.
 * Header: Content-Type: application/json
 */

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: PUT, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Baca input baik dari raw JSON maupun POST
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
        $idAnggota = (int)($inputData['id_anggota'] ?? $inputData['id'] ?? 0);
        $nama = trim($inputData['nama'] ?? '');
        $alamat = trim($inputData['alamat'] ?? '');
        $tangga_daftar = !empty($inputData['tangga_daftar']) ? $inputData['tangga_daftar'] : null;

        if ($idAnggota <= 0) {
            sendJsonResponse('error', 'ID anggota tidak valid', null, 400);
        }
        if (empty($nama)) {
            sendJsonResponse('error', 'Nama anggota wajib diisi', null, 400);
        }

        $stmt = $conn->prepare("UPDATE anggota SET nama = :nama, alamat = :alamat WHERE id_anggota = :id");
        $stmt->execute([
            ':nama'   => $nama,
            ':alamat' => $alamat,
            ':id'     => $idAnggota
        ]);

        sendJsonResponse('success', 'Data anggota berhasil diperbarui', ['id_anggota' => $idAnggota]);

    } else {
        // Default: Entity Buku
        $idBuku = (int)($inputData['id_buku'] ?? $inputData['id'] ?? 0);
        $judul = trim($inputData['judul'] ?? '');
        $penulis = trim($inputData['penulis'] ?? '');
        $eidisi = trim($inputData['eidisi'] ?? 'Edisi 1');
        $penerbit = trim($inputData['penerbit'] ?? '');
        $tahun_terbit = trim($inputData['tahun_terbit'] ?? '');
        $denda = (int)($inputData['denda'] ?? 1000);
        $batas_hari_pinjam = isset($inputData['batas_hari_pinjam']) ? max(1, (int)$inputData['batas_hari_pinjam']) : 7;
        $kategori = trim($inputData['nama_kategori'] ?? '');
        $ratingVal = isset($inputData['jumlah_rating']) ? max(1, min(5, (int)$inputData['jumlah_rating'])) : null;

        if ($idBuku <= 0) {
            sendJsonResponse('error', 'ID buku tidak valid', null, 400);
        }
        if (empty($judul)) {
            sendJsonResponse('error', 'Judul buku wajib diisi', null, 400);
        }

        // Format tanggal jika hanya input tahun
        if (strlen($tahun_terbit) === 4 && is_numeric($tahun_terbit)) {
            $tahun_terbit = $tahun_terbit . '-01-01';
        }

        // Cek keberadaan buku
        $checkStmt = $conn->prepare("SELECT id_buku, id_rating FROM buku WHERE id_buku = :id");
        $checkStmt->execute([':id' => $idBuku]);
        $currentBook = $checkStmt->fetch();

        if (!$currentBook) {
            sendJsonResponse('error', 'Buku dengan ID tersebut tidak ditemukan', null, 404);
        }

        $conn->beginTransaction();

        // 1. Update atau tentukan rating
        $idRating = $currentBook['id_rating'];
        if ($ratingVal !== null) {
            $stmtR = $conn->prepare("SELECT id_rating FROM rating WHERE jumlah_rating = :r LIMIT 1");
            $stmtR->execute([':r' => $ratingVal]);
            $rRow = $stmtR->fetch();
            if ($rRow) {
                $idRating = (int)$rRow['id_rating'];
            } else {
                $stmtIdR = $conn->query("SELECT COALESCE(MAX(id_rating), 0) + 1 AS next_id FROM rating");
                $idRating = (int)$stmtIdR->fetch()['next_id'];
                $insR = $conn->prepare("INSERT INTO rating (id_rating, jumlah_rating) VALUES (:id, :jml)");
                $insR->execute([':id' => $idRating, ':jml' => $ratingVal]);
            }
        }

        // 2. Update tabel buku
        $stmtBuku = $conn->prepare("UPDATE buku SET 
                                        judul = :judul, 
                                        penulis = :penulis, 
                                        eidisi = :eidisi, 
                                        penerbit = :penerbit, 
                                        tahun_terbit = :tahun_terbit, 
                                        denda = :denda,
                                        batas_hari_pinjam = :batas_hari,
                                        id_rating = :id_rating
                                    WHERE id_buku = :id");
        $stmtBuku->execute([
            ':judul'        => $judul,
            ':penulis'      => $penulis,
            ':eidisi'       => $eidisi,
            ':penerbit'     => $penerbit,
            ':tahun_terbit' => $tahun_terbit,
            ':denda'        => $denda,
            ':batas_hari'   => $batas_hari_pinjam,
            ':id_rating'    => $idRating,
            ':id'           => $idBuku
        ]);

        // 3. Update atau insert kategori
        if (!empty($kategori)) {
            $stmtCheckKat = $conn->prepare("SELECT id_kategori FROM kategori WHERE id_buku = :id_buku LIMIT 1");
            $stmtCheckKat->execute([':id_buku' => $idBuku]);
            $katRow = $stmtCheckKat->fetch();

            if ($katRow) {
                $stmtUpKat = $conn->prepare("UPDATE kategori SET nama_kategori = :nama WHERE id_kategori = :id_kat");
                $stmtUpKat->execute([
                    ':nama'   => $kategori,
                    ':id_kat' => $katRow['id_kategori']
                ]);
            } else {
                $stmtIdK = $conn->query("SELECT COALESCE(MAX(id_kategori), 0) + 1 AS next_id FROM kategori");
                $nextIdK = (int)$stmtIdK->fetch()['next_id'];
                $stmtInKat = $conn->prepare("INSERT INTO kategori (id_kategori, id_buku, nama_kategori) VALUES (:id_kat, :id_buku, :nama)");
                $stmtInKat->execute([
                    ':id_kat'  => $nextIdK,
                    ':id_buku' => $idBuku,
                    ':nama'    => $kategori
                ]);
            }
        }

        $conn->commit();

        sendJsonResponse('success', 'Data buku berhasil diperbarui', [
            'id_buku' => $idBuku,
            'judul'   => $judul
        ]);
    }
} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    sendJsonResponse('error', 'Gagal memperbarui data: ' . $e->getMessage(), null, 500);
}
