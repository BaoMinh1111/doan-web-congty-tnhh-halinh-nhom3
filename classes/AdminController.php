<?php

/**
 * Class AdminController
 *
 * Controller quản trị hệ thống: dashboard, quản lý sản phẩm.
 * Kế thừa BaseController để dùng chung renderView, jsonResponse, redirect,
 * post(), get(), requireAdmin().
 * Mọi method đều gọi $this->requireAdmin() đầu tiên — đảm bảo không có
 * method nào bị hở quyền truy cập.
 *
 * Phụ thuộc (inject qua constructor):
 *   - ProductService  → nghiệp vụ sản phẩm + danh mục
 *   - OrderService    → nghiệp vụ đơn hàng (dùng cho dashboard stats)
 *   - AuthService     → đăng xuất admin
 *
 * @package App\Controllers
 * @author  Ha Linh Technology Solutions
 */
class AdminController extends BaseController
{
    // THUỘC TÍNH

    /**
     * @var ProductService
     */
    private ProductService $productService;

    /**
     * @var OrderService
     */
    private OrderService $orderService;

    /**
     * @var AuthService
     */
    private AuthService $authService;


    // CONSTRUCTOR

    /**
     * Khởi tạo AdminController với các Service được inject vào.
     *
     * @param ProductService $productService
     * @param OrderService   $orderService
     * @param AuthService    $authService
     */
    public function __construct(
        ProductService $productService,
        OrderService   $orderService,
        AuthService    $authService
    ) {
        parent::__construct();
        $this->productService = $productService;
        $this->orderService   = $orderService;
        $this->authService    = $authService;
    }


    // DASHBOARD

    /**
     * Hiển thị trang dashboard tổng quan.
     * Thống kê nhanh: tổng sản phẩm, tổng đơn hàng, doanh thu tháng hiện tại,
     * sản phẩm sắp hết hàng.
     *
     * Route: GET /admin/dashboard
     *
     * @return void
     */
    public function dashboard(): void
    {
        $this->requireAdmin();

        try {
            $totalProducts    = $this->productService->countProducts();
            $totalOrders      = $this->orderService->countOrders();
            $revenueMonth     = $this->orderService->getRevenueByMonth(
                (int) date('Y'),
                (int) date('m')
            );
            $lowStockProducts = $this->productService->getLowStockProducts(5);
            $recentOrders     = $this->orderService->getRecentOrders(10);
        } catch (RuntimeException $e) {
            error_log('[AdminController::dashboard] ' . $e->getMessage());
            $this->renderSystemError('Không thể tải dữ liệu dashboard. Vui lòng thử lại.');
            return;
        }

        // Nhất quán với manageProducts(): dùng renderViewToString + layout
        $content = $this->renderViewToString('admin/dashboard', [
            'totalProducts'    => $totalProducts,
            'totalOrders'      => $totalOrders,
            'revenueMonth'     => $revenueMonth,
            'lowStockProducts' => $lowStockProducts,
            'recentOrders'     => $recentOrders,
        ]);

        $this->renderView('layouts/admin', [
            'content'       => $content,
            'title'         => 'Dashboard - Quản trị',
            'activeMenu'    => 'dashboard',
            'adminUsername' => SessionHelper::getSessionUsername() ?? '',
        ]);
    }


    // QUẢN LÝ SẢN PHẨM

    /**
     * Hiển thị danh sách sản phẩm với phân trang và lọc theo danh mục.
     * Hỗ trợ tìm kiếm qua AJAX — nếu là AJAX request thì trả JSON,
     * ngược lại render view đầy đủ.
     *
     * Route: GET /admin/products
     * Route: GET /admin/products?page=2&cat=1&q=iphone
     *
     * @return void
     */
    public function manageProducts(): void
    {
        $this->requireAdmin();

        $page       = $this->get('page', 1);
        $categoryId = $this->get('cat',  0);
        $keyword    = $this->get('q',    '');

        // Lấy danh sách sản phẩm (có lọc nếu truyền tham số)
        $products   = $this->productService->getProductsWithCategory($keyword, $categoryId, $page);
        $categories = $this->productService->getAllCategories();
        $totalPages = $this->productService->countProductPages($keyword, $categoryId);

        // AJAX → trả JSON cho tìm kiếm realtime
        if ($this->isAjax()) {
            $this->jsonResponse([
                'success'    => true,
                'products'   => array_map(
                    fn($p) => $p->toPublicArray(),
                    $products
                ),
                'totalPages' => $totalPages,
                'page'       => $page,
            ]);
        }

        $content = $this->renderViewToString('admin/products/list', [
            'products'    => $products,
            'categories'  => $categories,
            'currentPage' => $page,
            'totalPages'  => $totalPages,
            'categoryId'  => $categoryId,
            'keyword'     => $keyword,
        ]);

        $this->renderView('layouts/admin', [
            'content'       => $content,
            'title'         => 'Quản lý sản phẩm',
            'activeMenu'    => 'products',
            'adminUsername' => SessionHelper::getSessionUsername() ?? '',
        ]);
    }

    /**
     * Hiển thị form thêm sản phẩm mới (GET) hoặc xử lý thêm mới (POST).
     *
     * GET  /admin/products/add   → render form thêm mới
     * POST /admin/products/add   → validate + upload ảnh + lưu DB → redirect danh sách
     *
     * @return void
     */
    public function addProduct(): void
    {
        $this->requireAdmin();

        if ($this->isPost()) {
            $this->handleSaveProduct(null);
            return;
        }

        // GET → render form thêm mới (truyền product = null để view biết đây là thêm mới)
        $content = $this->renderViewToString('admin/products/form', [
            'product'    => null,
            'categories' => $this->productService->getAllCategories(),
            'errors'     => [],
            'formTitle'  => 'Thêm sản phẩm mới',
            'old'        => [],
            'csrf_token' => $this->generateCsrfToken(),
        ]);

        $this->renderView('layouts/admin', [
            'content'       => $content,
            'title'         => 'Thêm sản phẩm - Quản trị',
            'activeMenu'    => 'products',
            'adminUsername' => SessionHelper::getSessionUsername() ?? '',
        ]);
    }

    /**
     * Hiển thị form chỉnh sửa sản phẩm (GET) hoặc xử lý cập nhật (POST).
     *
     * GET  /admin/products/edit?id=1  → render form điền sẵn dữ liệu
     * POST /admin/products/edit?id=1  → validate + upload ảnh mới (nếu có) + cập nhật DB
     *
     * @return void
     */
    public function editProduct(): void
    {
        $this->requireAdmin();

        $id = $this->get('id', 0);

        if ($id <= 0) {
            $this->redirect('/admin/products');
        }

        // Kiểm tra sản phẩm tồn tại
        $product = $this->productService->getProductById($id);
        if ($product === null) {
            $this->redirect('/admin/products');
        }

        if ($this->isPost()) {
            $this->handleSaveProduct($id);
            return;
        }

        // GET → render form điền sẵn dữ liệu sản phẩm hiện tại
        $content = $this->renderViewToString('admin/products/form', [
            'product'    => $product,
            'categories' => $this->productService->getAllCategories(),
            'errors'     => [],
            'formTitle'  => 'Chỉnh sửa sản phẩm',
            'old'        => [],
            'csrf_token' => $this->generateCsrfToken(),
        ]);

        $this->renderView('layouts/admin', [
            'content'       => $content,
            'title'         => 'Chỉnh sửa sản phẩm - Quản trị',
            'activeMenu'    => 'products',
            'adminUsername' => SessionHelper::getSessionUsername() ?? '',
        ]);
    }

    /**
     * Xử lý xoá sản phẩm.
     * Chỉ chấp nhận POST để tránh xoá nhầm khi crawler/bot gọi GET.
     * Verify CSRF token để chống Cross-Site Request Forgery.
     * Xoá ảnh trên disk trước, sau đó xoá bản ghi trong DB.
     *
     * Route: POST /admin/products/delete
     *
     * @return void
     */
    public function deleteProduct(): void
    {
        $this->requireAdmin();

        if (!$this->isPost()) {
            $this->redirect('/admin/products');
        }

        // Verify CSRF token
        if (!$this->verifyCsrfToken($this->post('csrf_token'))) {
            if ($this->isAjax()) {
                $this->jsonResponse(['success' => false, 'message' => 'Yêu cầu không hợp lệ (CSRF).'], 403);
            }
            $this->redirect('/admin/products');
        }

        $id = $this->post('id', 0);

        if ($id <= 0) {
            if ($this->isAjax()) {
                $this->jsonResponse(['success' => false, 'message' => 'ID sản phẩm không hợp lệ.'], 400);
            }
            $this->redirect('/admin/products');
        }

        $product = $this->productService->getProductById($id);

        if ($product === null) {
            if ($this->isAjax()) {
                $this->jsonResponse(['success' => false, 'message' => 'Sản phẩm không tồn tại.'], 404);
            }
            $this->redirect('/admin/products');
        }

        try {
            // Xoá ảnh trên disk trước — nếu xoá DB thất bại thì còn biết file cũ
            UploadHelper::deleteProductImage($product->getImage());
            $deleted = $this->productService->deleteProduct($id);
        } catch (RuntimeException $e) {
            error_log('[AdminController::deleteProduct] ' . $e->getMessage());
            if ($this->isAjax()) {
                $this->jsonResponse(['success' => false, 'message' => 'Lỗi hệ thống. Vui lòng thử lại.'], 500);
            }
            $this->redirect('/admin/products');
        }

        if ($this->isAjax()) {
            $this->jsonResponse([
                'success' => $deleted,
                'message' => $deleted ? 'Xoá sản phẩm thành công.' : 'Không thể xoá sản phẩm.',
            ]);
        }

        $this->redirect('/admin/products');
    }


    // XỬ LÝ FORM SẢN PHẨM (nội bộ)

    /**
     * Xử lý chung cho cả thêm mới và cập nhật sản phẩm.
     * Tách riêng để tránh lặp code giữa addProduct() và editProduct().
     *
     * Quy trình:
     *   1. Thu thập dữ liệu từ POST.
     *   2. Xử lý upload ảnh — nếu thất bại thì dừng ngay, render lỗi upload trước.
     *   3. Validate dữ liệu qua ProductService.
     *   4. Nếu có lỗi → render lại form với thông báo lỗi.
     *   5. Nếu hợp lệ → lưu DB (bọc try-catch) → redirect danh sách.
     *
     * @param  int|null $id null = thêm mới, int = cập nhật theo ID.
     * @return void
     */
    private function handleSaveProduct(?int $id): void
    {
        $formTitle = $id !== null ? 'Chỉnh sửa sản phẩm' : 'Thêm sản phẩm mới';

        // Bước 1: Thu thập dữ liệu từ POST
        $data = [
            'name'        => $this->post('name'),
            'category_id' => $this->post('category_id', 0),
            'price'       => $this->post('price', 0.0),
            'stock'       => $this->post('stock', 0),
            'description' => $this->post('description'),
            'image'       => '',
        ];

        // Bước 2: Xử lý upload ảnh
        $oldImage = '';

        if ($id !== null) {
            $existing      = $this->productService->getProductById($id);
            $oldImage      = $existing?->getImage() ?? '';
            $data['image'] = $oldImage; // giữ ảnh cũ nếu không upload mới
        }

        if (UploadHelper::hasFile($_FILES['image'] ?? [])) {
            $uploadResult = UploadHelper::uploadProductImage($_FILES['image'], $oldImage);

            if (!$uploadResult['success']) {
                // Upload thất bại → reset image về rỗng (không giữ giá trị cũ gây nhầm lẫn),
                // ưu tiên hiển thị lỗi upload trước khi validate các trường khác.
                $data['image'] = '';
                $this->renderProductForm($id, $formTitle, $data, ['image' => $uploadResult['message']]);
                return;
            }

            $data['image'] = $uploadResult['filename'];
        }

        // Bước 3: Validate dữ liệu qua ProductService
        $errors = $this->productService->validateProductData($data);

        // Bước 4: Có lỗi → render lại form kèm thông báo lỗi
        if (!empty($errors)) {
            $this->renderProductForm($id, $formTitle, $data, $errors);
            return;
        }

        // Bước 5: Hợp lệ → lưu DB
        try {
            if ($id === null) {
                $this->productService->createProduct($data);
            } else {
                $this->productService->updateProduct($id, $data);
            }
        } catch (RuntimeException $e) {
            error_log('[AdminController::handleSaveProduct] ' . $e->getMessage());
            $this->renderProductForm($id, $formTitle, $data, [
                'system' => 'Lỗi hệ thống khi lưu dữ liệu. Vui lòng thử lại.',
            ]);
            return;
        }

        $this->redirect('/admin/products');
    }

    /**
     * Render lại form sản phẩm kèm lỗi — dùng chung khi upload thất bại,
     * validate thất bại, hoặc DB lỗi trong handleSaveProduct().
     * Tách ra để tránh lặp code render trong các nhánh lỗi khác nhau.
     *
     * @param  int|null $id        ID sản phẩm (null nếu thêm mới).
     * @param  string   $formTitle Tiêu đề form.
     * @param  array    $old       Dữ liệu cũ để điền lại form.
     * @param  array    $errors    Mảng lỗi truyền vào view.
     * @return void
     */
    private function renderProductForm(?int $id, string $formTitle, array $old, array $errors): void
    {
        $product = $id !== null ? $this->productService->getProductById($id) : null;

        $content = $this->renderViewToString('admin/products/form', [
            'product'    => $product,
            'categories' => $this->productService->getAllCategories(),
            'errors'     => $errors,
            'formTitle'  => $formTitle,
            'old'        => $old,
        ]);

        $this->renderView('layouts/admin', [
            'content'       => $content,
            'title'         => $formTitle . ' - Quản trị',
            'activeMenu'    => 'products',
            'adminUsername' => SessionHelper::getSessionUsername() ?? '',
        ]);
    }


    // CSRF & LỖI HỆ THỐNG

    /**
     * Sinh CSRF token mới và lưu vào session.
     * Gọi khi render form — truyền token vào view để nhúng vào hidden input.
     *
     * Cách dùng trong Controller:
     *   'csrf_token' => $this->generateCsrfToken()
     *
     * Cách dùng trong View:
     *   <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
     *
     * @return string Token vừa sinh.
     */
    protected function generateCsrfToken(): string
    {
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        return $token;
    }

    /**
     * Verify CSRF token từ POST so với token lưu trong session.
     * Xoá token sau khi verify (one-time use) để chống replay attack.
     *
     * @param  string $token Token nhận từ POST.
     * @return bool
     */
    private function verifyCsrfToken(string $token): bool
    {
        $sessionToken = $_SESSION['csrf_token'] ?? '';

        // Xoá ngay sau khi verify — one-time use
        unset($_SESSION['csrf_token']);

        // hash_equals() chống timing attack khi so sánh chuỗi bí mật
        return !empty($sessionToken) && hash_equals($sessionToken, $token);
    }

    /**
     * Render trang lỗi hệ thống thay vì để trang trắng.
     * Dùng khi try-catch bắt được RuntimeException từ Service/Model.
     *
     * @param  string $message Thông báo lỗi hiển thị cho admin.
     * @return void
     */
    private function renderSystemError(string $message): void
    {
        $content = $this->renderViewToString('admin/error', [
            'errorMessage' => $message,
        ]);

        $this->renderView('layouts/admin', [
            'content'       => $content,
            'title'         => 'Lỗi hệ thống - Quản trị',
            'activeMenu'    => '',
            'adminUsername' => SessionHelper::getSessionUsername() ?? '',
        ]);
    }
}