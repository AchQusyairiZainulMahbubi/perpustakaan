<?php
/**
 * Schema Upgrade: Tambahkan kolom baru untuk sistem peminjaman & denda
 * Jalankan sekali saja, lalu hapus file ini.
 */
require_once __DIR__ . '/../config/database.php';

header('Content-Type: text/html; charset=UTF-8');

$db = new Database();
$conn = $db->getConnection();

$migrations = [
    // Peminjaman
    "ALTER TABLE peminjaman ADD COLUMN tgl_kembali_rencana DATE" => "Tambah tgl_kembali_rencana ke peminjaman",
    "ALTER TABLE peminjaman ADD COLUMN status ENUM('Dipinjam','Dikembalikan') DEFAULT 'Dipinjam'" => "Tambah status ke peminjaman",
    // Pengembalian
    "ALTER TABLE pengembalian ADD COLUMN id_peminjaman INT" => "Tambah id_peminjaman ke pengembalian",
    "ALTER TABLE pengembalian ADD COLUMN tgl_kembali DATE" => "Tambah tgl_kembali ke pengembalian",
    "ALTER TABLE pengembalian ADD COLUMN denda_total INT DEFAULT 0" => "Tambah denda_total ke pengembalian",
    "ALTER TABLE pengembalian ADD COLUMN status_denda ENUM('Lunas','Belum Lunas') DEFAULT 'Lunas'" => "Tambah status_denda ke pengembalian",
    // Anggota
    "ALTER TABLE anggota ADD COLUMN no_hp CHAR(20)" => "Tambah no_hp ke anggota",
    "ALTER TABLE anggota ADD COLUMN email CHAR(100)" => "Tambah email ke anggota",
    // Detail
    "ALTER TABLE detail_peminajaman ADD COLUMN tgl_kembali_rencana DATE" => "Tambah tgl_kembali_rencana ke detail_peminajaman",
    // Buku: batas hari pinjam (berbeda-beda per buku)
    "ALTER TABLE buku ADD COLUMN batas_hari_pinjam INT DEFAULT 7" => "Tambah batas_hari_pinjam ke buku",
];

echo "<h2>Schema Upgrade</h2><ul>";

foreach ($migrations as $sql => $desc) {
    try {
        $conn->exec($sql);
        echo "<li style='color:green'>✅ $desc</li>";
    } catch (PDOException $e) {
        $msg = $e->getMessage();
        if (strpos($msg, 'Duplicate column') !== false || strpos($msg, '1060') !== false) {
            echo "<li style='color:gray'>⏭️ $desc (sudah ada)</li>";
        } else {
            echo "<li style='color:red'>❌ $desc — $msg</li>";
        }
    }
}

// Berikan variasi batas hari pinjam untuk setiap buku
$durasiBuku = [
    1 => 7,   // PHP & MySQL: 7 hari
    2 => 14,  // Clean Code: 14 hari
    3 => 10,  // SIM: 10 hari
    4 => 14,  // Rekayasa PL: 14 hari
    5 => 7,   // Struktur Data: 7 hari
    6 => 5,   // Basis Data: 5 hari
    7 => 7,   // UI/UX: 7 hari
    8 => 21,  // AI & ML: 21 hari
];

foreach ($durasiBuku as $id => $durasi) {
    $st = $conn->prepare("UPDATE buku SET batas_hari_pinjam = :d WHERE id_buku = :id");
    $st->execute([':d' => $durasi, ':id' => $id]);
}
echo "<li style='color:green'>✅ Update variasi batas hari pinjam tiap buku</li>";

echo "</ul><p>Selesai! Database telah ter-upgrade.</p>";
