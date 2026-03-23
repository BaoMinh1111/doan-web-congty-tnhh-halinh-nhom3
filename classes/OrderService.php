<?php

require_once 'CartService.php';
require_once 'OrderEntity.php';
require_once 'OrderItemEntity.php';
require_once 'InventoryModel.php';

/**
 * Class OrderService
 *
 * Xử lý nghiệp vụ đặt hàng:
 * - Lấy dữ liệu từ Cart
 * - Kiểm tra tồn kho
 * - Tạo Order + OrderItem
 * - Trừ kho
 *
 * @package App\Services
 */
class OrderService
{
    // ================= THUỘC TÍNH =================

    private CartService $cartService;
    private InventoryModel $inventoryModel;
    private mysqli $conn;


    // ================= CONSTRUCTOR =================

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
        $this->cartService = new CartService();
        $this->inventoryModel = new InventoryModel($conn);
    }


    // ================= PLACE ORDER =================

    /**
     * Đặt hàng
     */
    public function placeOrder(int $userId): array
    {
        $cart = $this->cartService->all();

        if (empty($cart)) {
            return [
                'success' => false,
                'message' => 'Giỏ hàng trống'
            ];
        }

        // ================= KIỂM TRA TỒN KHO =================

        foreach ($cart as $item) {
            if (!$this->inventoryModel->hasStock($item['product_id'], $item['quantity'])) {
                return [
                    'success' => false,
                    'message' => 'Không đủ hàng cho sản phẩm ID: ' . $item['product_id']
                ];
            }
        }

        // ================= TRANSACTION =================

        $this->conn->begin_transaction();

        try {
            // ================= TẠO ORDER =================

            $total = $this->cartService->getTotal();

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

            // ================= TẠO ORDER ITEMS =================

            foreach ($cart as $item) {

                $orderItem = new OrderItemEntity([
                    'order_id'   => $orderId,
                    'product_id' => $item['product_id'],
                    'quantity'   => $item['quantity'],
                    'price'      => 100 // mock
                ]);

                $errors = $orderItem->validate();
                if (!empty($errors)) {
                    throw new Exception(implode(' | ', $errors));
                }

                $this->insertOrderItem($orderItem);

                // ================= TRỪ KHO =================

                $this->inventoryModel->decreaseStock(
                    $item['product_id'],
                    $item['quantity']
                );
            }

            // ================= COMMIT =================

            $this->conn->commit();

            // Xoá giỏ hàng
            $this->cartService->clear();

            return [
                'success' => true,
                'message' => 'Đặt hàng thành công',
                'order_id' => $orderId
            ];

        } catch (Exception $e) {

            // ================= ROLLBACK =================

            $this->conn->rollback();

            return [
                'success' => false,
                'message' => 'Lỗi: ' . $e->getMessage()
            ];
        }
    }


    // ================= PRIVATE DB METHODS =================

    /**
     * Insert Order
     */
    private function insertOrder(OrderEntity $order): int
    {
        $data = $order->toArray();

        $sql = "INSERT INTO orders (user_id, total_amount, status, created_at)
                VALUES (?, ?, ?, ?)";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param(
            "idss",
            $data['user_id'],
            $data['total_amount'],
            $data['status'],
            $data['created_at']
        );

        $stmt->execute();

        return $this->conn->insert_id;
    }


    /**
     * Insert OrderItem
     */
    private function insertOrderItem(OrderItemEntity $item): void
    {
        $data = $item->toArray();

        $sql = "INSERT INTO order_items (order_id, product_id, quantity, price)
                VALUES (?, ?, ?, ?)";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param(
            "iiid",
            $data['order_id'],
            $data['product_id'],
            $data['quantity'],
            $data['price']
        );

        $stmt->execute();
    }
}
