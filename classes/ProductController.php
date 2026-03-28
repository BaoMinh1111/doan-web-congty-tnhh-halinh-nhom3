<?php

// require_once đã được xóa — để bootstrap.php lo việc này.

/**
 * Class ProductController
 *
 * Xử lý các request liên quan đến sản phẩm:
 *   - Trang chi tiết sản phẩm (render view).
 *   - Tìm kiếm sản phẩm (trả JSON cho AJAX).
 *
 * Kế thừa BaseController → renderView(), jsonResponse(), redirect(), get(), isGet()...
 *
 * @package App\Controllers
 * @author  Ha Linh Technology Solutions
 */
class ProductController extends BaseController
{
    private ProductService $productService;

    public function __construct(ProductService $productService)
    {
        parent::__construct();
        $this->productService = $productService;
    }


    /**
     * Trang chi tiết sản phẩm.
     * Lấy product ID từ ?id=N.
     *
     * Dữ liệu view:
     *   $product    → ProductEntity
     *   $category   → CategoryEntity|null
     *   $categories → CategoryEntity[] (menu sidebar)
     */
    public function detail(): void
    {
        // Fix: thêm isGet() check — nhất quán với search(), tránh nhận POST/PUT
        if (!$this->isGet()) {
            $this->redirect('/');
            return;
        }

        $id = $this->get('id', 0);

        if ($id <= 0) {
            $this->redirect('/?error=invalid_product');
            return; // Fix: thêm return sau redirect()
        }

        $detail = $this->productService->getProductDetail($id);

        if ($detail === null) {
            $this->redirect('/?error=product_not_found');
            return; // Fix: thêm return sau redirect()
        }

        $categories = $this->productService->getAllCategories();

        $this->renderView('product/detail', [
            'product'    => $detail['product'],
            'category'   => $detail['category'],
            'categories' => $categories,
            'pageTitle'  => $detail['product']->getName() . ' — Hà Linh Tech',
        ]);
    }

    /**
     * Tìm kiếm sản phẩm — AJAX endpoint.
     * Query string: ?q=keyword&category_id=N
     *
     * Fix UX realtime search: bỏ điều kiện chặn cứng "cả 2 đều rỗng → 400".
     * Nếu cả 2 rỗng → trả mảng rỗng (FE tự quyết định khi nào gọi AJAX).
     *
     * Fix scope $categories/$results: khai báo trước try để tránh undefined var nếu catch chạy.
     *
     * Fix: không kèm getAllCategories() trong mỗi lần search — categories không đổi,
     * FE đã có sẵn từ lần load trang, không cần request lại mỗi keystroke.
     *
     * Response JSON thành công:
     *   { "success": true, "keyword": "rtx", "category_id": 2, "total": 3, "data": [...] }
     *
     * Response JSON lỗi:
     *   { "success": false, "message": "..." }
     */
    public function search(): void
    {
        if (!$this->isGet()) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Phương thức không hợp lệ. Chỉ chấp nhận GET.',
            ], 405);
            return;
        }

        $keyword    = trim($this->get('q', ''));
        $categoryId = $this->get('category_id', 0);

        // Fix: không validate keyword+category ở đây — để FE quyết định khi nào gọi.
        // Nếu cả 2 rỗng → trả mảng rỗng thay vì 400, tránh chặn UX tìm kiếm realtime.

        if (mb_strlen($keyword) > 100) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Từ khoá không được vượt quá 100 ký tự.',
            ], 400);
            return;
        }

        // Fix scope: khai báo trước try → tránh "undefined variable" nếu catch chạy
        $results = [];

        try {
            if ($keyword === '' && $categoryId <= 0) {
                // Cả 2 rỗng → trả rỗng, không query DB
                $results = [];
            } elseif ($keyword === '' && $categoryId > 0) {
                // Chỉ lọc danh mục — dùng getByCategory() thay vì searchAndFilter
                $results = $this->productService->getByCategory($categoryId);
            } else {
                // Có keyword (± categoryId)
                $results = $this->productService->searchAndFilter($keyword, $categoryId);
            }
        } catch (Throwable $e) {
            error_log('[ProductController::search] ' . $e->getMessage());
            $this->jsonResponse([
                'success' => false,
                'message' => 'Đã có lỗi xảy ra trong quá trình tìm kiếm. Vui lòng thử lại.',
            ], 500);
            return;
        }

        // Fix: bỏ getAllCategories() khỏi response —
        // categories không đổi theo keystroke, FE load 1 lần từ trang, không cần gửi lại.
        $data = array_map(fn(array $item) => [
            'product'  => $item['product']->toArray(),
            'category' => $item['category']?->toArray(),
        ], $results);

        $this->jsonResponse([
            'success'     => true,
            'keyword'     => $keyword,
            'category_id' => $categoryId,
            'total'       => count($data),
            'data'        => $data,
        ]);
    }
}
