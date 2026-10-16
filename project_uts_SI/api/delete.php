<?php
/**
 * API Delete: Menghapus data berdasarkan ID.
 * Mendukung metode DELETE, POST, dan query parameter id.
 * Header: Content-Type: application/json
 */

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: DELETE, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Ambil ID dari raw JSON, POST, atau Query parameter GET/DELETE
$rawInput = file_get_contents('php://input');
$inputData = json_decode($rawInput, true);
if (!is_array($inputData)) {
    $inputData = $_POST;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : (int)($inputData['id_buku'] ?? $inputData['id'] ?? 0);
$entity = isset($_GET['entity']) ? strtolower(trim($_GET['entity'])) : (isset($inputData['entity']) ? strtolower(trim($inputData['entity'])) : 'buku');

if ($id <= 0) {
    sendJsonResponse('error', 'ID data yang akan dihapus tidak valid', null, 400);
}

$database = new Database();
$conn = $database->getConnection();

try {
    if ($entity === 'anggota') {
        $check = $conn->prepare("SELECT id_anggota, nama FROM anggota WHERE id_anggota = :id");
        $check->execute([':id' => $id]);
        $anggota = $check->fetch();

        if (!$anggota) {
            sendJsonResponse('error', 'Data anggota tidak ditemukan', null, 404);
        }

        $conn->beginTransaction();
        // Hapus referensi peminjaman jika ada
        $conn->prepare("DELETE FROM peminjaman WHERE id_anggota = :id")->execute([':id' => $id]);
        $conn->prepare("DELETE FROM anggota WHERE id_anggota = :id")->execute([':id' => $id]);
        $conn->commit();

        sendJsonResponse('success', "Anggota '{$anggota['nama']}' berhasil dihapus", ['id' => $id]);

    } else {
        // Default: Entity Buku
        $check = $conn->prepare("SELECT id_buku, judul FROM buku WHERE id_buku = :id");
        $check->execute([':id' => $id]);
        $buku = $check->fetch();

        if (!$buku) {
            sendJsonResponse('error', 'Data buku tidak ditemukan', null, 404);
        }

        $conn->beginTransaction();

        // 1. Hapus relasi di detail_peminajaman jika ada
        $stmtDelDetail = $conn->prepare("DELETE FROM detail_peminajaman WHERE id_buku = :id");
        $stmtDelDetail->execute([':id' => $id]);

        // 2. Hapus relasi di kategori jika ada
        $stmtDelKat = $conn->prepare("DELETE FROM kategori WHERE id_buku = :id");
        $stmtDelKat->execute([':id' => $id]);

        // 3. Hapus data di buku
        $stmtDelBuku = $conn->prepare("DELETE FROM buku WHERE id_buku = :id");
        $stmtDelBuku->execute([':id' => $id]);

        $conn->commit();

        sendJsonResponse('success', "Buku '{$buku['judul']}' berhasil dihapus", ['id_buku' => $id]);
    }
} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    sendJsonResponse('error', 'Gagal menghapus data: ' . $e->getMessage(), null, 500);
}
