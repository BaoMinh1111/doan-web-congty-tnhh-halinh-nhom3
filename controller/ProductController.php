<?php
require_once 'BaseController.php';
require_once __DIR__ . '/../models/ProductModel.php';
require_once __DIR__ . '/../models/CategoryModel.php';

class ProductController extends BaseController {
    private $productModel;
    private $categoryModel;

    public function __construct() {
        parent::__construct(); // Khởi tạo Session qua BaseController
        $this->productModel = new ProductModel();
        $this->categoryModel = new CategoryModel();
    }

    /**
     * Hiển thị danh sách sản phẩm theo Danh mục
     * URL ví dụ: index.php?controller=product&action=category&id=1
     */
    public function category($categoryId) {
    $categoryId = (int)$categoryId;
    
    // 1. Lấy thông tin danh mục hiện tại
    $category = $this->categoryModel->getById($categoryId);
    if (!$category) {
        $this->redirect('index.php');
    }

    // 2. GỌI MODEL ĐÃ CHUẨN HÓA (Thay cho đoạn SQL prepare cũ)
    // Lúc này $productList sẽ chứa đủ cả sản phẩm mới thêm
    $productList = $this->productModel->getByCategoryId($categoryId);

    // 3. Lấy danh sách tất cả danh mục để làm Sidebar
    $allCategories = $this->categoryModel->getAll();

    $this->renderView('product/category', [
        'pageTitle' => 'Danh mục: ' . ($category['name'] ?? 'Sản phẩm'),
        'currentCategory' => $category,
        'products' => $productList,
        'categories' => $allCategories
    ]);
}

    /**
     * Hiển thị Chi tiết một sản phẩm
     * URL ví dụ: index.php?controller=product&action=detail&id=10
     */
    public function detail($id) {
        $id = (int)$id;
        $product = $this->productModel->getById($id);

        if (!$product) {
            $this->redirect('index.php');
        }

        // Lấy các sản phẩm liên quan (cùng danh mục, trừ sản phẩm hiện tại)
        $sql = "SELECT * FROM products WHERE category_id = :cid AND id != :id LIMIT 4";
        $related = Database::getInstance()->getConnection()->prepare($sql);
        $related->execute([':cid' => $product['category_id'], ':id' => $id]);

        $this->renderView('product/detail', [
            'pageTitle' => $product['name'],
            'product' => $product,
            'relatedProducts' => $related->fetchAll()
        ]);
    }
    public function search() {
    $keyword = $_GET['keyword'] ?? '';
    
    // Gọi Model để tìm kiếm (không phân biệt hoa thường nhờ SQL LIKE)
    $products = $this->productModel->searchByName($keyword);
    $categories = $this->categoryModel->getAll();

    $this->renderView('product/search_results', [
        'pageTitle' => "Kết quả tìm kiếm cho: '$keyword'",
        'products' => $products,
        'keyword' => $keyword,
        'categories' => $categories
    ]);
}
public function suggestAjax() {
    $keyword = $_GET['keyword'] ?? '';
    
    if (!empty($keyword)) {
        // Tìm kiếm sản phẩm theo từ khóa
        $products = $this->productModel->searchByName($keyword);
        
        if (!empty($products)) {
            foreach ($products as $product) {
                // Trả về các dòng HTML để hiển thị trong bảng gợi ý
                echo '<a href="index.php?controller=product&action=detail&id='.$product['id'].'" 
                         class="list-group-item list-group-item-action py-2">';
                echo '  <div class="d-flex align-items-center">';
                echo '      <img src="assets/images/products/'.$product['image'].'" style="width: 30px; height: 30px; object-fit: contain;" class="me-2">';
                echo '      <span class="small text-truncate">'.$product['name'].'</span>';
                echo '  </div>';
                echo '</a>';
            }
        } else {
            echo '<div class="list-group-item small text-muted">Không tìm thấy sản phẩm nào...</div>';
        }
    }
    exit(); // Kết thúc để không load thêm giao diện khác
}
}