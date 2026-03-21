<?php

/**
 * Class BaseController (Abstract)
 *
 * Lớp cha chung cho tất cả Controller trong ứng dụng.
 * Cung cấp các phương thức tái sử dụng: render view, trả JSON, redirect.
 * Các Controller con kế thừa lớp này và chỉ tập trung viết logic nghiệp vụ riêng.
 *
 * Cách dùng điển hình trong lớp con:
 *   class HomeController extends BaseController
 *   {
 *       public function index(): void
 *       {
 *           $products = ...;
 *           $this->renderView('home/index', ['products' => $products]);
 *       }
 *   }
 *
 * @package App\Controllers
 * @author  Ha Linh Technology Solutions
 */
abstract class BaseController
{
    // THUỘC TÍNH

    /**
     * Đường dẫn thư mục chứa các file View.
     * Mặc định trỏ đến thư mục views/ tính từ BASE_PATH.
     *
     * @var string
     */
    protected string $viewPath;


    // CONSTRUCTOR

    /**
     * Khởi tạo BaseController.
     * Thiết lập đường dẫn viewPath từ hằng số BASE_PATH (định nghĩa ở index.php).
     */
    public function __construct()
    {
        $this->viewPath = (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__))
            . '/views/';
    }


    // RENDER VIEW

    /**
     * Include file view và truyền dữ liệu vào trong đó.
     * Dùng extract() để biến mảng $data thành các biến cục bộ trong view.
     *
     * Cách dùng:
     *   $this->renderView('home/index', ['products' => $products, 'title' => 'Trang chủ']);
     *   // → include views/home/index.php
     *   // → trong view dùng $products, $title trực tiếp
     *
     * @param  string $view Đường dẫn file view tương đối từ viewPath, không cần đuôi .php.
     *                      Ví dụ: 'home/index', 'admin/products/list'.
     * @param  array  $data Mảng dữ liệu truyền vào view. Key sẽ thành tên biến.
     * @return void
     * @throws RuntimeException Nếu file view không tồn tại.
     */
    protected function renderView(string $view, array $data = []): void
    {
        $filePath = $this->viewPath . ltrim($view, '/') . '.php';

        if (!file_exists($filePath)) {
            throw new RuntimeException(
                "Không tìm thấy file view: {$filePath}"
            );
        }

        // Đưa các key của $data thành biến cục bộ trong scope của view
        extract($data, EXTR_SKIP);

        // Dùng require thay vì include: view là bắt buộc,
        // không có thì dừng hẳn thay vì tiếp tục chạy với output thiếu.
        require $filePath;
    }


    // JSON RESPONSE

    /**
     * Trả về dữ liệu dạng JSON và kết thúc request.
     * Dùng cho các AJAX endpoint: tìm kiếm sản phẩm, cập nhật giỏ hàng, ...
     * Encode trước, kiểm tra lỗi, rồi mới gửi header — tránh trình duyệt nhận
     * header JSON nhưng body rỗng khi encode thất bại.
     *
     * Cách dùng:
     *   $this->jsonResponse(['success' => true, 'data' => $products]);
     *   $this->jsonResponse(['success' => false, 'message' => 'Không tìm thấy'], 404);
     *
     * @param  mixed $data   Dữ liệu cần trả về (mảng, object, ...).
     * @param  int   $status HTTP status code. Mặc định 200.
     * @return void          Hàm kết thúc request bằng exit sau khi echo JSON.
     * @throws RuntimeException Nếu json_encode thất bại.
     */
    protected function jsonResponse(mixed $data, int $status = 200): void
    {
        // Encode trước — nếu thất bại thì throw trước khi gửi bất kỳ header nào
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($json === false) {
            throw new RuntimeException(
                'Không thể encode dữ liệu sang JSON: ' . json_last_error_msg()
            );
        }

        // Encode thành công → mới gửi header
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');

        echo $json;
        exit;
    }


    // REDIRECT

    /**
     * Chuyển hướng request sang URL khác và kết thúc request.
     * Strip ký tự \r và \n khỏi URL để chống Header Injection Attack.
     *
     * Cách dùng:
     *   $this->redirect('/login');
     *   $this->redirect('/admin/dashboard');
     *
     * @param  string $url    URL đích cần chuyển hướng.
     * @param  int    $status HTTP status code cho redirect. Mặc định 302 (Found).
     *                        Dùng 301 nếu redirect vĩnh viễn.
     * @return void           Hàm kết thúc request bằng exit.
     */
    protected function redirect(string $url, int $status = 302): void
    {
        // Strip \r \n để chống Header Injection: attacker không thể inject header giả
        // bằng cách truyền URL chứa ký tự xuống dòng.
        $safeUrl = str_replace(["\r", "\n"], '', $url);

        http_response_code($status);
        header('Location: ' . $safeUrl);
        exit;
    }


    // TIỆN ÍCH BỔ SUNG

    /**
     * Render file view thành chuỗi và trả về thay vì echo thẳng.
     * Dùng để nhúng nội dung view vào layout chung (header, footer, sidebar).
     *
     * Cách dùng điển hình với layout:
     *   // Trong Controller:
     *   $content = $this->renderViewToString('products/list', ['products' => $products]);
     *   $this->renderView('layouts/main', ['content' => $content, 'title' => 'Sản phẩm']);
     *
     *   // Trong views/layouts/main.php:
     *   <?php require 'partials/header.php'; ?>
     *   <main><?= $content ?></main>
     *   <?php require 'partials/footer.php'; ?>
     *
     * @param  string $view Đường dẫn file view tương đối từ viewPath, không cần đuôi .php.
     * @param  array  $data Mảng dữ liệu truyền vào view.
     * @return string       Nội dung HTML đã render.
     * @throws RuntimeException Nếu file view không tồn tại.
     */
    protected function renderViewToString(string $view, array $data = []): string
    {
        $filePath = $this->viewPath . ltrim($view, '/') . '.php';

        if (!file_exists($filePath)) {
            throw new RuntimeException(
                "Không tìm thấy file view: {$filePath}"
            );
        }

        extract($data, EXTR_SKIP);

        // Bật output buffer để bắt toàn bộ output của view thành chuỗi
        ob_start();
        require $filePath;
        return (string) ob_get_clean();
    }


    // LẤY INPUT AN TOÀN

    /**
     * Lấy và sanitize giá trị từ $_POST.
     * Trả về giá trị mặc định nếu key không tồn tại.
     * Tự động cast kiểu kết quả theo kiểu của $default.
     *
     * Cách dùng:
     *   $username = $this->post('username');        // string, mặc định ''
     *   $id       = $this->post('id', 0);           // cast về int vì default là int
     *   $price    = $this->post('price', 0.0);      // cast về float vì default là float
     *   $note     = $this->post('note', null);      // trả null nếu không có
     *
     * @param  string $key     Tên key trong $_POST.
     * @param  mixed  $default Giá trị mặc định. Kiểu của $default quyết định kiểu trả về.
     * @return mixed           Giá trị đã sanitize và cast đúng kiểu.
     */
    protected function post(string $key, mixed $default = ''): mixed
    {
        $value = $_POST[$key] ?? $default;
        return $this->castInput($value, $default);
    }

    /**
     * Lấy và sanitize giá trị từ $_GET.
     * Trả về giá trị mặc định nếu key không tồn tại.
     * Tự động cast kiểu kết quả theo kiểu của $default.
     *
     * Cách dùng:
     *   $keyword    = $this->get('q');          // string, mặc định ''
     *   $categoryId = $this->get('cat', 0);     // cast về int vì default là int
     *   $page       = $this->get('page', 1);    // cast về int
     *
     * @param  string $key     Tên key trong $_GET.
     * @param  mixed  $default Giá trị mặc định. Kiểu của $default quyết định kiểu trả về.
     * @return mixed           Giá trị đã sanitize và cast đúng kiểu.
     */
    protected function get(string $key, mixed $default = ''): mixed
    {
        $value = $_GET[$key] ?? $default;
        return $this->castInput($value, $default);
    }

    /**
     * Sanitize và cast giá trị input về đúng kiểu dựa theo $default.
     * Được dùng nội bộ bởi post() và get().
     *
     * Lưu ý về bool: HTTP không có kiểu boolean — form luôn gửi chuỗi.
     * KHÔNG dùng (bool) $value vì (bool)"false" = true (chuỗi khác rỗng luôn truthy).
     * Thay vào đó kiểm tra danh sách giá trị truthy thường gặp trong form HTML.
     *
     * @param  mixed $value   Giá trị thô từ $_POST / $_GET.
     * @param  mixed $default Giá trị mặc định xác định kiểu đích.
     * @return mixed
     */
    private function castInput(mixed $value, mixed $default): mixed
    {
        // Sanitize string trước khi cast
        if (is_string($value)) {
            $value = ValidatorHelper::sanitizeInput($value);
        }

        // Cast về đúng kiểu của $default
        return match (gettype($default)) {
            'integer' => (int)   $value,
            'double'  => (float) $value,
            'boolean' => in_array($value, ['1', 'true', 'on', 'yes'], true),
            'NULL'    => $value,
            default   => (string) $value,
        };
    }


    // KIỂM SOÁT TRUY CẬP

    /**
     * Yêu cầu người dùng phải đăng nhập.
     * Nếu chưa đăng nhập → redirect về trang login và kết thúc request.
     * Gọi ở đầu method cần bảo vệ thay vì tự check session mỗi nơi.
     *
     * Cách dùng:
     *   public function profile(): void
     *   {
     *       $this->requireLogin();
     *       // ... logic sau khi đã xác nhận đăng nhập
     *   }
     *
     * @param  string $loginUrl URL trang đăng nhập. Mặc định '/login'.
     * @return void
     */
    protected function requireLogin(string $loginUrl = '/login'): void
    {
        if (!SessionHelper::isLoggedIn()) {
            $this->redirect($loginUrl);
        }
    }

    /**
     * Yêu cầu người dùng phải là Admin.
     * Nếu chưa đăng nhập → redirect về trang login.
     * Nếu đã đăng nhập nhưng không phải admin → redirect về trang 403.
     * Gọi ở đầu mọi method trong AdminController.
     *
     * Cách dùng:
     *   public function dashboard(): void
     *   {
     *       $this->requireAdmin();
     *       // ... logic chỉ admin mới chạy được
     *   }
     *
     * @param  string $loginUrl URL trang đăng nhập. Mặc định '/login'.
     * @param  string $forbiddenUrl URL trang 403. Mặc định '/403'.
     * @return void
     */
    protected function requireAdmin(string $loginUrl = '/login', string $forbiddenUrl = '/403'): void
    {
        if (!SessionHelper::isLoggedIn()) {
            $this->redirect($loginUrl);
        }

        if (!SessionHelper::isAdmin()) {
            $this->redirect($forbiddenUrl);
        }
    }


    // KIỂM TRA REQUEST

    /**
     * Kiểm tra request hiện tại có phải AJAX không.
     * Dùng để Controller quyết định trả về JSON hay render view.
     *
     * Cách dùng:
     *   if ($this->isAjax()) {
     *       $this->jsonResponse($data);
     *   } else {
     *       $this->renderView('products/list', $data);
     *   }
     *
     * @return bool
     */
    protected function isAjax(): bool
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Lấy HTTP method của request hiện tại (GET, POST, PUT, DELETE, ...).
     *
     * @return string
     */
    protected function getMethod(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    /**
     * Kiểm tra request hiện tại có phải GET không.
     *
     * @return bool
     */
    protected function isGet(): bool
    {
        return $this->getMethod() === 'GET';
    }

    /**
     * Kiểm tra request hiện tại có phải POST không.
     *
     * @return bool
     */
    protected function isPost(): bool
    {
        return $this->getMethod() === 'POST';
    }
}