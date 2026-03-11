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
        $this->price = (float)($data['price'] ?? 0);
        $this->description = $data['description'] ?? '';
        $this->image = $data['image'] ?? '';
        $this->categoryId = (int)($data['category_id'] ?? 0);
        $this->stock = $data['stock'] ?? null;
    }

    // ===== GETTER =====

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

    public function getStock(): ?int
    {
        return $this->stock;
    }

    // ===== VALIDATE DATA =====

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

    // ===== CONVERT DATA =====

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


/* Các vấn đề cần sửa: 
 * Thiếu getter cho description và image: ProductModel đã gọi $product->getDescription() và $product->getImage() nhưng Entity không có 2 getter này
 * toJson() không xử lý lỗi encode
 * validate() chưa kiểm tra độ dài name: ít nhất 3 kí tự, dài nhất 255 kí tự
 * Constructor không trim input: lỗi phổ biến khi user nhập có khoảng trắng thừa
 * toArray() nên dùng JSON_UNESCAPED_UNICODE và bỏ id khi insert: Khi dùng toArray() để INSERT vào DB, nếu id = 0 sẽ gây lỗi hoặc insert sai
 * dùng associative array thay cho indexed array
*/
