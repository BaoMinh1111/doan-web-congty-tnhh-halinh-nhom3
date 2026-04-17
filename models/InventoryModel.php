<?php
require_once 'BaseModel.php';

class InventoryModel extends BaseModel {
    protected $table = 'inventory'; // Hoặc có thể nằm chung bảng products tùy thiết kế DB của bạn

    /**
     * Cập nhật số lượng tồn kho (Tăng khi nhập hàng, giảm khi bán hàng)
     */
    public function updateStock(int $productId, int $change) {
        $sql = "UPDATE products SET stock = stock + :change WHERE id = :pid";
        return $this->prepareStmt($sql, [
            ':change' => $change,
            ':pid'    => $productId
        ]);
    }

    /**
     * Lấy danh sách các linh kiện sắp hết hàng (Dành cho Admin Dashboard)
     */
    public function getLowStockProducts($threshold = 10) {
        $sql = "SELECT id, name, stock FROM products WHERE stock <= :threshold ORDER BY stock ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':threshold', (int)$threshold, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Đồng bộ kho hàng sau khi thanh toán (Phục vụ OrderService)
     */
    public function reduceStockAfterOrder(int $orderId) {
        // Logic: Lấy tất cả items trong order_details và trừ vào products.stock
        $sql = "UPDATE products p
                JOIN orderdetails od ON p.id = od.product_id
                SET p.stock = p.stock - od.quantity
                WHERE od.order_id = :oid";
        return $this->prepareStmt($sql, [':oid' => $orderId]);
    }

    /**
     * Hoàn lại tồn kho khi khách hủy đơn
     */
    public function restoreStockAfterCancel(int $orderId) {
        $sql = "UPDATE products p
            JOIN orderdetails od ON p.id = od.product_id
            SET p.stock = p.stock + od.quantity
            WHERE od.order_id = :oid";
        return $this->prepareStmt($sql, [':oid' => $orderId]);
    }
}