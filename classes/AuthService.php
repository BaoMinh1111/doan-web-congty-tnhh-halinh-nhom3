<?php

/**
 * Class AuthService
 *
 * Lớp dịch vụ xử lý xác thực người dùng: đăng nhập, kiểm tra phiên, đăng xuất.
 * Là lớp Service thuần túy: không kế thừa Model hay Controller.
 * Phụ thuộc vào UserModel để truy vấn CSDL và quản lý $_SESSION.
 *
 * Cách dùng điển hình:
 *   $auth = new AuthService(new UserModel());
 *   $auth->login('admin', 'mypassword');
 *   if ($auth->checkSession()) { ... }
 *   $auth->logout();
 *
 * @package App\Services
 * @author  Ha Linh Technology Solutions
 */
class AuthService
{
    // HẰNG SỐ – khoá lưu trong $_SESSION

    /**
     * Key lưu ID người dùng trong session.
     * Khai báo public để SessionHelper có thể đọc mà không tự định nghĩa lại.
     */
    public const SESSION_USER_ID   = 'auth_user_id';

    /**
     * Key lưu username trong session.
     */
    public const SESSION_USERNAME  = 'auth_username';

    /**
     * Key lưu role trong session.
     * Khai báo public để SessionHelper có thể đọc mà không tự định nghĩa lại.
     */
    public const SESSION_ROLE      = 'auth_role';

    /**
     * Key lưu thời điểm đăng nhập (Unix timestamp).
     * Khai báo public để SessionHelper dùng kiểm tra hết hạn.
     */
    public const SESSION_LOGGED_AT = 'auth_logged_at';

    /**
     * Thời gian tồn tại tối đa của session (giây).
     * Khai báo public để SessionHelper dùng kiểm tra hết hạn.
     * Mặc định: 2 giờ.
     */
    public const SESSION_LIFETIME  = 7200;


    // THUỘC TÍNH

    /**
     * @var UserModel
     */
    private UserModel $userModel;

    /**
     * Cache kết quả checkSession() trong cùng 1 request.
     * Tránh gọi lặp nhiều lần khi dùng nhiều getter liên tiếp.
     *
     * @var bool|null  null = chưa kiểm tra, true/false = kết quả đã cache.
     */
    private ?bool $sessionValid = null;


    // CONSTRUCTOR

    /**
     * Khởi tạo AuthService với UserModel được inject vào.
     * Đảm bảo session đã được khởi động trước khi sử dụng.
     *
     * @param UserModel $userModel
     */
    public function __construct(UserModel $userModel)
    {
        $this->userModel = $userModel;
        $this->startSession();
    }


    // SESSION MANAGEMENT

    /**
     * Khởi động session nếu chưa có.
     * Tách riêng để dễ kiểm soát và test.
     *
     * @return void
     */
    private function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }


    // ĐĂNG NHẬP

    /**
     * Xác thực và đăng nhập người dùng.
     *
     * Quy trình:
     *   1. Gọi UserModel::authenticate() để kiểm tra username + password.
     *   2. Nếu hợp lệ → regenerate session ID (chống Session Fixation).
     *   3. Ghi thông tin cần thiết vào $_SESSION.
     *
     * Cách dùng:
     *   $result = $auth->login('admin', 'mypassword');
     *   if ($result['success']) { redirect('/admin/dashboard'); }
     *   else { echo $result['message']; }
     *
     * @param  string $username
     * @param  string $plainPassword Mật khẩu plain-text từ form.
     * @return array  ['success' => bool, 'message' => string, 'user' => UserEntity|null]
     */
    public function login(string $username, string $plainPassword): array
    {
        // Validate đầu vào cơ bản trước khi truy vấn DB
        if (empty(trim($username)) || empty($plainPassword)) {
            return [
                'success' => false,
                'message' => 'Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu.',
                'user'    => null,
            ];
        }

        // Uỷ quyền xác thực cho UserModel
        $user = $this->userModel->authenticate($username, $plainPassword);

        if ($user === null) {
            return [
                'success' => false,
                'message' => 'Tên đăng nhập hoặc mật khẩu không đúng.',
                'user'    => null,
            ];
        }

        // Regenerate session ID để chống Session Fixation Attack
        session_regenerate_id(true);

        // Chỉ lưu thông tin tối thiểu vào session – không lưu password_hash
        $_SESSION[self::SESSION_USER_ID]   = $user->getId();
        $_SESSION[self::SESSION_USERNAME]  = $user->getUsername();
        $_SESSION[self::SESSION_ROLE]      = $user->getRole();
        $_SESSION[self::SESSION_LOGGED_AT] = time();

        // Invalidate cache để checkSession() tự đánh giá lại sau login
        $this->sessionValid = null;

        return [
            'success' => true,
            'message' => 'Đăng nhập thành công.',
            'user'    => $user,
        ];
    }


    // ĐĂNG KÝ

    /**
     * Đăng ký tài khoản người dùng mới.
     *
     * Quy trình:
     *   1. Validate dữ liệu đầu vào qua UserEntity::validate().
     *   2. Kiểm tra username và email chưa tồn tại trong DB.
     *   3. Hash mật khẩu qua UserEntity::setPassword().
     *   4. Lưu vào DB qua UserModel::insertEntity().
     *
     * Cách dùng:
     *   $result = $auth->register([
     *       'username' => 'lananh',
     *       'password' => 'mypassword123',
     *       'email'    => 'lananh@gmail.com',
     *       'role'     => 'user',
     *   ]);
     *   if ($result['success']) { redirect('/login'); }
     *
     * @param  array $data Mảng dữ liệu từ form đăng ký.
     * @return array ['success' => bool, 'message' => string, 'userId' => int|null]
     */
    public function register(array $data): array
    {
        // Validate đầu vào cơ bản
        $plainPassword = $data['password'] ?? '';
        if (empty($plainPassword)) {
            return [
                'success' => false,
                'message' => 'Mật khẩu không được để trống.',
                'userId'  => null,
            ];
        }

        // Tạo entity và validate các trường (username, email, role)
        // validate() KHÔNG kiểm tra passwordHash — đúng vì chưa hash ở bước này
        $user   = new UserEntity($data);
        $errors = $user->validate();

        if (!empty($errors)) {
            return [
                'success' => false,
                'message' => implode(' | ', $errors),
                'userId'  => null,
            ];
        }

        // Kiểm tra trước để trả lỗi thân thiện với người dùng.
        // Lưu ý: đây chỉ là lớp UX — race condition vẫn được xử lý
        // bằng try/catch PDOException bên dưới (lớp bảo vệ thực sự).
        if ($this->userModel->isUsernameTaken($user->getUsername())) {
            return [
                'success' => false,
                'message' => 'Tên đăng nhập đã tồn tại.',
                'userId'  => null,
            ];
        }

        if ($this->userModel->isEmailTaken($user->getEmail())) {
            return [
                'success' => false,
                'message' => 'Email đã được sử dụng.',
                'userId'  => null,
            ];
        }

        // Hash mật khẩu — setPassword() tự validate độ dài
        try {
            $user->setPassword($plainPassword);
        } catch (InvalidArgumentException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'userId'  => null,
            ];
        }

        // Lưu vào DB — bắt race condition nếu 2 request insert cùng lúc
        try {
            $newId = $this->userModel->insertEntity($user);
        } catch (RuntimeException $e) {
            // PDOException error code 23000 = Duplicate entry (username/email trùng)
            if (str_contains($e->getMessage(), '23000')) {
                return [
                    'success' => false,
                    'message' => 'Tên đăng nhập hoặc email đã tồn tại.',
                    'userId'  => null,
                ];
            }
            throw $e; // Lỗi DB khác → ném lại để xử lý ở tầng trên
        }

        return [
            'success' => true,
            'message' => 'Đăng ký tài khoản thành công.',
            'userId'  => $newId,
        ];
    }


    // KIỂM TRA PHIÊN

    /**
     * Kiểm tra người dùng có đang đăng nhập hợp lệ không.
     * Kết quả được cache trong $sessionValid để tránh gọi lặp nhiều lần
     * khi dùng nhiều getter (getSessionUserId, isAdmin, ...) trong cùng 1 request.
     *
     * Quy trình kiểm tra:
     *   1. Session phải có đủ các key bắt buộc.
     *   2. Session không được vượt quá SESSION_LIFETIME (chống session cũ).
     *
     * Cách dùng:
     *   if (!$auth->checkSession()) { redirect('/login'); }
     *
     * @return bool
     */
    public function checkSession(): bool
    {
        // Trả về cache nếu đã kiểm tra trong request này
        if ($this->sessionValid !== null) {
            return $this->sessionValid;
        }

        // Kiểm tra các key bắt buộc có tồn tại không
        if (
            empty($_SESSION[self::SESSION_USER_ID])   ||
            empty($_SESSION[self::SESSION_USERNAME])  ||
            empty($_SESSION[self::SESSION_ROLE])      ||
            !isset($_SESSION[self::SESSION_LOGGED_AT])
        ) {
            return $this->sessionValid = false;
        }

        // Kiểm tra session có hết hạn chưa
        if ((time() - $_SESSION[self::SESSION_LOGGED_AT]) > self::SESSION_LIFETIME) {
            $this->logout();
            return $this->sessionValid = false;
        }

        return $this->sessionValid = true;
    }

    /**
     * Kiểm tra người dùng trong session có phải Admin không.
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->checkSession() && $_SESSION[self::SESSION_ROLE] === 'admin';
    }

    /**
     * Lấy ID người dùng từ session hiện tại.
     * Trả về null nếu chưa đăng nhập.
     *
     * @return int|null
     */
    public function getSessionUserId(): ?int
    {
        return $this->checkSession()
            ? (int) $_SESSION[self::SESSION_USER_ID]
            : null;
    }

    /**
     * Lấy username từ session hiện tại.
     * Trả về null nếu chưa đăng nhập.
     *
     * @return string|null
     */
    public function getSessionUsername(): ?string
    {
        return $this->checkSession()
            ? (string) $_SESSION[self::SESSION_USERNAME]
            : null;
    }

    /**
     * Lấy role từ session hiện tại.
     * Trả về null nếu chưa đăng nhập.
     *
     * @return string|null
     */
    public function getSessionRole(): ?string
    {
        return $this->checkSession()
            ? (string) $_SESSION[self::SESSION_ROLE]
            : null;
    }


    // ĐĂNG XUẤT

    /**
     * Đăng xuất người dùng: xoá toàn bộ dữ liệu session và huỷ session.
     *
     * Quy trình:
     *   1. Xoá các key auth khỏi $_SESSION.
     *   2. Huỷ cookie session trên trình duyệt.
     *   3. Destroy session hoàn toàn.
     *
     * Cách dùng:
     *   $auth->logout();
     *   header('Location: /login');
     *   exit;
     *
     * @return void
     */
    public function logout(): void
    {
        // Xoá các key auth khỏi session
        unset(
            $_SESSION[self::SESSION_USER_ID],
            $_SESSION[self::SESSION_USERNAME],
            $_SESSION[self::SESSION_ROLE],
            $_SESSION[self::SESSION_LOGGED_AT]
        );

        // Reset cache
        $this->sessionValid = null;

        // Xoá cookie session trên trình duyệt
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        // Huỷ hoàn toàn session
        session_destroy();
    }
}

/* Các vấn đề cần sửa:
* checkSession() gọi lặp nhiều lần không cần thiết 
* unset($errors['password_hash']): Đây là dấu hiệu UserEntity::validate() đang validate password_hash — nhưng ở bước register thì chưa hash
* register() có race condition 
*/
