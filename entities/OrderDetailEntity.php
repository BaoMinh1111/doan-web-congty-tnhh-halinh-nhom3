<?php
class OrderDetailEntity {
    private int $orderId;
    private int $productId;
    private int $quantity;
    private float $priceAtPurchase; // Lưu giá tại thời điểm mua để làm báo cáo Fintech

    public function __construct(array $data) {
        $this->orderId = (int)($data['order_id'] ?? 0);
        $this->productId = (int)($data['product_id'] ?? 0);
        $this->quantity = (int)($data['quantity'] ?? 0);
        $this->priceAtPurchase = (float)($data['price'] ?? 0);
    }
}