<?php

/**
 * Class AdminEntity
 *
 * Đại diện cho một tài khoản Admin trong hệ thống.
 * Admin là người duy nhất có quyền quản lý sản phẩm, đơn hàng và khách hàng.
 *
 * Lưu ý bảo mật:
 *   - Password KHÔNG BAO GIỜ được lưu dưới dạng plaintext.
 *   - toArray() và toJson() KHÔNG trả về passwordHash.
 *   - Việc hash/verify password được thực hiện qua hashPassword() và verifyPassword().
 *
 * @package App\Entities
 * @author  Ha Linh Technology Solutions
 */
class AdminEntity
{
    // THUỘC TÍNH

    /** @var int ID admin (primary key bảng admins) */
    private int $id;

    /** @var string Tên hiển thị của admin */
    private string $name;

    /** @var string Username dùng để đăng nhập */
    private string $username;

    /** @var string Email admin */
    private string $email;

    /**
     * Chuỗi hash của password (bcrypt).
     * KHÔNG bao giờ expose ra ngoài Entity.
     *
     * @var string
     */
    private string $passwordHash;


    // CONSTRUCTOR

    /**
     * Khởi tạo AdminEntity từ mảng dữ liệu.
     *
     * Cách dùng:
     *   $admin = new AdminEntity($rowFromDb);   // từ DB (password_hash đã có)
     *   $admin = new AdminEntity($_POST);        // từ form (password_hash = '')
     *
     * @param array $data Mảng với các key: id, name, username, email, password_hash.
     */
    public function __construct(array $data)
    {
        $this->id           = isset($data['id'])            ? (int)    $data['id']                 : 0;
        $this->name         = isset($data['name'])          ? (string) trim($data['name'])         : '';
        $this->username     = isset($data['username'])      ? (string) trim($data['username'])     : '';
        $this->email        = isset($data['email'])         ? (string) trim($data['email'])        : '';
        $this->passwordHash = isset($data['password_hash']) ? (string) $data['password_hash']      : '';
    }


    // GETTERS

    public function getId(): int           { return $this->id;           }
    public function getName(): string      { return $this->name;         }
    public function getUsername(): string  { return $this->username;     }
    public function getEmail(): string     { return $this->email;        }
    public function getPasswordHash(): string { return $this->passwordHash; }


    // PASSWORD

    /**
     * Hash một plaintext password và lưu vào $passwordHash.
     * Dùng khi tạo admin mới hoặc đổi mật khẩu.
     *
     * Cách dùng:
     *   $admin->hashPassword('matkhau123');
     *   // sau đó $admin->getPasswordHash() trả về chuỗi bcrypt
     *
     * @param string $plainPassword Mật khẩu dạng thô từ form.
     * @return void
     */
    public function hashPassword(string $plainPassword): void
    {
        $this->passwordHash = password_hash($plainPassword, PASSWORD_BCRYPT);
    }

    /**
     * Xác minh plaintext password có khớp với hash đang lưu không.
     * Dùng khi admin đăng nhập.
     *
     * Cách dùng:
     *   if ($admin->verifyPassword($inputPassword)) { // cho đăng nhập }
     *
     * @param string $plainPassword Mật khẩu người dùng nhập vào form.
     * @return bool
     */
    public function verifyPassword(string $plainPassword): bool
    {
        return password_verify($plainPassword, $this->passwordHash);
    }


    // VALIDATE

    /**
     * Kiểm tra tính hợp lệ của dữ liệu admin.
     *
     * Quy tắc:
     *   - name     : bắt buộc, 2–100 ký tự.
     *   - username : bắt buộc, 4–50 ký tự, chỉ chứa chữ/số/gạch dưới.
     *   - email    : bắt buộc, đúng định dạng, tối đa 255 ký tự.
     *
     * Lưu ý: KHÔNG validate passwordHash ở đây vì hash luôn hợp lệ sau khi
     * gọi hashPassword(). Validate password thô nên làm ở Controller trước khi hash.
     *
     * @return array Mảng lỗi (rỗng nếu hợp lệ).
     */
    public function validate(): array
    {
        $errors = [];

        // name
        if (empty($this->name)) {
            $errors['name'] = 'Tên admin không được để trống.';
        } elseif (mb_strlen($this->name) < 2) {
            $errors['name'] = 'Tên admin phải có ít nhất 2 ký tự.';
        } elseif (mb_strlen($this->name) > 100) {
            $errors['name'] = 'Tên admin không được vượt quá 100 ký tự.';
        }

        // username: chỉ cho phép chữ thường, số, gạch dưới
        if (empty($this->username)) {
            $errors['username'] = 'Username không được để trống.';
        } elseif (!preg_match('/^[a-z0-9_]{4,50}$/', $this->username)) {
            $errors['username'] = 'Username phải có 4–50 ký tự, chỉ gồm chữ thường, số và dấu gạch dưới.';
        }

        // email
        if (empty($this->email)) {
            $errors['email'] = 'Email không được để trống.';
        } elseif (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email không đúng định dạng.';
        } elseif (mb_strlen($this->email) > 255) {
            $errors['email'] = 'Email không được vượt quá 255 ký tự.';
        }

        return $errors;
    }


    // SERIALIZE

    /**
     * Chuyển entity thành mảng.
     * KHÔNG bao gồm passwordHash — bảo mật.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id'       => $this->id,
            'name'     => $this->name,
            'username' => $this->username,
            'email'    => $this->email,
            // password_hash bị ẩn có chủ đích
        ];
    }

    /**
     * Chuyển entity thành chuỗi JSON.
     * KHÔNG bao gồm passwordHash — bảo mật.
     *
     * @return string
     * @throws RuntimeException Nếu json_encode thất bại.
     */
    public function toJson(): string
    {
        $json = json_encode($this->toArray(), JSON_UNESCAPED_UNICODE);

        if ($json === false) {
            throw new RuntimeException(
                'Không thể encode AdminEntity sang JSON: ' . json_last_error_msg()
            );
        }

        return $json;
    }
}
