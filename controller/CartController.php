<?php
require_once __DIR__ . '/BaseController.php';
// Đi ngược ra thư mục gốc (..) rồi mới đi vào thư mục services
require_once __DIR__ . '/../services/CartService.php';
require_once __DIR__ . '/../models/ProductModel.php';

class CartController extends BaseController {
    private $cartService;
    private $productModel;
    public function __construct() {
        parent::__construct();
        // CartService cần ProductModel để check giá thực tế
        $this->cartService = new CartService(new ProductModel());
    }

    /**
     * Hiển thị trang giỏ hàng (views/cart/index.php)
     */
    public function index() {
        $this->renderView('cart/index', [
            'pageTitle' => 'Giỏ hàng của bạn',
            'cartItems' => $this->cartService->getCartDetails(),
            'totalPrice' => $this->cartService->getTotalPrice()
        ]);
    }

    /**
     * Xử lý thêm vào giỏ bằng AJAX
     */
    public function addAjax() {
        $productId = (int)($_POST['product_id'] ?? 0);
        $quantity = (int)($_POST['quantity'] ?? 1);

        if ($productId > 0) {
            $this->cartService->addToCart($productId, $quantity);
            $this->jsonResponse('success', 'Đã thêm vào giỏ hàng', [
                'count' => $this->cartService->countItems()
            ]);
        } else {
            $this->jsonResponse('error', 'Sản phẩm không hợp lệ');
        }
    }
    /**
     * Xử lý thêm vào giỏ hàng từ Form (Trang chủ & Tab Linh kiện)
     */
    public function add() {
        // Lấy dữ liệu từ POST
        $productId = (int)($_POST['product_id'] ?? 0);
        $quantity = (int)($_POST['quantity'] ?? 1);

        if ($productId > 0) {
            // Gọi Service để lưu vào Session
            $this->cartService->addToCart($productId, $quantity);
            
            // Cập nhật lại số lượng giỏ hàng hiển thị ở Header (nếu cần)
            $_SESSION['cart_count'] = $this->cartService->countItems();
            
            // Sau khi thêm xong, chuyển hướng thẳng đến trang giỏ hàng
            header("Location: index.php?controller=cart");
            exit();
        } else {
            // Nếu có lỗi, quay lại trang trước đó
            header("Location: " . $_SERVER['HTTP_REFERER']);
            exit();
        }
    }
    public function delete() {
        $productId = $_GET['id'] ?? null;
        if ($productId) {
            unset($_SESSION['cart'][$productId]);

            // ✅ Thêm dòng này để đồng bộ lại count
            $_SESSION['cart_count'] = $this->cartService->countItems();
        }
        header("Location: index.php?controller=cart");
        exit();
    }

    public function buyNow() {
        $productId = $_GET['id'] ?? 0;

        // Lưu riêng vào session buynow, KHÔNG đụng vào $_SESSION['cart']
        $_SESSION['buynow'] = [$productId => 1];

        header("Location: index.php?controller=order&action=checkoutNow");
        exit();
    }
public function updateAjax() {
        // Xóa bộ đệm để trả về JSON sạch
        if (ob_get_length()) ob_clean();

        $productId = (int)($_POST['product_id'] ?? 0);
        $quantity = (int)($_POST['quantity'] ?? 1);

        if ($productId > 0) {
            // Sử dụng Service để cập nhật (Đồng bộ cấu trúc dữ liệu)
            $this->cartService->updateQuantity($productId, $quantity);
            
            // Lấy dữ liệu mới nhất sau khi cập nhật
            $total = $this->cartService->getTotalPrice();
            $count = $this->cartService->countItems();
            
            $_SESSION['cart_count'] = $count;

            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'success',
                'data' => [
                    'total' => $total,
                    'count' => $count
                ]
            ]);
            exit;
        }
        
        echo json_encode(['status' => 'error', 'message' => 'Dữ liệu không hợp lệ']);
        exit;
    }
}