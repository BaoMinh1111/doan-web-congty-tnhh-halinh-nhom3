<?php
abstract class BaseModel {
    protected $db; // Đối tượng PDO [cite: 144]
    protected $table; // Tên bảng sẽ được lớp con override [cite: 145]

    // public function __construct() {
    //     try {
    //         $this->db = new PDO("mysql:host=localhost;dbname=db_demo", "root", "");
    //         $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    //     } catch (PDOException $e) {
    //         die("Kết nối thất bại: " . $e->getMessage());
    //     }
    //     $this->connect();
    // }
    public function __construct() {
        // Chỉ dùng một cách kết nối duy nhất qua Singleton
        $this->db = Database::getInstance()->getConnection();
        // Ép kiểu fetch mặc định là mảng kết hợp
        $this->db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }

    protected function connect() {
        // Lấy kết nối từ Singleton [cite: 147]
        $this->db = Database::getInstance()->getConnection();
    }

    // Chống SQL Injection bằng Prepared Statement [cite: 149]
    protected function prepareStmt($sql, $params = []) {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    protected function fetchAll($sql, $params = []) {
        return $this->prepareStmt($sql, $params)->fetchAll();
    }

    protected function fetchOne($sql, $params = []) {
        return $this->prepareStmt($sql, $params)->fetch();
    }
}