<?php
require_once __DIR__ . '/BaseModel.php';

class OrderModel extends BaseModel {

    public function __construct() {
        parent::__construct();
    }

    // =========================================================
    //  TẠO / GHI ĐƠN HÀNG
    // =========================================================

    /**
     * Tạo đơn hàng mới.
     * Hỗ trợ cả thành viên đã đăng nhập (user_id có giá trị)
     * lẫn khách vãng lai (user_id = NULL).
     *
     * $orderData cần có:
     *   user_id    – int|null
     *   full_name  – string
     *   address    – string
     *   phone      – string
     *   email      – string
     *   note       – string (tuỳ chọn)
     *   total_amt  – float
     *
     * $cartItems: mỗi phần tử dạng
     *   ['product' => ['id' => ..., 'price' => ...], 'quantity' => ...]
     *
     * @return int  ID đơn hàng vừa tạo
     */
    public function createOrder(array $orderData, array $cartItems): int {
        try {
            $this->db->beginTransaction();

            // Lưu email để tra cứu / merge đơn hàng của khách vãng lai
            $sqlOrder = "INSERT INTO orders
                            (user_id, customer_name, customer_address, phone, email,
                             note, total_price, status, created_at)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $this->prepareStmt($sqlOrder, [
                isset($orderData['user_id']) ? $orderData['user_id'] : null,
                $orderData['full_name'],
                $orderData['address'],
                $orderData['phone'],
                $orderData['email']  ?? '',
                $orderData['note']   ?? 'Đơn hàng từ hệ thống',
                $orderData['total_amt'],
                'pending',
                date('Y-m-d H:i:s'),
            ]);

            $orderId = (int) $this->db->lastInsertId();

            $sqlItem = "INSERT INTO orderdetails (order_id, product_id, quantity, price_at_purchase)
                        VALUES (?, ?, ?, ?)";
            foreach ($cartItems as $item) {
                $this->prepareStmt($sqlItem, [
                    $orderId,
                    $item['product']['id'],
                    $item['quantity'],
                    $item['product']['price'],
                ]);
            }

            $this->db->commit();
            return $orderId;

        } catch (Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            die("Lỗi lưu đơn hàng: " . $e->getMessage());
        }
    }

    /**
     * Thêm một dòng chi tiết vào đơn hàng đã tồn tại.
     * Dùng khi cần bổ sung sản phẩm lẻ sau khi đơn đã được tạo.
     */
    public function addDetail(int $orderId, array $item): void {
        $sql = "INSERT INTO orderdetails (order_id, product_id, quantity, price_at_purchase)
                VALUES (:oid, :pid, :qty, :price)";
        $this->prepareStmt($sql, [
            ':oid'   => $orderId,
            ':pid'   => $item['product']['id'],
            ':qty'   => $item['quantity'],
            ':price' => $item['product']['price'],
        ]);
    }

    // =========================================================
    //  ĐỌC ĐƠN HÀNG
    // =========================================================

    /**
     * Lấy thông tin đơn hàng theo ID, kèm thông tin khách hàng (nếu là thành viên).
     */
    public function getById(int $id) {
        $sql = "SELECT o.*, u.fullname, u.email
                FROM orders o
                LEFT JOIN users u ON o.user_id = u.id
                WHERE o.id = :id";
        return $this->fetchOne($sql, [':id' => $id]);
    }

    /**
     * Lấy danh sách sản phẩm trong một đơn hàng (kèm tên & ảnh sản phẩm).
     */
    public function getItemsByOrderId(int $orderId): array {
        $sql = "SELECT oi.*, p.name AS product_name, p.image AS product_image
                FROM orderdetails oi
                JOIN products p ON oi.product_id = p.id
                WHERE oi.order_id = :order_id";
        return $this->fetchAll($sql, [':order_id' => $orderId]);
    }

    /**
     * Lấy chi tiết đơn hàng – alias linh hoạt hơn cho getItemsByOrderId,
     * dùng positional placeholder (tương thích với db->query($sql, [...]) style).
     */
    public function getOrderDetails(int $orderId): array {
        $sql = "SELECT od.*, p.name, p.image
                FROM orderdetails od
                JOIN products p ON od.product_id = p.id
                WHERE od.order_id = ?";
        return $this->db->query($sql, [$orderId])->fetchAll();
    }

    /**
     * Lịch sử đơn hàng của thành viên đã đăng nhập.
     */
    public function getOrdersByUserId(int $userId): array {
        if ($userId <= 0) return [];
        $sql = "SELECT * FROM orders WHERE user_id = :user_id ORDER BY created_at DESC";
        return $this->fetchAll($sql, [':user_id' => $userId]);
    }

    /**
     * Tra cứu đơn hàng của khách vãng lai theo email.
     * Dùng để gợi ý merge khi khách đăng ký tài khoản.
     */
    public function getGuestOrdersByEmail(string $email): array {
        $sql = "SELECT * FROM orders
                WHERE email = :email AND user_id IS NULL
                ORDER BY created_at DESC";
        return $this->fetchAll($sql, [':email' => $email]);
    }

    /**
     * Lấy tất cả đơn hàng – dùng cho trang quản trị (admin).
     * Dùng LEFT JOIN để không bỏ sót đơn của khách vãng lai (user_id = NULL).
     */
    public function getAllOrders(): array {
        $sql = "SELECT o.*, u.fullname, u.email
                FROM orders o
                LEFT JOIN users u ON o.user_id = u.id
                ORDER BY o.created_at DESC";
        return $this->db->query($sql)->fetchAll();
    }

    // =========================================================
    //  CẬP NHẬT ĐƠN HÀNG
    // =========================================================

    /**
     * Cập nhật trạng thái đơn hàng và ghi nhận thời điểm thay đổi.
     */
    public function updateStatus(int $orderId, string $status): bool {
        $sql = "UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?";
        return $this->db->prepare($sql)->execute([$status, $orderId]);
    }

    /**
     * Merge đơn hàng của khách vãng lai vào tài khoản vừa đăng ký.
     * Gọi ngay sau khi user đăng ký thành công.
     */
    public function mergeGuestOrders(string $email, int $userId): mixed {
        $sql = "UPDATE orders SET user_id = :user_id
                WHERE email = :email AND user_id IS NULL";
        return $this->prepareStmt($sql, [':user_id' => $userId, ':email' => $email]);
    }

    // =========================================================
    //  KHUYẾN MÃI
    // =========================================================

    /**
     * Áp mã giảm giá cho đơn hàng thông qua Stored Procedure.
     */
    public function applyPromoCode(int $orderId, string $code): void {
        $sql = "CALL ApplyPromotionToOrder(:oid, :code)";
        $this->prepareStmt($sql, [':oid' => $orderId, ':code' => $code]);
    }

    /**
     * Lấy thông tin khuyến mãi theo mã code (chỉ trả về nếu đang active).
     */
    public function getPromotionByCode(string $code) {
        $sql = "SELECT * FROM promotions WHERE code = :code AND active = 1 LIMIT 1";
        return $this->fetchOne($sql, [':code' => $code]);
    }
}