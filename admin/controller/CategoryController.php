<?php
require_once __DIR__ . '/../../models/CategoryModel.php';

class CategoryController {
    private $categoryModel;

    public function __construct() {
        $this->categoryModel = new CategoryModel();
    }

    // Liệt kê danh mục
    public function index() {
    $categories = $this->categoryModel->getAll();
    $products_by_cat = [];
    $current_cat_name = "";

    // Nếu Vũ nhấn vào một danh mục cụ thể (có cat_id trên URL)
    if (isset($_GET['cat_id'])) {
        $cat_id = $_GET['cat_id'];
        
        // Gọi ProductModel để lấy sản phẩm theo ID danh mục
        require_once __DIR__ . '/../../models/ProductModel.php';
        $productModel = new ProductModel();
        $products_by_cat = $productModel->getByCategoryId($cat_id); // Chúng ta sẽ viết hàm này sau
        
        // Tìm tên danh mục hiện tại để hiển thị lên tiêu đề
        foreach ($categories as $cat) {
            if ($cat['id'] == $cat_id) { $current_cat_name = $cat['name']; break; }
        }
    }

    include 'views/category/index.php';
}

    // Form thêm mới
    public function create() {
        include 'views/category/create.php';
    }

    // Lưu danh mục mới
    public function store() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = trim($_POST['name']);
        if (!empty($name)) {
            $result = $this->categoryModel->insert(['name' => $name]);
            
            // Dù thành công hay thất bại (do trùng tên), ta đều Redirect về index
            header("Location: index.php?controller=category&action=index");
            exit(); 
        }
    }
}

    // Xóa danh mục
    public function delete() {
        $id = $_GET['id'] ?? null;
        if ($id) {
            $this->categoryModel->delete($id);
        }
        header("Location: index.php?controller=category&action=index");
        exit();
    }
}