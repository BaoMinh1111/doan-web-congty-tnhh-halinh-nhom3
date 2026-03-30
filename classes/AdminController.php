<?php

// require_once đã được xóa — để bootstrap.php lo việc này.

/**
 * Class AdminController
 *
 * Xử lý tất cả các trang quản trị dành cho Admin.
 * Mọi public action đều gọi $this->requireAdmin() đầu tiên.
 * Ngoài ra, AdminMiddleware tại router cũng chặn trước — double-check để an toàn.
 *
 * Pattern nhất quán với AdminController_1 (orders):
 *   - FlashMessage thay vì ?success=...&error=... trên query string.
 *   - renderAdmin() thay vì renderView() — gộp layout + flash vào một nơi.
 *   - respondError() / respondSuccess() — trả lỗi/thành công nhất quán cho cả AJAX và form.
 *   - CSRF token cho mọi POST action thay đổi dữ liệu.
 *   - return; ngay sau mọi redirect() / jsonResponse().
 *
 * Trách nhiệm:
 *   - Quản lý danh mục (CRUD).
 *   - Quản lý khách hàng (list + search).
 *   - Quản lý đơn hàng (list + detail + update status).
 *
 * @package App\Controllers
 * @author  Ha Linh Technology Solutions
 */
class AdminController extends BaseController
{
    // ── THUỘC TÍNH ────────────────────────────────────────────

    /** @var CategoryModel */
    private CategoryModel $categoryModel;

    /** @var CustomerModel */
    private CustomerModel $customerModel;

    /** @var ProductService */
    private ProductService $productService;

    /** @var OrderService */
    private OrderService $orderService;

    /** @var AuthService */
    private AuthService $authService;

    /** Số khách hàng mỗi trang. */
    private const CUSTOMERS_PER_PAGE = 20;


    // ── CONSTRUCTOR ───────────────────────────────────────────

    /**
     * @param CategoryModel  $categoryModel
     * @param CustomerModel  $customerModel
     * @param ProductService $productService
     * @param OrderService   $orderService
     * @param AuthService    $authService
     */
    public function __construct(
        CategoryModel  $categoryModel,
        CustomerModel  $customerModel,
        ProductService $productService,
        OrderService   $orderService,
        AuthService    $authService
    ) {
        parent::__construct();
        $this->categoryModel  = $categoryModel;
        $this->customerModel  = $customerModel;
        $this->productService = $productService;
        $this->orderService   = $orderService;
        $this->authService    = $authService;
    }


    // ════════════════════════════════════════════════════════
    // QUẢN LÝ DANH MỤC — CRUD
    // ════════════════════════════════════════════════════════

    /**
     * Danh sách tất cả danh mục.
     * Route: GET /admin/categories
     *
     * Dữ liệu view (qua renderAdmin):
     *   $categories → CategoryEntity[]
     *   Flash message (nếu có) được FlashMessage::get() trả về trong renderAdmin().
     */
    public function manageCategories(): void
    {
        $this->requireAdmin();

        try {
            $categories = $this->categoryModel->getAll();
        } catch (RuntimeException $e) {
            error_log('[AdminController::manageCategories] ' . $e->getMessage());
            FlashMessage::error('Không thể tải danh sách danh mục. Vui lòng thử lại.');
            $categories = [];
        }

        $this->renderAdmin('admin/categories/index', [
            'categories' => $categories,
        ], 'Quản lý danh mục', 'categories');
    }

    /**
     * Form thêm danh mục mới.
     * Route: GET /admin/categories/create
     */
    public function categoryCreate(): void
    {
        $this->requireAdmin();

        $this->renderAdmin('admin/categories/form', [
            'action'   => 'create',
            'category' => null,         // view phân biệt create vs edit
            'csrf'     => $this->generateCsrfToken(),
        ], 'Thêm danh mục mới', 'categories');
    }

    /**
     * Xử lý POST thêm danh mục mới.
     * Route: POST /admin/categories/store
     *
     * Luồng: CSRF → validate → add → redirect + flash.
     * Lỗi validate → render lại form, giữ dữ liệu người dùng đã nhập.
     */
    public function categoryStore(): void
    {
        $this->requireAdmin();

        if (!$this->isPost()) {
            $this->redirect('/admin/categories');
            return;
        }

        if (!$this->verifyCsrfToken($this->post('csrf_token'))) {
            $this->respondError('Yêu cầu không hợp lệ (CSRF).', 403, '/admin/categories');
            return;
        }

        $name        = $this->post('name', '');
        $description = $this->post('description', '');

        try {
            $this->categoryModel->add([
                'name'        => $name,
                'description' => $description,
            ]);

            FlashMessage::success('Thêm danh mục thành công.');
            $this->redirect('/admin/categories');
            return;

        } catch (InvalidArgumentException $e) {
            // Lỗi validate → render lại form với dữ liệu cũ và thông báo lỗi
            // Không redirect để giữ nguyên dữ liệu người dùng đã nhập
            $this->renderAdmin('admin/categories/form', [
                'action'   => 'create',
                'category' => null,
                'csrf'     => $this->generateCsrfToken(),
                'oldName'  => $name,
                'oldDesc'  => $description,
                'formError'=> $e->getMessage(),
            ], 'Thêm danh mục mới', 'categories');
        }
    }

    /**
     * Form chỉnh sửa danh mục.
     * Route: GET /admin/categories/edit?id=3
     */
    public function categoryEdit(): void
    {
        $this->requireAdmin();

        $id = $this->get('id', 0);

        if ($id <= 0) {
            FlashMessage::error('ID danh mục không hợp lệ.');
            $this->redirect('/admin/categories');
            return;
        }

        $category = $this->categoryModel->getById($id);

        if ($category === null) {
            FlashMessage::error('Không tìm thấy danh mục #' . $id . '.');
            $this->redirect('/admin/categories');
            return;
        }

        $this->renderAdmin('admin/categories/form', [
            'action'   => 'edit',
            'category' => $category,
            'csrf'     => $this->generateCsrfToken(),
        ], 'Chỉnh sửa danh mục', 'categories');
    }

    /**
     * Xử lý POST cập nhật danh mục.
     * Route: POST /admin/categories/update
     *
     * Luồng: CSRF → tồn tại → validate → update → redirect + flash.
     * Lỗi validate → render lại form, giữ dữ liệu người dùng đã nhập.
     */
    public function categoryUpdate(): void
    {
        $this->requireAdmin();

        if (!$this->isPost()) {
            $this->redirect('/admin/categories');
            return;
        }

        if (!$this->verifyCsrfToken($this->post('csrf_token'))) {
            $this->respondError('Yêu cầu không hợp lệ (CSRF).', 403, '/admin/categories');
            return;
        }

        $id          = $this->post('id', 0);
        $name        = $this->post('name', '');
        $description = $this->post('description', '');

        if ($id <= 0) {
            FlashMessage::error('ID danh mục không hợp lệ.');
            $this->redirect('/admin/categories');
            return;
        }

        $existing = $this->categoryModel->getById($id);

        if ($existing === null) {
            FlashMessage::error('Không tìm thấy danh mục #' . $id . '.');
            $this->redirect('/admin/categories');
            return;
        }

        try {
            $this->categoryModel->update($id, [
                'name'        => $name,
                'description' => $description,
            ]);

            FlashMessage::success('Cập nhật danh mục thành công.');
            $this->redirect('/admin/categories');
            return;

        } catch (InvalidArgumentException $e) {
            // Render lại form, giữ dữ liệu người dùng đã nhập
            $this->renderAdmin('admin/categories/form', [
                'action'   => 'edit',
                'category' => $existing,
                'csrf'     => $this->generateCsrfToken(),
                'oldName'  => $name,
                'oldDesc'  => $description,
                'formError'=> $e->getMessage(),
            ], 'Chỉnh sửa danh mục', 'categories');
        }
    }

    /**
     * Xử lý POST xóa danh mục.
     * Route: POST /admin/categories/delete
     *
     * Nhận ID qua POST body — tránh xóa nhầm bằng GET URL.
     * Kiểm tra còn sản phẩm liên kết không trước khi xóa.
     */
    public function categoryDelete(): void
    {
        $this->requireAdmin();

        if (!$this->isPost()) {
            $this->redirect('/admin/categories');
            return;
        }

        if (!$this->verifyCsrfToken($this->post('csrf_token'))) {
            $this->respondError('Yêu cầu không hợp lệ (CSRF).', 403, '/admin/categories');
            return;
        }

        $id = $this->post('id', 0);

        if ($id <= 0) {
            FlashMessage::error('ID danh mục không hợp lệ.');
            $this->redirect('/admin/categories');
            return;
        }

        // Kiểm tra còn sản phẩm liên kết — tránh vi phạm FK hoặc mất liên kết dữ liệu
        $productCount = $this->productService->countProductsByCategory($id);

        if ($productCount > 0) {
            FlashMessage::error(
                'Không thể xóa danh mục này vì đang có ' . $productCount . ' sản phẩm liên kết.'
            );
            $this->redirect('/admin/categories');
            return;
        }

        $ok = $this->categoryModel->delete($id);

        if ($ok) {
            FlashMessage::success('Đã xóa danh mục thành công.');
        } else {
            FlashMessage::error('Không thể xóa danh mục. Vui lòng thử lại.');
        }

        $this->redirect('/admin/categories');
    }


    // ════════════════════════════════════════════════════════
    // QUẢN LÝ KHÁCH HÀNG — LIST + SEARCH
    // ════════════════════════════════════════════════════════

    /**
     * Danh sách khách hàng, hỗ trợ tìm kiếm + phân trang.
     * Route: GET /admin/customers[?q=keyword&page=2]
     *
     * Luồng:
     *   - Có keyword (?q=...) → tìm kiếm, không phân trang.
     *   - Không có keyword   → danh sách đầy đủ, phân trang.
     *
     * Dữ liệu view:
     *   $customers  → CustomerEntity[]   trang hiện tại hoặc kết quả tìm
     *   $keyword    → string             từ khoá đang tìm ('' = không tìm)
     *   $page       → int               trang hiện tại
     *   $totalPages → int               tổng số trang (0 khi đang tìm)
     *   $total      → int               tổng số customer
     */
    public function manageCustomers(): void
    {
        $this->requireAdmin();

        $keyword = trim($this->get('q', ''));
        $page    = max(1, $this->get('page', 1));

        try {
            if ($keyword !== '') {
                // Chế độ tìm kiếm — trả hết kết quả, không phân trang
                $customers  = $this->customerModel->search($keyword);
                $total      = count($customers);
                $totalPages = 0; // 0 = đang tìm, view ẩn pagination
            } else {
                // Chế độ danh sách — phân trang
                $total      = $this->customerModel->count();
                $totalPages = (int) ceil($total / self::CUSTOMERS_PER_PAGE);
                $page       = min($page, max(1, $totalPages)); // clamp trang hợp lệ
                $customers  = $this->customerModel->paginate($page, self::CUSTOMERS_PER_PAGE);
            }
        } catch (RuntimeException $e) {
            error_log('[AdminController::manageCustomers] ' . $e->getMessage());
            FlashMessage::error('Không thể tải danh sách khách hàng. Vui lòng thử lại.');
            $customers  = [];
            $total      = 0;
            $totalPages = 0;
        }

        $this->renderAdmin('admin/customers/index', [
            'customers'  => $customers,
            'keyword'    => $keyword,
            'page'       => $page,
            'totalPages' => $totalPages,
            'total'      => $total,
        ], 'Quản lý khách hàng', 'customers');
    }

    /**
     * Xử lý POST xóa khách hàng.
     * Route: POST /admin/customers/delete
     *
     * Nhận ID qua POST body — tránh xóa nhầm bằng GET URL.
     * Kiểm tra còn đơn hàng liên kết không trước khi xóa.
     */
    public function customerDelete(): void
    {
        $this->requireAdmin();

        if (!$this->isPost()) {
            $this->redirect('/admin/customers');
            return;
        }

        if (!$this->verifyCsrfToken($this->post('csrf_token'))) {
            $this->respondError('Yêu cầu không hợp lệ (CSRF).', 403, '/admin/customers');
            return;
        }

        $id = $this->post('id', 0);

        if ($id <= 0) {
            FlashMessage::error('ID khách hàng không hợp lệ.');
            $this->redirect('/admin/customers');
            return;
        }

        $customer = $this->customerModel->getById($id);

        if ($customer === null) {
            FlashMessage::error('Không tìm thấy khách hàng #' . $id . '.');
            $this->redirect('/admin/customers');
            return;
        }

        // Kiểm tra còn đơn hàng liên kết — tránh mất dữ liệu lịch sử
        $orders = $this->customerModel->getOrdersByCustomerId($id);

        if (!empty($orders)) {
            FlashMessage::error(
                'Không thể xóa khách hàng "'
                . $customer->getName()
                . '" vì còn ' . count($orders) . ' đơn hàng liên kết.'
            );
            $this->redirect('/admin/customers');
            return;
        }

        $ok = $this->customerModel->delete($id);

        if ($ok) {
            FlashMessage::success('Đã xóa khách hàng "' . $customer->getName() . '" thành công.');
        } else {
            FlashMessage::error('Không thể xóa khách hàng. Vui lòng thử lại.');
        }

        $this->redirect('/admin/customers');
    }


    // ════════════════════════════════════════════════════════
    // QUẢN LÝ ĐƠN HÀNG
    // (Copy từ AdminController_1 — không thay đổi logic)
    // ════════════════════════════════════════════════════════

    /**
     * Danh sách đơn hàng, lọc theo trạng thái + phân trang.
     * Hỗ trợ AJAX để lọc realtime không reload trang.
     * Route: GET /admin/orders[?status=pending&page=2]
     */
    public function manageOrders(): void
    {
        $this->requireAdmin();

        $status = $this->get('status', '');
        $page   = $this->get('page', 1);

        if ($status !== '' && !in_array($status, OrderService::VALID_STATUSES, true)) {
            $status = '';
        }

        try {
            $result     = $this->orderService->getOrdersByStatus($status, $page);
            $orders     = $result['data']      ?? [];
            $totalPages = $result['totalPages'] ?? 1;
            $stats      = $this->orderService->getOrderCountByStatus();
        } catch (RuntimeException $e) {
            error_log('[AdminController::manageOrders] ' . $e->getMessage());
            $this->renderAdmin('admin/orders/list', [
                'orders'        => [],
                'totalPages'    => 1,
                'currentPage'   => 1,
                'currentStatus' => $status,
                'statusLabels'  => OrderService::STATUS_LABELS,
                'stats'         => [],
            ], 'Quản lý đơn hàng', 'orders');
            return;
        }

        if ($this->isAjax()) {
            $this->jsonResponse([
                'success'    => true,
                'orders'     => $orders,
                'totalPages' => $totalPages,
                'page'       => $page,
            ]);
            return;
        }

        $this->renderAdmin('admin/orders/list', [
            'orders'        => $orders,
            'currentPage'   => $page,
            'totalPages'    => $totalPages,
            'currentStatus' => $status,
            'statusLabels'  => OrderService::STATUS_LABELS,
            'stats'         => $stats,
        ], 'Quản lý đơn hàng', 'orders');
    }

    /**
     * Chi tiết một đơn hàng + form cập nhật trạng thái inline.
     * Route: GET /admin/orders/detail?id=5
     */
    public function viewOrder(): void
    {
        $this->requireAdmin();

        $id = $this->get('id', 0);

        if ($id <= 0) {
            FlashMessage::error('ID đơn hàng không hợp lệ.');
            $this->redirect('/admin/orders');
            return;
        }

        try {
            $order      = $this->orderService->getOrderById($id);
            $orderItems = $this->orderService->getOrderItems($id);
        } catch (RuntimeException $e) {
            error_log('[AdminController::viewOrder] ' . $e->getMessage());
            FlashMessage::error('Lỗi hệ thống khi tải đơn hàng. Vui lòng thử lại.');
            $this->redirect('/admin/orders');
            return;
        }

        if ($order === null) {
            FlashMessage::error('Không tìm thấy đơn hàng #' . $id . '.');
            $this->redirect('/admin/orders');
            return;
        }

        $this->renderAdmin('admin/orders/detail', [
            'order'         => $order,
            'orderItems'    => $orderItems,
            'statusLabels'  => OrderService::STATUS_LABELS,
            'validStatuses' => OrderService::VALID_STATUSES,
            'csrf'          => $this->generateCsrfToken(),
        ], 'Chi tiết đơn hàng #' . $id, 'orders');
    }

    /**
     * Cập nhật trạng thái đơn hàng.
     * Route: POST /admin/orders/update-status
     * Hỗ trợ AJAX và form thông thường.
     */
    public function updateOrderStatus(): void
    {
        $this->requireAdmin();

        if (!$this->isPost()) {
            $this->redirect('/admin/orders');
            return;
        }

        if (!$this->verifyCsrfToken($this->post('csrf_token'))) {
            $this->respondError('Yêu cầu không hợp lệ (CSRF).', 403, '/admin/orders');
            return;
        }

        $id        = $this->post('id', 0);
        $newStatus = $this->post('status');

        if ($id <= 0) {
            $this->respondError('ID đơn hàng không hợp lệ.', 400, '/admin/orders');
            return;
        }

        if (!in_array($newStatus, OrderService::VALID_STATUSES, true)) {
            $this->respondError('Trạng thái không hợp lệ.', 400, '/admin/orders/detail?id=' . $id);
            return;
        }

        $result = $this->orderService->updateOrderStatus($id, $newStatus);

        if (!$result['success']) {
            $httpStatus = str_contains($result['message'], 'hệ thống') ? 500 : 422;
            $this->respondError($result['message'], $httpStatus, '/admin/orders/detail?id=' . $id);
            return;
        }

        $label = OrderService::STATUS_LABELS[$newStatus] ?? $newStatus;
        $this->respondSuccess(
            $result['message'],
            '/admin/orders/detail?id=' . $id,
            ['newStatus' => $newStatus, 'label' => $label]
        );
    }


    // ════════════════════════════════════════════════════════
    // PRIVATE HELPERS
    // ════════════════════════════════════════════════════════

    /**
     * Trả lỗi nhất quán cho cả AJAX và form thông thường.
     *
     * @param string $message    Thông báo lỗi.
     * @param int    $httpStatus HTTP status (400, 403, 404, 422, 500...).
     * @param string $redirectUrl URL redirect cho form request.
     * @param string $flashType  Loại flash (mặc định error).
     */
    private function respondError(
        string $message,
        int    $httpStatus,
        string $redirectUrl,
        string $flashType = FlashMessage::TYPE_ERROR
    ): void {
        if ($this->isAjax()) {
            $this->jsonResponse(['success' => false, 'message' => $message], $httpStatus);
            return;
        }
        FlashMessage::set($message, $flashType);
        $this->redirect($redirectUrl);
    }

    /**
     * Trả thành công nhất quán cho cả AJAX và form thông thường.
     *
     * @param string $message     Thông báo thành công.
     * @param string $redirectUrl URL redirect cho form request.
     * @param array  $extraData   Dữ liệu bổ sung trong AJAX response.
     */
    private function respondSuccess(string $message, string $redirectUrl, array $extraData = []): void
    {
        if ($this->isAjax()) {
            $this->jsonResponse(array_merge(['success' => true, 'message' => $message], $extraData));
            return;
        }
        FlashMessage::success($message);
        $this->redirect($redirectUrl);
    }

    /**
     * Render layout admin với flash message.
     * Nơi DUY NHẤT gọi FlashMessage::get() — tránh đọc 2 lần (lần 2 luôn rỗng).
     *
     * @param string $view       Tên view (tương đối từ viewPath).
     * @param array  $data       Dữ liệu truyền vào view.
     * @param string $title      Tiêu đề trang <title>.
     * @param string $activeMenu Key menu đang active để highlight sidebar.
     */
    private function renderAdmin(string $view, array $data, string $title, string $activeMenu): void
    {
        $content = $this->renderViewToString($view, $data);

        $this->renderView('layouts/admin', [
            'content'       => $content,
            'title'         => $title,
            'activeMenu'    => $activeMenu,
            'adminUsername' => SessionHelper::getSessionUsername() ?? '',
            'flash'         => FlashMessage::get(), // đọc 1 lần duy nhất
        ]);
    }
}
