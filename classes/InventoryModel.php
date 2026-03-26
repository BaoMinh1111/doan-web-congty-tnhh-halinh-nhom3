<?php

/**
 * Class InventoryModel
 *
 * Quản lý tồn kho:
 * - Lấy tồn kho theo product_id
 * - Tăng / giảm tồn kho
 * - Liên kết với InventoryEntity và ProductEntity
 * - Phục vụ trực tiếp cho OrderService (checkStock, decreaseStock)
 *
 * @package App\Models
 */
class InventoryModel extends BaseModel
{
    protected string $table = 'inventory';
    protected string $primaryKey = 'id';

    private ProductModel $productModel;

    // =========================================================================
    // CONSTRUCTOR
    // =========================================================================
    public function __construct()
    {
        parent::__construct();
        $this->productModel = new ProductModel();
    }

    // =========================================================================
    // LẤY INVENTORY
    // =========================================================================

    /**
     * Lấy tồn kho theo product_id
     *
     * @return InventoryEntity|null
     */
    public function getByProductId(int $productId): ?InventoryEntity
    {
        $row = $this->fetchOne(
            "SELECT * FROM inventory WHERE product_id = ?",
            [$productId]
        );

        if (!$row) return null;

        return new InventoryEntity($row, $this->productModel);
    }

    /**
     * Lấy theo ID
     */
    public function getById(int $id): ?InventoryEntity
    {
        $row = $this->find($id);
        return $row ? new InventoryEntity($row, $this->productModel) : null;
    }

    // =========================================================================
    // LƯU / CẬP NHẬT
    // =========================================================================

    /**
     * Lưu inventory (insert hoặc update)
     */
    public function save(InventoryEntity $inventory): int|bool
    {
        $data = $inventory->toArray();

        if ($inventory->getId() > 0) {
            return $this->update($inventory->getId(), $data);
        }

        return $this->insert($data);
    }

    // =========================================================================
    // TĂNG TỒN KHO
    // =========================================================================

    public function increaseStock(int $productId, int $qty): bool
    {
        return $this->transaction(function () use ($productId, $qty) {

            $inventory = $this->getByProductId($productId);

            if ($inventory === null) {
                throw new RuntimeException("Không tìm thấy tồn kho của sản phẩm ID={$productId}");
            }

            $inventory->increaseStock($qty);

            return (bool) $this->save($inventory);
        });
    }

    // =========================================================================
    // GIẢM TỒN KHO (DÙNG TRONG ORDER SERVICE)
    // =========================================================================

    public function decreaseStock(int $productId, int $qty): bool
    {
        return $this->transaction(function () use ($productId, $qty) {

            $inventory = $this->getByProductId($productId);

            if ($inventory === null) {
                throw new RuntimeException("Không có dữ liệu tồn kho cho sản phẩm ID={$productId}");
            }

            // check tồn kho (trùng logic với OrderService nhưng vẫn nên giữ để an toàn)
            if (!$inventory->hasEnoughStock($qty)) {
                throw new RuntimeException(
                    "Không đủ hàng (còn {$inventory->getQuantity()}, cần {$qty})"
                );
            }

            // giảm tồn kho
            $inventory->decreaseStock($qty);

            return (bool) $this->save($inventory);
        });
    }

    // =========================================================================
    // HELPER NÂNG CAO (OPTIONAL NHƯNG “WOW”)
    // =========================================================================

    /**
     * Kiểm tra nhanh còn hàng không
     */
    public function isInStock(int $productId): bool
    {
        $inventory = $this->getByProductId($productId);
        return $inventory !== null && !$inventory->isOutOfStock();
    }

    /**
     * Lấy số lượng tồn kho
     */
    public function getStockQuantity(int $productId): int
    {
        $inventory = $this->getByProductId($productId);
        return $inventory ? $inventory->getQuantity() : 0;
    }
}
Bạn đã gửi
<?php

/**
 * Class InventoryModel
 *
 * Lớp này dùng để quản lý tồn kho sản phẩm
 * - Lấy tồn kho theo product
 * - Tăng / giảm số lượng
 * - Dùng trong OrderService khi đặt hàng
 */
class InventoryModel extends BaseModel
{
    protected string $table = 'inventory';
    protected string $primaryKey = 'id';

    // Dùng để lấy thông tin product liên quan
    private ProductModel $productModel;

    // =========================================================================
    // CONSTRUCTOR
    // =========================================================================
    public function __construct()
    {
        parent::__construct();
        $this->productModel = new ProductModel();
    }

    // =========================================================================
    // LẤY INVENTORY
    // =========================================================================

    /**
     * Lấy tồn kho theo product_id
     * → dùng nhiều nhất trong OrderService
     */
    public function getByProductId(int $productId): ?InventoryEntity
    {
        $row = $this->fetchOne(
            "SELECT * FROM inventory WHERE product_id = ?",
            [$productId]
        );

        // nếu có dữ liệu thì trả về entity, không thì null
        return $row ? new InventoryEntity($row, $this->productModel) : null;
    }

    /**
     * Lấy theo id của inventory
     */
    public function getById(int $id): ?InventoryEntity
    {
        $row = $this->find($id);
        return $row ? new InventoryEntity($row, $this->productModel) : null;
    }

    /**
     * Lấy tất cả tồn kho (dùng cho admin)
     */
    public function getAll(): array
    {
        $rows = $this->fetchAll("SELECT * FROM inventory ORDER BY updated_at DESC");

        // convert từng dòng DB thành object InventoryEntity
        return array_map(
            fn($row) => new InventoryEntity($row, $this->productModel),
            $rows
        );
    }

    // =========================================================================
    // LƯU / CẬP NHẬT
    // =========================================================================

    /**
     * Lưu inventory (tự động insert hoặc update)
     */
    public function save(InventoryEntity $inventory): int|bool
    {
        $data = $inventory->toArray();

        // nếu đã có id → update
        if ($inventory->getId() > 0) {
            return $this->update($inventory->getId(), $data);
        }

        // chưa có id → insert mới
        return $this->insert($data);
    }

    // =========================================================================
    // TĂNG TỒN KHO
    // =========================================================================

    /**
     * Tăng số lượng sản phẩm trong kho
     */
    public function increaseStock(int $productId, int $qty): bool
    {
        return $this->transaction(function () use ($productId, $qty) {

            // lấy tồn kho hiện tại
            $inventory = $this->getByProductId($productId);

            // nếu chưa có record → tạo mới luôn
            if ($inventory === null) {
                $inventory = new InventoryEntity([
                    'product_id' => $productId,
                    'quantity'   => 0
                ], $this->productModel);
            }

            // tăng số lượng
            $inventory->increaseStock($qty);

            // lưu lại DB
            return (bool) $this->save($inventory);
        });
    }

    // =========================================================================
    // GIẢM TỒN KHO (DÙNG KHI ĐẶT HÀNG)
    // =========================================================================

    /**
     * Giảm tồn kho của 1 sản phẩm
     */
    public function decreaseStock(int $productId, int $qty): bool
    {
        return $this->transaction(function () use ($productId, $qty) {

            $inventory = $this->getByProductId($productId);

            // không có tồn kho → lỗi
            if ($inventory === null) {
                throw new RuntimeException("Không có tồn kho cho sản phẩm ID={$productId}");
            }

            // kiểm tra đủ hàng không
            if (!$inventory->hasEnoughStock($qty)) {
                throw new RuntimeException(
                    "Không đủ hàng (còn {$inventory->getQuantity()}, cần {$qty})"
                );
            }

            // giảm số lượng
            $inventory->decreaseStock($qty);

            // lưu lại DB
            return (bool) $this->save($inventory);
        });
    }

    // =========================================================================
    // GIẢM TỒN KHO NHIỀU SẢN PHẨM (QUAN TRỌNG)
    // =========================================================================

    /**
     * Giảm tồn kho cho nhiều sản phẩm cùng lúc
     * → dùng khi user đặt nhiều sản phẩm trong 1 đơn hàng
     */
    public function decreaseStockBatch(array $items): bool
    {
        return $this->transaction(function () use ($items) {

            foreach ($items as $item) {
                $productId = (int)$item['product_id'];
                $qty       = (int)$item['quantity'];

                $inventory = $this->getByProductId($productId);

                // nếu thiếu hàng → dừng luôn
                if (!$inventory || !$inventory->hasEnoughStock($qty)) {
                    throw new RuntimeException("Sản phẩm {$productId} không đủ hàng.");
                }

                // giảm tồn
                $inventory->decreaseStock($qty);

                // lưu lại
                $this->save($inventory);
            }

            return true;
        });
    }

    // =========================================================================
    // XOÁ INVENTORY
    // =========================================================================

    /**
     * Xoá tồn kho theo product_id
     */
    public function deleteByProductId(int $productId): bool
    {
        return (bool) $this->prepareStmt(
            "DELETE FROM inventory WHERE product_id = ?",
            [$productId]
        );
    }

    // =========================================================================
    // HELPER
    // =========================================================================

    /**
     * Kiểm tra còn hàng không
     */
    public function isInStock(int $productId): bool
    {
        $inventory = $this->getByProductId($productId);
        return $inventory !== null && !$inventory->isOutOfStock();
    }

    /**
     * Lấy số lượng tồn kho
     */
    public function getStockQuantity(int $productId): int
    {
        $inventory = $this->getByProductId($productId);
        return $inventory ? $inventory->getQuantity() : 0;
    }
}
