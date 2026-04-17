<?php
require_once 'BaseModel.php';

class ProductModel extends BaseModel {
    // Override tên bảng từ lớp cha [cite: 157]
    protected $table = 'products';

    /**
     * Lấy toàn bộ sản phẩm kèm theo tên danh mục (JOIN) [cite: 159, 166]
     * Mục đích: Hiển thị trên trang chủ hoặc danh sách Admin [cite: 167]
     */
    public function getAll() {
        $sql = "SELECT p.*, c.name as category_name 
                FROM {$this->table} p 
                JOIN categories c ON p.category_id = c.id 
                ORDER BY p.id DESC";
        return $this->fetchAll($sql);
    }

    /**
     * Lấy chi tiết một sản phẩm theo ID [cite: 160]
     */
    public function getById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id";
        return $this->fetchOne($sql, [':id' => $id]);
    }

    /**
     * Tìm kiếm sản phẩm theo từ khóa (Dành cho chức năng AJAX Search) [cite: 161]
     * @param string $keyword: Tên sản phẩm hoặc mô tả [cite: 161]
     */
    public function search($keyword) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE name LIKE :keyword OR description LIKE :keyword";
        $params = [':keyword' => '%' . $keyword . '%'];
        return $this->fetchAll($sql, $params);
    }

    /**
     * Thêm sản phẩm mới (Dành cho trang Admin) [cite: 163]
     * Sử dụng mảng dữ liệu để linh hoạt khi form thay đổi [cite: 163]
     */
    public function add($data) {
        $sql = "INSERT INTO {$this->table} (name, price, description, image, category_id, stock) 
                VALUES (:name, :price, :description, :image, :category_id, :stock)";
        return $this->prepareStmt($sql, [
            ':name'        => $data['name'],
            ':price'       => $data['price'],
            ':description' => $data['description'],
            ':image'       => $data['image'],
            ':category_id' => $data['category_id'],
            ':stock'       => $data['stock'] ?? 0
        ]);
    }

    /**
     * Cập nhật thông tin sản phẩm [cite: 164]
     */
    public function update($data) {
        $sql = "UPDATE products SET name = :name, price = :price, category_id = :category_id, 
        description = :description, image = :image, specifications = :specifications 
        WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':name'           => $data['name'],
            ':price'          => $data['price'],
            ':category_id'    => $data['category_id'],
            ':description'    => $data['description'],
            ':image'          => $data['image'],
            ':specifications' => $data['specifications'] ?? null,
            ':id'             => $data['id']
        ]);
    }

    public function updateSpecifications($product_id, $specs) {
        // Lọc bỏ các ô bỏ trống, rồi encode JSON
        $specs_json = json_encode(
            array_filter($specs, fn($v) => trim($v) !== ''),
            JSON_UNESCAPED_UNICODE
        );
        $sql  = "UPDATE products SET specifications = :specs WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':specs' => $specs_json,
            ':id'    => $product_id
        ]);
    }
    public function searchByName($keyword) {
    // Chỉ cần tìm theo tên, bỏ điều kiện status đi vì bảng của Vũ không có cột này
    $sql = "SELECT * FROM products WHERE name LIKE ?";
    
    $stmt = $this->db->prepare($sql);
    
    // Thực thi với từ khóa tìm kiếm
    $stmt->execute(["%$keyword%"]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    /**
 * Hàm thêm mới sản phẩm vào Database (Dành cho Admin)
 */
    public function insert($data) {
        $sql = "INSERT INTO products (name, price, category_id, description, image, stock) 
            VALUES (:name, :price, :category_id, :description, :image, :stock)";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':name'        => $data['name'],
            ':price'       => $data['price'],
            ':category_id' => $data['category_id'],
            ':description' => $data['description'],
            ':image'       => $data['image'],
            ':stock'       => $data['stock'] ?? 0   // ← THÊM DÒNG NÀY
        ]);
    }

/**
 * Hàm xóa sản phẩm (Dành cho Admin)
 */
    public function delete($id) {
    $sql = "DELETE FROM products WHERE id = ?";
    $stmt = $this->db->prepare($sql);
    return $stmt->execute([$id]);
}
public function getByCategoryId($cat_id) {
    // Sửa lại SQL: Bỏ lọc stock > 0 để sản phẩm mới thêm vẫn hiện lên
    // Thêm ORDER BY id DESC để sản phẩm mới nhất nằm trên cùng
    $sql = "SELECT * FROM {$this->table} WHERE category_id = :cat_id ORDER BY id DESC";
    
    // Sử dụng fetchAll của BaseModel để đồng bộ với toàn hệ thống
    return $this->fetchAll($sql, [':cat_id' => $cat_id]);
}

    /**
     * Lấy sản phẩm có phân trang
     */
    public function getWithPagination($limit, $offset) {
        $sql  = "SELECT p.*, c.name as category_name 
             FROM {$this->table} p 
             JOIN categories c ON p.category_id = c.id 
             ORDER BY p.id DESC
             LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit',  (int)$limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Đếm tổng số sản phẩm
     */
    public function countAll() {
        $sql = "SELECT COUNT(*) as total FROM {$this->table}";
        $result = $this->fetchOne($sql);
        return $result['total'];
    }

    public function updateStock($id, $stock) {
        $sql  = "UPDATE products SET stock = :stock WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':stock' => $stock, ':id' => $id]);
    }
}