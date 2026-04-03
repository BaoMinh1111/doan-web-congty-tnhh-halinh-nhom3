<?php

/**
 * Class Database
 *
 * Lớp quản lý kết nối PDO duy nhất cho toàn bộ ứng dụng.
 * Áp dụng Singleton Pattern để đảm bảo chỉ có một instance tồn tại.
 *
 * Trách nhiệm duy nhất của lớp này: cung cấp và quản lý kết nối PDO.
 * Xử lý transaction KHÔNG thuộc về lớp này — hãy dùng BaseModel::transaction().
 *
 * @package App\Support
 * @author  Ha Linh Technology Solutions
 */
class Database
{
    // THUỘC TÍNH

    /**
     * Instance duy nhất của lớp Database (Singleton).
     *
     * @var Database|null
     */
    private static ?Database $instance = null;

    /**
     * Đối tượng PDO thực sự dùng để truy vấn CSDL.
     *
     * @var PDO|null
     */
    private ?PDO $pdo = null;

    /**
     * Thông tin cấu hình kết nối CSDL.
     * Được nạp từ file config hoặc truyền vào lúc khởi tạo.
     *
     * @var array{host: string, dbname: string, user: string, pass: string, charset: string, port: int}
     */
    private array $config = [];


    // CONSTRUCTOR (private – ngăn tạo object trực tiếp từ bên ngoài)

    /**
     * Constructor private – chỉ được gọi nội bộ qua getInstance().
     * Nạp cấu hình và khởi tạo kết nối PDO.
     *
     * @param array $config Mảng cấu hình
     * @throws RuntimeException Nếu kết nối CSDL thất bại.
     */
    private function __construct(array $config = [])
    {
        $this->config = !empty($config) ? $config : $this->loadConfig();
        $this->connect();
    }

    /**
     * Ngăn clone object (vi phạm Singleton).
     */
    private function __clone() {}

    /**
     * Ngăn unserialize object (vi phạm Singleton).
     *
     * @throws RuntimeException
     */
    public function __wakeup(): void
    {
        throw new RuntimeException('Không thể unserialize lớp Database (Singleton).');
    }


    // SINGLETON – PHƯƠNG THỨC TĨNH

    /**
     * Trả về instance duy nhất của Database.
     * Nếu chưa có thì tạo mới, nếu đã có thì trả về cái cũ.
     *
     * @param array $config Cấu hình kết nối (chỉ dùng lần đầu tạo instance).
     * @return Database Instance duy nhất.
     */
    public static function getInstance(array $config = []): Database
    {
        if (self::$instance === null) {
            self::$instance = new self($config);
        }

        return self::$instance;
    }

    /**
     * Reset instance (dùng cho unit test hoặc khi cần tạo lại kết nối).
     * KHÔNG dùng trong production.
     *
     * @return void
     */
    public static function resetInstance(): void
    {
        self::$instance = null;
    }


    // KẾT NỐI PDO

    /**
     * Khởi tạo kết nối PDO từ thông tin trong $this->config.
     *
     * Các PDO option được chọn:
     *   - ERRMODE_EXCEPTION    : ném PDOException khi có lỗi SQL → bắt được bằng try/catch
     *   - EMULATE_PREPARES=false: dùng prepared statement thật thay vì giả lập → an toàn hơn
     *   - ATTR_PERSISTENT=false : không dùng persistent connection → tránh lỗi trạng thái giữa các request
     *
     * @throws RuntimeException Nếu kết nối thất bại (bắt PDOException).
     * @return void
     */
    private function connect(): void
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $this->config['host'],
            $this->config['port']    ?? 3306,
            $this->config['dbname'],
            $this->config['charset'] ?? 'utf8mb4'
        );

        $options = [
            PDO::ATTR_ERRMODE          => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_PERSISTENT       => false,
        ];

        try {
            $this->pdo = new PDO(
                $dsn,
                $this->config['user'],
                $this->config['pass'],
                $options
            );
        } catch (PDOException $e) {
            // Ném RuntimeException để ẩn thông tin nhạy cảm khỏi người dùng cuối
            throw new RuntimeException(
                'Không thể kết nối cơ sở dữ liệu. Vui lòng thử lại sau. [' . $e->getCode() . ']'
            );
        }
    }


    // PUBLIC API

    /**
     * Trả về đối tượng PDO để các Model sử dụng truy vấn.
     *
     * Cách dùng trong BaseModel:
     *   $this->db = Database::getInstance()->getConnection();
     *
     * @return PDO
     * @throws RuntimeException Nếu PDO chưa được khởi tạo.
     */
    public function getConnection(): PDO
    {
        if ($this->pdo === null) {
            throw new RuntimeException('Kết nối PDO chưa được khởi tạo.');
        }

        return $this->pdo;
    }

    /**
     * Kiểm tra kết nối còn sống không (ping).
     *
     * Hữu ích cho các tiến trình chạy lâu (CLI script, queue worker)
     * khi connection có thể bị MySQL timeout sau thời gian không hoạt động.
     * Với web request bình thường (PHP tắt sau mỗi request) thì không cần dùng.
     *
     * @return bool
     */
    public function isConnected(): bool
    {
        try {
            $this->pdo?->query('SELECT 1');
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Kết nối lại nếu connection đã bị đứt.
     *
     * @return void
     */
    public function reconnect(): void
    {
        $this->pdo = null;
        $this->connect();
    }


    // CONFIG LOADER

    /**
     * Nạp cấu hình CSDL.
     * Ưu tiên theo thứ tự:
     *   1. File config/database.php (nếu tồn tại)
     *   2. Hằng số PHP được định nghĩa trước (DB_HOST, DB_NAME, ...)
     *   3. Không có fallback mặc định → throw luôn
     *
     * Không có fallback credential mặc định để tránh vô tình deploy
     * lên hosting thật mà không có file config.
     *
     * @return array
     * @throws RuntimeException Nếu không tìm thấy cấu hình hợp lệ.
     */
    private function loadConfig(): array
    {
        // Ưu tiên 1: Nạp từ file config/database.php
        // Dùng hằng số BASE_PATH (định nghĩa ở index.php) thay vì dirname(__DIR__)
        // để tránh lỗi khi cấu trúc thư mục thay đổi.
        $configFile = (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 2))
            . '/config/database.php';

        if (file_exists($configFile)) {
            $cfg = require $configFile;
            if (is_array($cfg)) {
                return $cfg;
            }
        }

        // Ưu tiên 2: Dùng hằng số PHP
        if (defined('DB_HOST')) {
            $config = [
                'host'    => DB_HOST,
                'port'    => defined('DB_PORT')    ? (int) DB_PORT : 3306,
                'dbname'  => defined('DB_NAME')    ? DB_NAME       : '',
                'user'    => defined('DB_USER')    ? DB_USER       : '',
                'pass'    => defined('DB_PASS')    ? DB_PASS       : '',
                'charset' => defined('DB_CHARSET') ? DB_CHARSET    : 'utf8mb4',
            ];

            // Validate các trường bắt buộc – tránh kết nối với credential rỗng
            $missing = array_filter(
                ['DB_NAME' => $config['dbname'], 'DB_USER' => $config['user']],
                'empty'
            );
            if (!empty($missing)) {
                throw new RuntimeException(
                    'Cấu hình CSDL không đầy đủ. Các hằng số sau còn thiếu hoặc rỗng: '
                    . implode(', ', array_keys($missing))
                );
            }

            return $config;
        }

        // Ưu tiên 3: Không có config → throw ngay để dev phát hiện sớm
        throw new RuntimeException(
            'Không tìm thấy cấu hình CSDL. ' .
            'Vui lòng tạo file config/database.php hoặc định nghĩa hằng số DB_HOST, DB_NAME, DB_USER, DB_PASS.'
        );
    }
}
