<?php

require_once 'ProductModel.php';

/**
 * Class CartService
 *
 * Lớp này đóng vai trò xử lý toàn bộ nghiệp vụ giỏ hàng.
 * Không làm việc với HTTP (Controller xử lý phần đó),
 * chỉ tập trung xử lý dữ liệu và logic.
 *
 * Ý tưởng thiết kế:
 * - Sử dụng session để lưu giỏ hàng (đơn giản, phù hợp đồ án)
 * - Mỗi sản phẩm trong cart được lưu theo productId
 * - Dữ liệu lưu gồm: id, name, price, quantity
 */
class CartService
{
    private string $sessionKey = 'cart';

    private ProductModel $productModel;


    // ================= CONSTRUCTOR =================

    public function __construct(?ProductModel $productModel = null)
    {
        /**
         * Dependency Injection:
         * Cho phép truyền model từ ngoài (dễ test, đúng chuẩn OOP)
         */
        $this->productModel = $productModel ?? new ProductModel();

        /**
         * Đảm bảo session luôn tồn tại trước khi thao tác
         */
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        /**
         * Nếu chưa có giỏ hàng thì khởi tạo
         * Tránh lỗi undefined index
         */
        if (!isset($_SESSION[$this->sessionKey])) {
            $_SESSION[$this->sessionKey] = [];
        }
    }


    // ================= LẤY DỮ LIỆU =================

    /**
     * Lấy toàn bộ giỏ hàng
     *
     * @return array
     */
    public function all(): array
    {
        return $_SESSION[$this->sessionKey];
    }


    /**
     * Đếm tổng số lượng sản phẩm trong giỏ
     *
     * Ý tưởng:
     * - Duyệt qua cart
     * - Cộng tất cả quantity lại
     */
    public function getTotalQuantity(): int
    {
        $total = 0;

        foreach ($_SESSION[$this->sessionKey] as $item) {
            $total += $item['quantity'];
        }

        return $total;
    }


    // ================= THÊM =================

    /**
     * Thêm sản phẩm vào giỏ
     *
     * Luồng xử lý:
     * 1. Validate input
     * 2. Lấy sản phẩm từ DB (ProductEntity)
     * 3. Kiểm tra tồn kho (nếu có)
     * 4. Nếu đã có → cộng số lượng
     * 5. Nếu chưa có → tạo mới
     */
    public function add(int $productId, int $quantity = 1): array
    {
        try {
            /**
             * Validate dữ liệu đầu vào
             */
            if ($productId <= 0 || $quantity <= 0) {
                return [
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ'
                ];
            }

            /**
             * Lấy sản phẩm dạng Entity (không phải array)
             */
            $product = $this->productModel->getById($productId);

            if (!$product) {
                return [
                    'success' => false,
                    'message' => 'Sản phẩm không tồn tại'
                ];
            }

            /**
             * (Optional) kiểm tra tồn kho
             * nếu không có inventory thì có thể bỏ qua
             */
            if (method_exists($this->productModel, 'checkStock')) {
                if (!$this->productModel->checkStock($productId, $quantity)) {
                    return [
                        'success' => false,
                        'message' => 'Không đủ hàng trong kho'
                    ];
                }
            }

            $cart = &$_SESSION[$this->sessionKey];

            if (isset($cart[$productId])) {
                /**
                 * Nếu đã có → cộng dồn số lượng
                 */
                $cart[$productId]['quantity'] += $quantity;
            } else {
                /**
                 * Nếu chưa có → tạo mới
                 * Sử dụng getter của Entity để đảm bảo type-safe
                 */
                $cart[$productId] = [
                    'id'       => $product->getId(),
                    'name'     => $product->getName(),
                    'price'    => (float)$product->getPrice(),
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
                'message' => 'Lỗi hệ thống khi thêm sản phẩm'
            ];
        }
    }


    // ================= XOÁ =================

    /**
     * Xoá 1 sản phẩm khỏi giỏ
     */
    public function remove(int $productId): array
    {
        try {
            $cart = &$_SESSION[$this->sessionKey];

            if (!isset($cart[$productId])) {
                return [
                    'success' => false,
                    'message' => 'Sản phẩm không tồn tại trong giỏ'
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
                'message' => 'Lỗi khi xoá'
            ];
        }
    }


    // ================= CẬP NHẬT =================

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
                    'message' => 'Không tồn tại trong giỏ'
                ];
            }

            if ($quantity < 0) {
                return [
                    'success' => false,
                    'message' => 'Số lượng không hợp lệ'
                ];
            }

            if ($quantity === 0) {
                return $this->remove($productId);
            }

            /**
             * Có thể kiểm tra tồn kho lại khi update
             */
            if (method_exists($this->productModel, 'checkStock')) {
                if (!$this->productModel->checkStock($productId, $quantity)) {
                    return [
                        'success' => false,
                        'message' => 'Vượt quá số lượng tồn kho'
                    ];
                }
            }

            $cart[$productId]['quantity'] = $quantity;

            return [
                'success' => true,
                'message' => 'Cập nhật thành công'
            ];

        } catch (Throwable $e) {
            error_log($e->getMessage());

            return [
                'success' => false,
                'message' => 'Lỗi cập nhật'
            ];
        }
    }


    // ================= XOÁ HẾT =================

    /**
     * Xoá toàn bộ giỏ hàng
     */
    public function clear(): void
    {
        $_SESSION[$this->sessionKey] = [];
    }


    // ================= TỔNG TIỀN =================

    /**
     * Tính tổng tiền
     *
     * Ý tưởng:
     * - mỗi sản phẩm có: price * quantity
     * - cộng lại toàn bộ
     */
    public function getTotal(): float
    {
        $total = 0;

        foreach ($_SESSION[$this->sessionKey] as $item) {
            $total += $item['price'] * $item['quantity'];
        }

        return $total;
    }


    // ================= FORMAT =================

    /**
     * Format tiền (hiển thị đẹp)
     */
    public function formatMoney(float $amount): string
    {
        return number_format($amount, 0, ',', '.') . ' VND';
    }
}
