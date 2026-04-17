<?php
class Database {
    private static $instance = null; // Lưu instance duy nhất [cite: 6]
    private $pdo;
    private $config = [
        'host' => 'sql303.infinityfree.com',  // ← đổi từ localhost
        'dbname' => 'if0_41015985_if0_41015985_halinh_db', // ← đổi từ db_demo
        'user' => 'if0_41015985',              // ← đổi từ root
        'pass' => 'DoAnWeb2026'                // ← đổi từ rỗng
    ];

    // Ngăn tạo object trực tiếp từ bên ngoài [cite: 10]
    private function __construct() {
        try {
            $dsn = "mysql:host={$this->config['host']};dbname={$this->config['dbname']};charset=utf8mb4";
            $this->pdo = new PDO($dsn, $this->config['user'], $this->config['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
        } catch (PDOException $e) {
            die("Lỗi kết nối DB: " . $e->getMessage());
        }
    }

    // Tạo hoặc trả về instance duy nhất [cite: 11]
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // Trả về đối tượng PDO để thực thi SQL [cite: 12]
    public function getConnection() {
        return $this->pdo;
    }
}