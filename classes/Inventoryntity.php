<?php

/**
 * Class ProductEntity
 *
 * Lớp này đại diện cho 1 sản phẩm trong hệ thống.
 * Chủ yếu dùng để:
 * - Lưu dữ liệu sản phẩm (id, tên, giá,...)
 * - Hỗ trợ tính toán đơn giản (tổng tiền, kiểm tra trạng thái)
 * 
 */
class ProductEntity
{
    // =========================================================================
    // THUỘC TÍNH
    // =========================================================================
    private int $id;              // id sản phẩm
    private string $name;         // tên sản phẩm
    private string $sku;          // mã sản phẩm
    private float $price;         // giá tiền
    private ?string $description; // mô tả (có thể null)
    private bool $isActive;       // có đang bán không
    private string $createdAt;    // ngày tạo
    private string $updatedAt;    // ngày cập nhật

    // =========================================================================
    // CONSTRUCTOR
    // =========================================================================
    /**
     * Nhận dữ liệu từ DB (array) và gán vào object
     */
    public function __construct(array $data)
    {
        $this->id          = (int)($data['id'] ?? 0);
        $this->name        = trim($data['name'] ?? '');
        $this->sku         = trim($data['sku'] ?? '');
        $this->price       = (float)($data['price'] ?? 0);
        $this->description = $data['description'] ?? null;
        $this->isActive    = isset($data['is_active']) ? (bool)$data['is_active'] : true;
        $this->createdAt   = $data['created_at'] ?? date('Y-m-d H:i:s');
        $this->updatedAt   = $data['updated_at'] ?? date('Y-m-d H:i:s');

        // kiểm tra dữ liệu cơ bản (tránh lỗi)
        $this->validate();
    }

    // =========================================================================
    // VALIDATE
    // =========================================================================
    /**
     * Kiểm tra dữ liệu hợp lệ
     */
    private function validate(): void
    {
        // tên không được rỗng
        if ($this->name === '') {
            throw new InvalidArgumentException("Tên sản phẩm không được rỗng");
        }

        // sku không được rỗng
        if ($this->sku === '') {
            throw new InvalidArgumentException("SKU không được rỗng");
        }

        // giá không được âm
        if ($this->price < 0) {
            throw new InvalidArgumentException("Giá không được âm");
        }
    }

    // =========================================================================
    // GETTER (lấy dữ liệu)
    // =========================================================================
    public function getId(): int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getSku(): string { return $this->sku; }
    public function getPrice(): float { return $this->price; }
    public function getDescription(): ?string { return $this->description; }
    public function getIsActive(): bool { return $this->isActive; }
    public function getCreatedAt(): string { return $this->createdAt; }
    public function getUpdatedAt(): string { return $this->updatedAt; }

    // =========================================================================
    // SETTER (dùng khi muốn sửa dữ liệu)
    // =========================================================================

    public function setName(string $name): void
    {
        $this->name = trim($name);
        $this->validate();           // kiểm tra lại
        $this->touchUpdatedAt();     // update thời gian
    }

    public function setSku(string $sku): void
    {
        $this->sku = trim($sku);
        $this->validate();
        $this->touchUpdatedAt();
    }

    public function setPrice(float $price): void
    {
        // không cho giá âm
        if ($price < 0) {
            throw new InvalidArgumentException("Giá không được âm");
        }

        $this->price = $price;
        $this->touchUpdatedAt();
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
        $this->touchUpdatedAt();
    }

    public function setIsActive(bool $isActive): void
    {
        $this->isActive = $isActive;
        $this->touchUpdatedAt();
    }

    /**
     * Khi có thay đổi → cập nhật lại thời gian
     */
    private function touchUpdatedAt(): void
    {
        $this->updatedAt = date('Y-m-d H:i:s');
    }

    // =========================================================================
    // HELPER (dùng trong Cart / Order)
    // =========================================================================

    /**
     * Tính tổng tiền theo số lượng
     * Ví dụ: mua 3 cái → price * 3
     */
    public function getTotalPrice(int $qty): float
    {
        if ($qty <= 0) return 0;
        return $this->price * $qty;
    }

    /**
     * Format giá để hiển thị (VD: 100000 → 100.000 VNĐ)
     */
    public function getFormattedPrice(): string
    {
        return number_format($this->price, 0, ',', '.') . ' VNĐ';
    }

    /**
     * Kiểm tra sản phẩm có đang bán không
     */
    public function isAvailable(): bool
    {
        return $this->isActive && $this->price > 0;
    }

    /**
     * Kiểm tra SKU có đúng format không (chữ + số)
     */
    public function isValidSku(): bool
    {
        return preg_match('/^[A-Z0-9\-]+$/', $this->sku) === 1;
    }

    /**
     * Debug nhanh (dùng khi test/log)
     */
    public function debug(): string
    {
        return "Product[ID={$this->id}, Name={$this->name}, Price={$this->price}]";
    }

    // =========================================================================
    // CHUYỂN THÀNH ARRAY (để lưu DB)
    // =========================================================================
    public function toArray(): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'sku'         => $this->sku,
            'price'       => $this->price,
            'description' => $this->description,
            'is_active'   => $this->isActive ? 1 : 0,
            'created_at'  => $this->createdAt,
            'updated_at'  => $this->updatedAt,
        ];
    }
}
Bạn đã gửi
<?php

/**
 * Class InventoryEntity
 *
 * Lớp này đại diện cho dữ liệu tồn kho của 1 sản phẩm
 * - Mỗi sản phẩm sẽ có 1 dòng inventory
 * - Dùng để check còn hàng không và trừ hàng khi đặt đơn
 * 
 * Lưu ý: class này KHÔNG làm việc với DB (việc đó do InventoryModel)
 */
class InventoryEntity
{
    // =========================================================================
    // THUỘC TÍNH
    // =========================================================================
    private int $id;            // id của inventory
    private int $productId;     // id sản phẩm
    private int $quantity;      // số lượng còn trong kho
    private ?string $warehouse; // tên kho (có thể null)
    private string $createdAt;
    private string $updatedAt;

    // dùng để lấy thông tin product khi cần
    private ?ProductEntity $product = null;
    private ProductModel $productModel;

    // =========================================================================
    // CONSTRUCTOR
    // =========================================================================
    /**
     * Nhận dữ liệu từ DB (dạng array)
     */
    public function __construct(array $data, ?ProductModel $productModel = null)
    {
        $this->id         = (int)($data['id'] ?? 0);
        $this->productId  = (int)($data['product_id'] ?? 0);

        // đảm bảo số lượng không bị âm
        $this->quantity   = max(0, (int)($data['quantity'] ?? 0));

        $this->warehouse  = $data['warehouse'] ?? null;
        $this->createdAt  = $data['created_at'] ?? date('Y-m-d H:i:s');
        $this->updatedAt  = $data['updated_at'] ?? date('Y-m-d H:i:s');

        // nếu không truyền từ ngoài vào thì tự tạo
        $this->productModel = $productModel ?? new ProductModel();
    }

    // =========================================================================
    // GETTER (lấy dữ liệu)
    // =========================================================================
    public function getId(): int { return $this->id; }
    public function getProductId(): int { return $this->productId; }
    public function getQuantity(): int { return $this->quantity; }
    public function getWarehouse(): ?string { return $this->warehouse; }
    public function getCreatedAt(): string { return $this->createdAt; }
    public function getUpdatedAt(): string { return $this->updatedAt; }

    /**
     * Lấy thông tin sản phẩm (lazy load → chỉ load khi cần)
     */
    public function getProduct(): ?ProductEntity
    {
        if ($this->product === null && $this->productId > 0) {
            $this->product = $this->productModel->getById($this->productId);
        }

        return $this->product;
    }

    // =========================================================================
    // SETTER (cập nhật dữ liệu)
    // =========================================================================

    /**
     * Set lại số lượng (ít dùng, thường dùng increase/decrease)
     */
    public function setQuantity(int $qty): void
    {
        if ($qty < 0) {
            throw new InvalidArgumentException("Số lượng không được âm");
        }

        $this->quantity = $qty;
        $this->touchUpdatedAt();
    }

    public function setWarehouse(?string $warehouse): void
    {
        $this->warehouse = $warehouse;
        $this->touchUpdatedAt();
    }

    /**
     * Cập nhật thời gian khi có thay đổi
     */
    private function touchUpdatedAt(): void
    {
        $this->updatedAt = date('Y-m-d H:i:s');
    }

    // =========================================================================
    // LOGIC TỒN KHO
    // =========================================================================

    /**
     * Kiểm tra đủ hàng để bán không
     */
    public function hasEnoughStock(int $qty): bool
    {
        return $this->quantity >= $qty;
    }

    /**
     * Kiểm tra đã hết hàng chưa
     */
    public function isOutOfStock(): bool
    {
        return $this->quantity <= 0;
    }

    // =========================================================================
    // TĂNG / GIẢM TỒN KHO
    // =========================================================================

    /**
     * Tăng số lượng (nhập thêm hàng)
     */
    public function increaseStock(int $qty): void
    {
        if ($qty <= 0) {
            throw new InvalidArgumentException("Số lượng tăng phải > 0");
        }

        $this->quantity += $qty;
        $this->touchUpdatedAt();
    }

    /**
     * Giảm số lượng (khi đặt hàng)
     */
    public function decreaseStock(int $qty): void
    {
        if ($qty <= 0) {
            throw new InvalidArgumentException("Số lượng giảm phải > 0");
        }

        // không đủ hàng thì báo lỗi
        if ($qty > $this->quantity) {
            throw new RuntimeException(
                "Không đủ hàng (còn {$this->quantity}, cần {$qty})"
            );
        }

        $this->quantity -= $qty;
        $this->touchUpdatedAt();
    }

    // =========================================================================
    // HELPER
    // =========================================================================

    /**
     * Debug nhanh (in ra để test)
     */
    public function debug(): string
    {
        $productName = $this->getProduct()?->getName() ?? 'Unknown';

        return "Inventory[Product={$productName}, Qty={$this->quantity}]";
    }

    /**
     * Convert sang array để lưu DB
     */
    public function toArray(): array
    {
        return [
            'id'         => $this->id,
            'product_id' => $this->productId,
            'quantity'   => $this->quantity,
            'warehouse'  => $this->warehouse,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
