<?php

/**
 * Class BaseModel (Abstract)
 *
 * Lớp cha chung cho tất cả Model trong ứng dụng.
 * Cung cấp kết nối PDO dùng chung và các phương thức CRUD tái sử dụng.
 * Các Model con kế thừa lớp này và chỉ cần tập trung viết logic riêng.
 *
 * @package App\Models
 * @author  Ha Linh Technology Solutions
 */
abstract class BaseModel
{
    // THUỘC TÍNH

    /**
     * Đối tượng PDO dùng chung cho toàn bộ Model.
     * Được khởi tạo qua Database::getInstance()->getConnection().
     *
     * @var PDO
     */
    protected PDO $db;

    /**
     * Tên bảng trong CSDL.
     * Các lớp con PHẢI override thuộc tính này.
     *
     * @var string
     */
    protected string $table = '';


    // CONSTRUCTOR

    /**
     * Khởi tạo BaseModel: lấy kết nối PDO từ Database Singleton
     * và kiểm tra lớp con đã khai báo $table chưa.
     *
     * @throws RuntimeException Nếu lớp con quên khai báo $table.
     */
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();

        if (empty($this->table)) {
            throw new RuntimeException(
                'Model [' . static::class . '] chưa khai báo thuộc tính $table.'
            );
        }
    }


    // TRUY VẤN CƠ BẢN

    /**
     * Thực thi câu lệnh SQL không có tham số (không dùng prepared statement).
     * Chỉ dùng cho các câu lệnh nội bộ, không chứa dữ liệu từ người dùng.
     *
     * @param  string $sql Câu lệnh SQL thuần.
     * @return PDOStatement|false
     * @throws RuntimeException Nếu truy vấn thất bại.
     */
    protected function query(string $sql): PDOStatement|false
    {
        try {
            return $this->db->query($sql);
        } catch (PDOException $e) {
            throw new RuntimeException(
                'Lỗi truy vấn SQL: ' . $e->getMessage()
            );
        }
    }

    /**
     * Thực thi câu lệnh SQL có tham số (prepared statement).
     * Đây là phương thức chính nên dùng để chống SQL Injection.
     *
     * @param  string $sql    Câu lệnh SQL với placeholder (? hoặc :name).
     * @param  array  $params Mảng tham số tương ứng.
     * @return PDOStatement
     * @throws RuntimeException Nếu prepare hoặc execute thất bại.
     */
    protected function prepareStmt(string $sql, array $params = []): PDOStatement
    {
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            throw new RuntimeException(
                'Lỗi prepared statement [' . static::class . ']: ' . $e->getMessage()
            );
        }
    }


    // FETCH DỮ LIỆU

    /**
     * Trả về tất cả dòng kết quả dưới dạng mảng kết hợp.
     *
     * @param  string $sql    Câu lệnh SQL.
     * @param  array  $params Tham số cho prepared statement.
     * @return array          Mảng kết quả (rỗng nếu không có dòng nào).
     */
    protected function fetchAll(string $sql, array $params = []): array
    {
        return $this->prepareStmt($sql, $params)->fetchAll();
    }

    /**
     * Trả về đúng 1 dòng kết quả dưới dạng mảng kết hợp.
     *
     * @param  string     $sql    Câu lệnh SQL.
     * @param  array      $params Tham số cho prepared statement.
     * @return array|null         Mảng dữ liệu 1 dòng, hoặc null nếu không tìm thấy.
     */
    protected function fetchOne(string $sql, array $params = []): ?array
    {
        $result = $this->prepareStmt($sql, $params)->fetch();
        return $result !== false ? $result : null;
    }


    // CRUD CHUNG (các Model con có thể override nếu cần logic riêng)

    /**
     * Lấy tất cả bản ghi trong bảng, sắp xếp mới nhất trước.
     *
     * @return array
     */
    public function getAll(): array
    {
        return $this->fetchAll("SELECT * FROM {$this->table} ORDER BY id DESC");
    }

    /**
     * Lấy một bản ghi theo ID.
     *
     * @param  int        $id
     * @return array|null     Dữ liệu bản ghi hoặc null nếu không tồn tại.
     */
    public function getById(int $id): ?array
    {
        return $this->fetchOne(
            "SELECT * FROM {$this->table} WHERE id = ?",
            [$id]
        );
    }

    /**
     * Thêm mới một bản ghi vào bảng.
     * Tự động build câu INSERT từ mảng $data (key = tên cột).
     *
     * @param  array $data Mảng [tên_cột => giá_trị].
     * @return int         ID của bản ghi vừa thêm.
     * @throws InvalidArgumentException Nếu $data rỗng.
     */
    public function insert(array $data): int
    {
        if (empty($data)) {
            throw new InvalidArgumentException('Dữ liệu insert không được rỗng.');
        }

        $columns    = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $this->prepareStmt(
            "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})",
            array_values($data)
        );

        return (int) $this->db->lastInsertId();
    }

    /**
     * Cập nhật bản ghi theo ID.
     * Tự động build câu UPDATE từ mảng $data (key = tên cột).
     *
     * @param  int   $id
     * @param  array $data Mảng [tên_cột => giá_trị_mới].
     * @return bool        true nếu có ít nhất 1 dòng bị ảnh hưởng.
     * @throws InvalidArgumentException Nếu $data rỗng.
     */
    public function update(int $id, array $data): bool
    {
        if (empty($data)) {
            throw new InvalidArgumentException('Dữ liệu update không được rỗng.');
        }

        $setParts = implode(', ', array_map(
            fn($col) => "{$col} = ?",
            array_keys($data)
        ));

        $params   = array_values($data);
        $params[] = $id; // WHERE id = ?

        $stmt = $this->prepareStmt(
            "UPDATE {$this->table} SET {$setParts} WHERE id = ?",
            $params
        );

        return $stmt->rowCount() > 0;
    }

    /**
     * Xoá bản ghi theo ID.
     *
     * @param  int  $id
     * @return bool     true nếu có ít nhất 1 dòng bị xoá.
     */
    public function delete(int $id): bool
    {
        $stmt = $this->prepareStmt(
            "DELETE FROM {$this->table} WHERE id = ?",
            [$id]
        );

        return $stmt->rowCount() > 0;
    }

    /**
     * Kiểm tra bản ghi có tồn tại theo ID không.
     *
     * @param  int  $id
     * @return bool
     */
    public function exists(int $id): bool
    {
        $row = $this->fetchOne(
            "SELECT COUNT(*) as cnt FROM {$this->table} WHERE id = ?",
            [$id]
        );

        return isset($row['cnt']) && (int) $row['cnt'] > 0;
    }

    /**
     * Đếm tổng số bản ghi trong bảng.
     *
     * @return int
     */
    public function count(): int
    {
        $row = $this->fetchOne("SELECT COUNT(*) as cnt FROM {$this->table}");
        return isset($row['cnt']) ? (int) $row['cnt'] : 0;
    }


    // PHÂN TRANG

    /**
     * Lấy danh sách bản ghi theo trang (pagination).
     * Hữu ích cho trang danh sách sản phẩm, quản trị admin.
     * @param  int   $page  Trang hiện tại (bắt đầu từ 1).
     * @param  int   $limit Số bản ghi mỗi trang.
     * @return array
     * @throws InvalidArgumentException Nếu $page hoặc $limit không hợp lệ.
     */
    public function paginate(int $page = 1, int $limit = 10): array
    {
        if ($page < 1) {
            throw new InvalidArgumentException('Số trang phải >= 1.');
        }
        if ($limit < 1 || $limit > 100) {
            throw new InvalidArgumentException('Số bản ghi mỗi trang phải từ 1 đến 100.');
        }

        $offset = ($page - 1) * $limit;

        // LIMIT và OFFSET không hỗ trợ placeholder (?) trong PDO
        // nên ép kiểu int để đảm bảo an toàn
        return $this->fetchAll(
            "SELECT * FROM {$this->table} ORDER BY id DESC LIMIT {$limit} OFFSET {$offset}"
        );
    }
}