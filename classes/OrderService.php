<?php

/**
 * Class OrderService
 *
 * Chịu trách nhiệm điều phối toàn bộ quy trình đặt hàng:
 * - Nhận dữ liệu từ Controller
 * - Kiểm tra hợp lệ
 * - Tính tổng tiền
 * - Áp dụng khuyến mãi
 * - Lưu dữ liệu xuống database
 *
 * Nguyên tắc:
 * - Service KHÔNG xử lý chi tiết logic (ví dụ: tính discount)
 * - Chỉ gọi các lớp chuyên trách (Entity / Model)
 *
 * @package App\Services
 * @author  Ha Linh Technology Solutions
 */
class OrderService
{
    // =========================================================================
    // THUỘC TÍNH
    // =========================================================================

    private OrderModel       $orderModel;
    private OrderDetailModel $detailModel;
    private ProductModel     $productModel;
    private CustomerModel    $customerModel;
    private InventoryModel   $inventoryModel;
    private PromotionModel   $promotionModel;

    /**
     * Danh sách trạng thái hợp lệ — nguồn sự thật duy nhất ở tầng Service.
     * OrderModel::assertValidStatus() tham chiếu về đây thay vì tự định nghĩa riêng
     * → tránh hai nơi định nghĩa cùng một dữ liệu (đã fix vấn đề #9).
     *
     * AdminController và View import từ đây thay vì tự định nghĩa.
     */
    public const VALID_STATUSES = [
        'pending',
        'confirmed',
        'shipped',
        'completed',
        'cancelled',
    ];

    /**
     * Nhãn tiếng Việt cho từng trạng thái — dùng trong View / flash message.
     */
    public const STATUS_LABELS = [
        'pending'   => 'Chờ xử lý',
        'confirmed' => 'Đã xác nhận',
        'shipped'   => 'Đang giao',
        'completed' => 'Hoàn thành',
        'cancelled' => 'Đã huỷ',
    ];


    // =========================================================================
    // CONSTRUCTOR
    // =========================================================================

    /**
     * Inject dependency để dễ test và tách biệt các tầng.
     */
    public function __construct(
        OrderModel       $orderModel,
        OrderDetailModel $detailModel,
        ProductModel     $productModel,
        CustomerModel    $customerModel,
        InventoryModel   $inventoryModel,
        PromotionModel   $promotionModel
    ) {
        $this->orderModel     = $orderModel;
        $this->detailModel    = $detailModel;
        $this->productModel   = $productModel;
        $this->customerModel  = $customerModel;
        $this->inventoryModel = $inventoryModel;
        $this->promotionModel = $promotionModel;
    }


    // =========================================================================
    // TẠO ĐƠN HÀNG
    // =========================================================================

    /**
     * Tạo đơn hàng từ giỏ hàng.
     *
     * Luồng xử lý:
     * 1. Validate dữ liệu khách hàng
     * 2. Kiểm tra giỏ hàng không trống
     * 3. Duyệt từng sản phẩm: kiểm tra tồn tại + tồn kho + tính tổng
     * 4. Áp dụng khuyến mãi nếu có (check canUse với tổng GỐC)
     * 5. Transaction: tạo Customer → Order → OrderDetail → trừ tồn kho (atomic)
     * 6. Sau transaction: xoá cart + tăng used_count mã giảm giá
     *
     * @param  array       $formData      Thông tin khách hàng từ form.
     * @param  array       $cart          Giỏ hàng dạng [['product_id'=>1,'quantity'=>2],...].
     * @param  string|null $promotionCode Mã khuyến mãi người dùng nhập (null nếu không có).
     * @return array                      ['success'=>bool, 'order_id'=>int] hoặc ['success'=>false, 'message'=>string]
     */
    public function createOrderFromCart(
        array   $formData,
        array   $cart,
        ?string $promotionCode = null
    ): array {
        // ── Bước 1: Validate form ────────────────────────────────────────────
        $validationError = $this->validateFormData($formData);
        if ($validationError !== null) {
            return ['success' => false, 'message' => $validationError];
        }

        // ── Bước 2: Kiểm tra giỏ hàng ────────────────────────────────────────
        if (empty($cart)) {
            return ['success' => false, 'message' => 'Giỏ hàng trống.'];
        }

        // ── Bước 3: Duyệt giỏ hàng — kiểm tra sản phẩm + tồn kho + tính tổng
        $enrichedItems = [];
        $totalPrice    = 0.0;

        foreach ($cart as $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            $quantity  = (int) ($item['quantity']   ?? 0);

            if ($productId <= 0 || $quantity <= 0) {
                return ['success' => false, 'message' => 'Dữ liệu giỏ hàng không hợp lệ.'];
            }

            $product = $this->productModel->getById($productId);
            if ($product === null) {
                return ['success' => false, 'message' => "Sản phẩm ID={$productId} không tồn tại."];
            }

            // checkStock() kiểm tra sơ bộ trước transaction để phản hồi nhanh cho user.
            // Việc trừ tồn kho thực sự dùng atomic UPDATE bên trong transaction (bước 5)
            // → tránh oversell khi 2 request đồng thời (đã fix vấn đề #3).
            $stockError = $this->checkStock($product, $quantity);
            if ($stockError !== null) {
                return ['success' => false, 'message' => $stockError];
            }

            $price       = $product->getPrice();
            $totalPrice += $price * $quantity;

            $enrichedItems[] = [
                'product_id' => $product->getId(),
                'quantity'   => $quantity,
                'price'      => $price,
                'product'    => $product,
            ];
        }

        // ── Bước 4: Áp dụng khuyến mãi ───────────────────────────────────────
        $promo         = null;
        $originalTotal = $totalPrice; // lưu tổng GỐC — dùng để check canUse lại trong transaction

        if ($promotionCode !== null) {
            $promo = $this->promotionModel->getByCode($promotionCode);

            if ($promo === null) {
                return ['success' => false, 'message' => 'Mã khuyến mãi không tồn tại.'];
            }

            // canUse() nhận tổng GỐC — không phải tổng đã giảm
            // tránh: đơn 500k, giảm 100k, minOrder 450k → check canUse(400k) = false (sai)
            if (!$promo->canUse($originalTotal)) {
                return [
                    'success' => false,
                    'message' => $promo->getFailMessage($originalTotal),
                ];
            }

            $discount   = $promo->calculateDiscount($originalTotal);
            $totalPrice = max(0, $totalPrice - $discount);
        }

        // ── Bước 5: Transaction ───────────────────────────────────────────────
        try {
            $orderId = $this->orderModel->transaction(
                function () use ($formData, $enrichedItems, $totalPrice, $originalTotal, $promo): int {

                    // Check lại canUse với tổng GỐC trong transaction
                    // — phòng race condition: 2 request đồng thời dùng cùng mã
                    if ($promo !== null && !$promo->canUse($originalTotal)) {
                        throw new RuntimeException($promo->getFailMessage($originalTotal));
                    }

                    // Tạo hoặc cập nhật khách hàng
                    // resolveCustomer() chạy trong transaction để đảm bảo
                    // customer_id luôn hợp lệ khi insert orders
                    $customerId = $this->resolveCustomer($formData);

                    // Tạo đơn hàng
                    $orderId = $this->orderModel->insert([
                        'customer_id'  => $customerId,
                        'user_id'      => $formData['user_id'] ?? null,
                        'total_price'  => $totalPrice,
                        'status'       => 'pending',
                        'note'         => trim($formData['note'] ?? ''),
                        'created_at'   => date('Y-m-d H:i:s'),
                        'promotion_id' => $promo?->getId(),
                    ]);

                    if ($orderId <= 0) {
                        throw new RuntimeException('Tạo đơn hàng thất bại.');
                    }

                    // Lưu chi tiết đơn
                    foreach ($enrichedItems as $item) {
                        $this->detailModel->insert([
                            'order_id'          => $orderId,
                            'product_id'        => $item['product_id'],
                            'quantity'          => $item['quantity'],
                            'price_at_purchase' => $item['price'],
                        ]);
                    }

                    // Trừ tồn kho bằng atomic UPDATE — tránh oversell (đã fix vấn đề #3).
                    // decreaseStock() dùng: UPDATE inventory SET quantity = quantity - ?
                    //                       WHERE product_id = ? AND quantity >= ?
                    // Nếu rowCount() = 0 nghĩa là hàng vừa hết → throw để rollback toàn bộ
                    foreach ($enrichedItems as $item) {
                        if (!$this->inventoryModel->decreaseStock(
                            $item['product_id'],
                            $item['quantity']
                        )) {
                            throw new RuntimeException(
                                "Không thể cập nhật tồn kho cho sản phẩm \"{$item['product']->getName()}\". "
                                . 'Sản phẩm có thể vừa hết hàng.'
                            );
                        }
                    }

                    return $orderId;
                }
            );

            // ── Bước 6: Sau transaction thành công ───────────────────────────
            // clearCart() đặt NGOÀI transaction — session không rollback được,
            // nếu đặt bên trong mà sau đó rollback thì cart đã bị xoá mất
            SessionHelper::clearCart();

            // increaseUsedCount() đặt NGOÀI transaction — nếu tăng thất bại
            // không nên rollback cả đơn hàng đã tạo thành công.
            // Bọc try-catch riêng để log lỗi mà không ảnh hưởng response (đã fix vấn đề #7).
            if ($promo !== null) {
                try {
                    $this->promotionModel->increaseUsedCount($promo->getId());
                } catch (Throwable $e) {
                    // Đơn hàng vẫn thành công — chỉ log để admin xử lý thủ công nếu cần
                    error_log(
                        '[OrderService] increaseUsedCount thất bại cho promo ID='
                        . $promo->getId() . ': ' . $e->getMessage()
                    );
                }
            }

            return ['success' => true, 'order_id' => $orderId];

        } catch (RuntimeException $e) {
            // Lỗi logic nghiệp vụ → thông báo chi tiết có thể hiển thị cho user
            return ['success' => false, 'message' => $e->getMessage()];

        } catch (Throwable $e) {
            // Lỗi không mong đợi → log nội bộ, trả thông báo chung
            error_log('[OrderService::createOrderFromCart] ' . $e->getMessage());
            return ['success' => false, 'message' => 'Lỗi hệ thống. Vui lòng thử lại sau.'];
        }
    }


    // =========================================================================
    // QUẢN LÝ TRẠNG THÁI ĐƠN HÀNG (DÙNG CHO ADMIN)
    // =========================================================================

    /**
     * Cập nhật trạng thái đơn hàng.
     *
     * Luồng xử lý:
     * 1. Validate orderId và status mới
     * 2. Lấy đơn hàng hiện tại — kiểm tra tồn tại
     * 3. Kiểm tra chuyển trạng thái có hợp lệ không (state machine)
     * 4. Nếu huỷ đơn (cancelled) → hoàn lại tồn kho trong transaction
     * 5. Nếu trạng thái khác → cập nhật đơn thuần
     *
     * Luồng trạng thái hợp lệ:
     *   pending   → confirmed | cancelled
     *   confirmed → shipped   | cancelled
     *   shipped   → completed | cancelled
     *   completed → (không chuyển được — đơn đã hoàn thành)
     *   cancelled → (không chuyển được — đơn đã huỷ)
     *
     * @param  int    $orderId   ID đơn hàng cần cập nhật.
     * @param  string $newStatus Trạng thái mới muốn chuyển sang.
     * @return array             ['success'=>bool, 'message'=>string]
     */
    public function updateOrderStatus(int $orderId, string $newStatus): array
    {
        // ── Bước 1: Validate đầu vào ─────────────────────────────────────────
        if ($orderId <= 0) {
            return ['success' => false, 'message' => 'ID đơn hàng không hợp lệ.'];
        }

        if (!in_array($newStatus, self::VALID_STATUSES, true)) {
            return [
                'success' => false,
                'message' => 'Trạng thái không hợp lệ. Chấp nhận: '
                    . implode(', ', self::VALID_STATUSES),
            ];
        }

        // ── Bước 2: Lấy đơn hàng hiện tại ───────────────────────────────────
        $order = $this->orderModel->getById($orderId);

        if ($order === null) {
            return ['success' => false, 'message' => "Không tìm thấy đơn hàng ID={$orderId}."];
        }

        $currentStatus = $order['status'];

        // ── Bước 3: Kiểm tra state machine ───────────────────────────────────
        // Không cho phép cập nhật nếu trạng thái hiện tại là terminal
        $terminalStatuses = ['completed', 'cancelled'];
        if (in_array($currentStatus, $terminalStatuses, true)) {
            return [
                'success' => false,
                'message' => 'Đơn hàng đã ở trạng thái "'
                    . (self::STATUS_LABELS[$currentStatus] ?? $currentStatus)
                    . '" — không thể thay đổi.',
            ];
        }

        // Không cho phép chuyển về trạng thái trước đó (đi ngược luồng)
        $statusOrder = array_flip(['pending', 'confirmed', 'shipped', 'completed']);
        $isDowngrade = isset($statusOrder[$newStatus], $statusOrder[$currentStatus])
            && $statusOrder[$newStatus] < $statusOrder[$currentStatus]
            && $newStatus !== 'cancelled';

        if ($isDowngrade) {
            return [
                'success' => false,
                'message' => 'Không thể chuyển đơn hàng từ "'
                    . (self::STATUS_LABELS[$currentStatus] ?? $currentStatus)
                    . '" về "'
                    . (self::STATUS_LABELS[$newStatus] ?? $newStatus)
                    . '".',
            ];
        }

        // Không cập nhật nếu trạng thái không thay đổi
        if ($currentStatus === $newStatus) {
            return [
                'success' => false,
                'message' => 'Đơn hàng đã ở trạng thái "'
                    . (self::STATUS_LABELS[$newStatus] ?? $newStatus)
                    . '" rồi.',
            ];
        }

        // ── Bước 4: Huỷ đơn → hoàn lại tồn kho trong transaction ────────────
        if ($newStatus === 'cancelled') {
            return $this->cancelOrderWithRestock($orderId, $currentStatus);
        }

        // ── Bước 5: Chuyển trạng thái thông thường ───────────────────────────
        try {
            $updated = $this->orderModel->update($orderId, ['status' => $newStatus]);

            if (!$updated) {
                return [
                    'success' => false,
                    'message' => 'Cập nhật trạng thái thất bại. Vui lòng thử lại.',
                ];
            }

            return [
                'success'     => true,
                'message'     => 'Đã chuyển sang "'
                    . (self::STATUS_LABELS[$newStatus] ?? $newStatus) . '".',
                'old_status'  => $currentStatus,
                'new_status'  => $newStatus,
            ];

        } catch (Throwable $e) {
            error_log('[OrderService::updateOrderStatus] ' . $e->getMessage());
            return ['success' => false, 'message' => 'Lỗi hệ thống. Vui lòng thử lại sau.'];
        }
    }

    /**
     * Huỷ đơn hàng và hoàn lại tồn kho trong một transaction.
     *
     * Tách riêng khỏi updateOrderStatus() để:
     * - Logic hoàn kho phức tạp không làm nặng method chính
     * - Dễ test riêng lẻ
     * - Chỉ gọi khi $newStatus === 'cancelled'
     *
     * Chỉ hoàn kho khi đơn đang ở trạng thái đã trừ kho thực sự:
     *   - pending   → đã trừ kho lúc tạo đơn → cần hoàn
     *   - confirmed → đã trừ kho              → cần hoàn
     *   - shipped   → đã trừ kho              → cần hoàn
     * (Nếu sau này thêm logic trừ kho lúc confirmed thay vì lúc tạo,
     *  chỉ cần sửa điều kiện ở đây, không ảnh hưởng chỗ khác.)
     *
     * @param  int    $orderId       ID đơn hàng cần huỷ.
     * @param  string $currentStatus Trạng thái hiện tại của đơn (đã validate trước khi gọi).
     * @return array  ['success'=>bool, 'message'=>string]
     */
    private function cancelOrderWithRestock(int $orderId, string $currentStatus): array
    {
        // Các trạng thái đã trừ kho → cần hoàn khi huỷ
        $statusesWithDeductedStock = ['pending', 'confirmed', 'shipped'];
        $shouldRestock = in_array($currentStatus, $statusesWithDeductedStock, true);

        try {
            $this->orderModel->transaction(function () use ($orderId, $shouldRestock): void {

                // Cập nhật trạng thái đơn hàng thành cancelled
                $updated = $this->orderModel->update($orderId, ['status' => 'cancelled']);
                if (!$updated) {
                    throw new RuntimeException('Không thể cập nhật trạng thái đơn hàng.');
                }

                if (!$shouldRestock) {
                    return; // Không cần hoàn kho
                }

                // Lấy danh sách sản phẩm trong đơn để hoàn kho
                $items = $this->detailModel->getDetailsByOrder($orderId);

                if (empty($items)) {
                    // Không có chi tiết đơn → không hoàn kho, không throw
                    // (có thể đơn được tạo thủ công không qua giỏ hàng)
                    return;
                }

                foreach ($items as $item) {
                    // increaseStock() cộng lại số lượng vào inventory
                    // Nếu fail → throw để rollback toàn bộ (cả việc đổi status)
                    // → tránh tình trạng đơn bị huỷ nhưng kho không được hoàn
                    if (!$this->inventoryModel->increaseStock(
                        (int) $item['product_id'],
                        (int) $item['quantity']
                    )) {
                        throw new RuntimeException(
                            'Không thể hoàn tồn kho cho sản phẩm ID='
                            . $item['product_id'] . '. Huỷ đơn bị từ chối.'
                        );
                    }
                }
            });

            $restockNote = $shouldRestock ? ' Tồn kho đã được hoàn lại.' : '';

            return [
                'success'     => true,
                'message'     => 'Đơn hàng đã được huỷ.' . $restockNote,
                'old_status'  => $currentStatus,
                'new_status'  => 'cancelled',
            ];

        } catch (RuntimeException $e) {
            return ['success' => false, 'message' => $e->getMessage()];

        } catch (Throwable $e) {
            error_log('[OrderService::cancelOrderWithRestock] ' . $e->getMessage());
            return ['success' => false, 'message' => 'Lỗi hệ thống khi huỷ đơn. Vui lòng thử lại.'];
        }
    }

    /**
     * Lấy một đơn hàng theo ID — dùng cho trang chi tiết admin.
     *
     * Trả về array|null thay vì OrderEntity|null để nhất quán với
     * kiểu trả về thực tế của OrderModel::getById() (đã fix vấn đề #6).
     * Controller/View nhận array và truy cập trực tiếp qua key.
     *
     * @param  int        $orderId
     * @return array|null
     */
    public function getOrderById(int $orderId): ?array
    {
        if ($orderId <= 0) {
            return null;
        }

        return $this->orderModel->getById($orderId);
    }

    /**
     * Lấy danh sách chi tiết sản phẩm trong một đơn hàng.
     *
     * @param  int   $orderId
     * @return array
     */
    public function getOrderItems(int $orderId): array
    {
        return $this->detailModel->getDetailsByOrder($orderId);
    }

    /**
     * Lấy danh sách đơn hàng có lọc theo trạng thái và phân trang.
     * Dùng cho trang danh sách đơn hàng của admin.
     *
     * Gọi đúng tên method đã có trong OrderModel: paginateWithFilter()
     * (đã fix vấn đề #4 — tên cũ getByStatusPaginated() không tồn tại).
     *
     * @param  string $status  '' = lấy tất cả, hoặc 1 trong VALID_STATUSES
     * @param  int    $page    Trang hiện tại (bắt đầu từ 1)
     * @param  int    $limit   Số đơn mỗi trang
     * @return array           Mảng ['data', 'total', 'currentPage', 'totalPages', 'limit', 'status']
     */
    public function getOrdersByStatus(string $status = '', int $page = 1, int $limit = 15): array
    {
        // Chuyển string rỗng thành null — paginateWithFilter() dùng null để lấy tất cả
        return $this->orderModel->paginateWithFilter($page, $limit, $status ?: null);
    }

    /**
     * Đếm tổng số trang đơn hàng theo trạng thái — dùng để render pagination.
     *
     * Gọi OrderModel::count() đã override với optional status
     * thay vì countByStatus() không nhận tham số (đã fix vấn đề #5).
     *
     * @param  string $status '' = đếm tất cả
     * @param  int    $limit
     * @return int
     */
    public function countOrderPages(string $status = '', int $limit = 15): int
    {
        $total = $this->orderModel->count($status ?: null);
        return $total > 0 ? (int) ceil($total / $limit) : 0;
    }

    /**
     * Đếm số đơn hàng theo từng trạng thái — dùng để hiển thị badge số lượng trên tab lọc.
     *
     * Dùng OrderModel::getStatusSummary() — 1 query GROUP BY thay vì
     * 5 query riêng trong vòng foreach (đã fix vấn đề #1).
     *
     * Luôn trả đủ 5 trạng thái kể cả khi total = 0 → View không cần kiểm tra key tồn tại.
     *
     * @return array<string, int> ['pending' => 5, 'confirmed' => 3, ...]
     */
    public function getOrderCountByStatus(): array
    {
        // getStatusSummary() trả đủ 5 key với giá trị 0 nếu chưa có đơn
        return $this->orderModel->getStatusSummary();
    }

    /**
     * Đếm tổng số đơn hàng — dùng cho dashboard admin.
     *
     * @param  string|null $status null = đếm tất cả
     * @return int
     */
    public function countOrders(?string $status = null): int
    {
        return $this->orderModel->count($status);
    }

    /**
     * Lấy doanh thu theo tháng/năm — dùng cho widget dashboard.
     *
     * @param  int $year
     * @param  int $month
     * @return float
     */
    public function getRevenueByMonth(int $year, int $month): float
    {
        return $this->orderModel->getRevenueByMonth($year, $month);
    }

    /**
     * Lấy danh sách đơn hàng gần đây — dùng cho widget dashboard.
     *
     * @param  int $limit
     * @return array
     */
    public function getRecentOrders(int $limit = 5): array
    {
        return $this->orderModel->getRecentOrders($limit);
    }


    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

    /**
     * Validate thông tin khách hàng từ form đặt hàng.
     * Uỷ thác cho ValidatorHelper — Service không tự validate chuỗi.
     *
     * Sanitize toàn bộ mảng trước khi validate — đảm bảo các trường
     * như note, user_id cũng được làm sạch trước khi dùng (đã fix vấn đề #8).
     *
     * @param  array       $formData
     * @return string|null Thông báo lỗi đầu tiên, hoặc null nếu hợp lệ.
     */
    private function validateFormData(array $formData): ?string
    {
        // Sanitize toàn bộ mảng — bao gồm cả note, user_id
        $data = ValidatorHelper::sanitizeInput($formData);

        $requiredFields = [
            'name'    => 'Họ tên',
            'email'   => 'Email',
            'phone'   => 'Số điện thoại',
            'address' => 'Địa chỉ',
        ];

        foreach ($requiredFields as $field => $label) {
            $result = ValidatorHelper::validateRequired($data[$field] ?? '', $label);
            if ($result !== true) {
                return $result;
            }
        }

        $emailResult = ValidatorHelper::validateEmail($data['email']);
        if ($emailResult !== true) {
            return $emailResult;
        }

        $phoneResult = ValidatorHelper::validatePhone($data['phone']);
        if ($phoneResult !== true) {
            return $phoneResult;
        }

        return null;
    }

    /**
     * Kiểm tra sơ bộ tồn kho trước transaction — phản hồi nhanh cho user.
     *
     * Lưu ý: đây là kiểm tra pre-flight, không phải nguồn đảm bảo duy nhất.
     * Việc đảm bảo không oversell thực sự do atomic UPDATE trong decreaseStock()
     * bên trong transaction (WHERE quantity >= ?) xử lý.
     *
     * @param  ProductEntity $product
     * @param  int           $requestedQty
     * @return string|null   Thông báo lỗi hoặc null nếu đủ hàng.
     */
    private function checkStock(ProductEntity $product, int $requestedQty): ?string
    {
        $inventory = $this->inventoryModel->getByProductId($product->getId());

        if ($inventory === null) {
            return "Sản phẩm \"{$product->getName()}\" hiện không có thông tin tồn kho.";
        }

        $available = $inventory->getQuantity();

        if ($available < $requestedQty) {
            return "Sản phẩm \"{$product->getName()}\" không đủ hàng "
                . "(còn {$available}, yêu cầu {$requestedQty}).";
        }

        return null;
    }

    /**
     * Tạo mới hoặc cập nhật thông tin Customer theo email.
     *
     * Quy tắc:
     * - Email đã tồn tại → cập nhật thông tin mới nhất qua updateInfo() (có validate)
     * - Chưa tồn tại → tạo mới
     *
     * Chạy bên trong transaction để đảm bảo customer_id hợp lệ trước khi insert orders.
     *
     * Race condition (2 user cùng email đặt hàng đồng thời): trường hợp cả 2 cùng thấy
     * existing = null và cùng INSERT sẽ bị DB từ chối do UNIQUE constraint trên email.
     * Exception sẽ được bắt bởi transaction() → rollback an toàn. Đây là hạn chế đã biết,
     * cần xử lý bằng INSERT ... ON DUPLICATE KEY UPDATE nếu cần production-grade.
     *
     * Dùng updateInfo() thay vì update() thẳng để đi qua Entity validate —
     * tránh ghi dữ liệu bẩn vào DB.
     *
     * @param  array $formData Đã được sanitize qua validateFormData()
     * @return int   customer_id
     * @throws RuntimeException Nếu không thể tạo khách hàng.
     */
    private function resolveCustomer(array $formData): int
    {
        $email    = trim($formData['email']);
        $existing = $this->customerModel->getByEmail($email);

        if ($existing !== null) {
            // Dùng updateInfo() — có validate qua CustomerEntity trước khi update
            // Không dùng BaseModel::update() thẳng vì bỏ qua validate
            $this->customerModel->updateInfo($existing->getId(), [
                'name'    => trim($formData['name']),
                'email'   => $email,
                'phone'   => trim($formData['phone']),
                'address' => trim($formData['address']),
                'note'    => trim($formData['note'] ?? ''),
            ]);

            return $existing->getId();
        }

        // Tạo khách hàng mới
        $customerId = $this->customerModel->insert([
            'name'    => trim($formData['name']),
            'email'   => $email,
            'phone'   => trim($formData['phone']),
            'address' => trim($formData['address']),
            'user_id' => $formData['user_id'] ?? null,
            'note'    => trim($formData['note'] ?? ''),
        ]);

        if ($customerId <= 0) {
            throw new RuntimeException('Không thể tạo thông tin khách hàng.');
        }

        return $customerId;
    }
}
