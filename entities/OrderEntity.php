<?php
class OrderEntity {
    private int $id;
    private int $customerId;
    private float $totalPrice;
    private string $status; // 'pending', 'shipped', 'cancelled'
    private ?int $promotionId;
    private string $createdAt;

    public function __construct(array $data) {
        $this->id = (int)($data['id'] ?? 0);
        $this->customerId = (int)($data['customer_id'] ?? 0);
        $this->totalPrice = (float)($data['total_price'] ?? 0);
        $this->status = $data['status'] ?? 'pending';
        $this->promotionId = isset($data['promotion_id']) ? (int)$data['promotion_id'] : null;
        $this->createdAt = $data['created_at'] ?? date('Y-m-d H:i:s');
    }

    // Getters
    public function getId(): int { return $this->id; }
    public function getTotalPrice(): float { return $this->totalPrice; }
}