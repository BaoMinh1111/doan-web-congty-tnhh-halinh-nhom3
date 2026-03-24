<?php

require_once 'CartService.php';
require_once 'OrderEntity.php';
require_once 'OrderItemEntity.php';
require_once 'InventoryModel.php';
require_once 'PromotionModel.php';

/**
 * Class OrderService
 */
class OrderService
{
    private CartService $cartService;
    private InventoryModel $inventoryModel;
    private PromotionModel $promotionModel;
    private mysqli $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
        $this->cartService = new CartService();
        $this->inventoryModel = new InventoryModel($conn);
        $this->promotionModel = new PromotionModel($conn);
    }

    /**
     * Đặt hàng (có khuyến mãi)
     */
    public function placeOrder(int $userId, ?string $promoCode = null): array
    {
        if ($userId <= 0) {
            return [
                'success' => false,
                'message' => 'User không hợp lệ'
            ];
        }

        $cart = $this->cartService->all();

        if (empty($cart)) {
            return [
                'success' => false,
                'message' => 'Giỏ hàng trống'
            ];
        }

        // ===== CHECK STOCK =====
        foreach ($cart as $item) {
            if (!$this->inventoryModel->hasStock($item['product_id'], $item['quantity'])) {
                return [
                    'success' => false,
                    'message' => 'Không đủ hàng cho sản phẩm ID: ' . $item['product_id']
                ];
            }
        }

        // ===== TOTAL =====
        $total = $this->cartService->getTotal();
        $discount = 0;
        $promoId = null;

        // ===== APPLY PROMOTION =====
        if (!empty($promoCode)) {
            $promo = $this->promotionModel->apply($promoCode, $total);

            if (!$promo['success']) {
                return $promo;
            }

            $discount = $promo['discount'];
            $total    = $promo['final'];
            $promoId  = $promo['promo_id'] ?? null;
        }

        // ===== TRANSACTION =====
        $this->conn->begin_transaction();

        try {

            // ===== CREATE ORDER =====
            $orderEntity = new OrderEntity([
                'user_id'      => $userId,
                'total_amount' => $total,
                'status'       => 'pending'
            ]);

            $errors = $orderEntity->validate();
            if (!empty($errors)) {
                throw new Exception(implode(' | ', $errors));
            }

            $orderId = $this->insertOrder($orderEntity);

            // ===== CREATE ORDER ITEMS =====
            foreach ($cart as $item) {

                // dùng giá snapshot từ cart (FIX lỗi mock)
                $price = $item['price'] ?? 0;

                if ($price <= 0) {
                    throw new Exception('Giá sản phẩm không hợp lệ');
                }

                $orderItem = new OrderItemEntity([
                    'order_id'   => $orderId,
                    'product_id' => $item['product_id'],
                    'quantity'   => $item['quantity'],
                    'price'      => $price
                ]);

                $errors = $orderItem->validate();
                if (!empty($errors)) {
                    throw new Exception(implode(' | ', $errors));
                }

                $this->insertOrderItem($orderItem);

                // ===== TRỪ KHO =====
                $this->inventoryModel->decreaseStock(
                    $item['product_id'],
                    $item['quantity']
                );
            }

            // ===== TĂNG LƯỢT DÙNG PROMOTION =====
            if ($promoId) {
                $this->promotionModel->increaseUsedCount($promoId);
            }

            // ===== COMMIT =====
            $this->conn->commit();

            $this->cartService->clear();

            return [
                'success'  => true,
                'message'  => 'Đặt hàng thành công',
                'order_id' => $orderId,
                'discount' => $discount,
                'final'    => $total
            ];

        } catch (Exception $e) {

            $this->conn->rollback();

            return [
                'success' => false,
                'message' => 'Lỗi: ' . $e->getMessage()
            ];
        }
    }


    // ================= DB =================

    private function insertOrder(OrderEntity $order): int
    {
        $data = $order->toArray();

        $sql = "INSERT INTO orders (user_id, total_amount, status, created_at)
                VALUES (?, ?, ?, ?)";

        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            throw new Exception('Lỗi prepare order');
        }

        $stmt->bind_param(
            "idss",
            $data['user_id'],
            $data['total_amount'],
            $data['status'],
            $data['created_at']
        );

        if (!$stmt->execute()) {
            throw new Exception('Lỗi insert order');
        }

        return $this->conn->insert_id;
    }


    private function insertOrderItem(OrderItemEntity $item): void
    {
        $data = $item->toArray();

        $sql = "INSERT INTO order_items (order_id, product_id, quantity, price)
                VALUES (?, ?, ?, ?)";

        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            throw new Exception('Lỗi prepare order item');
        }

        $stmt->bind_param(
            "iiid",
            $data['order_id'],
            $data['product_id'],
            $data['quantity'],
            $data['price']
        );

        if (!$stmt->execute()) {
            throw new Exception('Lỗi insert order item');
        }
    }
}
