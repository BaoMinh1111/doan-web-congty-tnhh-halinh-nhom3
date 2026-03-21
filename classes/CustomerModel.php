<?php

require_once __DIR__ . '/CustomerEntity.php';

/**
 * Class CustomerModel
 *
 * Xử lý toàn bộ logic truy vấn liên quan đến bảng customers.
 * Kế thừa BaseModel → tái sử dụng $db, fetchAll(), fetchOne(), insert(), update().
 *
 * Hỗ trợ 2 luồng khách hàng:
 *   - Có tài khoản : registerGuest() với user_id hợp lệ.
 *   - Vãng lai     : registerGuest() với user_id = null, chỉ lưu thông tin giao hàng.
 *
 * Bảng tương ứng: customers
 * Quan hệ: One-to-Many với bảng orders (một customer có nhiều đơn hàng).
 *
 * @package App\Models
 * @author  Ha Linh Technology Solutions
 */
class CustomerModel extends BaseModel
{
    protected string $table = 'customers';


    // PHƯƠNG THỨC CHÍNH

    /**
     * Lấy tất cả khách hàng, mới nhất trước.
     * Override BaseModel::getAll() để wrap kết quả thành CustomerEntity thay vì mảng thô.
     *
     * @return CustomerEntity[]
     */
    public function getAll(): array
    {
        return array_map(
            fn($row) => new CustomerEntity($row),
            parent::getAll()
        );
    }

    /**
     * Lấy một khách hàng theo ID.
     * Override BaseModel::getById() để trả về CustomerEntity thay vì mảng thô.
     *
     * @param  int                 $id
     * @return CustomerEntity|null
     */
    public function getById(int $id): ?CustomerEntity
    {
        $row = parent::getById($id);

        return $row ? new CustomerEntity($row) : null;
    }

    /**
     * Tìm khách hàng theo email.
     * Dùng để kiểm tra email đã tồn tại chưa trước khi đăng ký,
     * hoặc để lấy lại thông tin khi khách vãng lai đặt hàng lần 2.
     *
     * @param  string              $email
     * @return CustomerEntity|null        null nếu chưa có.
     */
    public function getByEmail(string $email): ?CustomerEntity
    {
        $email = trim($email);

        if ($email === '') {
            return null;
        }

        $row = $this->fetchOne(
            "SELECT * FROM {$this->table} WHERE email = ?",
            [$email]
        );

        return $row ? new CustomerEntity($row) : null;
    }

    /**
     * Lấy tất cả đơn hàng của một khách hàng theo ID.
     * Dùng cho trang "Đơn hàng của tôi" phía customer.
     *
     * @param  int   $customerId
     * @return array Mảng dữ liệu đơn hàng thô (join với bảng orders).
     */
    public function getOrdersByCustomerId(int $customerId): array
    {
        return $this->fetchAll(
            "SELECT o.*
             FROM orders o
             WHERE o.customer_id = ?
             ORDER BY o.created_at DESC",
            [$customerId]
        );
    }

    /**
     * Lưu thông tin khách hàng khi đặt hàng.
     * Dùng cho CẢ 2 luồng:
     *   - Khách vãng lai    : truyền $data không có 'user_id' → lưu NULL vào DB, tránh FK constraint.
     *   - Khách có tài khoản: truyền $data kèm 'user_id' hợp lệ → lưu FK bình thường.
     *
     * Xử lý race condition bằng INSERT ... ON DUPLICATE KEY UPDATE:
     *   - Nếu email chưa tồn tại → INSERT bình thường, trả về lastInsertId.
     *   - Nếu email đã tồn tại (kể cả 2 request đồng thời) → DB tự UPDATE `name`,
     *     không INSERT trùng, sau đó SELECT lấy ID hiện có.
     *   → Toàn bộ là 1 câu SQL atomic, không có khoảng hở race condition.
     *
     * Yêu cầu: cột `email` trong bảng customers phải có UNIQUE constraint.
     *
     * @param  array $data Mảng thông tin giao hàng từ form checkout.
     * @return int         ID của customer (mới hoặc đã tồn tại).
     * @throws InvalidArgumentException Nếu dữ liệu không hợp lệ.
     * @throws RuntimeException         Nếu không lấy được ID sau khi upsert.
     */
    public function registerGuest(array $data): int
    {
        $entity = new CustomerEntity($data);
        $errors = $entity->validate();

        if (!empty($errors)) {
            throw new InvalidArgumentException(
                'Thông tin khách hàng không hợp lệ: ' . implode(', ', $errors)
            );
        }

        // INSERT ... ON DUPLICATE KEY UPDATE → atomic, không có race condition
        // Khi email trùng: DB update `name` (no-op thực chất), giữ nguyên bản ghi cũ.
        // LAST_INSERT_ID() trả về 0 nếu không có INSERT mới → cần SELECT thêm.
        $this->prepareStmt(
            "INSERT INTO {$this->table} (name, email, phone, address, user_id, note)
             VALUES (?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE name = VALUES(name)",
            [
                $entity->getName(),
                $entity->getEmail(),
                $entity->getPhone(),
                $entity->getAddress(),
                $entity->getUserId(), // null cho guest
                $entity->getNote(),
            ]
        );

        // lastInsertId() > 0 → vừa INSERT mới
        // lastInsertId() = 0 → email đã tồn tại (ON DUPLICATE KEY chạy) → SELECT lấy ID
        $lastId = (int) $this->db->lastInsertId();

        if ($lastId > 0) {
            return $lastId;
        }

        // Email đã tồn tại → lấy ID hiện có
        $existing = $this->getByEmail($entity->getEmail());

        return $existing?->getId()
            ?? throw new RuntimeException(
                'Không thể lấy ID customer sau upsert với email: ' . $entity->getEmail()
            );
    }

    /**
     * Cập nhật thông tin cá nhân của khách hàng.
     * Dùng cho trang "Cập nhật thông tin" phía customer.
     *
     * @param  int   $id
     * @param  array $data
     * @return bool
     * @throws InvalidArgumentException Nếu dữ liệu không hợp lệ.
     */
    public function updateInfo(int $id, array $data): bool
    {
        $entity = new CustomerEntity($data);
        $errors = $entity->validate();

        if (!empty($errors)) {
            throw new InvalidArgumentException(
                'Thông tin cập nhật không hợp lệ: ' . implode(', ', $errors)
            );
        }

        return parent::update($id, [
            'name'    => $entity->getName(),
            'email'   => $entity->getEmail(),
            'phone'   => $entity->getPhone(),
            'address' => $entity->getAddress(),
            'note'    => $entity->getNote(),
        ]);
    }

    /**
     * Lấy danh sách khách hàng theo trang — dùng cho trang admin quản lý customer.
     * Override BaseModel::paginate() để wrap kết quả thành CustomerEntity[].
     *
     * Cách dùng:
     *   $customers = $customerModel->paginate(page: 2, limit: 15);
     *   // → trả về CustomerEntity[] của trang 2, mỗi trang 15 bản ghi
     *
     * @param  int              $page  Trang hiện tại (bắt đầu từ 1).
     * @param  int              $limit Số bản ghi mỗi trang (1–100).
     * @return CustomerEntity[]
     * @throws InvalidArgumentException Nếu $page hoặc $limit không hợp lệ (từ BaseModel).
     */
    public function paginate(int $page = 1, int $limit = 15): array
    {
        return array_map(
            fn($row) => new CustomerEntity($row),
            parent::paginate($page, $limit)
        );
    }

    /**
     * Tìm kiếm khách hàng theo tên hoặc email (LIKE, gần đúng).
     * Dùng cho trang quản lý customer của Admin.
     *
     * @param  string           $keyword
     * @return CustomerEntity[]          Mảng rỗng nếu keyword rỗng hoặc không tìm thấy.
     */
    public function search(string $keyword): array
    {
        $keyword = trim($keyword);

        if ($keyword === '') {
            return [];
        }

        $rows = $this->fetchAll(
            "SELECT * FROM {$this->table}
             WHERE name LIKE ? OR email LIKE ?
             ORDER BY id DESC",
            ['%' . $keyword . '%', '%' . $keyword . '%']
        );

        return array_map(fn($row) => new CustomerEntity($row), $rows);
    }
}

/* Các vấn đề cần sửa:
* paginate() override trả CustomerEntity[] nhưng gọi parent::paginate() trả về mảng có key 'data', 'total', 'totalPages'... — wrap sai kiểu
* getOrdersByCustomerId() trả mảng thô trong khi các method khác trả Entity
* updateInfo() có thể đổi email thành email đã tồn tại của customer khác: ktra có trùng email 
* registerGuest() cập nhật name khi email trùng — có thể ghi đè tên cũ không mong muốn
*/
