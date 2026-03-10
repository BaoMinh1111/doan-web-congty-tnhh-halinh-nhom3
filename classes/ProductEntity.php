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

    public function __construct(array $data)
    {
        $this->id = $data['id'] ?? 0;
        $this->name = $data['name'] ?? '';
        $this->price = $data['price'] ?? 0;
        $this->description = $data['description'] ?? '';
        $this->image = $data['image'] ?? '';
        $this->categoryId = $data['category_id'] ?? 0;
        $this->stock = $data['stock'] ?? null;
    }

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

    public function getCategoryId(): int
    {
        return $this->categoryId;
    }

    public function validate(): array
    {
        $errors = [];

        if (empty($this->name)) {
            $errors[] = "Tên sản phẩm không được rỗng";
        }

        if ($this->price <= 0) {
            $errors[] = "Giá phải lớn hơn 0";
        }

        if ($this->categoryId <= 0) {
            $errors[] = "Danh mục không hợp lệ";
        }

        return $errors;
    }

    public function toArray(): array
    {
        return [
            "id" => $this->id,
            "name" => $this->name,
            "price" => $this->price,
            "description" => $this->description,
            "image" => $this->image,
            "category_id" => $this->categoryId,
            "stock" => $this->stock
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->toArray());
    }
}
