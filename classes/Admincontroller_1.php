<?php

/**
 * Class FlashMessage
 *
 * Quản lý flash message — thông báo hiển thị một lần duy nhất sau redirect.
 * Cơ chế: set() ghi vào session → redirect → get() đọc và xoá khỏi session.
 *
 * Cách dùng trong Controller (trước redirect):
 *   FlashMessage::success('Thêm sản phẩm thành công.');
 *   $this->redirect('/admin/products');
 *
 * Cách dùng trong View / Layout:
 *   <?php foreach (FlashMessage::get() as $flash): ?>
 *       <div class="alert alert-<?= $flash['type'] ?>">
 *           <?= $flash['message'] ?>
 *       </div>
 *   <?php endforeach; ?>
 *
 * @package App\Helpers
 * @author  Ha Linh Technology Solutions
 */
class FlashMessage
{
    // HẰNG SỐ

    private const SESSION_KEY = 'flash_messages';

    public const TYPE_SUCCESS = 'success';
    public const TYPE_ERROR   = 'danger';
    public const TYPE_WARNING = 'warning';
    public const TYPE_INFO    = 'info';


    // KHỞI ĐỘNG SESSION

    private static function ensureStarted(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }


    // GHI MESSAGE

    /**
     * Thêm flash message vào session.
     * Message được escape khi set — View dùng thẳng không cần escape thêm.
     *
     * @param  string $message
     * @param  string $type    'success' | 'danger' | 'warning' | 'info'
     * @return void
     */
    public static function set(string $message, string $type = self::TYPE_SUCCESS): void
    {
        self::ensureStarted();
        $_SESSION[self::SESSION_KEY][] = [
            'type'    => $type,
            'message' => htmlspecialchars($message, ENT_QUOTES, 'UTF-8'),
        ];
    }

    /**
     * @param string $message
     * @return void
     */
    public static function success(string $message): void
    {
        self::set($message, self::TYPE_SUCCESS);
    }

    /**
     * @param string $message
     * @return void
     */
    public static function error(string $message): void
    {
        self::set($message, self::TYPE_ERROR);
    }

    /**
     * @param string $message
     * @return void
     */
    public static function warning(string $message): void
    {
        self::set($message, self::TYPE_WARNING);
    }

    /**
     * @param string $message
     * @return void
     */
    public static function info(string $message): void
    {
        self::set($message, self::TYPE_INFO);
    }


    // ĐỌC VÀ XOÁ

    /**
     * Lấy tất cả flash messages và xoá khỏi session (one-time read).
     * Trả mảng rỗng nếu không có message nào.
     *
     * @return array[] [['type' => string, 'message' => string], ...]
     */
    public static function get(): array
    {
        self::ensureStarted();
        $messages = $_SESSION[self::SESSION_KEY] ?? [];
        unset($_SESSION[self::SESSION_KEY]);
        return $messages;
    }

    /**
     * Kiểm tra có flash message nào đang chờ không.
     * Dùng để layout quyết định có render vùng alert không.
     *
     * @return bool
     */
    public static function has(): bool
    {
        self::ensureStarted();
        return !empty($_SESSION[self::SESSION_KEY]);
    }
}


/**
 * Class AdminMiddleware
 *
 * Middleware kiểm tra quyền admin tập trung tại router/index.php.
 * Chạy trước khi Controller xử lý — không phụ thuộc vào Controller tự gọi requireAdmin().
 * Đảm bảo không route admin nào bị hở dù developer quên.
 *
 * Cách dùng tại index.php / router:
 *   if (AdminMiddleware::isAdminRoute($_SERVER['REQUEST_URI'])) {
 *       AdminMiddleware::handle();
 *   }
 *
 * @package App\Middleware
 * @author  Ha Linh Technology Solutions
 */
class AdminMiddleware
{
    // HẰNG SỐ

    private const LOGIN_URL     = '/login';
    private const FORBIDDEN_URL = '/403';


    // MIDDLEWARE CHÍNH

    /**
     * Kiểm tra quyền admin cho request hiện tại.
     * Nếu không đủ quyền → redirect và dừng request.
     * Nếu đủ quyền → return bình thường, tiếp tục dispatch đến Controller.
     *
     * @return void
     */
    public static function handle(): void
    {
        self::ensureSession();

        // Chưa đăng nhập → lưu URL đang cố truy cập rồi về login
        if (!SessionHelper::isLoggedIn()) {
            self::saveIntendedUrl();
            self::redirectTo(self::LOGIN_URL);
        }

        // Đăng nhập rồi nhưng không phải admin → về 403
        if (!SessionHelper::isAdmin()) {
            self::redirectTo(self::FORBIDDEN_URL);
        }

        // Đủ quyền → tiếp tục xử lý request bình thường
    }

    /**
     * Kiểm tra URI có phải route admin không.
     * Dùng tại router để quyết định có apply middleware không.
     *
     * Cách dùng:
     *   if (AdminMiddleware::isAdminRoute($_SERVER['REQUEST_URI'])) {
     *       AdminMiddleware::handle();
     *   }
     *
     * @param  string $uri Request URI (VD: '/admin/products?page=2').
     * @return bool
     */
    public static function isAdminRoute(string $uri): bool
    {
        // strtok loại bỏ query string trước khi check prefix /admin
        return str_starts_with(strtok($uri, '?'), '/admin');
    }


    // INTENDED URL — redirect về đúng trang sau khi login thành công

    /**
     * Lưu URL đang cố truy cập vào session (chỉ lưu GET request).
     * AuthController đọc và redirect về đây sau khi login thành công.
     *
     * @return void
     */
    private static function saveIntendedUrl(): void
    {
        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
            $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'] ?? '/admin/dashboard';
        }
    }

    /**
     * Lấy và xoá intended URL. Dùng trong AuthController sau khi login.
     *
     * @param  string $default URL fallback nếu không có intended URL.
     * @return string
     */
    public static function getIntendedUrl(string $default = '/admin/dashboard'): string
    {
        self::ensureSession();
        $url = $_SESSION['intended_url'] ?? $default;
        unset($_SESSION['intended_url']);
        return $url;
    }


    // TIỆN ÍCH NỘI BỘ

    private static function ensureSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    private static function redirectTo(string $url): void
    {
        header('Location: ' . str_replace(["\r", "\n"], '', $url));
        exit;
    }
}


/**
 * Class AdminController (Phần 2 — Quản lý đơn hàng)
 *
 * Tách riêng với phần manageProducts để leader review song song.
 * Khi merge: copy 3 method, 1 hằng số ORDER_STATUS_LABELS và 2 private method
 * vào AdminController.php chính.
 *
 * Nguồn sự thật cho trạng thái đơn hàng:
 *   Dùng OrderEntity::VALID_STATUSES — không tự định nghĩa lại trong Controller
 *   để tránh lệch giữa Controller, Service và Entity.
 *
 * CSRF methods (generateCsrfToken, verifyCsrfToken):
 *   Đã định nghĩa trong BaseController (protected) — AdminController kế thừa dùng thẳng,
 *   không cần định nghĩa lại ở đây.
 *
 * @package App\Controllers
 * @author  Ha Linh Technology Solutions
 */
class AdminController extends BaseController
{
    // HẰNG SỐ — nhãn hiển thị trạng thái đơn hàng

    /**
     * Nhãn tiếng Việt hiển thị cho từng trạng thái.
     * Key khớp chính xác với 5 hằng số public của OrderEntity:
     *   STATUS_PENDING / STATUS_CONFIRMED / STATUS_SHIPPED / STATUS_COMPLETED / STATUS_CANCELLED.
     * STATUS_LABELS trong OrderEntity là private nên Controller tự định nghĩa nhãn UI ở đây.
     */
    private const ORDER_STATUS_LABELS = [
        OrderEntity::STATUS_PENDING   => 'Đang chờ xác nhận',
        OrderEntity::STATUS_CONFIRMED => 'Đã xác nhận',
        OrderEntity::STATUS_SHIPPED   => 'Đang giao hàng',
        OrderEntity::STATUS_COMPLETED => 'Hoàn thành',
        OrderEntity::STATUS_CANCELLED => 'Đã huỷ',
    ];


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


    // QUẢN LÝ ĐƠN HÀNG

    /**
     * Hiển thị danh sách đơn hàng với lọc theo trạng thái và phân trang.
     * Hỗ trợ AJAX để lọc realtime không reload trang.
     * Flash message được đọc và truyền vào view để hiển thị kết quả thao tác trước đó.
     *
     * Route: GET /admin/orders
     * Route: GET /admin/orders?status=pending&page=2
     *
     * @return void
     */
    public function manageOrders(): void
    {
        $this->requireAdmin();

        $status = $this->get('status', '');
        $page   = $this->get('page', 1);

        // Validate status — dùng getValidStatuses() để có 1 nguồn sự thật trong Controller
        if ($status !== '' && !in_array($status, self::getValidStatuses(), true)) {
            $status = '';
        }

        try {
            $orders     = $this->orderService->getOrdersByStatus($status, $page);
            $totalPages = $this->orderService->countOrderPages($status);
            // Đếm số đơn theo từng trạng thái — hiển thị badge số lượng trên tab lọc
            $stats      = $this->orderService->getOrderCountByStatus();
        } catch (RuntimeException $e) {
            error_log('[AdminController::manageOrders] ' . $e->getMessage());
            $this->renderAdmin('admin/orders/list', [
                'orders'        => [],
                'totalPages'    => 1,
                'currentPage'   => 1,
                'currentStatus' => $status,
                'statusLabels'  => self::ORDER_STATUS_LABELS,
                'stats'         => [],
                'error'         => 'Không thể tải danh sách đơn hàng. Vui lòng thử lại.',
            ], 'Quản lý đơn hàng', 'orders');
            return;
        }

        // AJAX → trả JSON cho lọc realtime
        if ($this->isAjax()) {
            $this->jsonResponse([
                'success'    => true,
                'orders'     => array_map(fn($o) => $o->toArray(), $orders),
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
            'statusLabels'  => self::ORDER_STATUS_LABELS,
            'stats'         => $stats,
        ], 'Quản lý đơn hàng', 'orders');
    }

    /**
     * Hiển thị chi tiết một đơn hàng: thông tin đơn + danh sách sản phẩm.
     * Có form cập nhật trạng thái inline ngay trong trang chi tiết.
     *
     * Route: GET /admin/orders/detail?id=5
     *
     * @return void
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
            'statusLabels'  => self::ORDER_STATUS_LABELS,
            'validStatuses' => self::getValidStatuses(),
            'csrf_token'    => $this->generateCsrfToken(),
        ], 'Chi tiết đơn hàng #' . $id . ' - Quản trị', 'orders');
    }

    /**
     * Cập nhật trạng thái đơn hàng.
     * Chỉ chấp nhận POST + CSRF token hợp lệ.
     * Hỗ trợ AJAX (trả JSON) và form thông thường (redirect + flash message).
     *
     * Route: POST /admin/orders/update-status
     * Body:  id (int), status (string), csrf_token (string)
     *
     * @return void
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

        // Validate status từ hằng số public của OrderEntity
        if (!in_array($newStatus, self::getValidStatuses(), true)) {
            $this->respondError('Trạng thái không hợp lệ.', 400, '/admin/orders/detail?id=' . $id);
            return;
        }

        try {
            $order = $this->orderService->getOrderById($id);
        } catch (RuntimeException $e) {
            error_log('[AdminController::updateOrderStatus] getOrderById: ' . $e->getMessage());
            $this->respondError('Lỗi hệ thống. Vui lòng thử lại.', 500, '/admin/orders');
            return;
        }

        if ($order === null) {
            $this->respondError('Không tìm thấy đơn hàng #' . $id . '.', 404, '/admin/orders');
            return;
        }

        if ($order->getStatus() === $newStatus) {
            $label = self::ORDER_STATUS_LABELS[$newStatus] ?? $newStatus;
            $this->respondError("Đơn hàng đã ở trạng thái '{$label}'.", 400, '/admin/orders/detail?id=' . $id, FlashMessage::TYPE_WARNING);
            return;
        }

        try {
            $updated = $this->orderService->updateOrderStatus($id, $newStatus);
        } catch (RuntimeException $e) {
            error_log('[AdminController::updateOrderStatus] update: ' . $e->getMessage());
            $this->respondError('Lỗi hệ thống khi cập nhật. Vui lòng thử lại.', 500, '/admin/orders/detail?id=' . $id);
            return;
        }

        $label = self::ORDER_STATUS_LABELS[$newStatus] ?? $newStatus;

        if ($updated) {
            $this->respondSuccess(
                "Đơn hàng #$id đã chuyển sang '{$label}'.",
                '/admin/orders/detail?id=' . $id,
                ['newStatus' => $newStatus, 'label' => $label]
            );
        } else {
            $this->respondError('Không thể cập nhật. Vui lòng thử lại.', 400, '/admin/orders/detail?id=' . $id);
        }
    }


    // HELPER — AJAX / FORM RESPONSE

    /**
     * Trả về mảng các trạng thái đơn hàng hợp lệ, build từ hằng số public của OrderEntity.
     * OrderEntity không có VALID_STATUSES array — dùng method này thay vì inline array
     * ở nhiều chỗ để có 1 nguồn sự thật duy nhất trong Controller.
     *
     * @return string[]
     */
    private static function getValidStatuses(): array
    {
        return [
            OrderEntity::STATUS_PENDING,
            OrderEntity::STATUS_CONFIRMED,
            OrderEntity::STATUS_SHIPPED,
            OrderEntity::STATUS_COMPLETED,
            OrderEntity::STATUS_CANCELLED,
        ];
    }

    /**
     * Trả lỗi nhất quán cho cả AJAX và form thông thường.
     * AJAX → jsonResponse với HTTP status code.
     * Form → FlashMessage + redirect.
     *
     * @param  string $message     Thông báo lỗi.
     * @param  int    $httpStatus  HTTP status code (400, 403, 404, 500, ...).
     * @param  string $redirectUrl URL redirect khi là form request.
     * @param  string $flashType   Loại flash (mặc định error, có thể dùng warning).
     * @return void
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
     * Trả kết quả thành công nhất quán cho cả AJAX và form thông thường.
     * AJAX → jsonResponse 200 kèm extra data.
     * Form → FlashMessage success + redirect.
     *
     * @param  string $message     Thông báo thành công.
     * @param  string $redirectUrl URL redirect khi là form request.
     * @param  array  $extraData   Dữ liệu bổ sung trả về trong AJAX response.
     * @return void
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


    // RENDER LAYOUT ADMIN

    /**
     * Gộp renderViewToString + renderView('layouts/admin') + adminUsername + flash.
     * renderAdmin() là nơi DUY NHẤT gọi FlashMessage::get() — các method không
     * truyền 'flash' vào $data nữa để tránh đọc 2 lần (lần 2 luôn rỗng).
     *
     * @param  string $view
     * @param  array  $data
     * @param  string $title
     * @param  string $activeMenu
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
            'flash'         => FlashMessage::get(), // đọc 1 lần duy nhất ở đây
        ]);
    }
}

/* Các vấn đề cần sửa: 
* renderAdmin() gọi FlashMessage::get() — nhưng các method như manageOrders() đã gọi FlashMessage::get() và truyền vào $data rồi, renderAdmin() lại gọi thêm lần 
nữa → messages bị đọc 2 lần, lần 2 luôn trả rỗng: Khi manageOrders() gọi renderAdmin(..., ['flash' => FlashMessage::get(), ...]) thì messages đã bị xoá khỏi 
session. renderAdmin() gọi lại FlashMessage::get() lần 2 → luôn rỗng → layout không bao giờ nhận được flash. Chọn 1 trong 2: bỏ FlashMessage::get() trong 
renderAdmin(), hoặc bỏ trong từng method và để renderAdmin() tự đọc.
* VALID_ORDER_STATUSES không khớp với OrderService — Service dùng 'confirmed' và 'completed', Controller dùng 'processing' và 'delivered': Khi admin chọn trạng 
thái 'delivered', Controller validate pass nhưng Service reject vì không có trong danh sách của Service. Cần thống nhất 1 nguồn sự thật — nên định nghĩa ở Entity 
hoặc Service rồi Controller import vào, không tự định nghĩa lại.
* verifyCsrfToken() và generateCsrfToken() được gọi nhưng không thấy định nghĩa ở đâu trong code đã gửi
* updateOrderStatus() quá dài — ~80 dòng, lặp pattern isAjax() ? jsonResponse : FlashMessage + redirect đến 5 lần: Nên tách helper respondError(string $msg, 
int $status, string $redirectUrl) để gộp 2 nhánh AJAX/non-AJAX lại. Giảm từ ~80 dòng xuống ~40 dòng, dễ đọc hơn nhiều.
*/
