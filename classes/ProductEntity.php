<?php

class ProductEntity
{
    private ?int $id;
    private string $name;
    private float $price;
    private string $description;
    private string $image;
    private int $categoryId;
    private ?int $stock;
    private string $createdAt;


    // ================= CONSTRUCTOR =================

    public function __construct(array $data = [])
    {
        $this->id          = $data['id'] ?? null;
        $this->name        = trim((string)($data['name'] ?? ''));
        $this->price       = (float)($data['price'] ?? 0);
        $this->description = trim((string)($data['description'] ?? ''));
        $this->image       = trim((string)($data['image'] ?? ''));
        $this->categoryId  = (int)($data['category_id'] ?? 0);
        $this->stock       = isset($data['stock']) ? (int)$data['stock'] : null;
        $this->createdAt   = $data['created_at'] ?? date('Y-m-d H:i:s');
    }


    // ================= GETTER =================

    public function getId(): ?int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getPrice(): float { return $this->price; }
    public function getDescription(): string { return $this->description; }
    public function getImage(): string { return $this->image; }
    public function getCategoryId(): int { return $this->categoryId; }
    public function getStock(): ?int { return $this->stock; }
    public function getCreatedAt(): string { return $this->createdAt; }


    // ================= SETTER =================

    public function setName(string $name): void
    {
        if (strlen(trim($name)) < 3) {
            throw new InvalidArgumentException("Tên sản phẩm không hợp lệ");
        }
        $this->name = trim($name);
    }

    public function setPrice(float $price): void
    {
        if ($price <= 0) {
            throw new InvalidArgumentException("Giá phải > 0");
        }
        $this->price = $price;
    }

    public function setStock(?int $stock): void
    {
        if ($stock !== null && $stock < 0) {
            throw new InvalidArgumentException("Stock không hợp lệ");
        }
        $this->stock = $stock;
    }


    // ================= VALIDATE =================

    public function validate(): array
    {
        $errors = [];

        if (strlen($this->name) < 3) {
            $errors[] = "Tên sản phẩm phải >= 3 ký tự";
        }

        if (strlen($this->name) > 255) {
            $errors[] = "Tên sản phẩm không được quá 255 ký tự";
        }

        if ($this->price <= 0) {
            $errors[] = "Giá phải > 0";
        }

        if ($this->categoryId <= 0) {
            $errors[] = "Danh mục không hợp lệ";
        }

        if ($this->stock !== null && $this->stock < 0) {
            $errors[] = "Stock không hợp lệ";
        }

        if ($this->image === '') {
            $errors[] = "Ảnh không được rỗng";
        }

        if (strtotime($this->createdAt) === false) {
            $errors[] = "created_at không hợp lệ";
        }

        return $errors;
    }


    // ================= EXPORT =================

    public function toArray(): array
    {
        return [
            "id"          => $this->id,
            "name"        => $this->name,
            "price"       => $this->price,
            "description" => $this->description,
            "image"       => $this->image,
            "category_id" => $this->categoryId,
            "stock"       => $this->stock,
            "created_at"  => $this->createdAt
        ];
    }

    public function toJson(): string
    {
        $json = json_encode($this->toArray(), JSON_UNESCAPED_UNICODE);

        if ($json === false) {
            throw new RuntimeException("JSON encode failed: " . json_last_error_msg());
        }

        return $json;
    }
}
