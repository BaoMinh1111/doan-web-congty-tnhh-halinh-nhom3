<?php

/**
 * Class OrderModel
 *
 * Quản lý dữ liệu đơn hàng, hỗ trợ CRUD, lấy chi tiết với khách hàng,
 * áp dụng khuyến mãi, phân trang, thống kê.
 *
 * @package App\Models
 */
class OrderModel extends BaseModel
{
    protected string $table = 'orders';
    protected string $primaryKey = 'id';
    protected string $defaultOrder = 'created_at DESC';

    private const VALID_STATUSES = [
        'pending', 'confirmed', 'shipped', 'completed', 'cancelled',
    ];

    private PromotionModel $promotionModel;

    public function __construct()
    {
        parent::__construct();
        $this->promotionModel = new PromotionModel();
    }

    // =========================================================================
    // TẠO ĐƠN HÀNG
    // =========================================================================

    public function createOrder(array $data): int
    {
        if (empty($data['customer_id'])) {
            throw new InvalidArgumentException('Thiếu customer_id khi tạo đơn hàng.');
        }
        if (!isset($data['total_price']) || $data['total_price'] < 0) {
            throw new InvalidArgumentException('total_price không hợp lệ.');
        }

        $data['status'] = $data['status'] ?? 'pending';

        // Áp dụng khuyến mãi nếu có
        if (!empty($data['promotion_id'])) {
            $promo = $this->promotionModel->getById($data['promotion_id']);
            if ($promo) {
                if ($promo['type'] === 'percent') {
                    $data['total_price'] *= (100 - $promo['value']) / 100;
                } elseif ($promo['type'] === 'fixed') {
                    $data['total_price'] -= $promo['value'];
                }
                $data['total_price'] = max(0, $data['total_price']);
            }
        }

        return $this->insert($data);
    }

    // =========================================================================
    // LẤY ĐƠN HÀNG
    // =========================================================================

    public function getById(int|string $id): ?array
    {
        return $this->fetchOne(
            "SELECT o.*, c.name AS customer_name, c.phone AS customer_phone, c.address AS customer_address
             FROM orders o
             LEFT JOIN customers c ON o.customer_id = c.id
             WHERE o.id = ?",
            [$id]
        );
    }

    public function getWithDetails(int $orderId): ?array
    {
        $order = $this->getById($orderId);
        if (!$order) return null;

        $items = $this->fetchAll(
            "SELECT od.product_id, od.quantity, od.price_at_purchase,
                    (od.quantity * od.price_at_purchase) AS subtotal,
                    p.name AS product_name, p.image AS product_image
             FROM order_details od
             INNER JOIN products p ON od.product_id = p.id
             WHERE od.order_id = ?
             ORDER BY p.name ASC",
            [$orderId]
        );

        // Nếu có promotion → gắn thông tin promotion
        if (!empty($order['promotion_id'])) {
            $order['promotion'] = $this->promotionModel->getById($order['promotion_id']);
        }

        return ['order' => $order, 'items' => $items];
    }

    public function getByCustomerId(int $customerId): array
    {
        return $this->fetchAll(
            "SELECT * FROM orders WHERE customer_id = ? ORDER BY created_at DESC",
            [$customerId]
        );
    }

    public function getByUserId(int $userId): array
    {
        return $this->fetchAll(
            "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC",
            [$userId]
        );
    }

    public function getByStatus(string $status): array
    {
        if (!in_array($status, self::VALID_STATUSES, true)) {
            throw new InvalidArgumentException("Trạng thái '{$status}' không hợp lệ.");
        }
        return $this->fetchAll(
            "SELECT o.*, c.name AS customer_name, c.phone AS customer_phone
             FROM orders o
             LEFT JOIN customers c ON o.customer_id = c.id
             WHERE o.status = ?
             ORDER BY o.created_at DESC",
            [$status]
        );
    }

    public function getByDateRange(string $from, string $to): array
    {
        return $this->fetchAll(
            "SELECT o.*, c.name AS customer_name
             FROM orders o
             LEFT JOIN customers c ON o.customer_id = c.id
             WHERE DATE(o.created_at) BETWEEN ? AND ?
             ORDER BY o.created_at DESC",
            [$from, $to]
        );
    }

    // =========================================================================
    // CẬP NHẬT
    // =========================================================================

    public function updateStatus(int $orderId, string $status): bool
    {
        if (!in_array($status, self::VALID_STATUSES, true)) {
            throw new InvalidArgumentException("Trạng thái '{$status}' không hợp lệ.");
        }
        return $this->update($orderId, ['status' => $status]);
    }

    public function updateTotalPrice(int $orderId, float $totalPrice): bool
    {
        if ($totalPrice < 0) throw new InvalidArgumentException('Tổng tiền không được âm.');
        return $this->update($orderId, ['total_price' => $totalPrice]);
    }

    // Áp dụng hoặc thay đổi khuyến mãi cho đơn hàng
    public function applyPromotion(int $orderId, int $promotionId): bool
    {
        $order = $this->getById($orderId);
        if (!$order) return false;

        $promo = $this->promotionModel->getById($promotionId);
        if (!$promo) return false;

        $total = $order['total_price'];

        if ($promo['type'] === 'percent') {
            $total *= (100 - $promo['value']) / 100;
        } elseif ($promo['type'] === 'fixed') {
            $total -= $promo['value'];
        }
        $total = max(0, $total);

        return $this->update($orderId, [
            'total_price' => $total,
            'promotion_id'=> $promotionId
        ]);
    }

    // =========================================================================
    // XOÁ ĐƠN HÀNG
    // =========================================================================

    public function delete(int|string $id): bool
    {
        return $this->transaction(function () use ($id): bool {
            $this->prepareStmt("DELETE FROM order_details WHERE order_id = ?", [$id]);
            return parent::delete($id);
        });
    }

    // =========================================================================
    // THỐNG KÊ
    // =========================================================================

    public function countByStatus(): array
    {
        return $this->fetchAll(
            "SELECT status, COUNT(*) AS total FROM orders GROUP BY status"
        );
    }

    public function getTotalRevenue(): float
    {
        $row = $this->fetchOne(
            "SELECT COALESCE(SUM(total_price), 0) AS revenue FROM orders WHERE status = 'completed'"
        );
        return isset($row['revenue']) ? (float)$row['revenue'] : 0.0;
    }

    public function getRecentOrders(int $limit = 5): array
    {
        $limit = max(1, (int)$limit);
        return $this->fetchAll(
            "SELECT o.*, c.name AS customer_name, c.phone AS customer_phone
             FROM orders o
             LEFT JOIN customers c ON o.customer_id = c.id
             ORDER BY o.created_at DESC
             LIMIT {$limit}"
        );
    }

    // =========================================================================
    // PHÂN TRANG VỚI LỌC
    // =========================================================================

    public function paginateWithFilter(int $page = 1, int $limit = 10, ?string $status = null): array
    {
        if ($page < 1) throw new InvalidArgumentException('Số trang phải >= 1.');
        if ($limit < 1 || $limit > 100) throw new InvalidArgumentException('Số bản ghi mỗi trang phải từ 1 đến 100.');

        $offset = ($page - 1) * $limit;
        $params = [];
        $where = '';

        if ($status !== null) {
            if (!in_array($status, self::VALID_STATUSES, true)) {
                throw new InvalidArgumentException("Trạng thái '{$status}' không hợp lệ.");
            }
            $where = 'WHERE o.status = ?';
            $params[] = $status;
        }

        $total = (int)($this->fetchOne("SELECT COUNT(*) AS cnt FROM orders o {$where}", $params)['cnt'] ?? 0);

        $data = $this->fetchAll(
            "SELECT o.*, c.name AS customer_name, c.phone AS customer_phone
             FROM orders o
             LEFT JOIN customers c ON o.customer_id = c.id
             {$where}
             ORDER BY o.created_at DESC
             LIMIT {$limit} OFFSET {$offset}",
            $params
        );

        return [
            'data' => $data,
            'total' => $total,
            'currentPage' => $page,
            'totalPages' => (int) ceil($total / $limit),
            'limit' => $limit,
            'status' => $status,
        ];
    }
}
