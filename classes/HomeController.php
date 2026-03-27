<?php

require_once __DIR__ . '/../services/ProductService.php';

/**
 * Class HomeController
 *
 * Xử lý các trang công khai dành cho người dùng:
 *   - Trang chủ (danh sách tất cả sản phẩm + sản phẩm nổi bật).
 *   - Trang lọc theo danh mục.
 *
 * Kế thừa BaseController → dùng renderView(), redirect(), get(), requireLogin()...
 * Nhận ProductService qua constructor (Dependency Injection).
 *
 * @package App\Controllers
 * @author  Ha Linh Technology Solutions
 */
class HomeController extends BaseController
{
    // THUỘC TÍNH

    /** @var ProductService */
    private ProductService $productService;


    // CONSTRUCTOR

    /**
     * @param ProductService $productService
     */
    public function __construct(ProductService $productService)
    {
        parent::__construct();
        $this->productService = $productService;
    }


    // ACTIONS

    /**
     * Trang chủ: hiển thị sản phẩm nổi bật + toàn bộ sản phẩm mới nhất.
     *
     * Dữ liệu truyền vào view:
     *   $featured   → array[] sản phẩm nổi bật (mới nhất, tối đa 8 sản phẩm)
     *   $products   → array[] tất cả sản phẩm kèm danh mục
     *   $categories → CategoryEntity[] tất cả danh mục (dùng cho menu sidebar)
     *
     * Cách dùng trong router:
     *   $controller->index();
     */
    public function index(): void
    {
        $featured   = $this->productService->getFeatured(8);
        $products   = $this->productService->getProductsWithCategory();
        $categories = $this->productService->getAllCategories();

        $this->renderView('home/index', [
            'featured'   => $featured,
            'products'   => $products,
            'categories' => $categories,
            'pageTitle'  => 'Trang chủ — Hà Linh Tech',
        ]);
    }

    /**
     * Trang danh sách sản phẩm theo danh mục.
     * Lấy categoryId từ query string: ?category_id=3
     *
     * Dữ liệu truyền vào view:
     *   $products        → array[] sản phẩm thuộc danh mục đó
     *   $categories      → CategoryEntity[] tất cả danh mục (menu sidebar)
     *   $currentCategory → CategoryEntity|null danh mục đang xem
     *
     * Cách dùng trong router:
     *   $controller->byCategory();   // lấy category_id từ $_GET tự động
     */
    public function byCategory(): void
    {
        // Dùng get() từ BaseController — tự động cast về int, mặc định 0
        $categoryId = $this->get('category_id', 0);

        if ($categoryId <= 0) {
            // Không có category_id hợp lệ → về trang chủ
            $this->redirect('/');
        }

        $products        = $this->productService->searchAndFilter('', $categoryId);
        $categories      = $this->productService->getAllCategories();
        $currentCategory = null;

        // Tìm danh mục hiện tại để hiển thị tên trên breadcrumb / title
        foreach ($categories as $cat) {
            if ($cat->getId() === $categoryId) {
                $currentCategory = $cat;
                break;
            }
        }

        $pageTitle = $currentCategory !== null
            ? $currentCategory->getName() . ' — Hà Linh Tech'
            : 'Danh mục — Hà Linh Tech';

        $this->renderView('home/category', [
            'products'        => $products,
            'categories'      => $categories,
            'currentCategory' => $currentCategory,
            'categoryId'      => $categoryId,
            'pageTitle'       => $pageTitle,
        ]);
    }
}

/* Các vấn đề cần sửa: 
* byCategory() không có return sau redirect() — pattern giống lỗi đã chỉ ở ProductController: Không crash vì redirect() gọi exit, nhưng cần thêm return; 
ngay sau để ý định rõ ràng và an toàn khi refactor. 
* byCategory() tìm $currentCategory bằng foreach trên $categories đã lấy — thừa 1 vòng lặp: Đã có $categories rồi nên không tốn thêm DB query. 
Nhưng vẫn có thể gọn hơn bằng cách thêm getCategoryById() vào ProductService, hoặc dùng array_filter.
* index() gọi 3 query DB riêng biệt: getFeatured(), getProductsWithCategory(), getAllCategories(): getProductsWithCategory() đã JOIN với categories — 
getAllCategories() có thể query lại bảng categories
* byCategory() dùng searchAndFilter('', $categoryId) để lấy sản phẩm theo danh mục — dùng sai method: searchAndFilter được thiết kế cho tìm kiếm, không phải 
lọc danh mục đơn thuần. Nên có ProductService::getByCategory($categoryId) riêng — rõ ý định hơn và dễ tối ưu query sau này.
*/
