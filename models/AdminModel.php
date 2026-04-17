<?php
require_once 'BaseModel.php';

class AdminModel extends BaseModel {
    protected $table = 'users';

    /**
     * Thống kê tổng quan cho Dashboard
     * Trả về: Tổng doanh thu, Tổng đơn hàng, Tổng khách hàng
     */
    public function getDashboardStats() {
        $stats = [];
        
        // 1. Tổng doanh thu từ các đơn đã giao thành công
        $sqlRevenue = "SELECT SUM(total_price) as total FROM orders WHERE status = 'shipped'";
        $stats['revenue'] = $this->fetchOne($sqlRevenue)['total'] ?? 0;

        // 2. Tổng số đơn hàng trong tháng này
        $sqlOrders = "SELECT COUNT(id) as count FROM orders WHERE MONTH(created_at) = MONTH(CURRENT_DATE())";
        $stats['monthly_orders'] = $this->fetchOne($sqlOrders)['count'] ?? 0;

        // 3. Tổng số khách hàng (không tính admin)
        $sqlCustomers = "SELECT COUNT(id) as count FROM users WHERE role = 'customer'";
        $stats['total_customers'] = $this->fetchOne($sqlCustomers)['count'] ?? 0;

        return $stats;
    }

    /**
     * Lấy biểu đồ doanh thu 7 ngày gần nhất
     */
    public function getRevenueChartData() {
        $sql = "SELECT DATE(created_at) as date, SUM(total_price) as daily_total 
                FROM orders 
                WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                AND status = 'shipped'
                GROUP BY DATE(created_at)
                ORDER BY date ASC";
        return $this->fetchAll($sql);
    }

    /**
     * Cập nhật lần đăng nhập cuối của Admin
     */
    public function updateLastLogin(int $adminId) {
        $sql = "UPDATE {$this->table} SET last_login = NOW() WHERE id = :id";
        return $this->prepareStmt($sql, [':id' => $adminId]);
    }
}