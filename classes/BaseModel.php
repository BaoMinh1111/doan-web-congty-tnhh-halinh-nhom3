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

    /**
     * Tên cột khoá chính của bảng.
     * Mặc định là 'id' (phù hợp hầu hết bảng có auto-increment).
     * Các lớp con override nếu khoá chính khác tên 'id'.
     *
     * @var string
     */
    protected string $primaryKey = 'id';

    /**
     * Cột và chiều mặc định để sắp xếp khi gọi getAll() và paginate().
     * Các lớp con override nếu muốn sắp xếp theo cột khác.
     *
     * @var string
     */
    protected string $defaultOrder = 'id DESC';


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


    // =========================================================================
    // TRANSACTION
    // =========================================================================

    /**
     * Bắt đầu một transaction.
     *
     * Dùng khi cần thực thi nhiều câu lệnh SQL liên quan nhau,
     * đảm bảo tất cả thành công hoặc tất cả rollback nếu có lỗi.
     *
     * @return void
     * @throws RuntimeException Nếu đã có transaction đang chạy.
     */
    public function beginTransaction(): void
    {
        if ($this->db->inTransaction()) {
            throw new RuntimeException(
                'Đã có transaction đang chạy. Không thể bắt đầu transaction mới.'
            );
        }
        $this->db->beginTransaction();
    }

    /**
     * Xác nhận (commit) transaction — lưu toàn bộ thay đổi vào CSDL.
     *
     * @return void
     * @throws RuntimeException Nếu không có transaction nào đang chạy.
     */
    public function commit(): void
    {
        if (!$this->db->inTransaction()) {
            throw new RuntimeException(
                'Không có transaction nào đang chạy để commit.'
            );
        }
        $this->db->commit();
    }

    /**
     * Huỷ (rollback) transaction — hoàn tác toàn bộ thay đổi chưa commit.
     *
     * Nên gọi trong catch block để đảm bảo CSDL không ở trạng thái dở dang.
     * Không throw exception nếu không có transaction đang chạy,
     * tránh che mất exception gốc khi rollback() được gọi trong catch block.
     *
     * @return void
     */
    public function rollback(): void
    {
        if ($this->db->inTransaction()) {
            $this->db->rollBack();
        }
    }

    /**
     * Thực thi một callback bên trong transaction.
     * Tự động commit nếu thành công, rollback nếu có exception.
     *
     * Đây là cách dùng transaction ngắn gọn và an toàn nhất,
     * thay thế cho việc gọi beginTransaction/commit/rollback thủ công.
     *
     * @param  callable $callback Hàm chứa các thao tác DB cần bọc trong transaction.
     * @return mixed              Giá trị trả về từ $callback (nếu có).
     * @throws Throwable          Ném lại exception nếu $callback thất bại.
     */
    public function transaction(callable $callback): mixed
    {
        $this->beginTransaction();
        try {
            $result = $callback();
            $this->commit();
            return $result;
        } catch (Throwable $e) {
            $this->rollback();
            throw $e;
        }
    }


    // =========================================================================
    // TRUY VẤN CƠ BẢN
    // =========================================================================

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
            throw new RuntimeException('Lỗi truy vấn SQL: ' . $e->getMessage());
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


    // =========================================================================
    // FETCH DỮ LIỆU
    // =========================================================================

    /**
     * Trả về tất cả dòng kết quả dưới dạng mảng kết hợp (associative array).
     *
     * Dùng PDO::FETCH_ASSOC để chỉ lấy key là tên cột,
     * tránh dữ liệu bị lặp như FETCH_BOTH mặc định.
     * Kết quả sạch hơn khi json_encode() cho AJAX.
     *
     * @param  string $sql    Câu lệnh SQL.
     * @param  array  $params Tham số cho prepared statement.
     * @return array          Mảng kết quả (rỗng nếu không có dòng nào).
     */
    protected function fetchAll(string $sql, array $params = []): array
    {
        return $this->prepareStmt($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Trả về đúng 1 dòng kết quả dưới dạng mảng kết hợp (associative array).
     *
     * @param  string     $sql    Câu lệnh SQL.
     * @param  array      $params Tham số cho prepared statement.
     * @return array|null         Mảng dữ liệu 1 dòng, hoặc null nếu không tìm thấy.
     */
    protected function fetchOne(string $sql, array $params = []): ?array
    {
        $result = $this->prepareStmt($sql, $params)->fetch(PDO::FETCH_ASSOC);
        return $result !== false ? $result : null;
    }


    // =========================================================================
    // CRUD CHUNG (các Model con có thể override nếu cần logic riêng)
    // =========================================================================

    /**
     * Lấy tất cả bản ghi trong bảng.
     *
     * Sắp xếp theo $defaultOrder đã khai báo ở lớp con (mặc định 'id DESC').
     * Không hard-code ORDER BY id DESC → tránh lỗi với bảng không có cột id.
     *
     * @return array
     */
    public function getAll(): array
    {
        return $this->fetchAll(
            "SELECT * FROM {$this->table} ORDER BY {$this->defaultOrder}"
        );
    }

    /**
     * Lấy một bản ghi theo khoá chính ($primaryKey).
     *
     * Dùng $this->primaryKey thay vì hard-code 'id'
     * → Model con với khoá chính khác tên 'id' vẫn dùng được.
     *
     * @param  int|string $id Giá trị khoá chính.
     * @return array|null     Dữ liệu bản ghi hoặc null nếu không tồn tại.
     */
    public function getById(int|string $id): ?array
    {
        return $this->fetchOne(
            "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?",
            [$id]
        );
    }

    /**
     * Thêm mới một bản ghi vào bảng.
     * Tự động build câu INSERT từ mảng $data (key = tên cột).
     *
     * Trả về lastInsertId() nếu bảng có auto-increment,
     * hoặc 0 nếu bảng không có (composite key, natural key...).
     * Lớp con tự xử lý giá trị trả về phù hợp với bảng của mình.
     *
     * @param  array $data Mảng [tên_cột => giá_trị].
     * @return int         ID bản ghi vừa thêm (0 nếu bảng không có auto-increment).
     * @throws InvalidArgumentException Nếu $data rỗng.
     */
    public function insert(array $data): int
    {
        if (empty($data)) {
            throw new InvalidArgumentException('Dữ liệu insert không được rỗng.');
        }

        $columns      = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $this->prepareStmt(
            "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})",
            array_values($data)
        );

        // lastInsertId() trả về '0' nếu bảng không có auto-increment
        // → ép int để nhất quán kiểu trả về
        // → Lớp con (vd: OrderDetailModel) nên override insert() nếu cần xử lý riêng
        return (int) $this->db->lastInsertId();
    }

    /**
     * Cập nhật bản ghi theo khoá chính ($primaryKey).
     * Tự động build câu UPDATE từ mảng $data (key = tên cột).
     *
     * Dùng $this->primaryKey thay vì hard-code 'id'
     * → hoạt động đúng với mọi bảng kể cả bảng khoá chính không tên 'id'.
     *
     * @param  int|string $id   Giá trị khoá chính.
     * @param  array      $data Mảng [tên_cột => giá_trị_mới].
     * @return bool             true nếu có ít nhất 1 dòng bị ảnh hưởng.
     * @throws InvalidArgumentException Nếu $data rỗng.
     */
    public function update(int|string $id, array $data): bool
    {
        if (empty($data)) {
            throw new InvalidArgumentException('Dữ liệu update không được rỗng.');
        }

        $setParts = implode(', ', array_map(
            fn($col) => "{$col} = ?",
            array_keys($data)
        ));

        $params   = array_values($data);
        $params[] = $id;

        $stmt = $this->prepareStmt(
            "UPDATE {$this->table} SET {$setParts} WHERE {$this->primaryKey} = ?",
            $params
        );

        return $stmt->rowCount() > 0;
    }

    /**
     * Xoá bản ghi theo khoá chính ($primaryKey).
     *
     * @param  int|string $id Giá trị khoá chính.
     * @return bool           true nếu có ít nhất 1 dòng bị xoá.
     */
    public function delete(int|string $id): bool
    {
        $stmt = $this->prepareStmt(
            "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?",
            [$id]
        );

        return $stmt->rowCount() > 0;
    }

    /**
     * Kiểm tra bản ghi có tồn tại theo khoá chính không.
     *
     * @param  int|string $id Giá trị khoá chính.
     * @return bool
     */
    public function exists(int|string $id): bool
    {
        $row = $this->fetchOne(
            "SELECT COUNT(*) as cnt FROM {$this->table} WHERE {$this->primaryKey} = ?",
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


    // =========================================================================
    // PHÂN TRANG
    // =========================================================================

    /**
     * Lấy danh sách bản ghi theo trang (pagination).
     * Hữu ích cho trang danh sách sản phẩm, quản trị admin.
     *
     * Trả về mảng gồm:
     *   - data        : danh sách bản ghi trang hiện tại
     *   - total       : tổng số bản ghi (dùng để render nút phân trang)
     *   - currentPage : trang hiện tại
     *   - totalPages  : tổng số trang
     *   - limit       : số bản ghi mỗi trang
     *
     * @param  int   $page  Trang hiện tại (bắt đầu từ 1).
     * @param  int   $limit Số bản ghi mỗi trang.
     * @return array        Mảng chứa data + thông tin phân trang.
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
        $data  = $this->fetchAll(
            "SELECT * FROM {$this->table} ORDER BY {$this->defaultOrder} LIMIT {$limit} OFFSET {$offset}"
        );

        // Cache $total vào biến để tránh gọi count() 2 lần (= 2 truy vấn DB)
        $total = $this->count();

        return [
            'data'        => $data,
            'total'       => $total,
            'currentPage' => $page,
            'totalPages'  => (int) ceil($total / $limit),
            'limit'       => $limit,
        ];
    }
}
