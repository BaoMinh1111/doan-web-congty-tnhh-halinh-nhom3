<?php

require_once __DIR__ . '/AdminEntity.php';

/**
 * Class AdminModel
 *
 * Xử lý toàn bộ logic truy vấn liên quan đến tài khoản Admin.
 * Kế thừa BaseModel → tái sử dụng $db, fetchAll(), fetchOne(), insert(), update().
 *
 * Trách nhiệm:
 *   - Xác thực đăng nhập (login).
 *   - Quản lý thông tin admin.
 *   - Quản lý sản phẩm, đơn hàng, khách hàng (delegate query cho các bảng liên quan).
 *
 * Bảng chính: admins
 *
 * @package App\Models
 * @author  Ha Linh Technology Solutions
 */
class AdminModel extends BaseModel
{
    protected string $table = 'admins';


    // XÁC THỰC ĐĂNG NHẬP

    /**
     * Xác thực đăng nhập admin.
     *
     * Quy trình:
     *   1. Tìm admin theo username trong DB.
     *   2. Dùng AdminEntity::verifyPassword() để so sánh password với hash.
     *   3. Trả về AdminEntity nếu hợp lệ, null nếu sai.
     *
     * Cách dùng:
     *   $admin = $adminModel->login('admin01', 'matkhau123');
     *   if ($admin !== null) { // đăng nhập thành công }
     *
     * @param  string           $username
     * @param  string           $plainPassword Mật khẩu thô từ form đăng nhập.
     * @return AdminEntity|null               null nếu sai username hoặc password.
     */
    public function login(string $username, string $plainPassword): ?AdminEntity
    {
        $username = trim($username);

        if ($username === '' || $plainPassword === '') {
            return null;
        }

        $row = $this->fetchOne(
            "SELECT * FROM {$this->table} WHERE username = ?",
            [$username]
        );

        if ($row === null) {
            return null; // Không tìm thấy username
        }

        $admin = new AdminEntity($row);

        // Dùng verifyPassword() của Entity — không tự so sánh hash ở đây
        return $admin->verifyPassword($plainPassword) ? $admin : null;
    }

    /**
     * Lấy admin theo ID.
     * Dùng để lấy lại thông tin admin từ session sau khi đăng nhập.
     *
     * @param  int              $id
     * @return AdminEntity|null
     */
    public function getById(int $id): ?AdminEntity
    {
        $row = $this->fetchOne(
            "SELECT * FROM {$this->table} WHERE id = ?",
            [$id]
        );

        return $row ? new AdminEntity($row) : null;
    }

    /**
     * Lấy admin theo username.
     * Dùng để kiểm tra username đã tồn tại chưa trước khi tạo mới.
     *
     * @param  string           $username
     * @return AdminEntity|null
     */
    public function getByUsername(string $username): ?AdminEntity
    {
        $username = trim($username);

        if ($username === '') {
            return null;
        }

        $row = $this->fetchOne(
            "SELECT * FROM {$this->table} WHERE username = ?",
            [$username]
        );

        return $row ? new AdminEntity($row) : null;
    }


    // QUẢN LÝ SẢN PHẨM (delegate sang bảng products)

    /**
     * Lấy tất cả sản phẩm kèm tên danh mục.
     * Dùng cho trang quản lý sản phẩm của Admin.
     *
     * @return array Mảng dữ liệu thô (chưa wrap Entity vì ProductEntity chưa được inject).
     */
    public function getAllProducts(): array
    {
        return $this->fetchAll(
            "SELECT p.*, c.name AS category_name
             FROM products p
             LEFT JOIN categories c ON p.category_id = c.id
             ORDER BY p.id DESC"
        );
    }

    /**
     * Xóa sản phẩm theo ID.
     * Dùng cho chức năng xóa sản phẩm ở trang admin.
     *
     * @param  int  $productId
     * @return bool
     */
    public function deleteProduct(int $productId): bool
    {
        $stmt = $this->prepareStmt(
            "DELETE FROM products WHERE id = ?",
            [$productId]
        );

        return $stmt->rowCount() > 0;
    }


    // QUẢN LÝ ĐƠN HÀNG (delegate sang bảng orders)

    /**
     * Lấy tất cả đơn hàng kèm thông tin khách hàng, mới nhất trước.
     * Dùng cho trang quản lý đơn hàng của Admin.
     *
     * @return array
     */
    public function getAllOrders(): array
    {
        return $this->fetchAll(
            "SELECT o.*, c.name AS customer_name, c.email AS customer_email, c.phone AS customer_phone
             FROM orders o
             LEFT JOIN customers c ON o.customer_id = c.id
             ORDER BY o.created_at DESC"
        );
    }

    /**
     * Cập nhật trạng thái đơn hàng.
     * Các trạng thái hợp lệ: pending → confirmed → shipped → completed | cancelled.
     *
     * @param  int    $orderId
     * @param  string $status  Trạng thái mới.
     * @return bool
     * @throws InvalidArgumentException Nếu status không hợp lệ.
     */
    public function updateOrderStatus(int $orderId, string $status): bool
    {
        $validStatuses = ['pending', 'confirmed', 'shipped', 'completed', 'cancelled'];

        if (!in_array($status, $validStatuses, true)) {
            throw new InvalidArgumentException(
                "Trạng thái đơn hàng không hợp lệ: '$status'. "
                . 'Chỉ chấp nhận: ' . implode(', ', $validStatuses) . '.'
            );
        }

        $stmt = $this->prepareStmt(
            "UPDATE orders SET status = ? WHERE id = ?",
            [$status, $orderId]
        );

        return $stmt->rowCount() > 0;
    }


    // QUẢN LÝ KHÁCH HÀNG (delegate sang bảng customers)

    /**
     * Lấy tất cả khách hàng, mới nhất trước.
     * Dùng cho trang quản lý customer của Admin.
     *
     * @return array
     */
    public function getAllCustomers(): array
    {
        return $this->fetchAll(
            "SELECT * FROM customers ORDER BY id DESC"
        );
    }

    /**
     * Xóa khách hàng theo ID.
     *
     * Lưu ý: Nên kiểm tra customer có đơn hàng liên kết không trước khi xóa.
     * Việc kiểm tra này nên thực hiện ở tầng Service.
     *
     * @param  int  $customerId
     * @return bool
     */
    public function deleteCustomer(int $customerId): bool
    {
        $stmt = $this->prepareStmt(
            "DELETE FROM customers WHERE id = ?",
            [$customerId]
        );

        return $stmt->rowCount() > 0;
    }
}
