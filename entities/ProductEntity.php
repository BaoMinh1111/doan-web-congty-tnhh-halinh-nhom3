<?php
class ProductEntity {
    private int $id;
    private string $name;
    private float $price;
    private string $description;
    private string $image;
    private int $categoryId;
    private ?int $stock;
    private ?string $specifications; // THÊM DÒNG NÀY

    public function __construct(array $data) {
        $this->id = $data['id'] ?? 0;
        $this->name = $data['name'] ?? '';
        $this->price = (float)($data['price'] ?? 0);
        $this->description = $data['description'] ?? '';
        $this->image = $data['image'] ?? 'default.jpg';
        $this->categoryId = (int)($data['category_id'] ?? 0);
        $this->stock = isset($data['stock']) ? (int)$data['stock'] : null;
        $this->specifications = $data['specifications'] ?? null; // THÊM DÒNG NÀY
    }

    public function validate(): array {
        $errors = [];
        if (empty($this->name)) $errors[] = "Tên sản phẩm không được để trống.";
        if ($this->price <= 0) $errors[] = "Giá phải lớn hơn 0.";
        if ($this->categoryId <= 0) $errors[] = "Danh mục không hợp lệ.";
        return $errors;
    }

    public function toArray(): array {
        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'price'          => $this->price,
            'description'    => $this->description,
            'image'          => $this->image,
            'category_id'    => $this->categoryId,
            'stock'          => $this->stock,
            'specifications' => $this->specifications, // THÊM DÒNG NÀY
        ];
    }

    // Getters
    public function getId()             { return $this->id; }
    public function getName()           { return $this->name; }
    public function getPrice()          { return $this->price; }
    public function getSpecifications() { return $this->specifications; } // THÊM DÒNG NÀY

    // Decode JSON thành array để dùng trong view
    public function getSpecificationsArray(): array { // THÊM HÀM NÀY
        return json_decode($this->specifications ?? '{}', true) ?? [];
    }
}