<?php

require_once 'BaseController.php';
require_once 'CartService.php';

/**
 * Class CartController
 *
 * Lớp này dùng để xử lý request từ phía người dùng:
 * - Nhận dữ liệu (GET/POST)
 * - Kiểm tra dữ liệu hợp lệ
 * - Gọi CartService xử lý
 * - Trả về view hoặc JSON
 *
 */
class CartController extends BaseController
{
    private CartService $cartService;


    // ================= CONSTRUCTOR =================

    public function __construct(?CartService $cartService = null)
    {
        parent::__construct();

        // nếu không truyền từ ngoài vào thì tự tạo
        $this->cartService = $cartService ?? new CartService();
    }


    // ================= INPUT =================

    /**
     * Lấy dữ liệu từ request
     * Có trim để tránh lỗi nhập dư khoảng trắng
     */
    private function getInput(string $key, $default = null)
    {
        $value = $_POST[$key] ?? $_GET[$key] ?? $default;

        // nếu là string thì trim
        if (is_string($value)) {
            return trim($value);
        }

        return $value;
    }


    // ================= RESPONSE =================

    /**
     * Format JSON thống nhất
     * Tránh mỗi chỗ trả 1 kiểu khác nhau
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
     * Tự xử lý response:
     * - AJAX → trả JSON
     * - Bình thường → redirect
     */
    private function handle(bool $success, string $message = '', array $data = []): void
    {
        if ($this->isAjax()) {
            $this->json($success, $message, $data);
            return;
        }

        // redirect về trang cart
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

            // check dữ liệu
            if ($productId <= 0 || $quantity <= 0) {
                return $this->handle(false, 'Dữ liệu không hợp lệ');
            }

            // gọi service xử lý
            $result = $this->cartService->add($productId, $quantity);

            return $this->handle(
                $result['success'],
                $result['message'] ?? 'Thêm thành công',
                $result['data'] ?? []
            );

        } catch (Throwable $e) {
            // tránh crash hệ thống
            return $this->handle(false, $e->getMessage());
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
                return $this->handle(false, 'Product ID không hợp lệ');
            }

            $result = $this->cartService->remove($productId);

            return $this->handle(
                $result['success'],
                $result['message'] ?? 'Đã xoá'
            );

        } catch (Throwable $e) {
            return $this->handle(false, $e->getMessage());
        }
    }


    /**
     * Cập nhật số lượng sản phẩm
     */
    public function update(): void
    {
        try {
            $productId = (int) $this->getInput('product_id');
            $quantity  = (int) $this->getInput('quantity');

            if ($productId <= 0 || $quantity < 0) {
                return $this->json(false, 'Dữ liệu không hợp lệ');
            }

            // nếu số lượng = 0 thì coi như xoá
            if ($quantity === 0) {
                return $this->remove();
            }

            $result = $this->cartService->update($productId, $quantity);

            return $this->json(
                $result['success'],
                $result['message'] ?? 'Cập nhật thành công'
            );

        } catch (Throwable $e) {
            return $this->json(false, $e->getMessage());
        }
    }


    /**
     * Xoá toàn bộ giỏ hàng
     */
    public function clear(): void
    {
        try {
            $this->cartService->clear();

            return $this->handle(true, 'Đã xoá toàn bộ giỏ hàng');

        } catch (Throwable $e) {
            return $this->handle(false, $e->getMessage());
        }
    }


    /**
     * Lấy tổng tiền (AJAX)
     */
    public function total(): void
    {
        try {
            $total = $this->cartService->getTotal();

            return $this->json(true, 'OK', [
                'total' => $total
            ]);

        } catch (Throwable $e) {
            return $this->json(false, $e->getMessage());
        }
    }
}
