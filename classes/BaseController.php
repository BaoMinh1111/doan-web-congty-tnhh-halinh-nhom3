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

        include $filePath;
    }


    // JSON RESPONSE

    /**
     * Trả về dữ liệu dạng JSON và kết thúc request.
     * Dùng cho các AJAX endpoint: tìm kiếm sản phẩm, cập nhật giỏ hàng, ...
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
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');

        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($json === false) {
            throw new RuntimeException(
                'Không thể encode dữ liệu sang JSON: ' . json_last_error_msg()
            );
        }

        echo $json;
        exit;
    }


    // REDIRECT

    /**
     * Chuyển hướng request sang URL khác và kết thúc request.
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
        http_response_code($status);
        header('Location: ' . $url);
        exit;
    }


    // TIỆN ÍCH BỔ SUNG

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
     * Kiểm tra request hiện tại có phải POST không.
     *
     * @return bool
     */
    protected function isPost(): bool
    {
        return $this->getMethod() === 'POST';
    }
}

/* Các vấn đề cần sửa:
* renderView() nên dùng require
* Thiếu renderViewToString(): Hiện tại renderView() echo thẳng ra output nhưng cta sẽ có layout chung (header, footer, sidebar)
* Thiếu method lấy input an toàn
* isPost() có nhưng thiếu isGet() —> không nhất quán
*/
