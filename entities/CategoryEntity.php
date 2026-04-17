<?php
class CategoryEntity {
    private int $id;
    private string $name;
    private string $description;

    public function __construct(array $data) { 
        $this->id = (int)($data['id'] ?? 0);
        $this->name = $data['name'] ?? '';
        $this->description = $data['description'] ?? '';
    }

    /**
     * Kiểm tra tính hợp lệ của danh mục
     */
    public function validate(): array {
        $errors = [];
        if (empty($this->name)) {
            $errors[] = "Tên danh mục không được để trống.";
        }
        return $errors;
    }

    /**
     * Trả về mảng để phục vụ JSON (AJAX)
     */
    public function toArray(): array {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description
        ];
    }

    // Getters để truy xuất dữ liệu an toàn [cite: 44]
    public function getId(): int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function getDescription(): string { return $this->description; }
}