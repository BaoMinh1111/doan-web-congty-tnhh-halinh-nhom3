<?php

class ProductEntity
{
    private int $id;
    private string $name;
    private float $price;
    private string $description;
    private string $image;
    private int $categoryId;
    private ?int $stock;

    public function __construct(array $data = [])
    {
        $this->id = isset($data['id']) ? (int)$data['id'] : 0;
        $this->name = isset($data['name']) ? trim((string)$data['name']) : '';
        $this->price = isset($data['price']) ? (float)$data['price'] : 0;
        $this->description = isset($data['description']) ? trim((string)$data['description']) : '';
        $this->image = isset($data['image']) ? trim((string)$data['image']) : '';
        $this->categoryId = isset($data['category_id']) ? (int)$data['category_id'] : 0;
        $this->stock = isset($data['stock']) ? (int)$data['stock'] : null;
    }

    // ===== GETTERS =====

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getImage(): string
    {
        return $this->image;
    }

    public function getCategoryId(): int
    {
        return $this->categoryId;
    }

    public function getStock(): ?int
    {
        return $this->stock;
    }

    // ===== VALIDATION =====

    public function validate(): array
    {
        $errors = [];

        $nameLength = strlen($this->name);

        if ($nameLength < 3) {
            $errors[] = "Tên sản phẩm phải có ít nhất 3 ký tự";
        }

        if ($nameLength > 255) {
            $errors[] = "Tên sản phẩm không được quá 255 ký tự";
        }

        if ($this->price <= 0) {
            $errors[] = "Giá phải lớn hơn 0";
        }

        if ($this->categoryId <= 0) {
            $errors[] = "Danh mục không hợp lệ";
        }

        if ($this->stock !== null && $this->stock < 0) {
            $errors[] = "Số lượng tồn kho không hợp lệ";
        }

        return $errors;
    }

    // ===== ARRAY EXPORT =====

    public function toArray(): array
    {
        $data = [
            "name" => $this->name,
            "price" => $this->price,
            "description" => $this->description,
            "image" => $this->image,
            "category_id" => $this->categoryId,
            "stock" => $this->stock
        ];

        // Chỉ thêm id khi update
        if ($this->id > 0) {
            $data["id"] = $this->id;
        }

        return $data;
    }

    // ===== JSON EXPORT =====

    public function toJson(): string
    {
        $json = json_encode($this->toArray(), JSON_UNESCAPED_UNICODE);

        if ($json === false) {
            throw new RuntimeException("JSON encode failed: " . json_last_error_msg());
        }

        return $json;
    }
}
