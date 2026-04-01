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
 * 
 * 1. Test AJAX cart + promotion (realtime tính tiền)
 * 2. Thêm validation giỏ hàng (số lượng > 0, tồn kho)
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

        // Tính tổng tiền giỏ hàng (bao gồm promotion nếu có)
        $total = $this->cartService->getTotal();

        /**
         * Truyền dữ liệu sang view:
         * - cart: danh sách sản phẩm
         * - total: tổng tiền (subtotal, discount, total)
         */
        $this->renderView('cart/index', [
            'cart'  => $cart,
            'total' => $total
        ]);
    }

    // ================= ACTION =================
    /**
     * Thêm sản phẩm vào giỏ hàng
     * BƯỚC LÀM:
     * 1. Lấy product_id và quantity từ POST/GET
     * 2. Validate dữ liệu (>0)
     * 3. Gọi CartService->add() (check tồn kho, số lượng)
     * 4. Trả kết quả (AJAX hoặc redirect)
     */
    public function add(): void
    {
        try {
            $productId = (int) $this->post('product_id', $this->get('product_id', 0));
            $quantity  = (int) $this->post('quantity', 1);

            // ===== VALIDATION =====
            if ($productId <= 0 || $quantity <= 0) {
                $this->handle(false, 'Dữ liệu không hợp lệ (quantity > 0)');
                return;
            }

            // Gọi service để thêm
            $result = $this->cartService->add($productId, $quantity);

            // Trả kết quả
            $this->handle(
                $result['success'],
                $result['message'] ?? 'Thêm thành công'
            );

        } catch (Throwable $e) {
            error_log($e->getMessage());
            $this->handle(false, 'Đã xảy ra lỗi, vui lòng thử lại.');
        }
    }

    /**
     * Xoá 1 sản phẩm khỏi giỏ hàng
     * BƯỚC LÀM:
     * 1. Lấy product_id
     * 2. Validate ID
     * 3. Gọi CartService->remove()
     * 4. Trả kết quả
     */
    public function remove(): void
    {
        try {
            $productId = (int) $this->post('product_id', $this->get('product_id', 0));

            if ($productId <= 0) {
                $this->handle(false, 'Product ID không hợp lệ');
                return;
            }

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
     * Cập nhật số lượng sản phẩm (AJAX)
     * BƯỚC LÀM:
     * 1. Lấy product_id và quantity
     * 2. Validate dữ liệu (>0, <= tồn kho)
     * 3. Nếu quantity=0 → remove luôn
     * 4. Gọi CartService->update()
     * 5. Trả kết quả JSON
     * 6. AJAX phía client có thể gọi total() để update realtime
     */
    public function update(): void
    {
        try {
            $productId = (int) $this->post('product_id', $this->get('product_id', 0));
            $quantity  = (int) $this->post('quantity', $this->get('quantity', 0));

            // ===== VALIDATION =====
            if ($productId <= 0 || $quantity < 0) {
                $this->json(false, 'Dữ liệu không hợp lệ (quantity >= 0)');
                return;
            }

            // Nếu = 0 → xoá luôn
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

            // ===== AJAX realtime total =====
            $total = $this->cartService->getTotal(); // bao gồm promotion, discount

            $this->json(
                $result['success'],
                $result['message'] ?? 'Cập nhật thành công',
                [
                    'item'  => $result['data'] ?? [],
                    'total' => $total
                ]
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
     * Lấy tổng tiền (AJAX realtime)
     * BƯỚC LÀM:
     * 1. Gọi CartService->getTotal()
     * 2. Trả JSON total (bao gồm subtotal, discount, total)
     * 3. Phía client update ngay trên view
     */
    public function total(): void
    {
        try {
            $total = $this->cartService->getTotal();
            $this->json(true, 'OK', [
                'total'    => $total['data']['total'] ?? 0,
                'subtotal' => $total['data']['subtotal'] ?? 0,
                'discount' => $total['data']['discount'] ?? 0
            ]);
        } catch (Throwable $e) {
            error_log($e->getMessage());
            $this->json(false, 'Đã xảy ra lỗi, vui lòng thử lại.');
        }
    }
}
