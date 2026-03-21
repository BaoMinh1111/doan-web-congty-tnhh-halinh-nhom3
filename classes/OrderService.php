<?php

/**
 * Class OrderService
 *
 * Xử lý toàn bộ quy trình tạo đơn hàng từ giỏ hàng (session-based cart).
 * Không bao gồm logic khuyến mãi (promotion) — sẽ mở rộng sau.
 *
 * @package App\Services
 * @author  Ha Linh Technology Solutions
 */
class OrderService
{
    // =========================================================================
    // THUỘC TÍNH
    // =========================================================================

    private OrderModel      $orderModel;
    private OrderDetailModel $detailModel;
    private ProductModel    $productModel;
    private CustomerModel   $customerModel;
    private InventoryModel  $inventoryModel;


    // =========================================================================
    // CONSTRUCTOR
    // =========================================================================

    /**
     * Inject tất cả Model cần thiết qua constructor (Dependency Injection).
     * Không dùng new Model() bên trong service → dễ test, dễ thay thế.
     */
    public function __construct(
        OrderModel       $orderModel,
        OrderDetailModel $detailModel,
        ProductModel     $productModel,
        CustomerModel    $customerModel,
        InventoryModel   $inventoryModel
    ) {
        $this->orderModel     = $orderModel;
        $this->detailModel    = $detailModel;
        $this->productModel   = $productModel;
        $this->customerModel  = $customerModel;
        $this->inventoryModel = $inventoryModel;
    }


    // =========================================================================
    // PUBLIC API
    // =========================================================================

    /**
     * Tạo đơn hàng từ giỏ hàng (session cart) — không có khuyến mãi.
     *
     * @param  array      $formData  Thông tin khách hàng từ form đặt hàng.
     *                               Cần có: name, email, phone, address.
     *                               Tuỳ chọn: note, user_id (nếu đã đăng nhập).
     * @param  array      $cart      Giỏ hàng dạng:
     *                               [
     *                                 ['product_id' => 1, 'quantity' => 2],
     *                                 ['product_id' => 3, 'quantity' => 1],
     *                               ]
     * @return array                 Kết quả:
     *                               ['success' => true,  'order_id' => 42]
     *                               ['success' => false, 'message'  => '...']
     */
    public function createOrderFromCart(array $formData, array $cart): array
    {
        // ── Bước 1: Validate form ────────────────────────────────────────────
        $validationError = $this->validateFormData($formData);
        if ($validationError !== null) {
            return ['success' => false, 'message' => $validationError];
        }

        // ── Bước 2: Validate giỏ hàng ────────────────────────────────────────
        if (empty($cart)) {
            return ['success' => false, 'message' => 'Giỏ hàng trống, không thể tạo đơn hàng.'];
        }

        // ── Bước 3 & 4: Kiểm tra tồn kho + tính tổng tiền ───────────────────
        // Gộp 2 bước vào một lần duyệt cart để tránh duyệt 2 lần
        $enrichedItems = [];  // cart items đã được bổ sung thông tin sản phẩm
        $totalPrice    = 0.0;

        foreach ($cart as $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            $quantity  = (int) ($item['quantity']   ?? 0);

            if ($productId <= 0 || $quantity <= 0) {
                return [
                    'success' => false,
                    'message' => 'Dữ liệu giỏ hàng không hợp lệ (product_id hoặc quantity <= 0).',
                ];
            }

            // Lấy thông tin sản phẩm dưới dạng ProductEntity
            // ProductModel::getById() trả ?ProductEntity (đã override từ BaseModel)
            $product = $this->productModel->getById($productId);
            if ($product === null) {
                return [
                    'success' => false,
                    'message' => "Sản phẩm ID={$productId} không tồn tại hoặc đã bị xoá.",
                ];
            }

            // Kiểm tra tồn kho — truyền Entity thay vì mảng, checkStock tự gọi getter
            $stockError = $this->checkStock($product, $quantity);
            if ($stockError !== null) {
                return ['success' => false, 'message' => $stockError];
            }

            // Dùng getter — type-safe, không phụ thuộc tên key mảng DB
            $priceAtPurchase = $product->getPrice();
            $totalPrice     += $priceAtPurchase * $quantity;

            $enrichedItems[] = [
                'product_id'        => $product->getId(),
                'quantity'          => $quantity,
                'price_at_purchase' => $priceAtPurchase,
                'product_name'      => $product->getName(), // dùng để log/debug & thông báo lỗi
                // Giữ toàn bộ Entity để dùng sau transaction nếu cần
                // Không ảnh hưởng đến dữ liệu ghi vào DB vì detailModel->insert() chỉ dùng
                // product_id, quantity, price_at_purchase — không đọc key 'product' này.
                'product'           => $product,
            ];
        }

        // ── Bước 5–8: Tạo Customer + Order + OrderDetail + Trừ tồn kho ──────
        // Toàn bộ bước này chạy trong transaction.
        // Nếu bất kỳ bước nào thất bại → rollback toàn bộ → CSDL không bị dở dang.
        try {
            $orderId = $this->orderModel->transaction(function () use (
                $formData,
                $enrichedItems,
                $totalPrice
            ): int {
                // Bước 5: Tạo hoặc lấy Customer
                $customerId = $this->resolveCustomer($formData);

                // Bước 6: Tạo bản ghi Order
                $orderId = $this->orderModel->insert([
                    'customer_id' => $customerId,
                    'user_id'     => $formData['user_id'] ?? null,
                    'total_price' => $totalPrice,
                    'status'      => 'pending',
                    'note'        => trim($formData['note'] ?? ''),
                    'created_at'  => date('Y-m-d H:i:s'),
                ]);

                if ($orderId <= 0) {
                    // insert() trả 0 → không có auto-increment id → throw để rollback
                    throw new RuntimeException('Tạo đơn hàng thất bại (insert không trả về ID).');
                }

                // Bước 7: Tạo từng dòng OrderDetail
                foreach ($enrichedItems as $item) {
                    $inserted = $this->detailModel->insert([
                        'order_id'         => $orderId,
                        'product_id'       => $item['product_id'],
                        'quantity'         => $item['quantity'],
                        'price_at_purchase' => $item['price_at_purchase'],
                    ]);

                    // detailModel->insert() trả 0 với bảng composite key → kiểm tra rowCount thay thế
                    // Ở đây dùng exception nếu insert thất bại (prepareStmt đã throw RuntimeException)
                    // nên không cần check thêm. Nhưng nếu muốn chắc chắn hơn thì check $inserted.
                }

                // Bước 8: Trừ tồn kho từng sản phẩm
                foreach ($enrichedItems as $item) {
                    $decreased = $this->inventoryModel->decreaseStock(
                        $item['product_id'],
                        $item['quantity']
                    );

                    if (!$decreased) {
                        // decreaseStock() trả false khi tồn kho không đủ hoặc không tìm thấy
                        throw new RuntimeException(
                            "Trừ tồn kho thất bại cho sản phẩm \"{$item['product_name']}\"."
                        );
                    }
                }

                return $orderId;
            });

            // Xoá giỏ hàng khỏi session SAU KHI transaction thành công.
            // Đặt ở đây (ngoài transaction) vì session không phải DB → không rollback được.
            // Nếu đặt bên trong transaction mà sau đó rollback, session vẫn đã bị xoá → mất cart.
            SessionHelper::clearCart();

            return ['success' => true, 'order_id' => $orderId];

        } catch (RuntimeException $e) {
            // RuntimeException từ nội bộ service/model → thông báo có thể hiển thị cho user
            return ['success' => false, 'message' => $e->getMessage()];

        } catch (Throwable $e) {
            // Lỗi không mong đợi (DB down, ...) → log chi tiết, trả thông báo chung cho user
            error_log('[OrderService] Lỗi không mong đợi: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Đã xảy ra lỗi hệ thống. Vui lòng thử lại sau.',
            ];
        }
    }

    /**
     * Cập nhật trạng thái đơn hàng (dùng cho trang admin).
     *
     * Trạng thái hợp lệ: pending → confirmed → shipped → completed
     *                    bất kỳ trạng thái nào → cancelled
     *
     * @param  int    $orderId ID đơn hàng.
     * @param  string $status  Trạng thái mới.
     * @return array           ['success' => bool, 'message' => string]
     */
    public function updateOrderStatus(int $orderId, string $status): array
    {
        $validStatuses = ['pending', 'confirmed', 'shipped', 'completed', 'cancelled'];

        if (!in_array($status, $validStatuses, strict: true)) {
            return [
                'success' => false,
                'message' => 'Trạng thái không hợp lệ. Chấp nhận: ' . implode(', ', $validStatuses),
            ];
        }

        $updated = $this->orderModel->update($orderId, ['status' => $status]);

        return $updated
            ? ['success' => true,  'message' => 'Cập nhật trạng thái thành công.']
            : ['success' => false, 'message' => 'Không tìm thấy đơn hàng hoặc trạng thái không thay đổi.'];
    }


    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

    /**
     * Validate thông tin khách hàng từ form đặt hàng.
     *
     * Uỷ thác toàn bộ logic validate cho ValidatorHelper (static class dùng chung).
     *
     * Nếu nhóm chưa hoàn thiện ValidatorHelper, fallback về nội tuyến tạm thời:
     *   ValidatorHelper::validateRequired() kiểm tra empty + trim.
     *   ValidatorHelper::validateEmail()    dùng filter_var FILTER_VALIDATE_EMAIL.
     *   ValidatorHelper::validatePhone()    kiểm tra regex SĐT Việt Nam.
     *   ValidatorHelper::sanitizeInput()    strip_tags + htmlspecialchars trước khi dùng.
     *
     * @param  array       $formData Input thô từ $_POST (chưa sanitize).
     * @return string|null Thông báo lỗi đầu tiên, hoặc null nếu toàn bộ hợp lệ.
     */
    private function validateFormData(array $formData): ?string
    {
        // Sanitize toàn bộ input trước khi validate
        // → loại bỏ tag HTML, ký tự đặc biệt nguy hiểm (XSS)
        $data = ValidatorHelper::sanitizeInput($formData);

        // Kiểm tra các trường bắt buộc — uỷ thác cho ValidatorHelper
        $requiredFields = [
            'name'    => 'Họ tên',
            'email'   => 'Email',
            'phone'   => 'Số điện thoại',
            'address' => 'Địa chỉ',
        ];

        foreach ($requiredFields as $field => $label) {
            // validateRequired() trả true nếu hợp lệ, trả string thông báo lỗi nếu không
            $result = ValidatorHelper::validateRequired($data[$field] ?? '', $label);
            if ($result !== true) {
                return $result; // trả về thông báo lỗi ngay khi gặp trường đầu tiên sai
            }
        }

        // Kiểm tra định dạng email
        $emailResult = ValidatorHelper::validateEmail($data['email']);
        if ($emailResult !== true) {
            return $emailResult;
        }

        // Kiểm tra số điện thoại VN (10 chữ số, bắt đầu 0)
        // validatePhone() nằm trong ValidatorHelper — nếu chưa có thì thêm vào Helper
        $phoneResult = ValidatorHelper::validatePhone($data['phone']);
        if ($phoneResult !== true) {
            return $phoneResult;
        }

        return null; // toàn bộ hợp lệ
    }

    /**
     * Kiểm tra tồn kho của một sản phẩm.
     *
     * Nhận ProductEntity thay vì ($productId, $productName) rời rạc:
     *   - Không cần truyền nhiều tham số liên quan đến cùng một object.
     *   - Nếu ProductEntity thêm getter mới (vd: getSku()), không cần sửa signature.
     *
     * InventoryModel::getByProductId() trả ?InventoryEntity
     * → dùng $inventory->getQuantity() thay vì $inventory['quantity'].
     *
     * @param  ProductEntity $product      Entity sản phẩm cần kiểm tra.
     * @param  int           $requestedQty Số lượng khách muốn mua.
     * @return string|null   Thông báo lỗi, hoặc null nếu đủ hàng.
     */
    private function checkStock(ProductEntity $product, int $requestedQty): ?string
    {
        // getByProductId() trả ?InventoryEntity (override trong InventoryModel)
        $inventory = $this->inventoryModel->getByProductId($product->getId());

        if ($inventory === null) {
            // Sản phẩm chưa có bản ghi tồn kho → coi như hết hàng
            return "Sản phẩm \"{$product->getName()}\" hiện không có thông tin tồn kho.";
        }

        // Dùng getter của InventoryEntity thay vì $inventory['quantity']
        $availableQty = $inventory->getQuantity();

        if ($availableQty < $requestedQty) {
            return "Sản phẩm \"{$product->getName()}\" không đủ hàng "
                . "(còn {$availableQty}, yêu cầu {$requestedQty}).";
        }

        return null; // đủ hàng
    }

    /**
     * Tạo mới hoặc lấy Customer theo email.
     *
     * Quy tắc:
     *   - Nếu email đã tồn tại trong bảng customers → dùng lại customer đó.
     *   - Nếu chưa có → tạo mới (guest checkout).
     *
     * CustomerModel::getByEmail() trả ?CustomerEntity (override trong CustomerModel).
     * → dùng $existing->getId() thay vì $existing['id'].
     *
     * Không throw exception — nếu insert thất bại thì prepareStmt() đã throw,
     * transaction sẽ tự rollback ở tầng trên.
     *
     * @param  array $formData
     * @return int   customer_id
     */
    private function resolveCustomer(array $formData): int
    {
        $email = trim($formData['email']);

        // getByEmail() trả ?CustomerEntity (không phải array)
        $existing = $this->customerModel->getByEmail($email);

        if ($existing !== null) {
            // Cập nhật thông tin mới nhất (địa chỉ, SĐT có thể đã thay đổi)
            // update() trong BaseModel vẫn nhận array → dùng $existing->getId() lấy PK
            $this->customerModel->update($existing->getId(), [
                'name'    => trim($formData['name']),
                'phone'   => trim($formData['phone']),
                'address' => trim($formData['address']),
                'note'    => trim($formData['note'] ?? ''),
            ]);

            return $existing->getId(); // getId() trả int, không cần ép kiểu
        }

        // Tạo khách hàng mới — insert() vẫn nhận array (BaseModel)
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