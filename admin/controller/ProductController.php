<?php
// Đi ngược ra 2 lần để vào thư mục models/services ở gốc
require_once __DIR__ . '/../../models/ProductModel.php';
require_once __DIR__ . '/../../models/CategoryModel.php';

class ProductController {
    private $productModel;
    private $categoryModel;

    public function __construct() {
        $this->productModel = new ProductModel();
        $this->categoryModel = new CategoryModel();
    }

    // 1. Trang danh sách sản phẩm
    public function index() {
        $products = $this->productModel->getAll();
        include 'views/product/index.php';
    }

    // 2. Trang form thêm sản phẩm
    public function create() {
        $categories = $this->categoryModel->getAll();
        include 'views/product/create.php';
    }

    // 3. Xử lý lưu sản phẩm
    public function store() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $imageName = 'default.jpg';

            if (!empty($_FILES['image']['name'])) {
                $imageName = time() . '_' . $_FILES['image']['name'];
                move_uploaded_file($_FILES['image']['tmp_name'], '../assets/images/products/' . $imageName);
            }

            $data = [
                'name'        => $_POST['name'],
                'price'       => $_POST['price'],
                'category_id' => $_POST['category_id'],
                'description' => $_POST['description'],
                'image'       => $imageName,
                'stock'       => (int)($_POST['stock'] ?? 0)  // ← THÊM DÒNG NÀY
            ];

            $this->productModel->insert($data);
            header("Location: index.php?controller=product&action=index");
            exit();
        }
    }

    // 4. Xóa sản phẩm
    public function delete() {
        $id = $_GET['id'] ?? null;
        if ($id) {
            $this->productModel->delete($id);
        }
        header("Location: index.php?controller=product&action=index");
        exit();
    }
    // 4. Hiển thị form sửa sản phẩm
    public function edit($id = null) {
        if ($id === null) {
            // Lấy id từ GET nếu có
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        }

        if ($id <= 0) {
            // Chuyển hướng về danh sách sản phẩm với thông báo lỗi
            header('Location: index.php?controller=product&action=index&error=missing_id');
            exit;
        }

        $product = $this->productModel->getById($id);

        // ===== THÊM DÒNG NÀY: Lấy danh sách danh mục =====
        $categoryModel = new CategoryModel();
        $categories = $categoryModel->getAll();
        // ================================================

        include __DIR__ . '/../../views/layout/header.php';

        // Lấy slug của danh mục sản phẩm
        $category_slug = $product['category_slug'] ?? '';
        $spec_fields   = $spec_config[$category_slug] ?? [];
        $current_specs = json_decode($product->specifications ?? '{}', true);

        require 'views/product/edit.php';
    }

// 5. Xử lý cập nhật vào DB
    public function update() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $product_id = (int) $_POST['product_id']; // ✅ lấy đúng field
            $cat_id     = (int) ($_POST['cat_id'] ?? 0);
            $specs      = $_POST['specs'] ?? [];
            $old_image  = $_POST['old_image'];
            $imageName  = $old_image;

            if (!empty($_FILES['image']['name'])) {
                $imageName = time() . '_' . $_FILES['image']['name'];
                move_uploaded_file($_FILES['image']['tmp_name'], '../assets/images/products/' . $imageName);
            }

            $data = [
                'id'          => $product_id, // ✅ dùng $product_id thay vì $_POST['id']
                'name'        => $_POST['name'],
                'price'       => $_POST['price'],
                'category_id' => $_POST['category_id'],
                'description' => $_POST['description'],
                'image'       => $imageName
            ];

            $this->productModel->update($data);
            $this->productModel->updateSpecifications($product_id, $specs);
            if ($cat_id > 0) {
                header("Location: index.php?controller=category&action=index&cat_id={$cat_id}");
            } else {
                header("Location: index.php?controller=product&action=index");
            }
            exit();
        }
    }

    // Cập nhật số lượng tồn kho
    public function updateStock() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id    = (int)$_POST['product_id'];
            $stock = (int)$_POST['stock'];

            if ($id > 0 && $stock >= 0) {
                $this->productModel->updateStock($id, $stock);
            }

            header("Location: index.php?controller=product&action=index");
            exit();
        }
    }
}