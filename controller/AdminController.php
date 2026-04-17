<?php
class AdminController {
    private $productModel;
    private $categoryModel;
    private $orderModel;
    private $authService;

    public function __construct() {
        $this->productModel = new ProductModel();
        $this->categoryModel = new CategoryModel();
        $this->orderModel = new OrderModel();
        
        // Khởi tạo AuthService để kiểm tra quyền
        $this->authService = new AuthService(new UserModel());
        $this->checkAdmin();
    }

    /**
     * Bảo mật: Chỉ cho phép Admin truy cập
     */
    private function checkAdmin() {
        if (!$this->authService->checkSession() || $_SESSION['user']['role'] !== 'admin') {
            header('Location: login.php');
            exit();
        }
    }

    /**
     * Quản lý Sản phẩm (Hiển thị danh sách)
     */
    public function listProducts() {
        $products = $this->productModel->getAll();
        // Trả về JSON nếu là yêu cầu AJAX hoặc include view Bootstrap
        return $products;
    }

    /**
     * Thêm sản phẩm mới (Xử lý logic từ Form Admin)
     */
    public function addProduct($postData, $fileData) {
        // Logic xử lý upload ảnh đơn giản
        $imageName = time() . '_' . $fileData['image']['name'];
        move_uploaded_file($fileData['image']['tmp_name'], "../assets/images/products/" . $imageName);
        
        $postData['image'] = $imageName;
        return $this->productModel->add($postData);
    }

    /**
     * Quản lý Đơn hàng: Xem tất cả đơn hàng từ khách
     */
    public function listOrders() {
        // Giả sử BaseModel có hàm fetchAll đơn giản cho các bảng
        $sql = "SELECT o.*, c.full_name FROM orders o 
                JOIN customers c ON o.customer_id = c.id 
                ORDER BY o.created_at DESC";
        return Database::getInstance()->getConnection()->query($sql)->fetchAll();
    }

    /**
     * Duyệt đơn hàng: Chuyển từ 'pending' sang 'shipped'
     */
    public function updateOrderStatus($orderId, $status) {
        $sql = "UPDATE orders SET status = :status WHERE id = :id";
        $stmt = Database::getInstance()->getConnection()->prepare($sql);
        return $stmt->execute([':status' => $status, ':id' => $orderId]);
    }
}