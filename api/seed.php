<?php
/**
 * API Seed: Helper untuk mengisi data awal jika database masih kosong.
 */

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');

$database = new Database();
$conn = $database->getConnection();

try {
    $seedSqlFile = __DIR__ . '/../sample_data.sql';
    if (!file_exists($seedSqlFile)) {
        sendJsonResponse('error', 'File sample_data.sql tidak ditemukan', null, 404);
    }

    $sql = file_get_contents($seedSqlFile);
    // Jalankan queries
    $conn->exec($sql);

    sendJsonResponse('success', 'Data sampel berhasil dimasukkan ke dalam database!', null);
} catch (Exception $e) {
    sendJsonResponse('error', 'Gagal memasukkan data sampel: ' . $e->getMessage(), null, 500);
}
