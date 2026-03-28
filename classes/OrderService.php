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
 */
class OrderService
{
    private OrderModel $orderModel;
    private OrderDetailModel $detailModel;
    private ProductModel $productModel;
    private CustomerModel $customerModel;
    private InventoryModel $inventoryModel;
    private PromotionModel $promotionModel;

    public function __construct(
        OrderModel $orderModel,
        OrderDetailModel $detailModel,
        ProductModel $productModel,
        CustomerModel $customerModel,
        InventoryModel $inventoryModel,
        PromotionModel $promotionModel
    ) {
        // Inject dependency để dễ test và tách biệt các tầng
        $this->orderModel = $orderModel;
        $this->detailModel = $detailModel;
        $this->productModel = $productModel;
        $this->customerModel = $customerModel;
        $this->inventoryModel = $inventoryModel;
        $this->promotionModel = $promotionModel;
    }

    /**
     * Tạo đơn hàng từ giỏ hàng
     *
     * Luồng xử lý:
     * 1. Validate dữ liệu
     * 2. Kiểm tra giỏ hàng
     * 3. Tính tổng tiền
     * 4. Áp dụng khuyến mãi
     * 5. Transaction lưu DB
     */
    public function createOrderFromCart(array $formData, array $cart, ?string $promotionCode = null): array
    {
        // ── BƯỚC 1: Kiểm tra dữ liệu khách hàng ─────────────────────
        // Đảm bảo các field như name, email, phone hợp lệ trước khi xử lý tiếp
        $validationError = $this->validateFormData($formData);
        if ($validationError !== null) {
            return ['success' => false, 'message' => $validationError];
        }

        // ── BƯỚC 2: Kiểm tra giỏ hàng ─────────────────────
        // Không cho tạo đơn nếu giỏ rỗng
        if (empty($cart)) {
            return ['success' => false, 'message' => 'Giỏ hàng trống.'];
        }

        $enrichedItems = []; // chứa dữ liệu đã xử lý để dùng trong transaction
        $totalPrice = 0.0;   // tổng tiền ban đầu

        // ── BƯỚC 3: Duyệt từng sản phẩm trong giỏ ─────────────────────
        foreach ($cart as $item) {

            // Ép kiểu để tránh lỗi dữ liệu từ client
            $productId = (int) ($item['product_id'] ?? 0);
            $quantity  = (int) ($item['quantity'] ?? 0);

            // Kiểm tra dữ liệu hợp lệ
            if ($productId <= 0 || $quantity <= 0) {
                return ['success' => false, 'message' => 'Dữ liệu giỏ hàng không hợp lệ.'];
            }

            // Lấy sản phẩm từ DB
            $product = $this->productModel->getById($productId);
            if ($product === null) {
                return ['success' => false, 'message' => 'Sản phẩm không tồn tại.'];
            }

            // Kiểm tra tồn kho
            // Logic này tách riêng để dễ tái sử dụng và dễ đọc
            $stockError = $this->checkStock($product, $quantity);
            if ($stockError !== null) {
                return ['success' => false, 'message' => $stockError];
            }

            // Lấy giá tại thời điểm mua
            // Không dùng lại giá từ DB sau này để tránh thay đổi
            $price = $product->getPrice();

            // Cộng vào tổng tiền
            $totalPrice += $price * $quantity;

            // Lưu lại để dùng trong transaction (tránh query lại DB)
            $enrichedItems[] = [
                'product_id' => $product->getId(),
                'quantity'   => $quantity,
                'price'      => $price,
                'product'    => $product,
            ];
        }

        // ── BƯỚC 4: Xử lý khuyến mãi ─────────────────────
        $promo = null;

        if ($promotionCode !== null) {

            // Lấy thông tin mã giảm giá theo code (thực tế người dùng nhập code)
            $promo = $this->promotionModel->getByCode($promotionCode);

            if ($promo === null) {
                return ['success' => false, 'message' => 'Mã khuyến mãi không tồn tại.'];
            }

            // Kiểm tra điều kiện sử dụng:
            // - còn hạn
            // - còn lượt dùng
            // - đủ giá trị đơn hàng
            if (!$promo->canUse($totalPrice)) {
                return [
                    'success' => false,
                    'message' => $promo->getFailMessage($totalPrice)
                ];
            }

            // Tính số tiền được giảm
            // Logic này nằm trong Entity để đảm bảo không bị lặp
            $discount = $promo->calculateDiscount($totalPrice);

            // Áp dụng giảm giá
            $totalPrice = max(0, $totalPrice - $discount);
        }

        // ── BƯỚC 5: Transaction ─────────────────────
        // Đảm bảo tất cả thao tác DB thành công hoặc rollback toàn bộ
        try {
            $orderId = $this->orderModel->transaction(function () use ($formData, $enrichedItems, $totalPrice, $promo) {

                // Kiểm tra lại promotion trong transaction để tránh lỗi đồng thời
                if ($promo !== null && !$promo->canUse($totalPrice)) {
                    throw new RuntimeException($promo->getFailMessage($totalPrice));
                }

                // Tạo hoặc cập nhật khách hàng
                $customerId = $this->resolveCustomer($formData);

                // Tạo đơn hàng
                $orderId = $this->orderModel->insert([
                    'customer_id' => $customerId,
                    'user_id'     => $formData['user_id'] ?? null,
                    'total_price' => $totalPrice,
                    'status'      => 'pending',
                    'note'        => trim($formData['note'] ?? ''),
                    'created_at'  => date('Y-m-d H:i:s'),
                    'promotion_id'=> $promo ? $promo->getId() : null,
                ]);

                if ($orderId <= 0) {
                    throw new RuntimeException('Tạo đơn hàng thất bại.');
                }

                // Lưu chi tiết đơn hàng
                foreach ($enrichedItems as $item) {
                    $this->detailModel->insert([
                        'order_id' => $orderId,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'price_at_purchase' => $item['price'],
                    ]);
                }

                // Trừ tồn kho
                foreach ($enrichedItems as $item) {
                    if (!$this->inventoryModel->decreaseStock($item['product_id'], $item['quantity'])) {
                        throw new RuntimeException('Không thể cập nhật tồn kho.');
                    }
                }

                return $orderId;
            });

            // ── BƯỚC 6: Sau khi thành công ─────────────────────
            SessionHelper::clearCart();

            // Cập nhật số lần sử dụng mã
            if ($promo !== null) {
                $this->promotionModel->increaseUsedCount($promo->getId());
            }

            return ['success' => true, 'order_id' => $orderId];

        } catch (RuntimeException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        } catch (Throwable $e) {
            error_log($e->getMessage());
            return ['success' => false, 'message' => 'Lỗi hệ thống.'];
        }
    }
}
