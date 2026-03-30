<?php

require_once 'BaseController.php';
require_once 'CartService.php';

/**
 * CartController
 * ---------------------
 * Dùng để xử lý giỏ hàng:
 * - Xem giỏ hàng
 * - Thêm / xoá / cập nhật sản phẩm
 * - Trả JSON nếu gọi bằng AJAX
 */
class CartController extends BaseController
{
    private CartService $cartService;

    // ================= CONSTRUCTOR =================

    public function __construct(?CartService $cartService = null)
    {
        parent::__construct();

        // Nếu không truyền từ ngoài thì tự tạo
        $this->cartService = $cartService ?? new CartService();
    }

    // ================= RESPONSE =================

    /**
     * Trả JSON cho AJAX
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
     * Xử lý trả kết quả:
     * - AJAX → trả JSON
     * - Không phải AJAX → redirect về cart
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
        // Lấy danh sách sản phẩm trong giỏ
        $cart = $this->cartService->all();

        // Tính tổng tiền
        $total = $this->cartService->getTotal();

        // Truyền dữ liệu sang view
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
            // Ưu tiên POST → fallback GET (phòng trường hợp gọi từ link)
            $productId = (int) $this->post('product_id', $this->get('product_id', 0));
            $quantity  = (int) $this->post('quantity', 1);

            // Check dữ liệu
            if ($productId <= 0 || $quantity <= 0) {
                $this->handle(false, 'Dữ liệu không hợp lệ');
                return;
            }

            // Gọi service để thêm
            $result = $this->cartService->add($productId, $quantity);

            $this->handle(
                $result['success'],
                $result['message'] ?? 'Thêm thành công'
            );

        } catch (Throwable $e) {
            // Không show lỗi hệ thống
            error_log($e->getMessage());
            $this->handle(false, 'Đã xảy ra lỗi, vui lòng thử lại.');
        }
    }

    /**
     * Xoá 1 sản phẩm khỏi giỏ
     */
    public function remove(): void
    {
        try {
            $productId = (int) $this->post('product_id', $this->get('product_id', 0));

            if ($productId <= 0) {
                $this->handle(false, 'Product ID không hợp lệ');
                return;
            }

            // Gọi service xoá
            $result = $this->cartService->remove($productId);

            $this->handle(
                $result['success'],
                $result['message'] ?? 'Đã xoá'
            );

        } catch (Throwable $e) {
            error_log($e->getMessage());
            $this->handle(false, 'Đã xảy ra lỗi, vui lòng thử lại.');
        }
    }

    /**
     * Cập nhật số lượng (AJAX)
     */
    public function update(): void
    {
        try {
            
            $productId = (int) $this->post('product_id', $this->get('product_id', 0));
            $quantity  = (int) $this->post('quantity', $this->get('quantity', 0));

            // Check dữ liệu
            if ($productId <= 0 || $quantity < 0) {
                $this->json(false, 'Dữ liệu không hợp lệ');
                return;
            }

            // Nếu = 0 thì xoá luôn cho gọn
            if ($quantity === 0) {
                $result = $this->cartService->remove($productId);

                $this->json(
                    $result['success'],
                    $result['message'] ?? 'Đã xoá'
                );
                return;
            }

            // Cập nhật số lượng
            $result = $this->cartService->update($productId, $quantity);

            $this->json(
                $result['success'],
                $result['message'] ?? 'Cập nhật thành công'
            );

        } catch (Throwable $e) {
            error_log($e->getMessage());
            $this->json(false, 'Đã xảy ra lỗi, vui lòng thử lại.');
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

        } catch (Throwable $e) {
            error_log($e->getMessage());
            $this->handle(false, 'Đã xảy ra lỗi, vui lòng thử lại.');
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

        } catch (Throwable $e) {
            error_log($e->getMessage());
            $this->json(false, 'Đã xảy ra lỗi, vui lòng thử lại.');
        }
    }
}
