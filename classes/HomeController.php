<?php

require_once __DIR__ . '/../services/ProductService.php';

/**
 * Class HomeController
 *
 * Xử lý các trang công khai dành cho người dùng:
 *   - Trang chủ: hiển thị sản phẩm nổi bật + danh sách sản phẩm có thể lọc theo danh mục.
 *   - Hỗ trợ lọc theo danh mục trực tiếp từ trang chủ qua ?category_id=N.
 *
 * Kế thừa BaseController → dùng renderView(), redirect(), get()...
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
     * Trang chủ: hiển thị sản phẩm nổi bật + danh sách sản phẩm.
     * Hỗ trợ lọc theo danh mục qua query string: ?category_id=3
     *
     * Luồng xử lý:
     *   - Không có category_id  → hiển thị tất cả sản phẩm + 8 sản phẩm nổi bật.
     *   - Có category_id hợp lệ → lọc sản phẩm theo danh mục đó, ẩn section nổi bật.
     *   - Có category_id nhưng không tồn tại → redirect về trang chủ không tham số.
     *
     * Dữ liệu truyền vào view:
     *   $products        → array[]              sản phẩm hiển thị (tất cả hoặc đã lọc)
     *   $featured        → array[]|null         sản phẩm nổi bật (null khi đang lọc)
     *   $categories      → CategoryEntity[]     tất cả danh mục (menu sidebar / tab lọc)
     *   $currentCategory → CategoryEntity|null  danh mục đang lọc (null = không lọc)
     *   $categoryId      → int                  ID danh mục đang lọc (0 = không lọc)
     *   $pageTitle       → string
     *
     * Cách dùng trong router:
     *   $controller->index();   // đọc category_id từ $_GET tự động
     */
    public function index(): void
    {
        $categoryId = $this->get('category_id', 0);
        $categories = $this->productService->getAllCategories();

        if ($categoryId > 0) {
            // --- CHẾ ĐỘ LỌC THEO DANH MỤC ---

            // Kiểm tra category có thực sự tồn tại không
            $currentCategory = null;
            foreach ($categories as $cat) {
                if ($cat->getId() === $categoryId) {
                    $currentCategory = $cat;
                    break;
                }
            }

            // Category không tồn tại → về trang chủ, xóa param rác khỏi URL
            if ($currentCategory === null) {
                $this->redirect('/');
            }

            $products  = $this->productService->searchAndFilter('', $categoryId);
            $featured  = null; // Ẩn section nổi bật khi đang lọc — không liên quan
            $pageTitle = $currentCategory->getName() . ' — Hà Linh Tech';

        } else {
            // --- CHẾ ĐỘ TRANG CHỦ ĐẦY ĐỦ ---
            $products        = $this->productService->getProductsWithCategory();
            $featured        = $this->productService->getFeatured(8);
            $currentCategory = null;
            $pageTitle       = 'Trang chủ — Hà Linh Tech';
        }

        $this->renderView('home/index', [
            'products'        => $products,
            'featured'        => $featured,
            'categories'      => $categories,
            'currentCategory' => $currentCategory,
            'categoryId'      => $categoryId,
            'pageTitle'       => $pageTitle,
        ]);
    }
}
