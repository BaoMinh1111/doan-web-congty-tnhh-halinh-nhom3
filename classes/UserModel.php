<?php

/**
 * Class UserModel
 *
 * Model thao tác với bảng `users` trong CSDL.
 * Kế thừa BaseModel để dùng chung PDO connection và các CRUD cơ bản.
 * Trả về UserEntity thay vì mảng thô để đảm bảo type-safe.
 *
 * @package App\Models
 * @author  Ha Linh Technology Solutions
 */
class UserModel extends BaseModel
{
    // THUỘC TÍNH

    /**
     * @var string
     */
    protected string $table = 'users';


    // OVERRIDE CRUD – trả về UserEntity thay vì array thô

    /**
     * Lấy tất cả người dùng, sắp xếp mới nhất trước.
     * Chỉ lấy các cột cần thiết, không lấy password_hash.
     * 
     * @return UserEntity[]
     */
    public function getAll(): array
    {
        $rows = $this->fetchAll(
            "SELECT id, username, role, email FROM {$this->table} ORDER BY id DESC"
        );
        return array_map(fn($row) => new UserEntity($row), $rows);
    }

    /**
     * Lấy một người dùng theo ID.
     *
     * @param  int             $id
     * @return UserEntity|null
     */
    public function getById(int $id): ?UserEntity
    {
        $row = $this->fetchOne(
            "SELECT * FROM {$this->table} WHERE id = ?",
            [$id]
        );
        return $row ? new UserEntity($row) : null;
    }

    /**
     * Thêm người dùng mới từ UserEntity.
     * Validate trước khi ghi vào CSDL.
     * Dùng getter rõ ràng thay vì toArray() để kiểm soát chặt chẽ trường password_hash.
     *
     * @param  UserEntity $user
     * @return int              ID của bản ghi vừa insert.
     * @throws InvalidArgumentException Nếu entity không hợp lệ.
     */
    public function insertEntity(UserEntity $user): int
    {
        $errors = $user->validate();
        if (!empty($errors)) {
            throw new InvalidArgumentException(
                'Dữ liệu UserEntity không hợp lệ: ' . implode(' | ', $errors)
            );
        }

        return $this->insert([
            'username'      =>  $user ->getUsername(),
            'password_hash' =>  $user ->getPassword_hash(),
            'role'          =>  $user ->getRole(),
            'email'         =>  $user ->getEmail(),
        ]);
    }

    /**
     * Cập nhật người dùng theo ID từ UserEntity.
     * Validate trước khi ghi vào CSDL.
     *
     * @param  int        $id
     * @param  UserEntity $user
     * @return bool             true nếu có ít nhất 1 dòng bị ảnh hưởng.
     * @throws InvalidArgumentException Nếu entity không hợp lệ.
     */
    public function updateEntity(int $id, UserEntity $user): bool
    {
        $errors = $user->validate();
        if (!empty($errors)) {
            throw new InvalidArgumentException(
                'Dữ liệu UserEntity không hợp lệ: ' . implode(' | ', $errors)
            );
        }
        return $this->update($id, $user->toArray());
    }


    // TÌM KIẾM

    /**
     * Tìm người dùng theo username (tìm chính xác, dùng cho đăng nhập).
     *
     * @param  string          $username
     * @return UserEntity|null
     */
    public function findByUsername(string $username): ?UserEntity
    {
        $row = $this->fetchOne(
            "SELECT * FROM {$this->table} WHERE username = ? LIMIT 1",
            [$username]
        );
        return $row ? new UserEntity($row) : null;
    }

    /**
     * Tìm người dùng theo email (tìm chính xác).
     *
     * @param  string          $email
     * @return UserEntity|null
     */
    public function findByEmail(string $email): ?UserEntity
    {
        $row = $this->fetchOne(
            "SELECT * FROM {$this->table} WHERE email = ? LIMIT 1",
            [$email]
        );
        return $row ? new UserEntity($row) : null;
    }
    /** 
     * Lấy tất cả người dùng có role 'admin'.
     * Không lấy password_hash.
     *
     * @return UserEntity[]
     */
    public function findAdmins():array
    {
        $row = $this->fetchAll(
            "SELECT id, username, role, email FROM {$this->$table) WHERE role = 'admin'
            ORDER BY id ASC"
        );
        return array_map(fn($row) =>new UserEntity($row), $rows);
    }
    
    /**
     * Kiểm tra username đã tồn tại chưa.
     * Truyền $excludeId khi đang update để tránh conflict với chính mình.
     *
     * @param  string   $username
     * @param  int|null $excludeId
     * @return bool
     */
    public function isUsernameTaken(string $username, ?int $excludeId = null): bool
    {
        if ($excludeId !== null) {
            $row = $this->fetchOne(
                "SELECT COUNT(*) as cnt FROM {$this->table} WHERE username = ? AND id != ?",
                [$username, $excludeId]
            );
        } else {
            $row = $this->fetchOne(
                "SELECT COUNT(*) as cnt FROM {$this->table} WHERE username = ?",
                [$username]
            );
        }
        return isset($row['cnt']) && (int) $row['cnt'] > 0;
    }

    /**
     * Kiểm tra email đã tồn tại chưa.
     * Truyền $excludeId khi đang update để tránh conflict với chính mình.
     *
     * @param  string   $email
     * @param  int|null $excludeId
     * @return bool
     */
    public function isEmailTaken(string $email, ?int $excludeId = null): bool
    {
        if ($excludeId !== null) {
            $row = $this->fetchOne(
                "SELECT COUNT(*) as cnt FROM {$this->table} WHERE email = ? AND id != ?",
                [$email, $excludeId]
            );
        } else {
            $row = $this->fetchOne(
                "SELECT COUNT(*) as cnt FROM {$this->table} WHERE email = ?",
                [$email]
            );
        }
        return isset($row['cnt']) && (int) $row['cnt'] > 0;
    }


    // AUTHENTICATION

    /**
     * Xác thực đăng nhập: tìm user theo username rồi verify mật khẩu.
     *
     * Cách dùng:
     *   $user = $userModel->authenticate('lananh', 'mypassword123');
     *   if ($user) { // đăng nhập thành công }
     *
     * @param  string          $username
     * @param  string          $plainPassword
     * @return UserEntity|null  UserEntity nếu thành công, null nếu thất bại.
     */
    public function authenticate(string $username, string $plainPassword): ?UserEntity
    {
        $user = $this->findByUsername($username);
        if ($user === null || !$user->verifyPassword($plainPassword)) {
            return null;
        }
        return $user;
    }


    // CẬP NHẬT ĐẶC BIỆT

    /**
     * Đổi mật khẩu cho người dùng theo ID.
     *
     * @param  int    $id
     * @param  string $newPlainPassword Mật khẩu mới plain-text (tối thiểu 6 ký tự).
     * @return bool
     * @throws RuntimeException         Nếu không tìm thấy user.
     * @throws InvalidArgumentException Nếu mật khẩu không hợp lệ.
     */
    public function changePassword(int $id, string $newPlainPassword): bool
    {
        $user = $this->getById($id);
        if ($user === null) {
            throw new RuntimeException("Không tìm thấy user với id = {$id}.");
        }

        // setPassword() tự validate độ dài và hash
        $user->setPassword($newPlainPassword);

        $stmt = $this->prepareStmt(
            "UPDATE {$this->table} SET password_hash = ? WHERE id = ?",
            [$user->getPasswordHash(), $id]
        );
        return $stmt->rowCount() > 0;
    }


    // PHÂN TRANG – override để trả về UserEntity[]

    /**
     * Lấy danh sách người dùng theo trang.
     *
     * @param  int         $page  Trang hiện tại (bắt đầu từ 1).
     * @param  int         $limit Số bản ghi mỗi trang (1–100).
     * @return UserEntity[]
     */
    public function paginate(int $page = 1, int $limit = 10): array
    {
        $rows = parent::paginate($page, $limit);
        return array_map(fn($row) => new UserEntity($row), $rows);
    }
}


