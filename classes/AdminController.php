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

        $this->renderAdmin('admin/dashboard', [
            'totalProducts'    => $totalProducts,
            'totalOrders'      => $totalOrders,
            'revenueMonth'     => $revenueMonth,
            'lowStockProducts' => $lowStockProducts,
            'recentOrders'     => $recentOrders,
        ], 'Dashboard - Quản trị', 'dashboard');
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
                'products'   => array_map(fn($p) => $p->toPublicArray(), $products),
                'totalPages' => $totalPages,
                'page'       => $page,
            ]);
            return; // jsonResponse() đã exit, return để tường minh luồng
        }

        $this->renderAdmin('admin/products/list', [
            'products'    => $products,
            'categories'  => $categories,
            'currentPage' => $page,
            'totalPages'  => $totalPages,
            'categoryId'  => $categoryId,
            'keyword'     => $keyword,
        ], 'Quản lý sản phẩm', 'products');
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

        $this->renderAdmin('admin/products/form', [
            'product'    => null,
            'categories' => $this->productService->getAllCategories(),
            'errors'     => [],
            'formTitle'  => 'Thêm sản phẩm mới',
            'old'        => [],
            'csrf_token' => $this->generateCsrfToken(),
        ], 'Thêm sản phẩm - Quản trị', 'products');
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
            return;
        }

        $product = $this->productService->getProductById($id);
        if ($product === null) {
            $this->redirect('/admin/products');
            return;
        }

        if ($this->isPost()) {
            $this->handleSaveProduct($id, $product);
            return;
        }

        $this->renderAdmin('admin/products/form', [
            'product'    => $product,
            'categories' => $this->productService->getAllCategories(),
            'errors'     => [],
            'formTitle'  => 'Chỉnh sửa sản phẩm',
            'old'        => [],
            'csrf_token' => $this->generateCsrfToken(),
        ], 'Chỉnh sửa sản phẩm - Quản trị', 'products');
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
            return;
        }

        if (!$this->verifyCsrfToken($this->post('csrf_token'))) {
            if ($this->isAjax()) {
                $this->jsonResponse(['success' => false, 'message' => 'Yêu cầu không hợp lệ (CSRF).'], 403);
                return;
            }
            $this->redirect('/admin/products');
            return;
        }

        $id = $this->post('id', 0);

        if ($id <= 0) {
            if ($this->isAjax()) {
                $this->jsonResponse(['success' => false, 'message' => 'ID sản phẩm không hợp lệ.'], 400);
                return;
            }
            $this->redirect('/admin/products');
            return;
        }

        $product = $this->productService->getProductById($id);

        if ($product === null) {
            if ($this->isAjax()) {
                $this->jsonResponse(['success' => false, 'message' => 'Sản phẩm không tồn tại.'], 404);
                return;
            }
            $this->redirect('/admin/products');
            return;
        }

        try {
            // Xoá DB trước — chỉ xoá ảnh khi DB thành công.
            // Nếu đảo ngược: ảnh mất nhưng record DB còn → dữ liệu broken không thể khôi phục.
            $deleted = $this->productService->deleteProduct($id);

            if ($deleted) {
                UploadHelper::deleteProductImage($product->getImage());
            }
        } catch (RuntimeException $e) {
            error_log('[AdminController::deleteProduct] ' . $e->getMessage());
            if ($this->isAjax()) {
                $this->jsonResponse(['success' => false, 'message' => 'Lỗi hệ thống. Vui lòng thử lại.'], 500);
                return;
            }
            $this->redirect('/admin/products');
            return;
        }

        if ($this->isAjax()) {
            $this->jsonResponse([
                'success' => $deleted,
                'message' => $deleted ? 'Xoá sản phẩm thành công.' : 'Không thể xoá sản phẩm.',
            ]);
            return;
        }

        $this->redirect('/admin/products');
    }


    // XỬ LÝ FORM SẢN PHẨM (nội bộ)

    /**
     * Xử lý chung cho cả thêm mới và cập nhật sản phẩm.
     * Nhận $product để tránh query DB 2 lần khi edit (đã query ở editProduct()).
     *
     * Quy trình:
     *   1. Thu thập dữ liệu từ POST.
     *   2. Xử lý upload ảnh — nếu thất bại thì dừng ngay, render lỗi upload trước.
     *   3. Validate dữ liệu qua ProductService.
     *   4. Nếu có lỗi → render lại form với thông báo lỗi.
     *   5. Nếu hợp lệ → lưu DB (bọc try-catch) → redirect danh sách.
     *
     * @param  int|null           $id      null = thêm mới, int = cập nhật theo ID.
     * @param  ProductEntity|null $product Entity hiện tại (truyền vào để tránh query lại).
     * @return void
     */
    private function handleSaveProduct(?int $id, ?ProductEntity $product = null): void
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
            // Dùng $product đã được truyền vào — không query DB lại
            $oldImage      = $product?->getImage() ?? '';
            $data['image'] = $oldImage;
        }

        if (UploadHelper::hasFile($_FILES['image'] ?? [])) {
            $uploadResult = UploadHelper::uploadProductImage($_FILES['image'], $oldImage);

            if (!$uploadResult['success']) {
                $data['image'] = '';
                $this->renderProductForm($product, $formTitle, $data, ['image' => $uploadResult['message']]);
                return;
            }

            $data['image'] = $uploadResult['filename'];
        }

        // Bước 3: Validate
        $errors = $this->productService->validateProductData($data);

        // Bước 4: Có lỗi → render lại form
        if (!empty($errors)) {
            $this->renderProductForm($product, $formTitle, $data, $errors);
            return;
        }

        // Bước 5: Lưu DB
        try {
            if ($id === null) {
                $this->productService->createProduct($data);
            } else {
                $this->productService->updateProduct($id, $data);
            }
        } catch (RuntimeException $e) {
            error_log('[AdminController::handleSaveProduct] ' . $e->getMessage());
            $this->renderProductForm($product, $formTitle, $data, [
                'system' => 'Lỗi hệ thống khi lưu dữ liệu. Vui lòng thử lại.',
            ]);
            return;
        }

        $this->redirect('/admin/products');
    }

    /**
     * Render lại form sản phẩm kèm lỗi.
     * Nhận $product thay vì $id để tránh query DB thêm lần nữa.
     * Sinh lại CSRF token mỗi lần render — đảm bảo form submit lần 2 vẫn có token hợp lệ.
     *
     * @param  ProductEntity|null $product Entity hiện tại (null nếu thêm mới).
     * @param  string             $formTitle
     * @param  array              $old      Dữ liệu cũ để điền lại form.
     * @param  array              $errors
     * @return void
     */
    private function renderProductForm(
        ?ProductEntity $product,
        string         $formTitle,
        array          $old,
        array          $errors
    ): void {
        $this->renderAdmin('admin/products/form', [
            'product'    => $product,
            'categories' => $this->productService->getAllCategories(),
            'errors'     => $errors,
            'formTitle'  => $formTitle,
            'old'        => $old,
            'csrf_token' => $this->generateCsrfToken(), // sinh lại mỗi lần render
        ], $formTitle . ' - Quản trị', 'products');
    }


    // RENDER LAYOUT ADMIN

    /**
     * Gộp renderViewToString + renderView('layouts/admin') + adminUsername vào 1 chỗ.
     * Tránh lặp 6 lần cùng đoạn code trong các method của AdminController.
     *
     * @param  string $view       Đường dẫn view nội dung (không cần .php).
     * @param  array  $data       Dữ liệu truyền vào view nội dung.
     * @param  string $title      Tiêu đề trang (thẻ <title>).
     * @param  string $activeMenu Key menu đang active trong sidebar.
     * @return void
     */
    private function renderAdmin(string $view, array $data, string $title, string $activeMenu): void
    {
        $content = $this->renderViewToString($view, $data);

        $this->renderView('layouts/admin', [
            'content'       => $content,
            'title'         => $title,
            'activeMenu'    => $activeMenu,
            'adminUsername' => SessionHelper::getSessionUsername() ?? '',
        ]);
    }

    /**
     * Render trang lỗi hệ thống thay vì để trang trắng.
     *
     * @param  string $message Thông báo lỗi hiển thị cho admin.
     * @return void
     */
    private function renderSystemError(string $message): void
    {
        $this->renderAdmin(
            'admin/error',
            ['errorMessage' => $message],
            'Lỗi hệ thống - Quản trị',
            ''
        );
    }
}