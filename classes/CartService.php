<?php

require_once 'ProductModel.php';
require_once 'InventoryModel.php';
require_once 'PromotionModel.php';

/**
 * CartService
 * -------------------------
 * Xử lý logic giỏ hàng:
 * 1. Thêm / xoá / cập nhật sản phẩm
 * 2. Lưu session
 * 3. Kiểm tra tồn kho
 * 4. Tính tổng tiền + áp dụng promotion
 */
class CartService
{
    private ProductModel $productModel;
    private InventoryModel $inventoryModel;
    private PromotionModel $promotionModel;
    private string $sessionKey = 'cart';


    public function __construct()
    {
        // Khởi tạo các model để dùng trong giỏ
        $this->productModel   = new ProductModel();
        $this->inventoryModel = new InventoryModel();
        $this->promotionModel = new PromotionModel();

        // Bước 1: Mở session nếu chưa có
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Bước 2: Tạo giỏ rỗng nếu chưa tồn tại
        if (!isset($_SESSION[$this->sessionKey])) {
            $_SESSION[$this->sessionKey] = [];
        }
    }

    /**
     * Format dữ liệu trả về
     * - Trả về mảng chuẩn ['success'=>bool, 'message'=>string, 'data'=>mixed]
     */
    private function response($success, $message, $data = null)
    {
        return [
            'success' => $success,
            'message' => $message,
            'data'    => $data
        ];
    }

    /**
     * Lấy tất cả sản phẩm trong giỏ
     */
    public function all(): array
    {
        return $_SESSION[$this->sessionKey];
    }

    /**
     * Thêm sản phẩm vào giỏ
     * Cách làm:
     * 1. Kiểm tra input hợp lệ (productId, quantity)
     * 2. Lấy thông tin sản phẩm từ DB
     * 3. Lấy tồn kho
     * 4. Nếu đã có trong giỏ thì cộng số lượng mới
     * 5. Nếu chưa có thì tạo mới
     * 6. Trả về mảng success + message + data
     */
    public function add(int $productId, int $quantity): array
    {
        try {
            // Validate input
            if ($productId <= 0 || $quantity <= 0) {
                throw new Exception("Dữ liệu không hợp lệ");
            }

            // Lấy sản phẩm từ DB
            $product = $this->productModel->find($productId);
            if (!$product) {
                throw new Exception("Không tìm thấy sản phẩm");
            }

            // Lấy tồn kho
            $stock = $this->inventoryModel->getStock($productId);

            // Nếu đã có trong giỏ → cộng thêm
            if (isset($_SESSION[$this->sessionKey][$productId])) {
                $newQty = $_SESSION[$this->sessionKey][$productId]['quantity'] + $quantity;

                // Check tồn kho
                if ($newQty > $stock) {
                    throw new Exception("Số lượng vượt quá tồn kho");
                }

                // Cập nhật số lượng mới
                $_SESSION[$this->sessionKey][$productId]['quantity'] = $newQty;

            } else {
                // Nếu chưa có trong giỏ → thêm mới
                if ($quantity > $stock) {
                    throw new Exception("Không đủ hàng trong kho");
                }

                // Lưu vào session
                $_SESSION[$this->sessionKey][$productId] = [
                    'product_id' => $productId,
                    'name'       => $product['name'],
                    'price'      => $product['price'],
                    'quantity'   => $quantity
                ];
            }

            return $this->response(true, "Thêm thành công", $_SESSION[$this->sessionKey][$productId]);

        } catch (Throwable $e) {
            // Bắt lỗi để không crash hệ thống
            return $this->response(false, $e->getMessage());
        }
    }

    /**
     * Xoá 1 sản phẩm khỏi giỏ
     * Cách làm:
     * 1. Kiểm tra sản phẩm có trong giỏ không
     * 2. Nếu có → unset session
     * 3. Trả về success
     */
    public function remove(int $productId): array
    {
        if (!isset($_SESSION[$this->sessionKey][$productId])) {
            return $this->response(false, "Sản phẩm không có trong giỏ");
        }

        unset($_SESSION[$this->sessionKey][$productId]);

        return $this->response(true, "Đã xoá sản phẩm");
    }

    /**
     * Cập nhật số lượng sản phẩm
     * Cách làm:
     * 1. Kiểm tra sản phẩm có trong giỏ không
     * 2. Nếu = 0 → gọi remove luôn
     * 3. Kiểm tra số lượng >= 0
     * 4. Kiểm tra tồn kho
     * 5. Cập nhật số lượng trong session
     * 6. Trả về success + message + data
     */
    public function update(int $productId, int $quantity): array
    {
        try {
            if (!isset($_SESSION[$this->sessionKey][$productId])) {
                throw new Exception("Không có sản phẩm trong giỏ");
            }

            if ($quantity == 0) {
                return $this->remove($productId);
            }

            if ($quantity < 0) {
                throw new Exception("Số lượng không hợp lệ");
            }

            $stock = $this->inventoryModel->getStock($productId);
            if ($quantity > $stock) {
                throw new Exception("Vượt quá số lượng tồn kho");
            }

            $_SESSION[$this->sessionKey][$productId]['quantity'] = $quantity;

            return $this->response(true, "Cập nhật thành công", $_SESSION[$this->sessionKey][$productId]);

        } catch (Throwable $e) {
            return $this->response(false, $e->getMessage());
        }
    }

    /**
     * Xoá toàn bộ giỏ hàng
     * Cách làm:
     * 1. Reset session về rỗng
     * 2. Trả về success
     */
    public function clear(): array
    {
        $_SESSION[$this->sessionKey] = [];
        return $this->response(true, "Đã xoá toàn bộ giỏ hàng");
    }

    /**
     * Tính tổng tiền giỏ hàng (có áp dụng promotion)
     * Cách làm:
     * 1. Duyệt từng sản phẩm → tính tổng tiền = price * quantity
     * 2. Gọi PromotionModel để tính giảm giá
     * 3. Trả về tổng tiền sau giảm
     */
    public function getTotal(): float
    {
        $total = 0;

        foreach ($_SESSION[$this->sessionKey] as $item) {
            $total += $item['price'] * $item['quantity'];
        }

        $discount = $this->promotionModel->calculateDiscount($total);

        return $total - $discount;
    }
}
