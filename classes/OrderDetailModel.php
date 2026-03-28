<?php

/**
 * Class OrderDetailModel
 *
 * Quản lý thao tác CSDL trên bảng order_details.
 * Kế thừa BaseModel → dùng chung kết nối PDO từ Database Singleton.
 *
 * Quan hệ:
 *   order_details ←→ orders   : Many-to-One (nhiều dòng chi tiết → 1 đơn hàng)
 *   order_details ←→ products : Many-to-One (nhiều dòng chi tiết → 1 sản phẩm)
 *
 * @package App\Models
 * @author  Ha Linh Technology Solutions
 */
class OrderDetailModel extends BaseModel
{
    // =========================================================================
    // CẤU HÌNH BẢNG
    // =========================================================================

    /**
     * Tên bảng trong CSDL.
     * Override $table của BaseModel.
     *
     * @var string
     */
    protected string $table = 'order_details';

    /**
     * Bảng này dùng composite key (order_id, product_id), không có cột 'id'.
     * $primaryKey của BaseModel không áp dụng được → các method dùng $primaryKey
     * (getById, update, delete, exists) đều bị override hoặc vô hiệu ở lớp này.
     *
     * Đặt giá trị rỗng để lỡ ai gọi nhầm các method kế thừa sẽ nhận lỗi rõ ràng.
     *
     * @var string
     */
    protected string $primaryKey = '';

    /**
     * Sắp xếp mặc định khi gọi getAll().
     * Override $defaultOrder của BaseModel.
     *
     * @var string
     */
    protected string $defaultOrder = 'order_id DESC';


    // =========================================================================
    // CONSTRUCTOR
    // =========================================================================

    /**
     * Gọi constructor của BaseModel.
     * BaseModel sẽ tự lấy kết nối PDO từ Database::getInstance().
     *
     * Override $primaryKey = '' nên BaseModel không kiểm tra $table rỗng
     * (chỉ kiểm tra $table, không kiểm tra $primaryKey) → vẫn hoạt động bình thường.
     */
    public function __construct()
    {
        parent::__construct();
    }


    // =========================================================================
    // THÊM CHI TIẾT ĐƠN HÀNG
    // =========================================================================

    /**
     * Thêm một dòng chi tiết vào đơn hàng.
     *
     * Override insert() của BaseModel vì:
     *   - Bảng dùng composite key → lastInsertId() luôn trả 0 (vô nghĩa).
     *   - Trả bool thay vì int để rõ ý nghĩa hơn.
     *   - Validate quantity và price trước khi INSERT.
     *
     * Nếu cặp (order_id, product_id) đã tồn tại → dùng updateQuantity() thay thế,
     * hoặc dùng insertOrUpdate() để tự động xử lý cả hai trường hợp.
     *
     * @param  int   $orderId         ID đơn hàng (FK → orders.id).
     * @param  int   $productId        ID sản phẩm (FK → products.id).
     * @param  int   $quantity         Số lượng (phải > 0).
     * @param  float $priceAtPurchase  Giá tại thời điểm mua (chụp lại từ products.price).
     * @return bool                    true nếu INSERT thành công.
     * @throws InvalidArgumentException Nếu quantity <= 0 hoặc price < 0.
     */
    public function addDetail(
        int   $orderId,
        int   $productId,
        int   $quantity,
        float $priceAtPurchase
    ): bool {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Số lượng sản phẩm phải lớn hơn 0.');
        }
        if ($priceAtPurchase < 0) {
            throw new InvalidArgumentException('Giá sản phẩm không được âm.');
        }

        $stmt = $this->prepareStmt(
            "INSERT INTO order_details (order_id, product_id, quantity, price_at_purchase)
             VALUES (?, ?, ?, ?)",
            [$orderId, $productId, $quantity, $priceAtPurchase]
        );

        return $stmt->rowCount() > 0;
    }

    /**
     * Thêm nhiều dòng chi tiết cùng lúc cho một đơn hàng (batch insert).
     *
     * Hiệu quả hơn gọi addDetail() nhiều lần vì chỉ thực hiện 1 câu INSERT.
     * Toàn bộ batch được bọc trong transaction → hoặc tất cả thành công,
     * hoặc rollback nếu có bất kỳ lỗi nào.
     *
     * Dùng trong OrderService::createOrderFromCart() khi tạo đơn hàng mới.
     *
     * Cấu trúc $items:
     *   [
     *     ['product_id' => 1, 'quantity' => 2, 'price_at_purchase' => 500000],
     *     ['product_id' => 3, 'quantity' => 1, 'price_at_purchase' => 1200000],
     *   ]
     *
     * @param  int   $orderId ID đơn hàng.
     * @param  array $items   Mảng các dòng chi tiết.
     * @return bool           true nếu tất cả dòng được INSERT thành công.
     * @throws InvalidArgumentException Nếu $items rỗng hoặc thiếu trường bắt buộc.
     */
    public function addDetails(int $orderId, array $items): bool
    {
        if (empty($items)) {
            throw new InvalidArgumentException('Danh sách chi tiết đơn hàng không được rỗng.');
        }

        // Validate từng dòng trước khi bắt đầu transaction
        foreach ($items as $index => $item) {
            if (empty($item['product_id'])) {
                throw new InvalidArgumentException("Dòng [{$index}]: thiếu product_id.");
            }
            if (empty($item['quantity']) || (int) $item['quantity'] <= 0) {
                throw new InvalidArgumentException("Dòng [{$index}]: quantity phải > 0.");
            }
            if (!isset($item['price_at_purchase']) || (float) $item['price_at_purchase'] < 0) {
                throw new InvalidArgumentException("Dòng [{$index}]: price_at_purchase không hợp lệ.");
            }
        }

        // Bọc toàn bộ trong transaction: hoặc tất cả thành công hoặc rollback hết
        return $this->transaction(function () use ($orderId, $items): bool {
            foreach ($items as $item) {
                $this->addDetail(
                    $orderId,
                    (int)   $item['product_id'],
                    (int)   $item['quantity'],
                    (float) $item['price_at_purchase']
                );
            }
            return true;
        });
    }

    /**
     * Thêm mới hoặc cập nhật số lượng nếu (order_id, product_id) đã tồn tại.
     *
     * Dùng INSERT ... ON DUPLICATE KEY UPDATE của MySQL:
     *   - Nếu chưa có → INSERT dòng mới.
     *   - Nếu đã có   → cộng dồn quantity (không ghi đè, tránh mất dữ liệu).
     *
     * Hữu ích khi người dùng thêm cùng sản phẩm nhiều lần vào giỏ hàng.
     *
     * @param  int   $orderId
     * @param  int   $productId
     * @param  int   $quantity
     * @param  float $priceAtPurchase
     * @return bool
     */
    public function insertOrUpdate(
        int   $orderId,
        int   $productId,
        int   $quantity,
        float $priceAtPurchase
    ): bool {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Số lượng sản phẩm phải lớn hơn 0.');
        }

        $stmt = $this->prepareStmt(
            "INSERT INTO order_details (order_id, product_id, quantity, price_at_purchase)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)",
            [$orderId, $productId, $quantity, $priceAtPurchase]
        );

        // rowCount() trả về 1 nếu INSERT, 2 nếu UPDATE (đặc thù MySQL ON DUPLICATE KEY)
        return $stmt->rowCount() > 0;
    }


    // =========================================================================
    // LẤY CHI TIẾT ĐƠN HÀNG
    // =========================================================================

    /**
     * Lấy tất cả dòng chi tiết của một đơn hàng, kèm tên và ảnh sản phẩm.
     *
     * JOIN products để lấy thêm product_name và product_image,
     * tránh phải gọi thêm ProductModel trong Controller/Service.
     *
     * Kết quả mỗi dòng gồm:
     *   order_id, product_id, quantity, price_at_purchase,
     *   subtotal (= qty * price), product_name, product_image
     *
     * @param  int   $orderId ID đơn hàng.
     * @return array          Mảng chi tiết (rỗng nếu đơn không có sản phẩm nào).
     */
    public function getDetailsByOrder(int $orderId): array
    {
        return $this->fetchAll(
            "SELECT od.order_id,
                    od.product_id,
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
    }

    /**
     * Lấy một dòng chi tiết cụ thể theo composite key (order_id + product_id).
     *
     * Override getById() của BaseModel vì bảng này dùng composite key.
     * Dùng khi cần kiểm tra hoặc cập nhật một sản phẩm cụ thể trong đơn.
     *
     * @param  int        $orderId   ID đơn hàng.
     * @param  int        $productId ID sản phẩm.
     * @return array|null            Dòng chi tiết hoặc null nếu không tìm thấy.
     */
    public function getDetail(int $orderId, int $productId): ?array
    {
        return $this->fetchOne(
            "SELECT od.*,
                    (od.quantity * od.price_at_purchase) AS subtotal,
                    p.name  AS product_name,
                    p.image AS product_image
             FROM   order_details od
             INNER JOIN products p ON od.product_id = p.id
             WHERE  od.order_id = ? AND od.product_id = ?",
            [$orderId, $productId]
        );
    }

    /**
     * Tính tổng tiền của một đơn hàng từ order_details.
     *
     * Dùng để verify lại tổng tiền trong OrderService trước khi lưu,
     * hoặc tính lại khi admin chỉnh sửa đơn hàng.
     *
     * @param  int   $orderId ID đơn hàng.
     * @return float          Tổng tiền (0.0 nếu không có dòng nào).
     */
    public function calculateOrderTotal(int $orderId): float
    {
        $row = $this->fetchOne(
            "SELECT COALESCE(SUM(quantity * price_at_purchase), 0) AS total
             FROM   order_details
             WHERE  order_id = ?",
            [$orderId]
        );

        return isset($row['total']) ? (float) $row['total'] : 0.0;
    }


    // =========================================================================
    // CẬP NHẬT CHI TIẾT ĐƠN HÀNG
    // =========================================================================

    /**
     * Cập nhật số lượng của một sản phẩm trong đơn hàng.
     *
     * Override update() của BaseModel vì bảng dùng composite key.
     * Chỉ cho phép cập nhật quantity — price_at_purchase không được sửa
     * sau khi đơn đã tạo (để giữ đúng giá tại thời điểm mua).
     *
     * @param  int  $orderId   ID đơn hàng.
     * @param  int  $productId ID sản phẩm.
     * @param  int  $quantity  Số lượng mới (phải > 0).
     * @return bool            true nếu cập nhật thành công.
     * @throws InvalidArgumentException Nếu quantity <= 0.
     */
    public function updateQuantity(int $orderId, int $productId, int $quantity): bool
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Số lượng sản phẩm phải lớn hơn 0.');
        }

        $stmt = $this->prepareStmt(
            "UPDATE order_details
             SET    quantity = ?
             WHERE  order_id = ? AND product_id = ?",
            [$quantity, $orderId, $productId]
        );

        return $stmt->rowCount() > 0;
    }


    // =========================================================================
    // XOÁ CHI TIẾT ĐƠN HÀNG
    // =========================================================================

    /**
     * Xoá một dòng chi tiết theo composite key (order_id + product_id).
     *
     * Override delete() của BaseModel vì bảng dùng composite key.
     * Dùng khi admin bỏ một sản phẩm ra khỏi đơn hàng đang ở trạng thái pending.
     *
     * @param  int  $orderId   ID đơn hàng.
     * @param  int  $productId ID sản phẩm.
     * @return bool            true nếu xoá thành công.
     */
    public function deleteDetail(int $orderId, int $productId): bool
    {
        $stmt = $this->prepareStmt(
            "DELETE FROM order_details
             WHERE  order_id = ? AND product_id = ?",
            [$orderId, $productId]
        );

        return $stmt->rowCount() > 0;
    }

    /**
     * Xoá toàn bộ chi tiết của một đơn hàng.
     *
     * Dùng trong 2 trường hợp:
     *   1. OrderModel::delete() gọi trước khi xoá bản ghi orders
     *      (tránh FK constraint violation).
     *   2. OrderService khi cần tạo lại toàn bộ chi tiết đơn hàng.
     *
     * @param  int  $orderId ID đơn hàng.
     * @return bool          true nếu có ít nhất 1 dòng bị xoá.
     */
    public function deleteByOrder(int $orderId): bool
    {
        $stmt = $this->prepareStmt(
            "DELETE FROM order_details WHERE order_id = ?",
            [$orderId]
        );

        return $stmt->rowCount() > 0;
    }


    // =========================================================================
    // KIỂM TRA TỒN TẠI
    // =========================================================================

    /**
     * Kiểm tra dòng chi tiết có tồn tại theo composite key không.
     *
     * Override exists() của BaseModel vì bảng dùng composite key.
     * Dùng trong insertOrUpdate() hoặc trước khi updateQuantity().
     *
     * @param  int  $orderId   ID đơn hàng.
     * @param  int  $productId ID sản phẩm.
     * @return bool
     */
    public function detailExists(int $orderId, int $productId): bool
    {
        $row = $this->fetchOne(
            "SELECT COUNT(*) AS cnt
             FROM   order_details
             WHERE  order_id = ? AND product_id = ?",
            [$orderId, $productId]
        );

        return isset($row['cnt']) && (int) $row['cnt'] > 0;
    }


    // =========================================================================
    // VÔ HIỆU HOÁ CÁC METHOD KẾ THỪA KHÔNG PHÙ HỢP
    // =========================================================================

    /**
     * Không dùng được vì bảng dùng composite key.
     * Dùng addDetail() hoặc addDetails() thay thế.
     *
     * @throws RuntimeException Luôn luôn.
     */
    public function insert(array $data): int
    {
        throw new RuntimeException(
            'OrderDetailModel::insert() không dùng được. '
            . 'Dùng addDetail() hoặc addDetails() thay thế.'
        );
    }

    /**
     * Không dùng được vì bảng dùng composite key.
     * Dùng updateQuantity() thay thế.
     *
     * @throws RuntimeException Luôn luôn.
     */
    public function update(int|string $id, array $data): bool
    {
        throw new RuntimeException(
            'OrderDetailModel::update() không dùng được. '
            . 'Dùng updateQuantity($orderId, $productId, $quantity) thay thế.'
        );
    }

    /**
     * Không dùng được vì bảng dùng composite key.
     * Dùng deleteDetail() hoặc deleteByOrder() thay thế.
     *
     * @throws RuntimeException Luôn luôn.
     */
    public function delete(int|string $id): bool
    {
        throw new RuntimeException(
            'OrderDetailModel::delete() không dùng được. '
            . 'Dùng deleteDetail($orderId, $productId) hoặc deleteByOrder($orderId) thay thế.'
        );
    }

    /**
     * Không dùng được vì bảng dùng composite key.
     * Dùng detailExists() thay thế.
     *
     * @throws RuntimeException Luôn luôn.
     */
    public function exists(int|string $id): bool
    {
        throw new RuntimeException(
            'OrderDetailModel::exists() không dùng được. '
            . 'Dùng detailExists($orderId, $productId) thay thế.'
        );
    }

    /**
     * Không dùng được vì bảng dùng composite key.
     * Dùng getDetail() thay thế.
     *
     * @throws RuntimeException Luôn luôn.
     */
    public function getById(int|string $id): ?array
    {
        throw new RuntimeException(
            'OrderDetailModel::getById() không dùng được. '
            . 'Dùng getDetail($orderId, $productId) thay thế.'
        );
    }
}