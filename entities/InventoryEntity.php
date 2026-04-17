<?php
class InventoryEntity {
    private int $productId;
    private int $quantity;
    private string $lastUpdated;
    private int $lowStockThreshold; // Ngưỡng báo động khi sắp hết hàng

    public function __construct(array $data) {
        $this->productId = (int)($data['product_id'] ?? 0);
        $this->quantity = (int)($data['quantity'] ?? 0);
        $this->lastUpdated = $data['last_updated'] ?? date('Y-m-d H:i:s');
        $this->lowStockThreshold = (int)($data['low_stock_threshold'] ?? 5); 
    }

    /**
     * Kiểm tra xem mặt hàng này có đang sắp hết không
     */
    public function isLowStock(): bool {
        return $this->quantity <= $this->lowStockThreshold;
    }

    /**
     * Kiểm tra còn đủ hàng để bán không
     */
    public function hasEnoughStock(int $requestedQty): bool {
        return $this->quantity >= $requestedQty;
    }

    // Getters
    public function getProductId() { return $this->productId; }
    public function getQuantity() { return $this->quantity; }
}