<?php

require_once __DIR__ . '/../models/CategoryModel.php';
require_once __DIR__ . '/../models/CustomerModel.php';
require_once __DIR__ . '/../services/ProductService.php';

/**
 * Class AdminController
 *
 * Xử lý tất cả các trang quản trị dành cho Admin.
 * Mọi action đều gọi $this->requireAdmin() đầu tiên —
 * chưa đăng nhập → redirect login, không phải admin → redirect 403.
 *
 * Trách nhiệm:
 *   - Quản lý danh mục sản phẩm (CRUD đầy đủ).
 *   - Quản lý khách hàng (danh sách + tìm kiếm).
 *
 * Kế thừa BaseController → dùng renderView(), redirect(), jsonResponse(),
 *   post(), get(), requireAdmin()...
 *
 * @package App\Controllers
 * @author  Ha Linh Technology Solutions
 */
class AdminController extends BaseController
{
    // THUỘC TÍNH

    /** @var CategoryModel */
    private CategoryModel $categoryModel;

    /** @var CustomerModel */
    private CustomerModel $customerModel;

    /** @var ProductService */
    private ProductService $productService;

    /** Số khách hàng mỗi trang trong danh sách quản lý. */
    private const CUSTOMERS_PER_PAGE = 20;


    // CONSTRUCTOR

    /**
     * @param CategoryModel  $categoryModel
     * @param CustomerModel  $customerModel
     * @param ProductService $productService Dùng để lấy danh mục cho sidebar/dropdown.
     */
    public function __construct(
        CategoryModel  $categoryModel,
        CustomerModel  $customerModel,
        ProductService $productService
    ) {
        parent::__construct();
        $this->categoryModel  = $categoryModel;
        $this->customerModel  = $customerModel;
        $this->productService = $productService;
    }


    // ════════════════════════════════════════════════════
    // QUẢN LÝ DANH MỤC (CRUD)
    // ════════════════════════════════════════════════════

    /**
     * Danh sách tất cả danh mục.
     * Route: GET /admin/categories
     *
     * Dữ liệu truyền vào view:
     *   $categories → CategoryEntity[]
     *   $success    → string|null  thông báo thành công (từ query string ?success=...)
     *   $error      → string|null  thông báo lỗi (từ query string ?error=...)
     */
    public function manageCategories(): void
    {
        $this->requireAdmin();

        $categories = $this->categoryModel->getAll();

        // Map mã lỗi/thành công từ query string thành thông báo hiển thị
        $successMap = [
            'category_added'   => 'Thêm danh mục thành công.',
            'category_updated' => 'Cập nhật danh mục thành công.',
            'category_deleted' => 'Xóa danh mục thành công.',
        ];

        $errorMap = [
            'category_not_found'    => 'Không tìm thấy danh mục.',
            'category_delete_failed'=> 'Không thể xóa danh mục. Vui lòng thử lại.',
            'category_has_products' => 'Không thể xóa danh mục đang có sản phẩm liên kết.',
            'invalid_data'          => 'Dữ liệu không hợp lệ. Vui lòng kiểm tra lại.',
        ];

        $successCode = $this->get('success', '');
        $errorCode   = $this->get('error', '');

        $this->renderView('admin/categories/index', [
            'categories' => $categories,
            'pageTitle'  => 'Quản lý danh mục',
            'success'    => $successMap[$successCode] ?? null,
            'error'      => $errorMap[$errorCode]     ?? null,
        ]);
    }

    /**
     * Hiển thị form thêm danh mục mới.
     * Route: GET /admin/categories/create
     */
    public function categoryCreate(): void
    {
        $this->requireAdmin();

        $this->renderView('admin/categories/form', [
            'pageTitle' => 'Thêm danh mục mới',
            'action'    => 'create',
            'category'  => null, // view dùng để phân biệt create vs edit
        ]);
    }

    /**
     * Xử lý POST thêm danh mục mới.
     * Route: POST /admin/categories/store
     *
     * Validate → add → redirect kèm thông báo.
     */
    public function categoryStore(): void
    {
        $this->requireAdmin();

        if (!$this->isPost()) {
            $this->redirect('/admin/categories');
        }

        $name        = $this->post('name', '');
        $description = $this->post('description', '');

        try {
            $this->categoryModel->add([
                'name'        => $name,
                'description' => $description,
            ]);

            $this->redirect('/admin/categories?success=category_added');

        } catch (InvalidArgumentException $e) {
            // Lỗi validate — hiển thị lại form với thông báo
            $this->renderView('admin/categories/form', [
                'pageTitle'   => 'Thêm danh mục mới',
                'action'      => 'create',
                'category'    => null,
                'error'       => $e->getMessage(),
                'oldName'     => $name,
                'oldDesc'     => $description,
            ]);
        }
    }

    /**
     * Hiển thị form chỉnh sửa danh mục.
     * Route: GET /admin/categories/edit?id=3
     *
     * Nếu ID không tồn tại → redirect về danh sách kèm lỗi.
     */
    public function categoryEdit(): void
    {
        $this->requireAdmin();

        $id = $this->get('id', 0);

        if ($id <= 0) {
            $this->redirect('/admin/categories?error=category_not_found');
        }

        $category = $this->categoryModel->getById($id);

        if ($category === null) {
            $this->redirect('/admin/categories?error=category_not_found');
        }

        $this->renderView('admin/categories/form', [
            'pageTitle' => 'Chỉnh sửa danh mục',
            'action'    => 'edit',
            'category'  => $category,
        ]);
    }

    /**
     * Xử lý POST cập nhật danh mục.
     * Route: POST /admin/categories/update
     *
     * Validate → update → redirect kèm thông báo.
     */
    public function categoryUpdate(): void
    {
        $this->requireAdmin();

        if (!$this->isPost()) {
            $this->redirect('/admin/categories');
        }

        $id          = $this->post('id', 0);
        $name        = $this->post('name', '');
        $description = $this->post('description', '');

        if ($id <= 0) {
            $this->redirect('/admin/categories?error=category_not_found');
        }

        // Kiểm tra danh mục tồn tại trước khi update
        $existing = $this->categoryModel->getById($id);
        if ($existing === null) {
            $this->redirect('/admin/categories?error=category_not_found');
        }

        try {
            $this->categoryModel->update($id, [
                'name'        => $name,
                'description' => $description,
            ]);

            $this->redirect('/admin/categories?success=category_updated');

        } catch (InvalidArgumentException $e) {
            // Lỗi validate — hiển thị lại form với dữ liệu cũ và thông báo lỗi
            $this->renderView('admin/categories/form', [
                'pageTitle' => 'Chỉnh sửa danh mục',
                'action'    => 'edit',
                'category'  => $existing,
                'error'     => $e->getMessage(),
                'oldName'   => $name,
                'oldDesc'   => $description,
            ]);
        }
    }

    /**
     * Xử lý POST xóa danh mục.
     * Route: POST /admin/categories/delete
     *
     * Kiểm tra có sản phẩm liên kết không → từ chối nếu có → xóa nếu không.
     * Nhận ID qua POST body (không qua GET) để tránh xóa nhầm bằng URL.
     */
    public function categoryDelete(): void
    {
        $this->requireAdmin();

        if (!$this->isPost()) {
            $this->redirect('/admin/categories');
        }

        $id = $this->post('id', 0);

        if ($id <= 0) {
            $this->redirect('/admin/categories?error=category_not_found');
        }

        // Kiểm tra danh mục còn sản phẩm liên kết không
        // → nếu có thì từ chối xóa để tránh vi phạm FK hoặc mất liên kết dữ liệu
        $productCount = $this->productService->countProductsByCategory($id);

        if ($productCount > 0) {
            $this->redirect('/admin/categories?error=category_has_products');
        }

        $ok = $this->categoryModel->delete($id);

        if ($ok) {
            $this->redirect('/admin/categories?success=category_deleted');
        } else {
            $this->redirect('/admin/categories?error=category_delete_failed');
        }
    }


    // ════════════════════════════════════════════════════
    // QUẢN LÝ KHÁCH HÀNG (LIST + SEARCH)
    // ════════════════════════════════════════════════════

    /**
     * Danh sách khách hàng có phân trang, hỗ trợ tìm kiếm theo tên / email.
     * Route: GET /admin/customers[?q=keyword&page=2]
     *
     * Luồng xử lý:
     *   - Có keyword (?q=...) → tìm kiếm, không phân trang (thường ít kết quả).
     *   - Không có keyword    → lấy tất cả theo trang (?page=...).
     *
     * Dữ liệu truyền vào view:
     *   $customers   → CustomerEntity[]  danh sách trang hiện tại (hoặc kết quả tìm)
     *   $keyword     → string            từ khoá tìm kiếm ('' nếu không tìm)
     *   $page        → int               trang hiện tại
     *   $totalPages  → int               tổng số trang (0 khi đang tìm kiếm)
     *   $total       → int               tổng số customer
     *   $success     → string|null       thông báo thành công
     *   $error       → string|null       thông báo lỗi
     */
    public function manageCustomers(): void
    {
        $this->requireAdmin();

        $keyword = trim($this->get('q', ''));
        $page    = max(1, $this->get('page', 1));

        $successMap = [
            'customer_deleted' => 'Đã xóa khách hàng thành công.',
        ];

        $errorMap = [
            'customer_not_found'    => 'Không tìm thấy khách hàng.',
            'customer_delete_failed'=> 'Không thể xóa khách hàng. Vui lòng thử lại.',
            'customer_has_orders'   => 'Không thể xóa khách hàng đang có đơn hàng.',
        ];

        $successCode = $this->get('success', '');
        $errorCode   = $this->get('error', '');

        if ($keyword !== '') {
            // Tìm kiếm — trả hết kết quả, không phân trang
            $customers  = $this->customerModel->search($keyword);
            $total      = count($customers);
            $totalPages = 0; // 0 = đang ở chế độ tìm kiếm, view ẩn pagination
        } else {
            // Danh sách đầy đủ — phân trang
            $total      = $this->customerModel->count();
            $totalPages = (int) ceil($total / self::CUSTOMERS_PER_PAGE);
            $page       = min($page, max(1, $totalPages)); // clamp page hợp lệ
            $customers  = $this->customerModel->paginate($page, self::CUSTOMERS_PER_PAGE);
        }

        $this->renderView('admin/customers/index', [
            'customers'  => $customers,
            'keyword'    => $keyword,
            'page'       => $page,
            'totalPages' => $totalPages,
            'total'      => $total,
            'pageTitle'  => 'Quản lý khách hàng',
            'success'    => $successMap[$successCode] ?? null,
            'error'      => $errorMap[$errorCode]     ?? null,
        ]);
    }

    /**
     * Xử lý POST xóa khách hàng.
     * Route: POST /admin/customers/delete
     *
     * Kiểm tra customer có đơn hàng liên kết không → từ chối nếu có.
     * Nhận ID qua POST body để tránh xóa nhầm bằng URL.
     */
    public function customerDelete(): void
    {
        $this->requireAdmin();

        if (!$this->isPost()) {
            $this->redirect('/admin/customers');
        }

        $id = $this->post('id', 0);

        if ($id <= 0) {
            $this->redirect('/admin/customers?error=customer_not_found');
        }

        // Kiểm tra customer tồn tại
        $customer = $this->customerModel->getById($id);
        if ($customer === null) {
            $this->redirect('/admin/customers?error=customer_not_found');
        }

        // Kiểm tra customer còn đơn hàng không — tránh mất dữ liệu lịch sử
        $orders = $this->customerModel->getOrdersByCustomerId($id);
        if (!empty($orders)) {
            $this->redirect('/admin/customers?error=customer_has_orders');
        }

        $ok = $this->customerModel->delete($id);

        if ($ok) {
            $this->redirect('/admin/customers?success=customer_deleted');
        } else {
            $this->redirect('/admin/customers?error=customer_delete_failed');
        }
    }
}
