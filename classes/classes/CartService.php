<?php

require_once 'ProductModel.php';

/**
 * Class CartService
 *
 * Xử lý toàn bộ logic liên quan đến giỏ hàng:
 * - Thêm sản phẩm
 * - Xoá sản phẩm
 * - Cập nhật số lượng
 * - Tính tổng tiền
 * - Lưu trữ session
 *
 * Nguyên tắc:
 * - Controller KHÔNG xử lý logic → chỉ gọi Service
 * - Service chịu trách nhiệm xử lý nghiệp vụ
 */
class CartService
{
    /**
     * Key lưu cart trong session
     */
    private string $sessionKey = 'cart';

    private ProductModel $productModel;


    // ================= CONSTRUCTOR =================

    public function __construct(?ProductModel $productModel = null)
    {
        /**
         * Dependency Injection:
         * - Cho phép truyền model từ ngoài (test dễ hơn)
         * - Nếu không có → tự khởi tạo
         */
        $this->productModel = $productModel ?? new ProductModel();

        /**
         * Khởi tạo session nếu chưa có
         */
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        /**
         * Nếu chưa có giỏ hàng → tạo mới
         */
        if (!isset($_SESSION[$this->sessionKey])) {
            $_SESSION[$this->sessionKey] = [];
        }
    }


    // ================= CORE METHODS =================

    /**
     * Lấy toàn bộ giỏ hàng
     */
    public function all(): array
    {
        return $_SESSION[$this->sessionKey];
    }


    /**
     * Thêm sản phẩm vào giỏ
     *
     * Ý tưởng:
     * - Nếu sản phẩm đã tồn tại → cộng thêm số lượng
     * - Nếu chưa có → thêm mới
     */
    public function add(int $productId, int $quantity = 1): array
    {
        try {
            /**
             * Lấy thông tin sản phẩm từ DB
             */
            $product = $this->productModel->findById($productId);

            if (!$product) {
                return [
                    'success' => false,
                    'message' => 'Sản phẩm không tồn tại'
                ];
            }

            $cart = &$_SESSION[$this->sessionKey];

            /**
             * Nếu đã có trong cart → cộng thêm số lượng
             */
            if (isset($cart[$productId])) {
                $cart[$productId]['quantity'] += $quantity;
            } else {
                /**
                 * Nếu chưa có → tạo item mới
                 */
                $cart[$productId] = [
                    'id'       => $product['id'],
                    'name'     => $product['name'],
                    'price'    => $product['price'],
                    'quantity' => $quantity
                ];
            }

            return [
                'success' => true,
                'message' => 'Thêm vào giỏ thành công',
                'data'    => $cart[$productId]
            ];

        } catch (Throwable $e) {
            error_log($e->getMessage());

            return [
                'success' => false,
                'message' => 'Lỗi khi thêm vào giỏ hàng'
            ];
        }
    }


    /**
     * Xoá sản phẩm khỏi giỏ
     */
    public function remove(int $productId): array
    {
        try {
            $cart = &$_SESSION[$this->sessionKey];

            if (!isset($cart[$productId])) {
                return [
                    'success' => false,
                    'message' => 'Sản phẩm không có trong giỏ'
                ];
            }

            unset($cart[$productId]);

            return [
                'success' => true,
                'message' => 'Đã xoá sản phẩm'
            ];

        } catch (Throwable $e) {
            error_log($e->getMessage());

            return [
                'success' => false,
                'message' => 'Lỗi khi xoá sản phẩm'
            ];
        }
    }


    /**
     * Cập nhật số lượng sản phẩm
     *
     * Quy ước:
     * - quantity = 0 → xoá sản phẩm
     */
    public function update(int $productId, int $quantity): array
    {
        try {
            $cart = &$_SESSION[$this->sessionKey];

            if (!isset($cart[$productId])) {
                return [
                    'success' => false,
                    'message' => 'Sản phẩm không tồn tại trong giỏ'
                ];
            }

            /**
             * Nếu quantity = 0 → xoá luôn
             */
            if ($quantity === 0) {
                return $this->remove($productId);
            }

            /**
             * Cập nhật số lượng
             */
            $cart[$productId]['quantity'] = $quantity;

            return [
                'success' => true,
                'message' => 'Cập nhật thành công'
            ];

        } catch (Throwable $e) {
            error_log($e->getMessage());

            return [
                'success' => false,
                'message' => 'Lỗi khi cập nhật'
            ];
        }
    }


    /**
     * Xoá toàn bộ giỏ hàng
     */
    public function clear(): void
    {
        $_SESSION[$this->sessionKey] = [];
    }


    /**
     * Tính tổng tiền giỏ hàng
     *
     * Ý tưởng:
     * - Duyệt từng sản phẩm
     * - Tổng = price * quantity
     */
    public function getTotal(): float
    {
        $total = 0;

        foreach ($_SESSION[$this->sessionKey] as $item) {
            $total += $item['price'] * $item['quantity'];
        }

        return $total;
    }
}
