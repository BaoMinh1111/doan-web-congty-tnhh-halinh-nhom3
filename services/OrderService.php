<?php
class OrderService {
    private OrderModel $orderModel;
    private CartService $cartService;

    public function __construct(OrderModel $om, CartService $cs) {
        $this->orderModel = $om;
        $this->cartService = $cs;
    }

    /**
     * Quy trình đặt hàng toàn diện
     */
    public function checkout(int $customerId, ?string $promoCode = null): array {
        try {
            $cartItems = $this->cartService->getCartDetails();
            if (empty($cartItems)) {
                throw new Exception("Giỏ hàng trống!");
            }

            $total = $this->cartService->getTotalPrice();

            // 1. Tạo đơn hàng tổng
            $orderId = $this->orderModel->createOrder($customerId, $total);

            // 2. Lưu chi tiết từng món hàng
            foreach ($cartItems as $item) {
                $this->orderModel->addDetail($orderId, $item);
            }

            // 3. Nếu có mã giảm giá, gọi Procedure để cập nhật giá và used_count
            if ($promoCode) {
                $this->orderModel->applyPromoCode($orderId, $promoCode);
            }

            // 4. Xóa giỏ hàng sau khi hoàn tất
            $this->cartService->clearCart();

            return ['success' => true, 'order_id' => $orderId];

        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}