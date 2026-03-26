<?php

require_once 'ProductModel.php';
require_once 'InventoryModel.php';
require_once 'PromotionModel.php';

class CartService
{
    private ProductModel $productModel;
    private InventoryModel $inventoryModel;
    private PromotionModel $promotionModel;
    private string $sessionKey = 'cart';


    public function __construct()
    {
        $this->productModel   = new ProductModel();
        $this->inventoryModel = new InventoryModel();
        $this->promotionModel = new PromotionModel();

        // mở session nếu chưa có
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // tạo giỏ hàng rỗng nếu chưa tồn tại
        if (!isset($_SESSION[$this->sessionKey])) {
            $_SESSION[$this->sessionKey] = [];
        }
    }


    // format trả dữ liệu chung
    private function response($success, $message, $data = null)
    {
        return [
            'success' => $success, // true là ok, false là lỗi
            'message' => $message, // thông báo
            'data'    => $data     // dữ liệu trả về
        ];
    }


    // lấy toàn bộ giỏ hàng
    public function all(): array
    {
        return $_SESSION[$this->sessionKey];
    }


    // thêm sản phẩm vào giỏ
    public function add(int $productId, int $quantity): array
    {
        try {
            // check dữ liệu đầu vào
            if ($productId <= 0 || $quantity <= 0) {
                throw new Exception("Dữ liệu không hợp lệ");
            }

            // lấy sản phẩm từ DB
            $product = $this->productModel->find($productId);

            // nếu không có thì báo lỗi
            if (!$product) {
                throw new Exception("Không tìm thấy sản phẩm");
            }

            // lấy số lượng tồn kho
            $stock = $this->inventoryModel->getStock($productId);

            // nếu đã có trong giỏ thì cộng thêm
            if (isset($_SESSION[$this->sessionKey][$productId])) {

                // số lượng mới sau khi cộng
                $newQty = $_SESSION[$this->sessionKey][$productId]['quantity'] + $quantity;

                // check vượt kho
                if ($newQty > $stock) {
                    throw new Exception("Số lượng vượt quá tồn kho");
                }

                // cập nhật lại số lượng
                $_SESSION[$this->sessionKey][$productId]['quantity'] = $newQty;

            } else {
                // nếu chưa có thì thêm mới

                // check đủ hàng không
                if ($quantity > $stock) {
                    throw new Exception("Không đủ hàng trong kho");
                }

                // lưu vào session
                $_SESSION[$this->sessionKey][$productId] = [
                    'product_id' => $productId,
                    'name'       => $product['name'],
                    'price'      => $product['price'],
                    'quantity'   => $quantity
                ];
            }

            // trả về item vừa thêm/cập nhật
            return $this->response(true, "Thêm thành công", $_SESSION[$this->sessionKey][$productId]);

        } catch (Throwable $e) {
            // bắt lỗi để không crash hệ thống
            return $this->response(false, $e->getMessage());
        }
    }


    // xoá 1 sản phẩm
    public function remove(int $productId): array
    {
        // check có tồn tại trong giỏ không
        if (!isset($_SESSION[$this->sessionKey][$productId])) {
            return $this->response(false, "Sản phẩm không có trong giỏ");
        }

        // xoá khỏi session
        unset($_SESSION[$this->sessionKey][$productId]);

        return $this->response(true, "Đã xoá sản phẩm");
    }


    // cập nhật số lượng
    public function update(int $productId, int $quantity): array
    {
        try {
            // check sản phẩm có trong giỏ không
            if (!isset($_SESSION[$this->sessionKey][$productId])) {
                throw new Exception("Không có sản phẩm trong giỏ");
            }

            // nếu = 0 thì xoá luôn cho nhanh
            if ($quantity == 0) {
                return $this->remove($productId);
            }

            // không cho số âm
            if ($quantity < 0) {
                throw new Exception("Số lượng không hợp lệ");
            }

            // check tồn kho
            $stock = $this->inventoryModel->getStock($productId);

            if ($quantity > $stock) {
                throw new Exception("Vượt quá số lượng trong kho");
            }

            // cập nhật lại
            $_SESSION[$this->sessionKey][$productId]['quantity'] = $quantity;

            return $this->response(true, "Cập nhật thành công", $_SESSION[$this->sessionKey][$productId]);

        } catch (Throwable $e) {
            return $this->response(false, $e->getMessage());
        }
    }


    // xoá toàn bộ giỏ
    public function clear(): array
    {
        // reset về rỗng
        $_SESSION[$this->sessionKey] = [];

        return $this->response(true, "Đã xoá toàn bộ giỏ hàng");
    }


    // tính tổng tiền
    public function getTotal(): array
    {
        $total = 0;

        // duyệt từng item trong giỏ
        foreach ($_SESSION[$this->sessionKey] as $item) {

            // cộng dồn tiền = giá * số lượng
            $total += $item['price'] * $item['quantity'];
        }

        // gọi qua promotion để tính giảm giá
        $discount = $this->promotionModel->calculateDiscount($total);

        return $this->response(true, "Tính tổng thành công", [
            'subtotal' => $total,            // tiền gốc
            'discount' => $discount,        // tiền giảm
            'total'    => $total - $discount // tiền sau giảm
        ]);
    }
}
