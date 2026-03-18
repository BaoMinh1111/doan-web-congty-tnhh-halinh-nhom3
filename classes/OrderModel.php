<?php

/**
 * Class OrderModel
 *
 * Xử lý toàn bộ thao tác CSDL liên quan đến bảng orders.
 * Kế thừa BaseModel → tái sử dụng kết nối PDO (từ Database Singleton)
 * và các phương thức CRUD chung (insert, update, delete, getById, paginate...).
 *
 * @package App\Models
 * @author  Ha Linh Technology Solutions
 */
class OrderModel extends BaseModel
{
    // =========================================================================
    // CẤU HÌNH BẢNG
    // =========================================================================

    /**
     * Tên bảng trong CSDL.
     * Override thuộc tính $table của BaseModel.
     *
     * @var string
     */
    protected string $table = 'orders';

    /**
     * Khoá chính của bảng orders là 'id' (auto-increment).
     * Giữ nguyên giá trị mặc định của BaseModel, khai báo lại để rõ ràng.
     *
     * @var string
     */
    protected string $primaryKey = 'id';

    /**
     * Sắp xếp mặc định: đơn hàng mới nhất lên đầu.
     * Override $defaultOrder của BaseModel.
     *
     * @var string
     */
    protected string $defaultOrder = 'created_at DESC';

    /**
     * Danh sách trạng thái hợp lệ của đơn hàng.
     * Dùng để validate trước khi cập nhật trạng thái.
     *
     * @var string[]
     */
    private const VALID_STATUSES = [
        'pending',
        'confirmed',
        'shipped',
        'completed',
        'cancelled',
    ];


    // =========================================================================
    // CONSTRUCTOR
    // =========================================================================

    /**
     * Gọi constructor của BaseModel.
     * BaseModel sẽ tự lấy kết nối PDO từ Database::getInstance().
     */
    public function __construct()
    {
        parent::__construct();
    }


    // =========================================================================
    // TẠO ĐƠN HÀNG
    // =========================================================================

    /**
     * Tạo đơn hàng mới và trả về ID vừa tạo.
     *
     * Dùng insert() của BaseModel → tự build câu INSERT,
     * không cần viết SQL thủ công.
     *
     * Dữ liệu $data cần truyền vào:
     *   - customer_id  (int, bắt buộc)
     *   - total_price  (float, bắt buộc)
     *   - status       (string, mặc định 'pending')
     *   - user_id      (int|null, nếu khách đã đăng ký)
     *   - promotion_id (int|null, nếu có khuyến mãi)
     *   - note         (string|null)
     *
     * @param  array $data Dữ liệu đơn hàng.
     * @return int         ID đơn hàng vừa tạo.
     * @throws InvalidArgumentException Nếu thiếu customer_id hoặc total_price.
     */
    public function createOrder(array $data): int
    {
        // Validate các trường bắt buộc
        if (empty($data['customer_id'])) {
            throw new InvalidArgumentException('Thiếu customer_id khi tạo đơn hàng.');
        }
        if (!isset($data['total_price']) || $data['total_price'] < 0) {
            throw new InvalidArgumentException('total_price không hợp lệ.');
        }

        // Gán trạng thái mặc định nếu không truyền vào
        $data['status'] = $data['status'] ?? 'pending';

        // Gọi insert() của BaseModel → trả về lastInsertId()
        return $this->insert($data);
    }


    // =========================================================================
    // LẤY ĐƠN HÀNG – CÁC TRƯỜNG HỢP ĐẶC THÙ
    // =========================================================================

    /**
     * Lấy đơn hàng theo ID kèm thông tin khách hàng (JOIN customers).
     *
     * Override getById() của BaseModel vì cần JOIN thêm bảng customers
     * để hiển thị tên, SĐT, địa chỉ trực tiếp mà không cần gọi thêm CustomerModel.
     *
     * Kết quả trả về gồm tất cả cột của orders + name, phone, address từ customers.
     *
     * @param  int|string $id ID đơn hàng.
     * @return array|null     Dữ liệu đơn hàng + thông tin khách, null nếu không tìm thấy.
     */
    public function getById(int|string $id): ?array
    {
        return $this->fetchOne(
            "SELECT o.*,
                    c.name    AS customer_name,
                    c.phone   AS customer_phone,
                    c.address AS customer_address
             FROM   orders o
             LEFT JOIN customers c ON o.customer_id = c.id
             WHERE  o.id = ?",
            [$id]
        );
    }

    /**
     * Lấy tất cả đơn hàng của một khách hàng, sắp xếp mới nhất trước.
     *
     * Dùng trong trang lịch sử đơn hàng của khách (nếu có đăng nhập)
     * hoặc trang quản trị khi xem đơn theo từng khách.
     *
     * @param  int   $customerId ID khách hàng.
     * @return array             Mảng đơn hàng (rỗng nếu chưa có đơn nào).
     */
    public function getByCustomerId(int $customerId): array
    {
        return $this->fetchAll(
            "SELECT * FROM orders
             WHERE  customer_id = ?
             ORDER BY created_at DESC",
            [$customerId]
        );
    }

    /**
     * Lấy tất cả đơn hàng của một user đã đăng ký (qua user_id).
     *
     * Khác getByCustomerId(): user_id là tài khoản đăng nhập (bảng users),
     * customer_id là thông tin người nhận hàng (bảng customers).
     * Một user có thể đặt nhiều đơn với nhiều địa chỉ nhận khác nhau.
     *
     * @param  int   $userId ID tài khoản người dùng.
     * @return array
     */
    public function getByUserId(int $userId): array
    {
        return $this->fetchAll(
            "SELECT * FROM orders
             WHERE  user_id = ?
             ORDER BY created_at DESC",
            [$userId]
        );
    }

    /**
     * Lấy danh sách đơn hàng theo trạng thái.
     *
     * Dùng trong trang quản trị để lọc đơn theo trạng thái
     *
     * @param  string $status Trạng thái cần lọc (pending/confirmed/shipped/completed/cancelled).
     * @return array
     * @throws InvalidArgumentException Nếu $status không hợp lệ.
     */
    public function getByStatus(string $status): array
    {
        if (!in_array($status, self::VALID_STATUSES, true)) {
            throw new InvalidArgumentException(
                "Trạng thái '{$status}' không hợp lệ. "
                . 'Các giá trị được phép: ' . implode(', ', self::VALID_STATUSES)
            );
        }

        return $this->fetchAll(
            "SELECT o.*,
                    c.name  AS customer_name,
                    c.phone AS customer_phone
             FROM   orders o
             LEFT JOIN customers c ON o.customer_id = c.id
             WHERE  o.status = ?
             ORDER BY o.created_at DESC",
            [$status]
        );
    }

    /**
     * Lấy danh sách đơn hàng trong khoảng thời gian.
     *
     * Dùng cho báo cáo doanh thu theo ngày/tháng trong trang quản trị.
     *
     * @param  string $from Ngày bắt đầu (định dạng Y-m-d, vd: '2025-01-01').
     * @param  string $to   Ngày kết thúc (định dạng Y-m-d, vd: '2025-12-31').
     * @return array
     */
    public function getByDateRange(string $from, string $to): array
    {
        return $this->fetchAll(
            "SELECT o.*,
                    c.name AS customer_name
             FROM   orders o
             LEFT JOIN customers c ON o.customer_id = c.id
             WHERE  DATE(o.created_at) BETWEEN ? AND ?
             ORDER BY o.created_at DESC",
            [$from, $to]
        );
    }


    /**
     * Lấy chi tiết đơn hàng kèm danh sách sản phẩm (3 bảng JOIN).
     *
     * Trả về 2 phần trong một mảng:
     *   - 'order'  : thông tin đơn hàng + tên/SĐT/địa chỉ khách hàng
     *   - 'items'  : mảng các dòng chi tiết, mỗi dòng gồm:
     *                  product_id, product_name, image, quantity,
     *                  price_at_purchase, subtotal (= qty * price)
     *
     * Dùng trong:
     *   - Trang chi tiết đơn hàng phía khách (xem lại đã đặt gì)
     *   - Trang quản trị khi admin xem chi tiết từng đơn
     *
     * @param  int        $orderId ID đơn hàng.
     * @return array|null          Mảng ['order' => [...], 'items' => [...]], null nếu không tìm thấy.
     */
    public function getWithDetails(int $orderId): ?array
    {
        // Lấy thông tin đơn hàng + khách hàng
        $order = $this->fetchOne(
            "SELECT o.*,
                    c.name    AS customer_name,
                    c.phone   AS customer_phone,
                    c.address AS customer_address,
                    c.email   AS customer_email
             FROM   orders o
             LEFT JOIN customers c ON o.customer_id = c.id
             WHERE  o.id = ?",
            [$orderId]
        );

        // Đơn không tồn tại → trả null luôn, không cần query tiếp
        if ($order === null) {
            return null;
        }

        // Lấy danh sách sản phẩm trong đơn (JOIN order_details → products)
        $items = $this->fetchAll(
            "SELECT od.product_id,
                    od.quantity,
                    od.price_at_purchase,
                    (od.quantity * od.price_at_purchase) AS subtotal,
                    p.name  AS product_name,
                    p.image AS product_image
             FROM   order_details od
             INNER JOIN products p ON od.product_id = p.id
             WHERE  od.order_id = ?
             ORDER BY p.name ASC",
            [$orderId]
        );

        return [
            'order' => $order,
            'items' => $items,
        ];
    }


    // =========================================================================
    // CẬP NHẬT ĐƠN HÀNG
    // =========================================================================

    /**
     * Cập nhật trạng thái đơn hàng.
     *
     * Đây là thao tác cập nhật phổ biến nhất trong quản trị đơn hàng.
     * Validate trạng thái trước khi gọi update() của BaseModel.
     *
     * Luồng trạng thái thông thường:
     *   pending → confirmed → shipped → completed
     *   (bất kỳ trạng thái nào) → cancelled
     *
     * @param  int    $orderId ID đơn hàng.
     * @param  string $status  Trạng thái mới.
     * @return bool            true nếu cập nhật thành công.
     * @throws InvalidArgumentException Nếu $status không hợp lệ.
     */
    public function updateStatus(int $orderId, string $status): bool
    {
        if (!in_array($status, self::VALID_STATUSES, true)) {
            throw new InvalidArgumentException(
                "Trạng thái '{$status}' không hợp lệ. "
                . 'Các giá trị được phép: ' . implode(', ', self::VALID_STATUSES)
            );
        }

        // Gọi update() của BaseModel → tự build câu UPDATE
        return $this->update($orderId, ['status' => $status]);
    }

    /**
     * Cập nhật tổng tiền đơn hàng.
     *
     * Dùng khi OrderService tính lại tổng tiền sau khi áp dụng khuyến mãi
     * hoặc khi admin chỉnh sửa đơn hàng.
     *
     * @param  int   $orderId    ID đơn hàng.
     * @param  float $totalPrice Tổng tiền mới.
     * @return bool
     * @throws InvalidArgumentException Nếu tổng tiền âm.
     */
    public function updateTotalPrice(int $orderId, float $totalPrice): bool
    {
        if ($totalPrice < 0) {
            throw new InvalidArgumentException('Tổng tiền không được âm.');
        }

        return $this->update($orderId, ['total_price' => $totalPrice]);
    }


    // =========================================================================
    // XOÁ ĐƠN HÀNG
    // =========================================================================

    /**
     * Xoá đơn hàng và toàn bộ order_details liên quan trong một transaction.
     *
     * @param  int|string $id ID đơn hàng.
     * @return bool           true nếu xoá thành công.
     */
    public function delete(int|string $id): bool
    {
        // Dùng transaction() của BaseModel: tự commit nếu thành công,
        // tự rollback nếu bất kỳ bước nào throw exception.
        return $this->transaction(function () use ($id): bool {

            // Bước 1: xoá order_details trước để tránh FK constraint
            $this->prepareStmt(
                "DELETE FROM order_details WHERE order_id = ?",
                [$id]
            );

            // Bước 2: xoá bản ghi orders (gọi delete() của BaseModel)
            return parent::delete($id);
        });
    }


    // =========================================================================
    // THỐNG KÊ – HỖ TRỢ TRANG QUẢN TRỊ
    // =========================================================================

    /**
     * Đếm số đơn hàng theo từng trạng thái.
     *
     * Dùng để hiển thị badge tổng quan trên dashboard admin:
     *   "Đang chờ: 5 | Đã xác nhận: 3 | Đang giao: 2 ..."
     *
     * Kết quả trả về dạng:
     *   [
     *     ['status' => 'pending',   'total' => 5],
     *     ['status' => 'confirmed', 'total' => 3],
     *     ...
     *   ]
     *
     * @return array
     */
    public function countByStatus(): array
    {
        return $this->fetchAll(
            "SELECT status, COUNT(*) AS total
             FROM   orders
             GROUP BY status"
        );
    }

    /**
     * Tính tổng doanh thu từ các đơn hàng đã hoàn thành.
     *
     * Chỉ tính đơn có status = 'completed' để phản ánh doanh thu thực tế.
     *
     * @return float Tổng doanh thu (0 nếu chưa có đơn hoàn thành nào).
     */
    public function getTotalRevenue(): float
    {
        $row = $this->fetchOne(
            "SELECT COALESCE(SUM(total_price), 0) AS revenue
             FROM   orders
             WHERE  status = 'completed'"
        );

        return isset($row['revenue']) ? (float) $row['revenue'] : 0.0;
    }

    /**
     * Lấy danh sách đơn hàng gần đây nhất (dùng cho widget dashboard).
     *
     * @param  int   $limit Số đơn cần lấy (mặc định 5).
     * @return array
     */
    public function getRecentOrders(int $limit = 5): array
    {
        // Ép kiểu int để an toàn khi nhúng thẳng vào SQL (LIMIT không dùng được placeholder)
        $limit = max(1, (int) $limit);

        return $this->fetchAll(
            "SELECT o.*,
                    c.name  AS customer_name,
                    c.phone AS customer_phone
             FROM   orders o
             LEFT JOIN customers c ON o.customer_id = c.id
             ORDER BY o.created_at DESC
             LIMIT {$limit}"
        );
    }


    // =========================================================================
    // PHÂN TRANG CÓ LỌC – DÀNH CHO TRANG QUẢN TRỊ
    // =========================================================================

    /**
     * Lấy danh sách đơn hàng phân trang, có thể lọc theo trạng thái.
     *
     * Mở rộng paginate() của BaseModel: thêm bộ lọc theo status
     * và JOIN customers để hiển thị tên khách ngay trong danh sách.
     *
     * @param  int         $page   Trang hiện tại (bắt đầu từ 1).
     * @param  int         $limit  Số đơn mỗi trang.
     * @param  string|null $status Lọc theo trạng thái (null = lấy tất cả).
     * @return array               Mảng ['data', 'total', 'currentPage', 'totalPages', 'limit'].
     * @throws InvalidArgumentException Nếu $status không hợp lệ.
     */
    public function paginateWithFilter(
        int    $page   = 1,
        int    $limit  = 10,
        ?string $status = null
    ): array {
        if ($page < 1) {
            throw new InvalidArgumentException('Số trang phải >= 1.');
        }
        if ($limit < 1 || $limit > 100) {
            throw new InvalidArgumentException('Số bản ghi mỗi trang phải từ 1 đến 100.');
        }

        $offset = ($page - 1) * $limit;
        $params = [];

        // Build điều kiện WHERE nếu có lọc theo status
        $where = '';
        if ($status !== null) {
            if (!in_array($status, self::VALID_STATUSES, true)) {
                throw new InvalidArgumentException(
                    "Trạng thái '{$status}' không hợp lệ."
                );
            }
            $where    = 'WHERE o.status = ?';
            $params[] = $status;
        }

        // Đếm tổng để tính số trang (cache vào biến, tránh query 2 lần)
        $countParams = $params;
        $total = (int) ($this->fetchOne(
            "SELECT COUNT(*) AS cnt FROM orders o {$where}",
            $countParams
        )['cnt'] ?? 0);

        // Lấy data trang hiện tại.
        // LIMIT và OFFSET được nhúng thẳng vào SQL (đã ép kiểu int ở trên),
        // KHÔNG truyền qua placeholder → chỉ cần $params (chứa status nếu có).
        // $dataParams đã bị xoá: tạo ra rồi không dùng là bug cũ gây nhầm lẫn.
        $data = $this->fetchAll(
            "SELECT o.*,
                    c.name  AS customer_name,
                    c.phone AS customer_phone
             FROM   orders o
             LEFT JOIN customers c ON o.customer_id = c.id
             {$where}
             ORDER BY o.created_at DESC
             LIMIT {$limit} OFFSET {$offset}",
            $params  // chỉ chứa status (nếu có), LIMIT/OFFSET đã nhúng vào SQL rồi
        );

        return [
            'data'        => $data,
            'total'       => $total,
            'currentPage' => $page,
            'totalPages'  => (int) ceil($total / $limit),
            'limit'       => $limit,
            'status'      => $status, // trả lại để View biết đang lọc theo trạng thái nào
        ];
    }
}