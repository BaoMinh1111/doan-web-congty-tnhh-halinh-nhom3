<?php
require_once 'BaseModel.php';

class PostModel extends BaseModel {
    protected $table = 'posts';

    public function getAll() {
        $sql = "SELECT * FROM {$this->table} ORDER BY created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getById(int $id) {
        $sql = "SELECT * FROM posts WHERE id = :id LIMIT 1";
        return $this->fetchOne($sql, [':id' => $id]);
    }

    public function add($data) {
        $sql = "INSERT INTO {$this->table} (title, content, image, author_id) 
                VALUES (:title, :content, :image, :author_id)";
        return $this->prepareStmt($sql, $data);
    }
    /**
     * Thêm bài viết mới vào Database
     */
    public function insert($data) {
    // Bước 1: Kiểm tra xem tiêu đề này đã tồn tại chưa
    $sqlCheck = "SELECT id FROM posts WHERE title = :title LIMIT 1";
    $stmtCheck = $this->db->prepare($sqlCheck);
    $stmtCheck->execute([':title' => $data['title']]);

    // Bước 2: Nếu tìm thấy 1 dòng trùng tên, thoát ra luôn và trả về false
    if ($stmtCheck->rowCount() > 0) {
        return false; 
    }

    // Bước 3: Nếu sạch sẽ, mới tiến hành INSERT
    $sql = "INSERT INTO posts (title, content, image, created_at) 
            VALUES (:title, :content, :image, :created_at)";
    
    return $this->prepareStmt($sql, [
        ':title'      => $data['title'],
        ':content'    => $data['content'],
        ':image'      => $data['image'],
        ':created_at' => $data['created_at']
    ]);
}

    /**
     * Xóa bài viết theo ID
     */
    public function delete($id) {
        $sql = "DELETE FROM posts WHERE id = ?";
        return $this->prepareStmt($sql, [$id])->execute();
    }

    public function update($data) {
        $sql = "UPDATE posts SET title = :title, content = :content, image = :image WHERE id = :id";
        return $this->prepareStmt($sql, [
            ':title'   => $data['title'],
            ':content' => $data['content'],
            ':image'   => $data['image'],
            ':id'      => $data['id'],
        ]);
    }
}