<?php

/**
 * Class OrderService
 *
 * Xử lý toàn bộ quy trình tạo đơn hàng từ giỏ hàng (session-based cart),
 * bao gồm áp dụng khuyến mãi nếu có.
 *
 * @package App\Services
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

    // =========================================================================
    // CONSTRUCTOR
    // =========================================================================

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
    // TẠO ĐƠN HÀNG TỪ GIỎ HÀNG
    // =========================================================================

    /**
     * Tạo đơn hàng từ giỏ hàng, có áp dụng khuyến mãi nếu có promotion_id.
     *
     * @param array $formData Thông tin khách hàng (name, email, phone, address, note, user_id)
     * @param array $cart Giỏ hàng:
     *                    [
     *                      ['product_id'=>1, 'quantity'=>2],
     *                      ['product_id'=>3, 'quantity'=>1],
     *                    ]
     * @param int|null $promotionId ID khuyến mãi (nếu khách có mã)
     * @return array Kết quả ['success'=>bool, 'order_id'=>int hoặc 'message'=>string]
     */
    public function createOrderFromCart(array $formData, array $cart, ?int $promotionId = null): array
    {
        // ── 1. Validate thông tin khách hàng ───────────────────────────────
        $validationError = $this->validateFormData($formData);
        if ($validationError !== null) {
            return ['success' => false, 'message' => $validationError];
        }

        // ── 2. Kiểm tra giỏ hàng không trống ───────────────────────────────
        if (empty($cart)) {
            return ['success' => false, 'message' => 'Giỏ hàng trống, không thể tạo đơn hàng.'];
        }

        $enrichedItems = []; // Lưu thông tin sản phẩm + quantity + giá
        $totalPrice = 0.0;   // Tổng tiền trước khuyến mãi

        // ── 3. Duyệt giỏ hàng: kiểm tra sản phẩm + tồn kho + tính tổng ──
        foreach ($cart as $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            $quantity  = (int) ($item['quantity'] ?? 0);

            if ($productId <= 0 || $quantity <= 0) {
                return ['success' => false, 'message' => 'Giỏ hàng không hợp lệ (product_id hoặc quantity <= 0).'];
            }

            // Lấy thông tin sản phẩm từ ProductModel
            $product = $this->productModel->getById($productId);
            if ($product === null) {
                return ['success' => false, 'message' => "Sản phẩm ID={$productId} không tồn tại."];
            }

            // Kiểm tra tồn kho
            $stockError = $this->checkStock($product, $quantity);
            if ($stockError !== null) {
                return ['success' => false, 'message' => $stockError];
            }

            // Lưu giá tại thời điểm mua → dùng cho order detail
            $priceAtPurchase = $product->getPrice();
            $totalPrice += $priceAtPurchase * $quantity;

            // Thêm vào mảng enrichedItems để dùng trong transaction
            $enrichedItems[] = [
                'product_id'        => $product->getId(),
                'quantity'          => $quantity,
                'price_at_purchase' => $priceAtPurchase,
                'product'           => $product, // giữ object để dùng thông báo lỗi
            ];
        }

        // ── 4. Áp dụng khuyến mãi nếu có ───────────────────────────────
        if ($promotionId !== null) {
            $promo = $this->promotionModel->getById($promotionId);
            if ($promo) {
                // Loại khuyến mãi
                if ($promo['type'] === 'percent') {
                    $totalPrice *= (100 - $promo['value']) / 100; // giảm theo %
                } elseif ($promo['type'] === 'fixed') {
                    $totalPrice -= $promo['value']; // giảm cố định
                }
                $totalPrice = max(0, $totalPrice); // tránh âm
            } else {
                return ['success' => false, 'message' => 'Khuyến mãi không tồn tại hoặc đã hết hạn.'];
            }
        }

        // ── 5. Thực hiện transaction: tạo Customer, Order, OrderDetail, trừ tồn kho ──
        try {
            $orderId = $this->orderModel->transaction(function () use ($formData, $enrichedItems, $totalPrice, $promotionId) {

                // 5a. Lấy hoặc tạo Customer
                $customerId = $this->resolveCustomer($formData);

                // 5b. Tạo Order
                $orderData = [
                    'customer_id' => $customerId,
                    'user_id'     => $formData['user_id'] ?? null,
                    'total_price' => $totalPrice,
                    'status'      => 'pending',
                    'note'        => trim($formData['note'] ?? ''),
                    'created_at'  => date('Y-m-d H:i:s'),
                ];
                if ($promotionId !== null) {
                    $orderData['promotion_id'] = $promotionId;
                }

                $orderId = $this->orderModel->insert($orderData);
                if ($orderId <= 0) throw new RuntimeException('Tạo đơn hàng thất bại.');

                // 5c. Tạo từng OrderDetail
                foreach ($enrichedItems as $item) {
                    $this->detailModel->insert([
                        'order_id'          => $orderId,
                        'product_id'        => $item['product_id'],
                        'quantity'          => $item['quantity'],
                        'price_at_purchase' => $item['price_at_purchase'],
                    ]);
                }

                // 5d. Trừ tồn kho
                foreach ($enrichedItems as $item) {
                    $decreased = $this->inventoryModel->decreaseStock($item['product_id'], $item['quantity']);
                    if (!$decreased) {
                        throw new RuntimeException("Trừ tồn kho thất bại cho sản phẩm \"{$item['product']->getName()}\".");
                    }
                }

                return $orderId;
            });

            // ── 6. Xoá giỏ hàng khỏi session sau khi transaction thành công ──
            SessionHelper::clearCart();

            return ['success' => true, 'order_id' => $orderId];

        } catch (RuntimeException $e) {
            // Lỗi logic nghiệp vụ → trả về thông báo chi tiết
            return ['success' => false, 'message' => $e->getMessage()];

        } catch (Throwable $e) {
            // Lỗi không mong đợi → log hệ thống, trả thông báo chung
            error_log('[OrderService] Unexpected error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Hệ thống gặp sự cố. Vui lòng thử lại sau.'];
        }
    }

    // =========================================================================
    // CẬP NHẬT TRẠNG THÁI ĐƠN HÀNG
    // =========================================================================

    public function updateOrderStatus(int $orderId, string $status): array
    {
        $validStatuses = ['pending','confirmed','shipped','completed','cancelled'];

        if (!in_array($status, $validStatuses, true)) {
            return ['success'=>false,'message'=>'Trạng thái không hợp lệ'];
        }

        $updated = $this->orderModel->update($orderId,['status'=>$status]);

        return $updated
            ? ['success'=>true,'message'=>'Cập nhật trạng thái thành công.']
            : ['success'=>false,'message'=>'Không tìm thấy đơn hàng hoặc trạng thái không thay đổi.'];
    }

    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

    // 1. Validate form data khách hàng
    private function validateFormData(array $formData): ?string
    {
        $data = ValidatorHelper::sanitizeInput($formData);

        $requiredFields = [
            'name' => 'Họ tên',
            'email'=> 'Email',
            'phone'=> 'Số điện thoại',
            'address'=> 'Địa chỉ',
        ];

        foreach ($requiredFields as $field => $label) {
            $result = ValidatorHelper::validateRequired($data[$field] ?? '', $label);
            if ($result !== true) return $result;
        }

        $emailResult = ValidatorHelper::validateEmail($data['email']);
        if ($emailResult !== true) return $emailResult;

        $phoneResult = ValidatorHelper::validatePhone($data['phone']);
        if ($phoneResult !== true) return $phoneResult;

        return null;
    }

    // 2. Kiểm tra tồn kho
    private function checkStock(ProductEntity $product, int $requestedQty): ?string
    {
        $inventory = $this->inventoryModel->getByProductId($product->getId());
        if ($inventory === null) {
            return "Sản phẩm \"{$product->getName()}\" hiện không có thông tin tồn kho.";
        }
        if ($inventory->getQuantity() < $requestedQty) {
            return "Sản phẩm \"{$product->getName()}\" không đủ hàng (còn {$inventory->getQuantity()}, yêu cầu {$requestedQty}).";
        }
        return null;
    }

    // 3. Lấy hoặc tạo Customer theo email
    private function resolveCustomer(array $formData): int
    {
        $email = trim($formData['email']);
        $existing = $this->customerModel->getByEmail($email);

        if ($existing !== null) {
            // Cập nhật thông tin mới nhất nếu đã tồn tại
            $this->customerModel->update($existing->getId(), [
                'name'    => trim($formData['name']),
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

        if ($customerId <= 0) throw new RuntimeException('Không thể tạo khách hàng.');
        return $customerId;
    }
}
