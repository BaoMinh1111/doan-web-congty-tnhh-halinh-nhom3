<?php

/**
 * Class InventoryModel
 *
 * Quản lý tồn kho sản phẩm — bảng `inventory`.
 * Kế thừa BaseModel để tái sử dụng kết nối PDO và CRUD cơ bản.
 *
 * @package App\Models
 * @author  Ha Linh Technology Solutions
 */
class InventoryModel extends BaseModel
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
    protected string $table = 'inventory';

    /**
     * Tên cột khoá chính của bảng inventory.
     * Override $primaryKey của BaseModel (mặc định 'id').
     *
     * @var string
     */
    protected string $primaryKey = 'id';

    /**
     * Sắp xếp mặc định khi gọi getAll().
     *
     * @var string
     */
    protected string $defaultOrder = 'product_id ASC';


    // =========================================================================
    // OVERRIDE — TRẢ VỀ ENTITY THAY VÌ MẢNG THÔ
    // =========================================================================

    /**
     * Lấy thông tin tồn kho theo product_id — trả về InventoryEntity.
     *
     * Đây là phương thức chính mà OrderService::checkStock() gọi (tầng 1).
     * Override BaseModel::getById() để trả Entity thay vì mảng thô,
     * nhưng tìm theo product_id (không phải PK id của bảng inventory).
     *
     * @param  int               $productId ID sản phẩm (FK → products.id).
     * @return InventoryEntity|null         Entity tồn kho, hoặc null nếu chưa có bản ghi.
     */
    public function getByProductId(int $productId): ?InventoryEntity
    {
        $row = $this->fetchOne(
            'SELECT * FROM inventory WHERE product_id = ? LIMIT 1',
            [$productId]
        );

        // fetchOne() trả null nếu không tìm thấy → trả null thẳng, không bọc Entity
        return $row !== null ? new InventoryEntity($row) : null;
    }


    // =========================================================================
    // THAO TÁC TỒN KHO
    // =========================================================================

    /**
     * Trừ tồn kho sau khi đơn hàng được tạo thành công.
     *
     * Đây là điểm gọi thứ 2 từ OrderService (bước 8, trong transaction).
     * 
     * @param  int  $productId ID sản phẩm.
     * @param  int  $quantity  Số lượng cần trừ (phải > 0).
     * @return bool            true nếu trừ thành công (đủ hàng + UPDATE thành công).
     *                         false nếu không đủ hàng hoặc không tìm thấy bản ghi.
     * @throws InvalidArgumentException Nếu $quantity <= 0.
     */
    public function decreaseStock(int $productId, int $quantity): bool
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException(
                "Số lượng trừ tồn kho phải > 0, nhận được: {$quantity}."
            );
        }

        $stmt = $this->prepareStmt(
            // Điều kiện "quantity >= ?" là tầng bảo vệ thứ 2 chống race condition
            // và chống tồn kho xuống âm — quan trọng khi nhiều request đồng thời.
            'UPDATE inventory
                SET quantity     = quantity - ?,
                    last_updated = NOW()
              WHERE product_id   = ?
                AND quantity    >= ?',
            [$quantity, $productId, $quantity]
        );

        // rowCount() = 1 → UPDATE thành công → đủ hàng
        // rowCount() = 0 → WHERE không khớp → không đủ hàng hoặc không tìm thấy bản ghi
        return $stmt->rowCount() > 0;
    }

    /**
     * Cộng tồn kho — dùng khi huỷ đơn hàng hoặc nhập thêm hàng.
     *
     * Đây là điểm gọi thứ 3 (increaseStock), không dùng trong createOrderFromCart()
     * nhưng cần thiết khi admin huỷ đơn và muốn hoàn lại tồn kho.
     *
     * @param  int  $productId ID sản phẩm.
     * @param  int  $quantity  Số lượng cần cộng thêm (phải > 0).
     * @return bool            true nếu cập nhật thành công.
     * @throws InvalidArgumentException Nếu $quantity <= 0.
     */
    public function increaseStock(int $productId, int $quantity): bool
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException(
                "Số lượng cộng tồn kho phải > 0, nhận được: {$quantity}."
            );
        }

        $stmt = $this->prepareStmt(
            'UPDATE inventory
                SET quantity     = quantity + ?,
                    last_updated = NOW()
              WHERE product_id   = ?',
            [$quantity, $productId]
        );

        return $stmt->rowCount() > 0;
    }

    /**
     * Khởi tạo bản ghi tồn kho cho sản phẩm mới.
     *
     * Gọi từ AdminController sau khi tạo sản phẩm mới,
     * đảm bảo mọi sản phẩm đều có bản ghi trong bảng inventory.
     * Nếu không gọi hàm này, getByProductId() sẽ trả null
     * → OrderService::checkStock() báo lỗi "không có thông tin tồn kho".
     *
     * Chiến lược khi đã tồn tại (SKIP):
     *   Nếu product_id đã có bản ghi → giữ nguyên, trả về ID cũ.
     *   Lý do: initStock() chỉ có nhiệm vụ "đảm bảo bản ghi tồn tại",
     *   không phải "cập nhật số lượng". Nếu cần cập nhật quantity,
     *   dùng increaseStock() hoặc update() trực tiếp — tường minh hơn.
     *
     * @param  int  $productId       ID sản phẩm (FK → products.id).
     * @param  int  $initialQuantity Số lượng ban đầu (mặc định 0, không được âm).
     * @return int                   ID bản ghi inventory (mới tạo hoặc đã tồn tại).
     * @throws InvalidArgumentException Nếu $productId <= 0.
     */
    public function initStock(int $productId, int $initialQuantity = 0): int
    {
        if ($productId <= 0) {
            throw new InvalidArgumentException(
                "productId phải > 0, nhận được: {$productId}."
            );
        }

        // Bước 1: Kiểm tra bản ghi đã tồn tại chưa
        $existing = $this->getByProductId($productId);

        if ($existing !== null) {
            // Đã có → SKIP, trả về ID cũ để caller biết bản ghi nào đang dùng.
            // Không insert trùng, không ghi đè quantity đang có.
            return $existing->getId();
        }

        // Bước 2: Chưa có → tạo mới
        // max(0, ...) đảm bảo initialQuantity không bao giờ âm ngay từ đầu.
        $newId = $this->insert([
            'product_id'   => $productId,
            'quantity'     => max(0, $initialQuantity),
            'last_updated' => date('Y-m-d H:i:s'),
        ]);

        if ($newId <= 0) {
            throw new RuntimeException(
                "Khởi tạo tồn kho thất bại cho product_id={$productId}."
            );
        }

        return $newId;
    }
}