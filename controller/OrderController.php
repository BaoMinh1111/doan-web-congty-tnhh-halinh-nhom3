<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/OrderModel.php';
require_once __DIR__ . '/../services/CartService.php';

class OrderController extends BaseController {
    private $orderModel;
    private $cartService;

    public function __construct() {
        parent::__construct();
        $this->orderModel  = new OrderModel();
        $productModel      = new ProductModel();
        $this->cartService = new CartService($productModel);
    }

    // =========================================================
    //  CHECKOUT THƯỜNG (từ giỏ hàng)
    // =========================================================

    public function checkout() {
        $cartItems = $this->cartService->getCartDetails();

        if (empty($cartItems)) {
            $_SESSION['error'] = "Giỏ hàng của bạn đang trống. Vui lòng chọn sản phẩm trước khi thanh toán!";
            header("Location: index.php?controller=cart");
            exit();
        }

        $this->renderCheckoutView($cartItems);
    }

    // =========================================================
    //  CHECKOUT CÓ CHỌN SẢN PHẨM (từ trang giỏ hàng mới)
    // =========================================================

    /**
     * Nhận danh sách product_id đã tick từ form giỏ hàng,
     * chỉ checkout những sản phẩm đó.
     */
    public function checkoutSelected() {
        if (empty($_GET['selected_ids'])) {
            $_SESSION['error'] = "Vui lòng chọn ít nhất 1 sản phẩm để thanh toán.";
            header("Location: index.php?controller=cart");
            exit();
        }

        $selectedIds = array_filter(
            array_map('intval', explode(',', $_GET['selected_ids']))
        );
        if (empty($selectedIds)) {
            $_SESSION['error'] = "Không có sản phẩm hợp lệ được chọn.";
            header("Location: index.php?controller=cart");
            exit();
        }

        // Lấy toàn bộ giỏ hàng rồi lọc theo ID đã chọn
        $allItems  = $this->cartService->getCartDetails();
        $cartItems = array_values(array_filter($allItems, function($item) use ($selectedIds) {
            return in_array((int)$item['product']['id'], $selectedIds);
        }));

        if (empty($cartItems)) {
            $_SESSION['error'] = "Không tìm thấy sản phẩm đã chọn trong giỏ hàng.";
            header("Location: index.php?controller=cart");
            exit();
        }

        // Lưu tạm danh sách sản phẩm đã chọn vào session để dùng ở placeOrder
        $_SESSION['selected_cart'] = $selectedIds;

        $this->renderCheckoutView($cartItems, ['isSelected' => true]);
    }

    // =========================================================
    //  MUA NGAY (bỏ qua giỏ hàng)
    // =========================================================

    public function checkoutNow() {
        if (empty($_SESSION['buynow'])) {
            header("Location: index.php");
            exit();
        }

        $cartItems = $this->getBuyNowItems();

        $this->renderCheckoutView($cartItems, [
            'pageTitle' => 'Mua ngay',
            'isBuyNow'  => true,
        ]);
    }

    // =========================================================
    //  ÁP DỤNG MÃ KHUYẾN MÃI
    // =========================================================

    public function applyPromotion() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $code       = trim($_POST['promo_code'] ?? '');
            $orderTotal = floatval($_POST['order_total'] ?? 0);

            $promo = $this->orderModel->getPromotionByCode($code);

            if ($promo) {
                $now = date('Y-m-d H:i:s');
                if ($now < $promo['start_date'] || $now > $promo['end_date']) {
                    echo json_encode(['success' => false, 'message' => 'Mã đã hết hạn!']);
                    exit;
                }
                if ($orderTotal < $promo['min_order_amount']) {
                    echo json_encode(['success' => false, 'message' => 'Đơn tối thiểu ' . number_format($promo['min_order_amount']) . 'đ']);
                    exit;
                }

                $discount = ($promo['type'] === 'percentage')
                    ? ($orderTotal * $promo['value'] / 100)
                    : $promo['value'];

                echo json_encode([
                    'success'   => true,
                    'discount'  => $discount,
                    'new_total' => $orderTotal - $discount,
                    'message'   => 'Áp dụng mã thành công!',
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Mã không hợp lệ!']);
            }
            exit;
        }
    }

    // =========================================================
    //  ĐẶT HÀNG
    // =========================================================

    public function placeOrder() {
        $isBuyNow  = !empty($_SESSION['buynow']);
        $isSelected = !empty($_SESSION['selected_cart']); // luồng chọn lẻ

        // Lấy đúng danh sách sản phẩm theo luồng
        if ($isBuyNow) {
            $cartItems = $this->getBuyNowItems();
        } elseif ($isSelected) {
            $selectedIds = $_SESSION['selected_cart'];
            $allItems    = $this->cartService->getCartDetails();
            $cartItems   = array_values(array_filter($allItems, function($item) use ($selectedIds) {
                return in_array((int)$item['product']['id'], $selectedIds);
            }));
        } else {
            $cartItems = $this->cartService->getCartDetails();
        }

        if (empty($cartItems)) {
            $_SESSION['error'] = "Giỏ hàng trống, không thể đặt hàng.";
            header("Location: index.php?controller=cart");
            exit();
        }

        $totalPrice = 0;
        foreach ($cartItems as $item) {
            $totalPrice += $item['subtotal'];
        }

        // Xử lý mã giảm giá ở backend
        $promoCode = trim($_POST['applied_promo_code'] ?? '');
        $discount  = 0;
        if (!empty($promoCode)) {
            $promo = $this->orderModel->getPromotionByCode($promoCode);
            if ($promo && $totalPrice >= $promo['min_order_amount']) {
                $now = date('Y-m-d H:i:s');
                if ($now >= $promo['start_date'] && $now <= $promo['end_date']) {
                    $discount = ($promo['type'] === 'percentage')
                        ? ($totalPrice * $promo['value'] / 100)
                        : $promo['value'];
                }
            }
        }
        $finalTotal = $totalPrice - $discount;

        $userId = isset($_SESSION['user']['id']) ? $_SESSION['user']['id'] : null;

        $orderData = [
            'user_id'    => $userId,
            'full_name'  => trim($_POST['full_name']
                ?? $_POST['fullname']
                ?? $_POST['receiver_name']
                ?? ($_SESSION['user']['fullname'] ?? 'Khách vãng lai')),
            'email'      => trim($_POST['email']
                ?? $_POST['receiver_email']
                ?? ($_SESSION['user']['email'] ?? '')),
            'phone'      => trim($_POST['phone']
                ?? $_POST['receiver_phone']
                ?? ($_SESSION['user']['phone'] ?? '')),
            'address'    => trim($_POST['address']
                ?? $_POST['receiver_address']
                ?? ($_SESSION['user']['address'] ?? '')),
            'note'       => trim($_POST['note'] ?? ''),
            'total_amt'  => $finalTotal,
            'status'     => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
        ];

        if (empty($orderData['full_name']) || empty($orderData['phone']) || empty($orderData['address'])) {
            $_SESSION['error'] = "Vui lòng điền đầy đủ thông tin giao hàng.";
            header("Location: index.php?controller=order&action=checkout");
            exit();
        }

        $orderId = $this->orderModel->createOrder($orderData, $cartItems);

        if (!$orderId) {
            die("Lỗi hệ thống: Không thể tạo đơn hàng.");
        }

        // Dọn session theo đúng luồng
        if ($isBuyNow) {
            unset($_SESSION['buynow']);
            $_SESSION['is_buynow'] = true;
        } elseif ($isSelected) {
            // Luồng chọn lẻ: xóa flag selected, giữ nguyên giỏ hàng
            // (confirmSuccess sẽ chỉ xóa đúng những sản phẩm đã mua)
            $_SESSION['is_selected']    = true;
            $_SESSION['ordered_ids']    = $selectedIds;
            unset($_SESSION['selected_cart']);
        }

        $this->renderView('order/payment', [
            'pageTitle'  => 'Thanh toán đơn hàng #' . $orderId,
            'order'      => $orderData,
            'orderId'    => $orderId,
            'cartItems'  => $cartItems,
            'discount'   => $discount,
            'finalTotal' => $finalTotal,
        ]);
    }

    // =========================================================
    //  SAU KHI THANH TOÁN
    // =========================================================

    public function confirmSuccess() {
        $orderId = isset($_GET['id'])     ? (int)$_GET['id'] : 0;
        $method  = isset($_GET['method']) ? $_GET['method']  : 'COD';

        $order      = $this->orderModel->getById($orderId);
        $orderItems = $this->orderModel->getItemsByOrderId($orderId);

        if (!$order) {
            die("Không tìm thấy đơn hàng #$orderId");
        }

        $originalTotal = 0;
        foreach ($orderItems as $item) {
            $originalTotal += $item['price_at_purchase'] * $item['quantity'];
        }
        $discount   = $originalTotal - $order['total_price'];
        $finalTotal = $order['total_price'];

        // Xóa cart TRƯỚC khi render để header/navbar cập nhật đúng
        if (!empty($_SESSION['is_buynow'])) {
            // Mua ngay: chỉ xóa flag, giữ nguyên giỏ hàng
            unset($_SESSION['is_buynow']);

        } elseif (!empty($_SESSION['is_selected'])) {
            // Chọn lẻ: chỉ xóa những sản phẩm đã mua khỏi giỏ hàng
            $orderedIds = $_SESSION['ordered_ids'] ?? [];
            if (!empty($orderedIds) && isset($_SESSION['cart'])) {
                foreach ($orderedIds as $pid) {
                    unset($_SESSION['cart'][$pid]);
                }
            }
            unset($_SESSION['is_selected'], $_SESSION['ordered_ids']);

        } else {
            // Checkout thường: xóa toàn bộ giỏ hàng
            unset($_SESSION['cart']);
        }

        $this->renderView('order/thanks', [
            'pageTitle'     => 'Đặt hàng thành công - Hà Linh Tech',
            'order'         => $order,
            'orderItems'    => $orderItems,
            'method'        => $method,
            'originalTotal' => $originalTotal,
            'discount'      => $discount,
            'finalTotal'    => $finalTotal,
        ]);
    }

    // =========================================================
    //  LỊCH SỬ & CHI TIẾT ĐƠN HÀNG
    // =========================================================

    public function history() {
        if (!isset($_SESSION['user'])) {
            $this->redirect('index.php?controller=auth&action=login');
        }

        $orders = $this->orderModel->getOrdersByUserId($_SESSION['user']['id']);

        require_once 'models/CategoryModel.php';
        $categories = (new CategoryModel())->getAll();

        require_once 'views/layout/header.php';
        $this->renderView('order/history', [
            'pageTitle'  => 'Lịch sử mua hàng - Hà Linh Tech',
            'orders'     => $orders,
            'categories' => $categories,
        ]);
        require_once 'views/layout/footer.php';
    }

    public function detail() {
        if (!isset($_SESSION['user'])) {
            header("Location: index.php?controller=auth&action=login");
            exit();
        }

        $orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $order   = $this->orderModel->getById($orderId);

        if (!$order || $order['user_id'] != $_SESSION['user']['id']) {
            die("Bạn không có quyền xem đơn hàng này hoặc đơn hàng không tồn tại.");
        }

        $orderItems = $this->orderModel->getItemsByOrderId($orderId);

        require_once 'models/CategoryModel.php';
        $categories = (new CategoryModel())->getAll();

        require_once 'views/layout/header.php';
        $this->renderView('order/detail', [
            'pageTitle'  => 'Chi tiết đơn hàng #' . $orderId,
            'order'      => $order,
            'orderItems' => $orderItems,
            'categories' => $categories,
        ]);
        require_once 'views/layout/footer.php';
    }

    // =========================================================
    //  HELPER PRIVATE
    // =========================================================

    /**
     * Render view checkout dùng chung cho checkout(), checkoutSelected(), checkoutNow().
     * $extra cho phép override pageTitle và truyền thêm flag (isSelected, isBuyNow...).
     */
    private function renderCheckoutView(array $cartItems, array $extra = []) {
        $totalPrice = array_sum(array_column($cartItems, 'subtotal'));

        $userData = null;
        if (isset($_SESSION['user'])) {
            require_once __DIR__ . '/../models/UserModel.php';
            $userData = (new UserModel())->getById($_SESSION['user']['id']);
        }

        $this->renderView('order/checkout', array_merge([
            'pageTitle'  => 'Thanh toán đơn hàng',
            'cartItems'  => $cartItems,
            'totalPrice' => $totalPrice,
            'user'       => $userData,
        ], $extra));
    }

    private function getBuyNowItems(): array {
        $cartBackup       = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
        $_SESSION['cart'] = $_SESSION['buynow'];
        $items            = $this->cartService->getCartDetails();
        $_SESSION['cart'] = $cartBackup;
        return $items;
    }

    // =========================================================
//  HỦY ĐƠN HÀNG (chỉ khi đang pending)
// =========================================================
    public function cancel() {
        if (!isset($_SESSION['user'])) {
            header("Location: index.php?controller=auth&action=login");
            exit();
        }

        $orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $order   = $this->orderModel->getById($orderId);

        // Kiểm tra đơn hàng có tồn tại và thuộc về user này không
        if (!$order || $order['user_id'] != $_SESSION['user']['id']) {
            $_SESSION['error'] = "Không tìm thấy đơn hàng.";
            header("Location: index.php?controller=order&action=history");
            exit();
        }

        // Chỉ cho hủy khi đang ở trạng thái pending
        if ($order['status'] !== 'pending') {
            $_SESSION['error'] = "Chỉ có thể hủy đơn hàng đang chờ xử lý.";
            header("Location: index.php?controller=order&action=history");
            exit();
        }

        // Cập nhật trạng thái thành cancelled
        $this->orderModel->updateStatus($orderId, 'cancelled');

        // Hoàn lại số lượng tồn kho
        require_once __DIR__ . '/../models/InventoryModel.php';
        $inventoryModel = new InventoryModel();
        $inventoryModel->restoreStockAfterCancel($orderId);

        $_SESSION['success'] = "Đơn hàng #$orderId đã được hủy thành công.";
        header("Location: index.php?controller=order&action=history");
        exit();
    }
}