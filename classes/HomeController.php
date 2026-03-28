<?php

// require_once đã được xóa — để bootstrap.php lo việc này.

/**
 * Class HomeController
 *
 * Xử lý các trang công khai:
 *   - Trang chủ đầy đủ (tất cả sản phẩm + nổi bật).
 *   - Trang chủ chế độ lọc danh mục (?category_id=N), dùng chung view.
 *
 * Kế thừa BaseController → renderView(), redirect(), get()...
 *
 * @package App\Controllers
 * @author  Ha Linh Technology Solutions
 */
class HomeController extends BaseController
{
    private ProductService $productService;

    public function __construct(ProductService $productService)
    {
        parent::__construct();
        $this->productService = $productService;
    }


    /**
     * Trang chủ — hỗ trợ lọc theo danh mục qua ?category_id=N.
     *
     * Luồng:
     *   - Không có category_id  → hiển thị tất cả sản phẩm + 8 sản phẩm nổi bật.
     *   - Có category_id hợp lệ → lọc sản phẩm theo danh mục, ẩn section nổi bật.
     *   - category_id không tồn tại → redirect về / (xóa param rác).
     *
     * Dữ liệu view:
     *   $products        → array[]             sản phẩm hiển thị
     *   $featured        → array[]|null        nổi bật (null khi đang lọc)
     *   $categories      → CategoryEntity[]    tất cả danh mục (sidebar / tab lọc)
     *   $currentCategory → CategoryEntity|null danh mục đang lọc
     *   $categoryId      → int                 0 = không lọc
     *   $pageTitle       → string
     */
    public function index(): void
    {
        $categoryId = $this->get('category_id', 0);
        $categories = $this->productService->getAllCategories();

        if ($categoryId > 0) {
            // ── CHẾ ĐỘ LỌC THEO DANH MỤC ──────────────────────────

            // Fix foreach trong Controller: dùng getCategoryById() thay vì tự loop
            // → Controller không làm việc của Service/Model
            $currentCategory = $this->productService->getCategoryById($categoryId);

            if ($currentCategory === null) {
                // Category không tồn tại → về trang chủ, xóa param rác
                $this->redirect('/');
                return; // Fix: thêm return sau redirect()
            }

            // Fix searchAndFilter('', $id): dùng getByCategory() — intent rõ ràng hơn
            $products  = $this->productService->getByCategory($categoryId);
            $featured  = null; // Ẩn section nổi bật khi đang lọc
            $pageTitle = $currentCategory->getName() . ' — Hà Linh Tech';

        } else {
            // ── CHẾ ĐỘ TRANG CHỦ ĐẦY ĐỦ ───────────────────────────
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
