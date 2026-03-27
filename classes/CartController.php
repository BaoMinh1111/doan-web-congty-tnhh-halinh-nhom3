<?php

require_once 'BaseController.php';
require_once 'CartService.php';

/**
 * Class CartController
 *
 */
class CartController extends BaseController
{
    private CartService $cartService;


    // ================= CONSTRUCTOR =================

    public function __construct(?CartService $cartService = null)
    {
        parent::__construct();

        /**
         * Dependency Injection:
         * - Có thể truyền CartService từ ngoài (test/unit test)
         * - Nếu không có thì tự khởi tạo
         */
        $this->cartService = $cartService ?? new CartService();
    }


    // ================= INPUT =================

    /**
     * Lấy dữ liệu từ request
     *
     * Cách làm:
     * - Ưu tiên POST → GET → default
     * - trim string để tránh lỗi khoảng trắng
     * - có thể mở rộng sanitize nếu cần
     */
    private function getInput(string $key, $default = null)
    {
        $value = $_POST[$key] ?? $_GET[$key] ?? $default;

        if (is_string($value)) {
            $value = trim($value);

            /**
             * sanitize cơ bản chống XSS
             * (áp dụng cho input dạng text như note, code giảm giá)
             */
            $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        }

        return $value;
    }


    // ================= RESPONSE =================

    /**
     * Trả JSON thống nhất
     *
     * Ý tưởng:
     * - Tất cả API trả cùng format
     * - Dễ xử lý phía frontend
     */
    private function json(bool $success, string $message = '', array $data = []): void
    {
        $this->jsonResponse([
            'success' => $success,
            'message' => $message,
            'data'    => $data
        ]);
    }


    /**
     * Xử lý response linh hoạt
     *
     * - Nếu AJAX → trả JSON
     * - Nếu request thường → redirect
     */
    private function handle(bool $success, string $message = '', array $data = []): void
    {
        if ($this->isAjax()) {
            $this->json($success, $message, $data);
            return;
        }

        $this->redirect('/cart');
    }


    // ================= VIEW =================

    /**
     * Hiển thị giỏ hàng
     */
    public function index(): void
    {
        $cart  = $this->cartService->all();
        $total = $this->cartService->getTotal();

        $this->renderView('cart/index', [
            'cart'  => $cart,
            'total' => $total
        ]);
    }


    // ================= ACTION =================

    /**
     * Thêm sản phẩm vào giỏ
     */
    public function add(): void
    {
        try {
            $productId = (int) $this->getInput('product_id');
            $quantity  = (int) $this->getInput('quantity', 1);

            // validate input
            if ($productId <= 0 || $quantity <= 0) {
                $this->handle(false, 'Dữ liệu không hợp lệ');
                return;
            }

            $result = $this->cartService->add($productId, $quantity);

            $this->handle(
                $result['success'],
                $result['message'] ?? 'Thêm thành công',
                $result['data'] ?? []
            );
            return;

        } catch (Throwable $e) {
            /**
             * Không trả lỗi thô ra client
             * → tránh lộ SQL / đường dẫn / hệ thống
             */
            error_log($e->getMessage());

            $this->handle(false, 'Đã xảy ra lỗi, vui lòng thử lại.');
            return;
        }
    }


    /**
     * Xoá sản phẩm khỏi giỏ
     */
    public function remove(): void
    {
        try {
            $productId = (int) $this->getInput('product_id');

            if ($productId <= 0) {
                $this->handle(false, 'Product ID không hợp lệ');
                return;
            }

            $result = $this->cartService->remove($productId);

            $this->handle(
                $result['success'],
                $result['message'] ?? 'Đã xoá'
            );
            return;

        } catch (Throwable $e) {
            error_log($e->getMessage());
            $this->handle(false, 'Đã xảy ra lỗi, vui lòng thử lại.');
            return;
        }
    }


    /**
     * Cập nhật số lượng
     */
    public function update(): void
    {
        try {
            $productId = (int) $this->getInput('product_id');
            $quantity  = (int) $this->getInput('quantity');

            if ($productId <= 0 || $quantity < 0) {
                $this->json(false, 'Dữ liệu không hợp lệ');
                return;
            }

            /**
             * Không gọi lại method remove()
             * → tránh phụ thuộc ngầm vào input
             * → gọi trực tiếp Service
             */
            if ($quantity === 0) {
                $result = $this->cartService->remove($productId);

                $this->json(
                    $result['success'],
                    $result['message'] ?? 'Đã xoá'
                );
                return;
            }

            $result = $this->cartService->update($productId, $quantity);

            $this->json(
                $result['success'],
                $result['message'] ?? 'Cập nhật thành công'
            );
            return;

        } catch (Throwable $e) {
            error_log($e->getMessage());
            $this->json(false, 'Đã xảy ra lỗi, vui lòng thử lại.');
            return;
        }
    }


    /**
     * Xoá toàn bộ giỏ hàng
     */
    public function clear(): void
    {
        try {
            $this->cartService->clear();

            $this->handle(true, 'Đã xoá toàn bộ giỏ hàng');
            return;

        } catch (Throwable $e) {
            error_log($e->getMessage());
            $this->handle(false, 'Đã xảy ra lỗi, vui lòng thử lại.');
            return;
        }
    }


    /**
     * Lấy tổng tiền (AJAX)
     */
    public function total(): void
    {
        try {
            $total = $this->cartService->getTotal();

            $this->json(true, 'OK', [
                'total' => $total
            ]);
            return;

        } catch (Throwable $e) {
            error_log($e->getMessage());
            $this->json(false, 'Đã xảy ra lỗi, vui lòng thử lại.');
            return;
        }
    }
}
