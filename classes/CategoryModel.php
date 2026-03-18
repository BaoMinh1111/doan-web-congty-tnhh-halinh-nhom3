<?php

/**
 * Class CategoryModel
 *
 * Xử lý toàn bộ logic truy vấn liên quan đến bảng categories.
 * Kế thừa BaseModel để tái sử dụng kết nối PDO và các phương thức CRUD chung.
 *
 * Bảng tương ứng: categories
 * Quan hệ: One-to-Many với bảng products (một danh mục có nhiều sản phẩm)
 *
 * @package App\Models
 * @author  Ha Linh Technology Solutions
 */
class CategoryModel extends BaseModel
{
    // THUỘC TÍNH

    /**
     * Tên bảng trong CSDL.
     * Override từ BaseModel.
     *
     * @var string
     */
    protected string $table = 'categories';


    // PHƯƠNG THỨC CHÍNH

    /**
     * Lấy tất cả danh mục, sắp xếp mới nhất trước.
     * Override từ BaseModel để trả về mảng CategoryEntity thay vì mảng thô.
     *
     * @return CategoryEntity[]
     */
    public function getAll(): array
    {
        $rows = $this->fetchAll(
            "SELECT * FROM {$this->table} ORDER BY id DESC"
        );

        return array_map(fn($row) => new CategoryEntity($row), $rows);
    }

    /**
     * Lấy một danh mục theo ID.
     * Override từ BaseModel để trả về CategoryEntity thay vì mảng thô.
     *
     * @param  int                $id
     * @return CategoryEntity|null    Entity nếu tìm thấy, null nếu không tồn tại.
     */
    public function getById(int $id): ?CategoryEntity
    {
        $row = $this->fetchOne(
            "SELECT * FROM {$this->table} WHERE id = ?",
            [$id]
        );

        return $row ? new CategoryEntity($row) : null;
    }

    /**
     * Tìm kiếm danh mục theo tên (tìm kiếm gần đúng – LIKE).
     * Hữu ích cho chức năng tìm kiếm nhanh ở trang admin.
     *
     * Cách dùng:
     *   $results = $categoryModel->search('RAM');
     *   // → trả về tất cả danh mục có tên chứa "RAM"
     *
     * @param  string         $keyword Từ khoá tìm kiếm.
     * @return CategoryEntity[]        Mảng kết quả (rỗng nếu keyword rỗng hoặc không tìm thấy).
     */
    public function search(string $keyword): array
    {
        // Trim trước, guard chuỗi rỗng sau khi trim
        // → tránh trường hợp keyword = "   " trả về toàn bộ bảng
        $keyword = trim($keyword);

        if ($keyword === '') {
            return [];
        }

        $rows = $this->fetchAll(
            "SELECT * FROM {$this->table} WHERE name LIKE ? ORDER BY id DESC",
            ['%' . $keyword . '%']
        );

        return array_map(fn($row) => new CategoryEntity($row), $rows);
    }

    /**
     * Thêm một danh mục mới vào bảng categories.
     * Validate dữ liệu trước khi INSERT.
     *
     * Cách dùng:
     *   $newId = $categoryModel->add(['name' => 'CPU', 'description' => 'Bộ vi xử lý']);
     *
     * @param  array $data Mảng ['name' => ..., 'description' => ...].
     * @return int         ID của danh mục vừa thêm.
     * @throws InvalidArgumentException Nếu dữ liệu không hợp lệ.
     */
    public function add(array $data): int
    {
        // Validate trước khi INSERT
        $entity = new CategoryEntity($data);
        $errors = $entity->validate();

        if (!empty($errors)) {
            throw new InvalidArgumentException(
                'Dữ liệu danh mục không hợp lệ: ' . implode(', ', $errors)
            );
        }

        return $this->insert([
            'name'        => $entity->getName(),
            'description' => $entity->getDescription(),
        ]);
    }

    /**
     * Cập nhật thông tin một danh mục theo ID.
     * Validate dữ liệu trước khi UPDATE.
     *
     * Cách dùng:
     *   $ok = $categoryModel->update(3, ['name' => 'VGA', 'description' => 'Card màn hình']);
     *
     * @param  int   $id
     * @param  array $data Mảng ['name' => ..., 'description' => ...].
     * @return bool        true nếu cập nhật thành công (có ít nhất 1 dòng bị ảnh hưởng).
     * @throws InvalidArgumentException Nếu dữ liệu không hợp lệ.
     */
    public function update(int $id, array $data): bool
    {
        // Validate trước khi UPDATE
        $entity = new CategoryEntity($data);
        $errors = $entity->validate();

        if (!empty($errors)) {
            throw new InvalidArgumentException(
                'Dữ liệu danh mục không hợp lệ: ' . implode(', ', $errors)
            );
        }

        return parent::update($id, [
            'name'        => $entity->getName(),
            'description' => $entity->getDescription(),
        ]);
    }

}
