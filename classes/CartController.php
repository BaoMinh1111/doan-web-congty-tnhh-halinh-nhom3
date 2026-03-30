<?php

require_once 'BaseController.php';
require_once 'CartService.php';

/**
 * CartController
 * ---------------------
 * Controller này dùng để xử lý toàn bộ chức năng liên quan đến giỏ hàng:
 * - Hiển thị giỏ hàng
 * - Thêm / xoá / cập nhật sản phẩm
 * - Hỗ trợ trả dữ liệu dạng JSON khi dùng AJAX
 */
class CartController extends BaseController
{
    private CartService $cartService;

    // ================= CONSTRUCTOR =================

    public function __construct(?CartService $cartService = null)
    {
        parent::__construct();

        /**
         * Nếu không truyền CartService từ bên ngoài vào
         * thì controller sẽ tự khởi tạo
         * → giúp code linh hoạt hơn (có thể dùng dependency injection)
         */
        $this->cartService = $cartService ?? new CartService();
    }

    // ================= RESPONSE =================

    /**
     * Hàm trả dữ liệu JSON
     * Dùng khi gọi AJAX từ phía client (JS)
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
     * Hàm xử lý response chung
     * - Nếu là AJAX → trả JSON
     * - Nếu không → redirect về trang cart
     */
    private function handle(bool $success, string $message = '', array $data = []): void
    {
        if ($this->isAjax()) {
            $this->json($success, $message, $data);
            return;
        }

        // Trường hợp submit form bình thường
        $this->redirect('/cart');
    }

    // ================= VIEW =================

    /**
     * Hiển thị trang giỏ hàng
     */
    public function index(): void
    {
        // Lấy toàn bộ sản phẩm trong giỏ
        $cart = $this->cartService->all();

        // Tính tổng tiền giỏ hàng
        $total = $this->cartService->getTotal();

        /**
         * Truyền dữ liệu sang view:
         * - cart: danh sách sản phẩm
         * - total: tổng tiền
         */
        $this->renderView('cart/index', [
            'cart'  => $cart,
            'total' => $total
        ]);
    }

    // ================= ACTION =================

    /**
     * Thêm sản phẩm vào giỏ hàng
     */
    public function add(): void
    {
        try {
            /**
             * Lấy dữ liệu từ request:
             * - Ưu tiên POST (đúng chuẩn)
             * - fallback GET (phòng trường hợp gọi từ link)
             */
            $productId = (int) $this->post('product_id', $this->get('product_id', 0));
            $quantity  = (int) $this->post('quantity', 1);

            // Validate dữ liệu
            if ($productId <= 0 || $quantity <= 0) {
                $this->handle(false, 'Dữ liệu không hợp lệ');
                return;
            }

            // Gọi service để xử lý logic
            $result = $this->cartService->add($productId, $quantity);

            // Trả kết quả
            $this->handle(
                $result['success'],
                $result['message'] ?? 'Thêm thành công'
            );

        } catch (Throwable $e) {
            // Ghi log lỗi để debug
            error_log($e->getMessage());

            // Không show lỗi hệ thống cho user
            $this->handle(false, 'Đã xảy ra lỗi, vui lòng thử lại.');
        }
    }

    /**
     * Xoá 1 sản phẩm khỏi giỏ hàng
     */
    public function remove(): void
    {
        try {
            // Lấy product_id (POST chuẩn, fallback GET)
            $productId = (int) $this->post('product_id', $this->get('product_id', 0));

            if ($productId <= 0) {
                $this->handle(false, 'Product ID không hợp lệ');
                return;
            }

            // Gọi service để xoá
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
     * Cập nhật số lượng sản phẩm (dùng AJAX)
     */
    public function update(): void
    {
        try {
            // Lấy dữ liệu từ request
            $productId = (int) $this->post('product_id', $this->get('product_id', 0));
            $quantity  = (int) $this->post('quantity', $this->get('quantity', 0));

            // Validate
            if ($productId <= 0 || $quantity < 0) {
                $this->json(false, 'Dữ liệu không hợp lệ');
                return;
            }

            /**
             * Nếu quantity = 0
             * → coi như xoá sản phẩm luôn (giúp code gọn hơn)
             */
            if ($quantity === 0) {
                $result = $this->cartService->remove($productId);

                $this->json(
                    $result['success'],
                    $result['message'] ?? 'Đã xoá'
                );
                return;
            }

            // Update số lượng
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
            // Gọi service để clear
            $this->cartService->clear();

            $this->handle(true, 'Đã xoá toàn bộ giỏ hàng');

        } catch (Throwable $e) {
            error_log($e->getMessage());
            $this->handle(false, 'Đã xảy ra lỗi, vui lòng thử lại.');
        }
    }

    /**
     * Lấy tổng tiền (dùng AJAX realtime)
     */
    public function total(): void
    {
        try {
            // Lấy tổng tiền từ service
            $total = $this->cartService->getTotal();

            // Trả về JSON
            $this->json(true, 'OK', [
                'total' => $total
            ]);

        } catch (Throwable $e) {
            error_log($e->getMessage());
            $this->json(false, 'Đã xảy ra lỗi, vui lòng thử lại.');
        }
    }
}
