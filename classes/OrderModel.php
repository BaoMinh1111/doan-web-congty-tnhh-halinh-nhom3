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
    // HELPER NỘI BỘ
    // =========================================================================

    /**
     * Validate trạng thái đơn hàng.
     * Dùng chung cho mọi method cần kiểm tra status → tránh lặp code.
     *
     * @param  string $status Trạng thái cần kiểm tra.
     * @return void
     * @throws InvalidArgumentException Nếu status không hợp lệ.
     */
    private function assertValidStatus(string $status): void
    {
        if (!in_array($status, self::VALID_STATUSES, true)) {
            throw new InvalidArgumentException(
                "Trạng thái '{$status}' không hợp lệ. "
                . 'Các giá trị được phép: ' . implode(', ', self::VALID_STATUSES)
            );
        }
    }

    /**
     * Build mệnh đề WHERE + params cho bộ lọc status (dùng nội bộ).
     *
     * Trả về mảng gồm:
     *   - 'clause' : chuỗi SQL (vd: "WHERE o.status = ?") hoặc rỗng nếu không lọc
     *   - 'params' : mảng tham số tương ứng
     *
     * Quy ước alias bảng orders là 'o' — nhất quán với mọi query JOIN trong class này.
     *
     * @param  string|null $status
     * @param  string      $prefix 'WHERE' hoặc 'AND' tuỳ vị trí trong câu SQL.
     * @return array{clause: string, params: array}
     */
    private function buildStatusFilter(?string $status, string $prefix = 'WHERE'): array
    {
        if ($status === null) {
            return ['clause' => '', 'params' => []];
        }

        $this->assertValidStatus($status);

        return [
            'clause' => "{$prefix} o.status = ?",
            'params' => [$status],
        ];
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
     *   - total_price  (float, bắt buộc, >= 0)
     *   - status       (string, mặc định 'pending')
     *   - user_id      (int|null, nếu khách đã đăng ký)
     *   - promotion_id (int|null, nếu có khuyến mãi)
     *   - note         (string|null)
     *
     * @param  array $data Dữ liệu đơn hàng.
     * @return int         ID đơn hàng vừa tạo.
     * @throws InvalidArgumentException Nếu thiếu customer_id, total_price âm, hoặc status không hợp lệ.
     */
    public function createOrder(array $data): int
    {
        if (empty($data['customer_id']) || (int) $data['customer_id'] <= 0) {
            throw new InvalidArgumentException('Thiếu hoặc sai customer_id khi tạo đơn hàng.');
        }

        if (!isset($data['total_price']) || (float) $data['total_price'] < 0) {
            throw new InvalidArgumentException('total_price không hợp lệ (phải >= 0).');
        }

        // Gán trạng thái mặc định nếu không truyền vào
        $data['status'] = $data['status'] ?? 'pending';

        // Validate status ngay cả khi là giá trị mặc định
        // → tránh trường hợp caller truyền status sai vào $data
        $this->assertValidStatus($data['status']);

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
            [(int) $id]
        );
    }

    /**
     * Lấy tất cả đơn hàng của một khách hàng theo customer_id.
     *
     * Đây là method gốc dùng nội bộ và cho tầng Service.
     * JOIN customers để trả đủ thông tin hiển thị (tên, SĐT, địa chỉ)
     * mà không cần gọi thêm CustomerModel ở tầng trên.
     *
     * Hỗ trợ lọc thêm theo trạng thái nếu cần.
     *
     * Dùng trong:
     *   - Trang lịch sử đơn hàng của khách (front-end)
     *   - Admin xem đơn hàng theo từng khách
     *
     * @param  int         $customerId ID khách hàng (FK từ bảng customers).
     * @param  string|null $status     Lọc thêm theo trạng thái (null = tất cả).
     * @return array                   Mảng đơn hàng, rỗng nếu khách chưa có đơn nào.
     * @throws InvalidArgumentException Nếu $customerId <= 0 hoặc $status không hợp lệ.
     */
    public function getByCustomerId(int $customerId, ?string $status = null): array
    {
        if ($customerId <= 0) {
            throw new InvalidArgumentException('customer_id phải là số nguyên dương.');
        }

        // Mệnh đề AND (không phải WHERE vì đã có WHERE o.customer_id = ?)
        $filter = $this->buildStatusFilter($status, 'AND');

        return $this->fetchAll(
            "SELECT o.*,
                    c.name    AS customer_name,
                    c.phone   AS customer_phone,
                    c.address AS customer_address
             FROM   orders o
             LEFT JOIN customers c ON o.customer_id = c.id
             WHERE  o.customer_id = ?
             {$filter['clause']}
             ORDER BY o.created_at DESC",
            array_merge([$customerId], $filter['params'])
        );
    }

    /**
     * Alias của getByCustomerId() — giữ để các method cũ gọi vào không bị break.
     *
     * @param  int         $customerId
     * @param  string|null $status
     * @return array
     */
    public function getOrdersByCustomerId(int $customerId, ?string $status = null): array
    {
        return $this->getByCustomerId($customerId, $status);
    }

    /**
     * Lấy tất cả đơn hàng của một user đã đăng ký (qua user_id).
     *
     * Khác getByCustomerId(): user_id là tài khoản đăng nhập (bảng users),
     * customer_id là thông tin người nhận hàng (bảng customers).
     * Một user có thể đặt nhiều đơn với nhiều địa chỉ nhận khác nhau.
     *
     * @param  int         $userId ID tài khoản người dùng.
     * @param  string|null $status Lọc thêm theo trạng thái (null = tất cả).
     * @return array
     * @throws InvalidArgumentException Nếu $userId <= 0 hoặc $status không hợp lệ.
     */
    public function getByUserId(int $userId, ?string $status = null): array
    {
        if ($userId <= 0) {
            throw new InvalidArgumentException('user_id phải là số nguyên dương.');
        }

        // Mệnh đề AND vì đã có WHERE o.user_id = ?
        $filter = $this->buildStatusFilter($status, 'AND');

        return $this->fetchAll(
            "SELECT o.*,
                    c.name    AS customer_name,
                    c.phone   AS customer_phone,
                    c.address AS customer_address
             FROM   orders o
             LEFT JOIN customers c ON o.customer_id = c.id
             WHERE  o.user_id = ?
             {$filter['clause']}
             ORDER BY o.created_at DESC",
            array_merge([$userId], $filter['params'])
        );
    }

    /**
     * Lấy danh sách đơn hàng theo trạng thái.
     *
     * Dùng trong trang quản trị để lọc đơn theo trạng thái.
     * Gọi vào getAllOrders() để tránh lặp SQL.
     *
     * @param  string $status Trạng thái cần lọc.
     * @return array
     * @throws InvalidArgumentException Nếu $status không hợp lệ.
     */
    public function getByStatus(string $status): array
    {
        // Delegate sang getAllOrders() — không lặp SQL
        return $this->getAllOrders($status);
    }

    /**
     * Lấy tất cả đơn hàng kèm thông tin khách hàng (dùng cho trang quản trị).
     *
     * Override getAll() của BaseModel để thêm JOIN customers,
     * tránh admin phải gọi thêm query riêng để lấy tên khách.
     *
     * Khác paginateWithFilter(): method này lấy TẤT CẢ đơn không phân trang.
     * Dùng khi cần export, thống kê tổng thể, hoặc dataset nhỏ.
     * Nếu đơn hàng nhiều (> 500) thì ưu tiên paginateWithFilter() thay thế.
     *
     * @param  string|null $status Lọc theo trạng thái (null = lấy tất cả).
     * @return array               Mảng đơn hàng, mỗi phần tử gồm cột orders + customer_name/phone.
     * @throws InvalidArgumentException Nếu $status không hợp lệ.
     */
    public function getAllOrders(?string $status = null): array
    {
        $filter = $this->buildStatusFilter($status, 'WHERE');

        return $this->fetchAll(
            "SELECT o.*,
                    c.name  AS customer_name,
                    c.phone AS customer_phone
             FROM   orders o
             LEFT JOIN customers c ON o.customer_id = c.id
             {$filter['clause']}
             ORDER BY o.created_at DESC",
            $filter['params']
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
     * @throws InvalidArgumentException Nếu $from > $to (khoảng thời gian ngược).
     */
    public function getByDateRange(string $from, string $to): array
    {
        // Validate thứ tự ngày để tránh truy vấn trả về rỗng âm thầm
        if ($from > $to) {
            throw new InvalidArgumentException(
                "Ngày bắt đầu ({$from}) không được lớn hơn ngày kết thúc ({$to})."
            );
        }

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
     *   - 'order'  : thông tin đơn hàng + tên/SĐT/địa chỉ/email khách hàng
     *   - 'items'  : mảng các dòng chi tiết, mỗi dòng gồm:
     *                  product_id, product_name, product_image,
     *                  quantity, price_at_purchase, subtotal (= qty * price)
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
        if ($orderId <= 0) {
            return null;
        }

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
     * @return bool            true nếu cập nhật thành công (rowCount > 0).
     * @throws InvalidArgumentException Nếu $orderId <= 0 hoặc $status không hợp lệ.
     */
    public function updateStatus(int $orderId, string $status): bool
    {
        if ($orderId <= 0) {
            throw new InvalidArgumentException('orderId phải là số nguyên dương.');
        }

        $this->assertValidStatus($status);

        return $this->update($orderId, ['status' => $status]);
    }

    /**
     * Cập nhật tổng tiền đơn hàng.
     *
     * Dùng khi OrderService tính lại tổng tiền sau khi áp dụng khuyến mãi
     * hoặc khi admin chỉnh sửa đơn hàng.
     *
     * @param  int   $orderId    ID đơn hàng.
     * @param  float $totalPrice Tổng tiền mới (>= 0).
     * @return bool
     * @throws InvalidArgumentException Nếu $orderId <= 0 hoặc $totalPrice âm.
     */
    public function updateTotalPrice(int $orderId, float $totalPrice): bool
    {
        if ($orderId <= 0) {
            throw new InvalidArgumentException('orderId phải là số nguyên dương.');
        }

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
     * Thứ tự: xoá order_details trước → xoá orders sau.
     * Đảo thứ tự sẽ vi phạm FK constraint nếu DB bật foreign key checks.
     *
     * @param  int|string $id ID đơn hàng.
     * @return bool           true nếu xoá thành công.
     * @throws InvalidArgumentException Nếu $id <= 0.
     */
    public function delete(int|string $id): bool
    {
        $id = (int) $id;

        if ($id <= 0) {
            throw new InvalidArgumentException('orderId phải là số nguyên dương.');
        }

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
     * Đếm tổng số đơn hàng.
     *
     * Override count() của BaseModel để hỗ trợ lọc thêm theo status.
     * Khi $status = null → đếm toàn bộ (gọi count() của BaseModel).
     *
     * @param  string|null $status Lọc theo trạng thái (null = đếm tất cả).
     * @return int
     * @throws InvalidArgumentException Nếu $status không hợp lệ.
     */
    public function count(?string $status = null): int
    {
        if ($status === null) {
            return parent::count();
        }

        $this->assertValidStatus($status);

        $row = $this->fetchOne(
            "SELECT COUNT(*) AS cnt FROM orders WHERE status = ?",
            [$status]
        );

        return isset($row['cnt']) ? (int) $row['cnt'] : 0;
    }

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
     * Chỉ trả về các trạng thái thực sự có đơn hàng (GROUP BY tự lọc).
     * Để luôn hiển thị đủ 5 trạng thái kể cả khi total = 0,
     * dùng getStatusSummary() thay thế.
     *
     * @return array
     */
    public function countByStatus(): array
    {
        return $this->fetchAll(
            "SELECT status, COUNT(*) AS total
             FROM   orders
             GROUP BY status
             ORDER BY FIELD(status, 'pending','confirmed','shipped','completed','cancelled')"
        );
    }

    /**
     * Lấy tổng quan số đơn theo TẤT CẢ trạng thái, kể cả trạng thái chưa có đơn nào.
     *
     * Khác countByStatus(): method này luôn trả đủ 5 trạng thái với total = 0
     * nếu chưa có đơn → tránh UI bị thiếu badge khi một trạng thái chưa có dữ liệu.
     *
     * Kết quả trả về dạng:
     *   [
     *     'pending'   => 5,
     *     'confirmed' => 3,
     *     'shipped'   => 0,
     *     'completed' => 12,
     *     'cancelled' => 1,
     *   ]
     *
     * @return array<string, int>
     */
    public function getStatusSummary(): array
    {
        // Khởi tạo với 0 để đảm bảo đủ 5 key dù DB chưa có data
        $summary = array_fill_keys(self::VALID_STATUSES, 0);

        $rows = $this->countByStatus();
        foreach ($rows as $row) {
            if (isset($summary[$row['status']])) {
                $summary[$row['status']] = (int) $row['total'];
            }
        }

        return $summary;
    }

    /**
     * Tính tổng doanh thu từ các đơn hàng đã hoàn thành.
     *
     * Chỉ tính đơn có status = 'completed' để phản ánh doanh thu thực tế.
     * COALESCE(SUM(...), 0) đảm bảo trả về 0.0 thay vì NULL khi chưa có đơn nào.
     *
     * @return float Tổng doanh thu (0.0 nếu chưa có đơn hoàn thành nào).
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
     * Tính doanh thu theo tháng/năm cụ thể.
     *
     * Dùng cho widget doanh thu tháng hiện tại trên dashboard admin.
     * Chỉ tính đơn 'completed'.
     *
     * @param  int $year  Năm (vd: 2025).
     * @param  int $month Tháng (1-12).
     * @return float
     * @throws InvalidArgumentException Nếu $month không hợp lệ.
     */
    public function getRevenueByMonth(int $year, int $month): float
    {
        if ($month < 1 || $month > 12) {
            throw new InvalidArgumentException('Tháng phải từ 1 đến 12.');
        }

        $row = $this->fetchOne(
            "SELECT COALESCE(SUM(total_price), 0) AS revenue
             FROM   orders
             WHERE  status = 'completed'
               AND  YEAR(created_at)  = ?
               AND  MONTH(created_at) = ?",
            [$year, $month]
        );

        return isset($row['revenue']) ? (float) $row['revenue'] : 0.0;
    }

    /**
     * Lấy danh sách đơn hàng gần đây nhất (dùng cho widget dashboard).
     *
     * @param  int $limit Số đơn cần lấy (mặc định 5, tối đa 100).
     * @return array
     */
    public function getRecentOrders(int $limit = 5): array
    {
        // Giới hạn trên để tránh truy vấn không kiểm soát
        $limit = min(max(1, $limit), 100);

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
     * Dùng count() đã override ở trên để đếm tổng theo status,
     * đảm bảo $total nhất quán với bộ lọc hiện tại.
     *
     * @param  int         $page   Trang hiện tại (bắt đầu từ 1).
     * @param  int         $limit  Số đơn mỗi trang (1 - 100).
     * @param  string|null $status Lọc theo trạng thái (null = lấy tất cả).
     * @return array               Mảng ['data', 'total', 'currentPage', 'totalPages', 'limit', 'status'].
     * @throws InvalidArgumentException Nếu $page/$limit không hợp lệ hoặc $status sai.
     */
    public function paginateWithFilter(
        int     $page   = 1,
        int     $limit  = 10,
        ?string $status = null
    ): array {
        if ($page < 1) {
            throw new InvalidArgumentException('Số trang phải >= 1.');
        }
        if ($limit < 1 || $limit > 100) {
            throw new InvalidArgumentException('Số bản ghi mỗi trang phải từ 1 đến 100.');
        }

        $offset = ($page - 1) * $limit;
        $filter = $this->buildStatusFilter($status, 'WHERE');

        // Dùng count() đã override → nhất quán với bộ lọc, không lặp SQL đếm
        $total = $this->count($status);

        $data = $this->fetchAll(
            "SELECT o.*,
                    c.name  AS customer_name,
                    c.phone AS customer_phone
             FROM   orders o
             LEFT JOIN customers c ON o.customer_id = c.id
             {$filter['clause']}
             ORDER BY o.created_at DESC
             LIMIT {$limit} OFFSET {$offset}",
            $filter['params']
        );

        return [
            'data'        => $data,
            'total'       => $total,
            'currentPage' => $page,
            'totalPages'  => $total > 0 ? (int) ceil($total / $limit) : 0,
            'limit'       => $limit,
            'status'      => $status,
        ];
    }
}
