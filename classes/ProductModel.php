<?php

/**
 * Class ProductModel
 *
 * Quản lý sản phẩm: CRUD, phân trang, search, liên kết tồn kho và khuyến mãi,
 * thống kê, type-safe với ProductEntity.
 *
 * @package App\Models
 */
class ProductModel extends BaseModel
{
    protected string $table = 'products';
    protected string $primaryKey = 'id';
    protected string $defaultOrder = 'created_at DESC';

    private InventoryModel $inventoryModel;
    private PromotionModel $promotionModel;

    public function __construct()
    {
        parent::__construct();
        $this->inventoryModel = new InventoryModel();
        $this->promotionModel = new PromotionModel();
    }

    // =========================================================================
    // CRUD
    // =========================================================================

    /**
     * Tạo sản phẩm mới
     *
     * @param array $data
     *   required: name, price
     *   optional: sku, description, is_active
     * @return int ID sản phẩm mới
     */
    public function insertProduct(array $data): int
    {
        $data['is_active'] = $data['is_active'] ?? 1;
        $data['created_at'] = date('Y-m-d H:i:s');

        // insert trả về auto-increment ID
        return $this->insert($data);
    }

    /**
     * Cập nhật sản phẩm theo ID
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function updateProduct(int $id, array $data): bool
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->update($id, $data);
    }

    /**
     * Soft delete: chỉ đánh dấu không active
     *
     * @param int $id
     * @return bool
     */
    public function deleteProduct(int $id): bool
    {
        return $this->updateProduct($id, ['is_active' => 0]);
    }

    /**
     * Hard delete: xóa hẳn bản ghi
     *
     * @param int $id
     * @return bool
     */
    public function hardDeleteProduct(int $id): bool
    {
        return parent::delete($id);
    }

    // =========================================================================
    // LẤY DỮ LIỆU
    // =========================================================================

    /**
     * Lấy sản phẩm theo ID
     *
     * @param int $id
     * @return ProductEntity|null
     */
    public function getById(int $id): ?ProductEntity
    {
        $row = $this->fetchOne("SELECT * FROM {$this->table} WHERE id = ?", [$id]);
        return $row ? new ProductEntity($row) : null;
    }

    /**
     * Lấy tất cả sản phẩm
     *
     * @param bool $onlyActive
     * @return ProductEntity[]
     */
    public function getAll(bool $onlyActive = true): array
    {
        $sql = "SELECT * FROM {$this->table}";
        if ($onlyActive) $sql .= " WHERE is_active = 1";
        $sql .= " ORDER BY {$this->defaultOrder}";

        $rows = $this->fetchAll($sql);
        return array_map(fn($r) => new ProductEntity($r), $rows);
    }

    /**
     * Tìm sản phẩm theo tên hoặc SKU
     *
     * @param string $keyword
     * @param bool $onlyActive
     * @return ProductEntity[]
     */
    public function search(string $keyword, bool $onlyActive = true): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE (name LIKE ? OR sku LIKE ?)";
        $params = ["%$keyword%", "%$keyword%"];
        if ($onlyActive) {
            $sql .= " AND is_active = 1";
        }
        $sql .= " ORDER BY {$this->defaultOrder}";

        $rows = $this->fetchAll($sql, $params);
        return array_map(fn($r) => new ProductEntity($r), $rows);
    }

    /**
     * Phân trang sản phẩm
     *
     * @param int $page
     * @param int $limit
     * @param bool $onlyActive
     * @return array ['data' => ProductEntity[], 'total' => int, 'totalPages' => int, 'currentPage' => int]
     */
    public function paginate(int $page = 1, int $limit = 10, bool $onlyActive = true): array
    {
        $page = max(1, $page);
        $limit = max(1, $limit);
        $offset = ($page - 1) * $limit;

        $where = $onlyActive ? 'WHERE is_active = 1' : '';
        $total = (int)($this->fetchOne("SELECT COUNT(*) AS cnt FROM {$this->table} {$where}")['cnt'] ?? 0);

        $sql = "SELECT * FROM {$this->table} {$where} ORDER BY {$this->defaultOrder} LIMIT {$limit} OFFSET {$offset}";
        $rows = $this->fetchAll($sql);
        $data = array_map(fn($r) => new ProductEntity($r), $rows);

        return [
            'data' => $data,
            'total' => $total,
            'totalPages' => (int)ceil($total / $limit),
            'currentPage' => $page,
        ];
    }

    // =========================================================================
    // INVENTORY & STOCK
    // =========================================================================

    /**
     * Kiểm tra tồn kho của sản phẩm
     *
     * @param int $productId
     * @param int $requiredQty
     * @return bool
     */
    public function checkStock(int $productId, int $requiredQty): bool
    {
        $inventory = $this->inventoryModel->getByProductId($productId);
        if ($inventory === null) return false;
        return $inventory->getQuantity() >= $requiredQty;
    }

    /**
     * Giảm tồn kho sản phẩm
     *
     * @param int $productId
     * @param int $qty
     * @return bool
     */
    public function decreaseStock(int $productId, int $qty): bool
    {
        return $this->inventoryModel->decreaseStock($productId, $qty);
    }

    // =========================================================================
    // PROMOTION
    // =========================================================================

    /**
     * Áp dụng khuyến mãi cho sản phẩm
     *
     * @param ProductEntity $product
     * @param int|null $promotionId
     * @return float Giá sau khuyến mãi
     */
    public function applyPromotion(ProductEntity $product, ?int $promotionId): float
    {
        $price = $product->getPrice();

        if ($promotionId === null) return $price;

        $promo = $this->promotionModel->getById($promotionId);
        if ($promo === null) return $price;

        if ($promo['type'] === 'percent') {
            $price *= (100 - $promo['value']) / 100;
        } elseif ($promo['type'] === 'fixed') {
            $price -= $promo['value'];
        }

        return max(0, $price);
    }

    // =========================================================================
    // THỐNG KÊ
    // =========================================================================

    /**
     * Đếm tổng sản phẩm
     *
     * @return int
     */
    public function countAll(): int
    {
        return (int)($this->fetchOne("SELECT COUNT(*) AS cnt FROM {$this->table}")['cnt'] ?? 0);
    }

    /**
     * Đếm sản phẩm còn hàng
     *
     * @return int
     */
    public function countInStock(): int
    {
        $rows = $this->inventoryModel->getAll(); // trả InventoryEntity[]
        $cnt = 0;
        foreach ($rows as $inv) {
            if ($inv->getQuantity() > 0) $cnt++;
        }
        return $cnt;
    }

    /**
     * Đếm sản phẩm hết hàng
     *
     * @return int
     */
    public function countOutOfStock(): int
    {
        return $this->countAll() - $this->countInStock();
    }
}
