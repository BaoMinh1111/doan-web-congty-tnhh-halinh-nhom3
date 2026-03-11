<?php

/**
 * Class UserEntity
 *
 * Đại diện cho một người dùng trong hệ thống.
 * Là lớp Entity thuần túy: chỉ chứa dữ liệu và các method tiện ích.
 * Không phụ thuộc vào bất kỳ lớp nào khác trong hệ thống.
 *
 * @package App\Entities
 * @author  Ha Linh Technology Solutions
 */
class UserEntity
{
    // THUỘC TÍNH

    /**
     * @var int
     */
    private int $id;

    /**
     * @var string
     */
    private string $username;

    /**
     * Mật khẩu đã hash bằng bcrypt. KHÔNG lưu plain-text.
     *
     * @var string
     */
    private string $passwordHash;

    /**
     * Vai trò: 'admin' hoặc 'user'.
     *
     * @var string
     */
    private string $role;

    /**
     * @var string
     */
    private string $email;


    // CONSTRUCTOR

    /**
     * Khởi tạo UserEntity từ mảng dữ liệu (thường là từ kết quả PDO fetch).
     *
     * Cách dùng:
     *   $user = new UserEntity($row);     // từ DB
     *   $user = new UserEntity($_POST);   // từ form
     *
     * @param array $data Mảng dữ liệu với các key: id, username, password_hash, role, email.
     */
    public function __construct(array $data)
    {
        $this->id           = isset($data['id'])            ? (int)    $data['id']                 : 0;
        $this->username     = isset($data['username'])      ? (string) trim($data['username'])     : '';
        $this->passwordHash = isset($data['password_hash']) ? (string) $data['password_hash']      : '';
        $this->role         = isset($data['role'])          ? (string) trim($data['role'])         : 'user';
        $this->email        = isset($data['email'])         ? (string) trim($data['email'])        : '';
    }


    // GETTERS

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @return string
     */
    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    /**
     * @return string
     */
    public function getRole(): string
    {
        return $this->role;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }


    // PASSWORD

    /**
     * Hash và gán mật khẩu mới. Dùng bcrypt cost-12.
     *
     * @param  string $plainPassword Mật khẩu plain-text (tối thiểu 6 ký tự).
     * @return void
     * @throws InvalidArgumentException Nếu mật khẩu quá ngắn.
     */
    public function setPassword(string $plainPassword): void
    {
        if (mb_strlen($plainPassword) < 6) {
            throw new InvalidArgumentException('Mật khẩu phải có ít nhất 6 ký tự.');
        }
        $this->passwordHash = password_hash($plainPassword, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    /**
     * Kiểm tra mật khẩu plain-text so với hash hiện tại.
     *
     * @param  string $plainPassword
     * @return bool
     */
    public function verifyPassword(string $plainPassword): bool
    {
        return password_verify($plainPassword, $this->passwordHash);
    }


    // ROLE HELPERS

    /**
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }


    // VALIDATE

    /**
     * Kiểm tra tính hợp lệ của dữ liệu người dùng.
     *
     * Quy tắc:
     *   - username: không được rỗng, không vượt quá 50 ký tự.
     *   - email: không được rỗng, đúng định dạng, không vượt quá 100 ký tự.
     *   - role: phải là 'admin' hoặc 'user'.
     *
     * Cách dùng:
     *   $errors = $user->validate();
     *   if (!empty($errors)) { // hiển thị lỗi }
     *
     * @return array Mảng lỗi (rỗng nếu hợp lệ). Key là tên trường, value là thông báo lỗi.
     */
    public function validate(): array
    {
        $errors = [];

        // Validate username
        if (empty($this->username)) {
            $errors['username'] = 'Tên đăng nhập không được để trống.';
        } elseif (mb_strlen($this->username) > 50) {
            $errors['username'] = 'Tên đăng nhập không được vượt quá 50 ký tự.';
        }

        // Validate email
        if (empty($this->email)) {
            $errors['email'] = 'Email không được để trống.';
        } elseif (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email không hợp lệ.';
        } elseif (mb_strlen($this->email) > 100) {
            $errors['email'] = 'Email không được vượt quá 100 ký tự.';
        }

        // Validate password_hash (bắt buộc phải có trước khi lưu vào CSDL)
        if (empty($this->passwordHash)) {
            $errors['password_hash'] = 'Mật khẩu chưa được thiết lập. Vui lòng gọi setPassword() trước khi lưu.';
        }

        // Validate role
        if (!in_array($this->role, ['admin', 'user'], true)) {
            $errors['role'] = 'Role phải là "admin" hoặc "user".';
        }

        return $errors;
    }


    // SERIALIZE

    /**
     * Chuyển entity thành mảng để truyền vào BaseModel::insert() / update().
     * Không bao gồm id (tránh ghi đè PK khi insert).
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'username'      => $this->username,
            'password_hash' => $this->passwordHash,
            'role'          => $this->role,
            'email'         => $this->email,
        ];
    }

    /**
     * Chuyển entity thành mảng an toàn (không có password_hash) cho View / API.
     *
     * @return array
     */
    public function toPublicArray(): array
    {
        return [
            'id'       => $this->id,
            'username' => $this->username,
            'role'     => $this->role,
            'email'    => $this->email,
        ];
    }

    /**
     * Chuyển entity thành chuỗi JSON (public, không có password_hash).
     * Dùng cho AJAX response.
     *
     * @return string
     */
    public function toJson(): string
    {
        return json_encode($this->toPublicArray(), JSON_UNESCAPED_UNICODE);
    }
}

/* Các vấn đề cần sửa:
* toJson() thiếu check lỗi encode
* validate() thiếu độ dài tối thiểu cho username: ít nhất 3 kí tự, dài nhất 50 kí 
* Nên validate username chỉ chứa ký tự hợp lệ
*/
