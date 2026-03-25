<?php

require_once __DIR__ . '/../services/ProductService.php';

/**
 * Class ProductController
 *
 * Xử lý các request liên quan đến sản phẩm:
 *   - Trang chi tiết sản phẩm (render view).
 *   - Tìm kiếm sản phẩm (trả JSON cho AJAX).
 *
 * Kế thừa BaseController → dùng renderView(), jsonResponse(), redirect(), get(), isAjax()...
 * Nhận ProductService qua constructor (Dependency Injection).
 *
 * @package App\Controllers
 * @author  Ha Linh Technology Solutions
 */
class ProductController extends BaseController
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
     * Trang chi tiết sản phẩm.
     * Lấy product ID từ query string: ?id=5
     *
     * Dữ liệu truyền vào view:
     *   $product   → ProductEntity
     *   $category  → CategoryEntity|null danh mục của sản phẩm
     *   $categories → CategoryEntity[] tất cả danh mục (menu sidebar)
     *
     * Nếu sản phẩm không tồn tại → redirect về trang chủ kèm thông báo lỗi.
     *
     * Cách dùng trong router:
     *   $controller->detail();   // lấy id từ $_GET tự động
     */
    public function detail(): void
    {
        // Dùng get() từ BaseController — tự động cast về int, mặc định 0
        $id = $this->get('id', 0);

        if ($id <= 0) {
            $this->redirect('/?error=invalid_product');
        }

        $detail = $this->productService->getProductDetail($id);

        if ($detail === null) {
            // Sản phẩm không tồn tại → về trang chủ
            $this->redirect('/?error=product_not_found');
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
     * Tìm kiếm sản phẩm — endpoint cho AJAX.
     *
     * Tham số nhận từ query string (?q=rtx&category_id=2):
     *   q           → từ khoá tìm kiếm (string, mặc định '')
     *   category_id → lọc theo danh mục (int, mặc định 0 = không lọc)
     *
     * Luồng xử lý:
     *   1. Nhận và validate tham số đầu vào.
     *   2. Gọi ProductService::searchAndFilter().
     *   3. Chuyển kết quả thành mảng thuần (có thể json_encode) rồi trả JSON.
     *
     * Response JSON thành công:
     *   {
     *     "success": true,
     *     "keyword": "rtx",
     *     "category_id": 2,
     *     "total": 3,
     *     "data": [
     *       {
     *         "product": { "id": 1, "name": "RTX 4090", ... },
     *         "category": { "id": 2, "name": "VGA", ... }
     *       },
     *       ...
     *     ]
     *   }
     *
     * Response JSON khi không có kết quả:
     *   { "success": true, "keyword": "xyz", "category_id": 0, "total": 0, "data": [] }
     *
     * Response JSON khi lỗi:
     *   { "success": false, "message": "Từ khoá hoặc danh mục không được để trống." }
     *
     * Cách dùng trong router:
     *   $controller->search();   // chỉ chấp nhận GET request
     */
    public function search(): void
    {
        // Chỉ chấp nhận GET
        if (!$this->isGet()) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Phương thức không hợp lệ. Chỉ chấp nhận GET.',
            ], 405);
        }

        // Lấy tham số từ BaseController::get() — tự động cast đúng kiểu
        $keyword    = trim($this->get('q', ''));
        $categoryId = $this->get('category_id', 0);

        // Guard: cả 2 đều trống → báo lỗi thay vì trả kết quả rỗng gây nhầm lẫn
        if ($keyword === '' && $categoryId <= 0) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Vui lòng nhập từ khoá hoặc chọn danh mục để tìm kiếm.',
            ], 400);
        }

        // Giới hạn độ dài keyword để tránh abuse
        if (mb_strlen($keyword) > 100) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Từ khoá tìm kiếm không được vượt quá 100 ký tự.',
            ], 400);
        }

        try {
            $results = $this->productService->searchAndFilter($keyword, $categoryId);
        } catch (Throwable $e) {
            // Không lộ chi tiết lỗi ra ngoài — log nội bộ, trả thông báo chung
            error_log('[ProductController::search] ' . $e->getMessage());
            $this->jsonResponse([
                'success' => false,
                'message' => 'Đã có lỗi xảy ra trong quá trình tìm kiếm. Vui lòng thử lại.',
            ], 500);
        }

        // Chuyển Entity thành mảng thuần để json_encode được
        // ProductEntity và CategoryEntity đều có toArray()
        $data = array_map(function (array $item) {
            return [
                'product'  => $item['product']->toArray(),
                'category' => $item['category']?->toArray(),
            ];
        }, $results);

        $this->jsonResponse([
            'success'     => true,
            'keyword'     => $keyword,
            'category_id' => $categoryId,
            'total'       => count($data),
            'data'        => $data,
        ]);
    }
}
