<?php

/**
 * Class ProductEntity
 *
 * Đại diện một sản phẩm, bao gồm getter/setter type-safe.
 * Dùng trong OrderService, InventoryModel, Cart, v.v.
 *
 * @package App\Entities
 */
class ProductEntity
{
    // =========================================================================
    // THUỘC TÍNH CƠ BẢN
    // =========================================================================
    private int $id;
    private string $name;
    private string $sku;
    private float $price;
    private ?string $description;
    private bool $isActive;
    private string $createdAt;
    private string $updatedAt;

    // =========================================================================
    // CONSTRUCTOR
    // =========================================================================
    /**
     * Khởi tạo từ mảng dữ liệu (DB row)
     *
     * @param array $data
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
    }

    // =========================================================================
    // GETTERS
    // =========================================================================
    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSku(): string
    {
        return $this->sku;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getIsActive(): bool
    {
        return $this->isActive;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): string
    {
        return $this->updatedAt;
    }

    // =========================================================================
    // SETTERS (nếu cần cập nhật trong runtime trước khi save lại DB)
    // =========================================================================
    public function setName(string $name): void
    {
        $this->name = trim($name);
    }

    public function setSku(string $sku): void
    {
        $this->sku = trim($sku);
    }

    public function setPrice(float $price): void
    {
        if ($price < 0) {
            throw new InvalidArgumentException('Price không được âm.');
        }
        $this->price = $price;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function setIsActive(bool $isActive): void
    {
        $this->isActive = $isActive;
    }

    public function setUpdatedAt(string $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    /**
     * Chuyển Entity thành mảng (dùng để insert/update DB)
     *
     * @return array
     */
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
