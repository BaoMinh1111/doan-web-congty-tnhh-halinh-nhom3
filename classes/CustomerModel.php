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
     * Trả về mảng thô có chủ đích: dữ liệu join từ nhiều bảng (orders + products...)
     * không map 1-1 vào một Entity đơn lẻ nào. Tầng Service hoặc Controller
     * sẽ chịu trách nhiệm wrap thành OrderEntity khi cần.
     *
     * @param  int   $customerId
     * @return array[] Mảng dữ liệu đơn hàng thô, mỗi phần tử là 1 mảng kết hợp.
     * @throws InvalidArgumentException Nếu $customerId không hợp lệ.
     */
    public function getOrdersByCustomerId(int $customerId): array
    {
        if ($customerId <= 0) {
            throw new InvalidArgumentException('Customer ID phải lớn hơn 0.');
        }

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
     *   - Khách vãng lai    : truyền $data không có 'user_id' → lưu NULL vào DB.
     *   - Khách có tài khoản: truyền $data kèm 'user_id' hợp lệ.
     *
     * Xử lý race condition bằng INSERT IGNORE:
     *   - Email chưa tồn tại → INSERT bình thường, trả về lastInsertId.
     *   - Email đã tồn tại (kể cả 2 request đồng thời) → IGNORE, không INSERT trùng,
     *     không ghi đè dữ liệu cũ, sau đó SELECT lấy ID hiện có.
     *   → Toàn bộ là 1 câu SQL atomic, không có race condition, không mất dữ liệu.
     *
     * Khác với ON DUPLICATE KEY UPDATE: INSERT IGNORE không ghi đè bất kỳ trường nào
     * → tên cũ của customer được giữ nguyên hoàn toàn.
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

        // INSERT IGNORE: nếu email đã tồn tại → bỏ qua hoàn toàn, giữ nguyên dữ liệu cũ
        // Không dùng ON DUPLICATE KEY UPDATE vì sẽ ghi đè tên/thông tin cũ không mong muốn
        $this->prepareStmt(
            "INSERT IGNORE INTO {$this->table} (name, email, phone, address, user_id, note)
             VALUES (?, ?, ?, ?, ?, ?)",
            [
                $entity->getName(),
                $entity->getEmail(),
                $entity->getPhone(),
                $entity->getAddress(),
                $entity->getUserId(), // null cho guest
                $entity->getNote(),
            ]
        );

        // lastInsertId() > 0 → vừa INSERT mới thành công
        // lastInsertId() = 0 → email đã tồn tại (IGNORE chạy) → SELECT lấy ID cũ
        $lastId = (int) $this->db->lastInsertId();

        if ($lastId > 0) {
            return $lastId;
        }

        // Email đã tồn tại → lấy ID hiện có, không thay đổi gì
        $existing = $this->getByEmail($entity->getEmail());

        return $existing?->getId()
            ?? throw new RuntimeException(
                'Không thể lấy ID customer sau INSERT IGNORE với email: ' . $entity->getEmail()
            );
    }

    /**
     * Cập nhật thông tin cá nhân của khách hàng.
     * Dùng cho trang "Cập nhật thông tin" phía customer.
     *
     * Kiểm tra email mới có bị trùng với customer KHÁC không trước khi UPDATE.
     * Nếu trùng → ném exception, không cho phép đổi thành email đã có người dùng.
     *
     * @param  int   $id   ID của customer cần cập nhật.
     * @param  array $data Dữ liệu mới.
     * @return bool
     * @throws InvalidArgumentException Nếu dữ liệu không hợp lệ hoặc email đã tồn tại.
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

        // Kiểm tra email mới có thuộc về customer KHÁC không
        // → cho phép giữ nguyên email cũ (same ID), chặn trùng với người khác
        $existingByEmail = $this->getByEmail($entity->getEmail());
        if ($existingByEmail !== null && $existingByEmail->getId() !== $id) {
            throw new InvalidArgumentException(
                'Email "' . $entity->getEmail() . '" đã được sử dụng bởi tài khoản khác.'
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
     * Viết SQL trực tiếp thay vì gọi parent::paginate() để đảm bảo luôn trả về
     * CustomerEntity[] đúng kiểu, không phụ thuộc vào kiểu trả về của BaseModel.
     *
     * Cách dùng:
     *   $customers = $customerModel->paginate(page: 2, limit: 15);
     *
     * @param  int              $page  Trang hiện tại (bắt đầu từ 1).
     * @param  int              $limit Số bản ghi mỗi trang (1–100).
     * @return CustomerEntity[]
     * @throws InvalidArgumentException Nếu $page hoặc $limit không hợp lệ.
     */
    public function paginate(int $page = 1, int $limit = 15): array
    {
        if ($page < 1) {
            throw new InvalidArgumentException('Số trang phải >= 1.');
        }
        if ($limit < 1 || $limit > 100) {
            throw new InvalidArgumentException('Số bản ghi mỗi trang phải từ 1 đến 100.');
        }

        $offset = ($page - 1) * $limit;

        $rows = $this->fetchAll(
            "SELECT * FROM {$this->table} ORDER BY id DESC LIMIT {$limit} OFFSET {$offset}"
        );

        return array_map(fn($row) => new CustomerEntity($row), $rows);
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
