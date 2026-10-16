<?php
/**
 * Konfigurasi Database Sistem Informasi Perpustakaan
 * Menggunakan PDO (PHP Data Objects) untuk keamanan (Prepared Statements) dan fleksibilitas.
 */

class Database {
    private $host = "localhost";
    private $db_name = "perpustakaan_sistem_informasi";
    private $username = "root";
    private $password = "";
    private $port = "3306";
    private $charset = "utf8mb4";
    public $conn;

    /**
     * Mendapatkan koneksi ke database MySQL via PDO
     * @return PDO|null
     */
    public function getConnection() {
        $this->conn = null;

        $dsn = "mysql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name . ";charset=" . $this->charset;

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $exception) {
            // Jika koneksi gagal, kembalikan respon JSON jika dipanggil dari API
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=UTF-8');
                http_response_code(500);
            }
            echo json_encode([
                "status"  => "error",
                "message" => "Koneksi database gagal: " . $exception->getMessage(),
                "hint"    => "Pastikan MySQL di XAMPP/WAMP sudah aktif dan database 'perpustakaan_sistem_informasi' sudah diimpor."
            ]);
            exit;
        }

        return $this->conn;
    }
}

/**
 * Helper function untuk mengirim respon JSON terstandar
 */
function sendJsonResponse($status, $message, $data = null, $httpCode = 200) {
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=UTF-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');
        http_response_code($httpCode);
    }
    
    $response = [
        "status"  => $status,
        "message" => $message
    ];

    if ($data !== null) {
        $response["data"] = $data;
    }

    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}
