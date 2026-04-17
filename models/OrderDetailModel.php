<?php
require_once 'BaseModel.php';

class OrderDetailModel extends BaseModel {
    protected $table = 'order_details';

    /**
     * Lấy danh sách sản phẩm của một đơn hàng cụ thể (Dùng cho trang Lịch sử mua hàng)
     */
    public function getItemsByOrderId(int $orderId): array {
        $sql = "SELECT od.*, p.name, p.image 
                FROM {$this->table} od 
                JOIN products p ON od.product_id = p.id 
                WHERE od.order_id = :oid";
        return $this->fetchAll($sql, [':oid' => $orderId]);
    }

    /**
     * Thống kê sản phẩm bán chạy (Dành cho Admin Dashboard)
     */
    public function getTopSelling($limit = 5) {
        $sql = "SELECT product_id, SUM(quantity) as total_sold 
                FROM {$this->table} 
                GROUP BY product_id 
                ORDER BY total_sold DESC 
                LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}