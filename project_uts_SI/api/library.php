<?php
/**
 * API: Peminjaman & Pengembalian & Anggota
 * Endpoint terpadu untuk portal User & Admin
 */
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$db   = new Database();
$conn = $db->getConnection();

$action = isset($_GET['action']) ? $_GET['action'] : '';
$method = $_SERVER['REQUEST_METHOD'];

// Parse input
$raw   = file_get_contents('php://input');
$input = json_decode($raw, true);
if (!is_array($input)) $input = $_POST;

try {
    // ============================================================
    // GET: Daftar buku tersedia untuk dipinjam
    // ============================================================
    // ============================================================
    // GET: Daftar buku tersedia untuk dipinjam
    // ============================================================
    if ($action === 'buku_tersedia' && $method === 'GET') {
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $q = "SELECT b.id_buku, b.judul, b.penulis, b.penerbit, b.eidisi, b.denda,
                     COALESCE(b.batas_hari_pinjam, 7) AS batas_hari_pinjam,
                     COALESCE(r.jumlah_rating,0) AS jumlah_rating,
                     COALESCE(k.nama_kategori,'Umum') AS nama_kategori,
                     CASE WHEN EXISTS (
                         SELECT 1 FROM detail_peminajaman dp 
                         JOIN peminjaman p ON dp.id_peminjaman = p.id_peminjaman 
                         WHERE dp.id_buku = b.id_buku AND p.status = 'Dipinjam'
                     ) THEN 0 ELSE 1 END AS is_tersedia
              FROM buku b
              LEFT JOIN rating r ON b.id_rating = r.id_rating
              LEFT JOIN kategori k ON b.id_buku = k.id_buku
              WHERE 1=1";
        $params = [];
        if ($search) {
            $q .= " AND (b.judul LIKE :s OR b.penulis LIKE :s OR b.penerbit LIKE :s OR k.nama_kategori LIKE :s)";
            $params[':s'] = "%$search%";
        }
        $q .= " ORDER BY b.id_buku DESC";
        $st = $conn->prepare($q);
        $st->execute($params);
        sendJsonResponse('success', 'OK', ['items' => $st->fetchAll()]);
    }

    // ============================================================
    // GET: Daftar anggota (untuk peminjaman & admin)
    // ============================================================
    elseif ($action === 'daftar_anggota' && $method === 'GET') {
        $st = $conn->query("
            SELECT a.id_anggota, a.nama, a.alamat, a.no_hp, a.email, a.tangga_daftar,
                   (SELECT COUNT(*) FROM peminjaman p WHERE p.id_anggota = a.id_anggota AND p.status = 'Dipinjam') AS pinjam_aktif_count
            FROM anggota a 
            ORDER BY a.id_anggota DESC
        ");
        sendJsonResponse('success', 'OK', ['items' => $st->fetchAll()]);
    }

    // ============================================================
    // POST: Hapus anggota (Admin)
    // ============================================================
    elseif ($action === 'hapus_anggota' && $method === 'POST') {
        $id_anggota = (int)($input['id_anggota'] ?? 0);
        if (!$id_anggota) sendJsonResponse('error', 'ID Anggota tidak valid', null, 400);

        // Cek jika sedang punya pinjaman aktif
        $cek = $conn->prepare("SELECT COUNT(*) AS c FROM peminjaman WHERE id_anggota = :id AND status = 'Dipinjam'");
        $cek->execute([':id' => $id_anggota]);
        if ((int)$cek->fetch()['c'] > 0) {
            sendJsonResponse('error', 'Anggota masih memiliki pinjaman buku yang belum dikembalikan!', null, 400);
        }

        $st = $conn->prepare("DELETE FROM anggota WHERE id_anggota = :id");
        $st->execute([':id' => $id_anggota]);
        sendJsonResponse('success', 'Anggota berhasil dihapus');
    }

    // ============================================================
    // GET: Peminjaman aktif milik anggota tertentu (untuk return & denda)
    // ============================================================
    elseif ($action === 'pinjam_aktif' && $method === 'GET') {
        $id_anggota = isset($_GET['id_anggota']) ? (int)$_GET['id_anggota'] : 0;
        $q = "SELECT p.id_peminjaman, p.tgl_pinjam, p.tgl_kembali_rencana, p.status,
                     a.id_anggota, a.nama AS nama_anggota, a.no_hp,
                     b.judul AS judul_buku, b.denda AS denda_per_hari,
                     COALESCE(b.batas_hari_pinjam, 7) AS batas_hari_pinjam,
                     COALESCE(k.nama_kategori, 'Umum') AS nama_kategori,
                     dp.id_detail, dp.id_buku, dp.id_pengembalian
              FROM peminjaman p
              JOIN anggota a ON p.id_anggota = a.id_anggota
              JOIN detail_peminajaman dp ON p.id_peminjaman = dp.id_peminjaman
              JOIN buku b ON dp.id_buku = b.id_buku
              LEFT JOIN kategori k ON b.id_buku = k.id_buku
              WHERE p.status = 'Dipinjam'";
        $params = [];
        if ($id_anggota > 0) {
            $q .= " AND p.id_anggota = :id";
            $params[':id'] = $id_anggota;
        }
        $q .= " ORDER BY p.tgl_kembali_rencana ASC";
        $st = $conn->prepare($q);
        $st->execute($params);
        $rows = $st->fetchAll();

        // Hitung denda & sisa hari real-time
        $today = new DateTime(date('Y-m-d'));
        foreach ($rows as &$row) {
            $tglRencana = $row['tgl_kembali_rencana'] ? new DateTime($row['tgl_kembali_rencana']) : null;
            $row['denda_saat_ini'] = 0;
            $row['hari_terlambat'] = 0;
            $row['sisa_hari'] = 0;
            $row['is_overdue'] = false;

            if ($tglRencana) {
                if ($today > $tglRencana) {
                    $diff = $today->diff($tglRencana)->days;
                    $row['hari_terlambat'] = $diff;
                    $row['denda_saat_ini'] = $diff * (int)$row['denda_per_hari'];
                    $row['is_overdue'] = true;
                } else {
                    $diff = $tglRencana->diff($today)->days;
                    $row['sisa_hari'] = $diff;
                    $row['is_overdue'] = false;
                }
            }
        }
        unset($row);

        sendJsonResponse('success', 'OK', ['items' => $rows]);
    }

    // ============================================================
    // GET: Semua peminjaman (Admin view & Sirkulasi)
    // ============================================================
    elseif ($action === 'semua_peminjaman' && $method === 'GET') {
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $statusFilter = isset($_GET['status']) ? trim($_GET['status']) : '';
        $q = "SELECT p.id_peminjaman, p.tgl_pinjam, p.tgl_kembali_rencana, p.status,
                     a.id_anggota, a.nama AS nama_anggota, a.no_hp,
                     b.judul AS judul_buku, b.denda AS denda_per_hari,
                     COALESCE(b.batas_hari_pinjam, 7) AS batas_hari_pinjam,
                     COALESCE(k.nama_kategori, 'Umum') AS nama_kategori,
                     dp.id_detail, dp.id_buku,
                     pg.id_pengembalian, pg.tgl_kembali, pg.denda_total, pg.status_denda,
                     pet.nama_petugas
              FROM peminjaman p
              JOIN anggota a ON p.id_anggota = a.id_anggota
              LEFT JOIN perugas pet ON p.id_petugas = pet.id_petugas
              JOIN detail_peminajaman dp ON p.id_peminjaman = dp.id_peminjaman
              JOIN buku b ON dp.id_buku = b.id_buku
              LEFT JOIN kategori k ON b.id_buku = k.id_buku
              LEFT JOIN pengembalian pg ON dp.id_pengembalian = pg.id_pengembalian
              WHERE 1=1";
        $params = [];
        if ($search) {
            $q .= " AND (a.nama LIKE :s OR b.judul LIKE :s OR pet.nama_petugas LIKE :s)";
            $params[':s'] = "%$search%";
        }
        if ($statusFilter) {
            $q .= " AND p.status = :st";
            $params[':st'] = $statusFilter;
        }
        $q .= " ORDER BY p.id_peminjaman DESC";
        $st = $conn->prepare($q);
        $st->execute($params);
        $rows = $st->fetchAll();

        $today = new DateTime(date('Y-m-d'));
        foreach ($rows as &$row) {
            if ($row['status'] === 'Dipinjam') {
                $tglR = $row['tgl_kembali_rencana'] ? new DateTime($row['tgl_kembali_rencana']) : null;
                $row['hari_terlambat'] = 0;
                $row['denda_berjalan'] = 0;
                $row['is_overdue'] = false;
                if ($tglR && $today > $tglR) {
                    $diff = $today->diff($tglR)->days;
                    $row['hari_terlambat'] = $diff;
                    $row['denda_berjalan'] = $diff * (int)$row['denda_per_hari'];
                    $row['is_overdue'] = true;
                }
            }
        }
        unset($row);

        sendJsonResponse('success', 'OK', ['items' => $rows]);
    }

    // ============================================================
    // POST: Daftar Anggota Baru
    // ============================================================
    elseif ($action === 'daftar_anggota' && $method === 'POST') {
        $nama   = trim($input['nama'] ?? '');
        $alamat = trim($input['alamat'] ?? '');
        $no_hp  = trim($input['no_hp'] ?? '');
        $email  = trim($input['email'] ?? '');

        if (empty($nama)) sendJsonResponse('error', 'Nama lengkap wajib diisi', null, 400);

        $stId = $conn->query("SELECT COALESCE(MAX(id_anggota),0)+1 AS n FROM anggota");
        $newId = (int)$stId->fetch()['n'];

        $st = $conn->prepare("INSERT INTO anggota (id_anggota, nama, alamat, no_hp, email, tangga_daftar)
                               VALUES (:id, :nama, :alamat, :no_hp, :email, :tgl)");
        $st->execute([
            ':id'     => $newId,
            ':nama'   => $nama,
            ':alamat' => $alamat,
            ':no_hp'  => $no_hp,
            ':email'  => $email,
            ':tgl'    => date('Y-m-d'),
        ]);
        sendJsonResponse('success', "Pendaftaran berhasil! Nomor ID Anggota Anda: #$newId", [
            'id_anggota' => $newId, 
            'nama' => $nama,
            'no_hp' => $no_hp,
            'email' => $email,
            'alamat' => $alamat,
            'tangga_daftar' => date('Y-m-d')
        ], 201);
    }

    // ============================================================
    // POST: Pinjam Buku (Menggunakan batas waktu tiap buku yang berbeda)
    // ============================================================
    elseif ($action === 'pinjam_buku' && $method === 'POST') {
        $id_anggota = (int)($input['id_anggota'] ?? 0);
        $id_buku    = (int)($input['id_buku'] ?? 0);

        if (!$id_anggota) sendJsonResponse('error', 'Pilih anggota peminjam terlebih dahulu', null, 400);
        if (!$id_buku)    sendJsonResponse('error', 'Pilih buku yang akan dipinjam', null, 400);

        // Ambil info buku & batas hari pinjamnya
        $bukuSt = $conn->prepare("SELECT id_buku, judul, batas_hari_pinjam, denda FROM buku WHERE id_buku = :id");
        $bukuSt->execute([':id' => $id_buku]);
        $bukuData = $bukuSt->fetch();
        if (!$bukuData) sendJsonResponse('error', 'Buku tidak ditemukan di katalog', null, 404);

        // Cek apakah buku sedang dipinjam
        $cekSt = $conn->prepare("SELECT COUNT(*) AS c FROM detail_peminajaman dp
                                  JOIN peminjaman p ON dp.id_peminjaman = p.id_peminjaman
                                  WHERE dp.id_buku = :id_buku AND p.status = 'Dipinjam'");
        $cekSt->execute([':id_buku' => $id_buku]);
        if ((int)$cekSt->fetch()['c'] > 0) {
            sendJsonResponse('error', 'Buku "' . $bukuData['judul'] . '" sedang dipinjam oleh anggota lain. Silakan pilih buku lain.', null, 409);
        }

        // Hitung batas pengembalian berdasarkan batas_hari_pinjam buku tersebut
        $durasiHari = (int)($bukuData['batas_hari_pinjam'] ?? 7);
        if ($durasiHari <= 0) $durasiHari = 7;

        $tgl_pinjam = date('Y-m-d');
        $tgl_rencana = date('Y-m-d', strtotime("+$durasiHari days"));

        $conn->beginTransaction();

        // 1. Insert peminjaman
        $stId = $conn->query("SELECT COALESCE(MAX(id_peminjaman),0)+1 AS n FROM peminjaman");
        $newPinjam = (int)$stId->fetch()['n'];

        $st = $conn->prepare("INSERT INTO peminjaman (id_peminjaman, id_anggota, id_petugas, tgl_pinjam, tgl_kembali_rencana, status)
                               VALUES (:id, :id_a, 1, :tgl, :tgl_r, 'Dipinjam')");
        $st->execute([':id'=>$newPinjam, ':id_a'=>$id_anggota, ':tgl'=>$tgl_pinjam, ':tgl_r'=>$tgl_rencana]);

        // 2. Insert detail
        $stId2 = $conn->query("SELECT COALESCE(MAX(id_detail),0)+1 AS n FROM detail_peminajaman");
        $newDetail = (int)$stId2->fetch()['n'];

        $st2 = $conn->prepare("INSERT INTO detail_peminajaman (id_detail, id_buku, id_pengembalian, id_peminjaman, jumlah_buku, tgl_kembali_rencana)
                                VALUES (:id, :id_b, NULL, :id_p, 1, :tgl_r)");
        $st2->execute([':id'=>$newDetail, ':id_b'=>$id_buku, ':id_p'=>$newPinjam, ':tgl_r'=>$tgl_rencana]);

        $conn->commit();

        sendJsonResponse('success', "Peminjaman berhasil! Buku wajib dikembalikan sebelum tanggal " . date('d M Y', strtotime($tgl_rencana)) . " (Durasi: $durasiHari Hari).", [
            'id_peminjaman'       => $newPinjam,
            'judul_buku'          => $bukuData['judul'],
            'durasi_hari'         => $durasiHari,
            'tgl_pinjam'          => $tgl_pinjam,
            'tgl_kembali_rencana' => $tgl_rencana,
            'denda_per_hari'      => (int)$bukuData['denda']
        ], 201);
    }

    // ============================================================
    // POST: Kembalikan Buku (+ Hitung Denda Keterlambatan)
    // ============================================================
    elseif ($action === 'kembalikan_buku' && $method === 'POST') {
        $id_peminjaman = (int)($input['id_peminjaman'] ?? 0);

        if (!$id_peminjaman) sendJsonResponse('error', 'ID Peminjaman tidak valid', null, 400);

        // Ambil data peminjaman lengkap
        $st = $conn->prepare("SELECT p.*, dp.id_detail, dp.id_buku, b.judul AS judul_buku, b.denda AS denda_per_hari,
                                     a.nama AS nama_anggota
                               FROM peminjaman p
                               JOIN anggota a ON p.id_anggota = a.id_anggota
                               JOIN detail_peminajaman dp ON p.id_peminjaman = dp.id_peminjaman
                               JOIN buku b ON dp.id_buku = b.id_buku
                               WHERE p.id_peminjaman = :id LIMIT 1");
        $st->execute([':id' => $id_peminjaman]);
        $pinjam = $st->fetch();

        if (!$pinjam) sendJsonResponse('error', 'Data transaksi peminjaman tidak ditemukan', null, 404);
        if ($pinjam['status'] === 'Dikembalikan') sendJsonResponse('error', 'Buku ini sudah tercatat dikembalikan sebelumnya', null, 400);

        // Hitung denda keterlambatan
        $today         = new DateTime(date('Y-m-d'));
        $tglRencana    = new DateTime($pinjam['tgl_kembali_rencana']);
        $dendaTotal    = 0;
        $hariTerlambat = 0;

        if ($today > $tglRencana) {
            $hariTerlambat = $today->diff($tglRencana)->days;
            $dendaTotal    = $hariTerlambat * (int)$pinjam['denda_per_hari'];
        }

        $conn->beginTransaction();

        // 1. Insert pengembalian
        $stId = $conn->query("SELECT COALESCE(MAX(id_pengembalian),0)+1 AS n FROM pengembalian");
        $newKembali = (int)$stId->fetch()['n'];

        $st2 = $conn->prepare("INSERT INTO pengembalian (id_pengembalian, tgl_peminjama, id_peminjaman, tgl_kembali, denda_total, status_denda)
                                VALUES (:id, :tgl_p, :id_p, :tgl_k, :denda, 'Lunas')");
        $st2->execute([
            ':id'    => $newKembali,
            ':tgl_p' => $pinjam['tgl_pinjam'],
            ':id_p'  => $id_peminjaman,
            ':tgl_k' => date('Y-m-d'),
            ':denda' => $dendaTotal,
        ]);

        // 2. Update detail_peminajaman
        $st3 = $conn->prepare("UPDATE detail_peminajaman SET id_pengembalian = :id_k WHERE id_peminjaman = :id_p");
        $st3->execute([':id_k' => $newKembali, ':id_p' => $id_peminjaman]);

        // 3. Update status peminjaman
        $st4 = $conn->prepare("UPDATE peminjaman SET status = 'Dikembalikan' WHERE id_peminjaman = :id");
        $st4->execute([':id' => $id_peminjaman]);

        $conn->commit();

        $msg = $dendaTotal > 0
            ? "Buku \"" . $pinjam['judul_buku'] . "\" berhasil dikembalikan! Terlambat $hariTerlambat hari. Total denda: Rp " . number_format($dendaTotal, 0, ',', '.') . " (Lunas)."
            : "Buku \"" . $pinjam['judul_buku'] . "\" berhasil dikembalikan tepat waktu! Bebas denda.";

        sendJsonResponse('success', $msg, [
            'id_pengembalian'     => $newKembali,
            'id_peminjaman'       => $id_peminjaman,
            'judul_buku'          => $pinjam['judul_buku'],
            'nama_anggota'        => $pinjam['nama_anggota'],
            'denda_total'         => $dendaTotal,
            'denda_per_hari'      => (int)$pinjam['denda_per_hari'],
            'hari_terlambat'      => $hariTerlambat,
            'tgl_kembali'         => date('Y-m-d'),
            'tgl_pinjam'          => $pinjam['tgl_pinjam'],
            'tgl_kembali_rencana' => $pinjam['tgl_kembali_rencana']
        ]);
    }

    // ============================================================
    // GET: Riwayat pengembalian
    // ============================================================
    elseif ($action === 'riwayat_kembali' && $method === 'GET') {
        $id_anggota = isset($_GET['id_anggota']) ? (int)$_GET['id_anggota'] : 0;
        $q = "SELECT pg.id_pengembalian, pg.tgl_kembali, pg.denda_total, pg.status_denda,
                     a.nama AS nama_anggota, a.id_anggota,
                     b.judul AS judul_buku, b.denda AS denda_per_hari,
                     p.tgl_pinjam, p.tgl_kembali_rencana
              FROM pengembalian pg
              JOIN peminjaman p ON pg.id_peminjaman = p.id_peminjaman
              JOIN anggota a ON p.id_anggota = a.id_anggota
              JOIN detail_peminajaman dp ON p.id_peminjaman = dp.id_peminjaman
              JOIN buku b ON dp.id_buku = b.id_buku
              WHERE 1=1";
        $params = [];
        if ($id_anggota > 0) {
            $q .= " AND p.id_anggota = :id";
            $params[':id'] = $id_anggota;
        }
        $q .= " ORDER BY pg.id_pengembalian DESC LIMIT 50";
        $st = $conn->prepare($q);
        $st->execute($params);
        sendJsonResponse('success', 'OK', ['items' => $st->fetchAll()]);
    }

    else {
        sendJsonResponse('error', "Action '$action' tidak dikenal atau method tidak sesuai", null, 404);
    }

} catch (Exception $e) {
    if ($conn->inTransaction()) $conn->rollBack();
    sendJsonResponse('error', 'Server error: ' . $e->getMessage(), null, 500);
}
