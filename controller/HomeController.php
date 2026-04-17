<?php
require_once __DIR__ . '/BaseController.php'; // Cùng thư mục controller
require_once __DIR__ . '/../models/ProductModel.php'; // Đi ra ngoài rồi vào models
require_once __DIR__ . '/../models/CategoryModel.php';
require_once __DIR__ . '/../models/PostModel.php';

class HomeController extends BaseController {
    private $productModel;
    private $categoryModel;
    private $postModel;

    public function __construct() {
        // Chạy construct của BaseController để khởi động Session
        parent::__construct();

        $this->productModel = new ProductModel();
        $this->categoryModel = new CategoryModel();
        $this->postModel = new PostModel();
    }

    /**
     * Trang chủ (index.php)
     */
    public function index() {
        $categories = $this->categoryModel->getAll();
        $latestPosts = $this->postModel->getAll();

        // Phân trang
        $perPage     = 8;
        $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        if ($currentPage < 1) $currentPage = 1;

        $totalProducts  = $this->productModel->countAll();
        $totalPages     = ceil($totalProducts / $perPage);
        $offset         = ($currentPage - 1) * $perPage;

        $latestProducts = $this->productModel->getWithPagination($perPage, $offset);

        $this->renderView('home/index', [
            'pageTitle'   => 'Hà Linh Tech - Thế giới linh kiện cao cấp',
            'categories'  => $categories,
            'products'    => $latestProducts,
            'posts'       => $latestPosts,
            'currentPage' => $currentPage,
            'totalPages'  => $totalPages
        ]);
    }
    //     // 1. Lấy sản phẩm (Code cũ của Vũ)
    // $products = $this->productModel->getAll();

    // // 2. Lấy bài viết (Thêm đoạn này nè Vũ)
    // require_once 'models/PostModel.php';
    // $postModel = new PostModel();
    // $posts = $postModel->getAll(); // Hàm này mình đã viết ở Model bài trước rồi

    // // 3. Truyền cả 2 sang View
    // include 'views/home/index.php';


    /**
     * Chức năng tìm kiếm nhanh bằng AJAX (Yêu cầu số 3)
     */
    public function ajaxSearch() {
        $keyword = ValidatorHelper::sanitize($_GET['keyword'] ?? '');

        if (empty($keyword)) {
            $this->jsonResponse('error', 'Vui lòng nhập từ khóa');
        }

        $results = $this->productModel->search($keyword);
        $this->jsonResponse('success', 'Tìm thấy ' . count($results) . ' sản phẩm', $results);
    }
}