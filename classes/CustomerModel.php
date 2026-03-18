<?php

require_once __DIR__ . '/CustomerEntity.php';

/**
 * Class CustomerModel
 *
 * Xử lý toàn bộ logic truy vấn liên quan đến bảng customers.
 * Kế thừa BaseModel → tái sử dụng $db, fetchAll(), fetchOne(), insert(), update().
 *
 * Hỗ trợ 2 luồng khách hàng:
 *   - Có tài khoản : registerGuest() + liên kết userId sau khi đăng ký.
 *   - Vãng lai     : registerGuest() với userId = 0, chỉ lưu thông tin giao hàng.
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
     * Dùng cho trang quản lý customer của Admin.
     *
     * @return CustomerEntity[]
     */
    public function getAll(): array
    {
        $rows = $this->fetchAll(
            "SELECT * FROM {$this->table} ORDER BY id DESC"
        );

        return array_map(fn($row) => new CustomerEntity($row), $rows);
    }

    /**
     * Lấy một khách hàng theo ID.
     *
     * @param  int                 $id
     * @return CustomerEntity|null
     */
    public function getById(int $id): ?CustomerEntity
    {
        $row = $this->fetchOne(
            "SELECT * FROM {$this->table} WHERE id = ?",
            [$id]
        );

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
     *   - Khách vãng lai  : truyền $data không có 'user_id' (hoặc user_id = 0).
     *   - Khách có tài khoản: truyền $data kèm 'user_id' hợp lệ.
     *
     * Nếu email đã tồn tại trong bảng → trả về ID cũ, không INSERT trùng.
     *
     * @param  array $data Mảng thông tin giao hàng từ form checkout.
     * @return int         ID của customer (mới hoặc đã tồn tại).
     * @throws InvalidArgumentException Nếu dữ liệu không hợp lệ.
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

        // Nếu email đã tồn tại → trả về ID cũ, tránh INSERT trùng
        $existing = $this->getByEmail($entity->getEmail());
        if ($existing !== null) {
            return $existing->getId();
        }

        return $this->insert([
            'name'    => $entity->getName(),
            'email'   => $entity->getEmail(),
            'phone'   => $entity->getPhone(),
            'address' => $entity->getAddress(),
            'user_id' => $entity->getUserId(), // 0 nếu vãng lai
            'note'    => $entity->getNote(),
        ]);
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
* getAll() và getById() viết lại SQL thừa: BaseModel đã có sẵn getAll() và getById() với SQL y chang
* registerGuest() có race condition: Khoảng thời gian giữa getByEmail() và insert() có thể xảy ra INSERT trùng nếu 2 request đồng thời
* Thiếu paginate() cho trang admin: Trang quản lý customer của admin sẽ cần phân trang. BaseModel đã có sẵn paginate() nhưng trả về mảng thô nên override thêm
*/
