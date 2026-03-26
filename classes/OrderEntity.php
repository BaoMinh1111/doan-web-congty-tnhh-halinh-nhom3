<?php

class OrderEntity
{
    // =========================================================================
    // HẰNG SỐ TRẠNG THÁI
    // =========================================================================
    public const STATUS_PENDING   = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_SHIPPED   = 'shipped';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    private const STATUS_LABELS = [
        self::STATUS_PENDING   => 'Đang chờ xác nhận',
        self::STATUS_CONFIRMED => 'Đã xác nhận',
        self::STATUS_SHIPPED   => 'Đang giao hàng',
        self::STATUS_COMPLETED => 'Hoàn thành',
        self::STATUS_CANCELLED => 'Đã huỷ',
    ];

    private const STATUS_BADGE_CLASSES = [
        self::STATUS_PENDING   => 'badge bg-warning text-dark',
        self::STATUS_CONFIRMED => 'badge bg-primary',
        self::STATUS_SHIPPED   => 'badge bg-info text-dark',
        self::STATUS_COMPLETED => 'badge bg-success',
        self::STATUS_CANCELLED => 'badge bg-secondary',
    ];

    // =========================================================================
    // THUỘC TÍNH – NHÓM 1: CỘT orders
    // =========================================================================
    private ?int $id;
    private int $customerId;
    private ?int $userId;
    private float $totalPrice;
    private string $status;
    private ?int $promotionId;
    private ?string $note;
    private ?string $createdAt;
    private ?string $updatedAt;

    // =========================================================================
    // THUỘC TÍNH – NHÓM 2: JOIN customers
    // =========================================================================
    private ?string $customerName;
    private ?string $customerPhone;
    private ?string $customerAddress;
    private ?string $customerEmail;

    // =========================================================================
    // THUỘC TÍNH – NHÓM 3: DANH SÁCH CHI TIẾT
    // =========================================================================
    private array $items;

    // =========================================================================
    // MỚI: LỊCH SỬ TRẠNG THÁI
    // =========================================================================
    private array $statusHistory = [];

    // =========================================================================
    // CONSTRUCTOR
    // =========================================================================
    public function __construct(array $data)
    {
        $this->id          = isset($data['id'])           ? (int) $data['id']           : null;
        $this->customerId  = isset($data['customer_id'])  ? (int) $data['customer_id']  : 0;
        $this->userId      = isset($data['user_id'])      ? (int) $data['user_id']      : null;
        $this->totalPrice  = isset($data['total_price'])  ? (float) $data['total_price'] : 0.0;
        $this->status      = $data['status']              ?? self::STATUS_PENDING;
        $this->promotionId = isset($data['promotion_id']) ? (int) $data['promotion_id'] : null;
        $this->note        = $data['note']                ?? null;
        $this->createdAt   = $data['created_at']          ?? null;
        $this->updatedAt   = $data['updated_at']          ?? null;

        $this->customerName    = $data['customer_name']    ?? null;
        $this->customerPhone   = $data['customer_phone']   ?? null;
        $this->customerAddress = $data['customer_address'] ?? null;
        $this->customerEmail   = $data['customer_email']   ?? null;

        $this->items = [];
    }

    // =========================================================================
    // GETTERS
    // =========================================================================
    public function getId(): ?int { return $this->id; }
    public function getCustomerId(): int { return $this->customerId; }
    public function getUserId(): ?int { return $this->userId; }
    public function getTotalPrice(): float { return $this->totalPrice; }
    public function getStatus(): string { return $this->status; }
    public function getPromotionId(): ?int { return $this->promotionId; }
    public function getNote(): ?string { return $this->note; }
    public function getCreatedAt(): ?string { return $this->createdAt; }
    public function getUpdatedAt(): ?string { return $this->updatedAt; }

    public function getCustomerName(): ?string { return $this->customerName; }
    public function getCustomerPhone(): ?string { return $this->customerPhone; }
    public function getCustomerAddress(): ?string { return $this->customerAddress; }
    public function getCustomerEmail(): ?string { return $this->customerEmail; }

    public function getItems(): array { return $this->items; }
    public function setItems(array $items): void { $this->items = $items; }

    public function getStatusHistory(): array { return $this->statusHistory; }

    // =========================================================================
    // VALIDATE
    // =========================================================================
    public function validate(): array
    {
        $errors = [];
        if ($this->customerId <= 0) $errors['customer_id'] = 'Thiếu thông tin khách hàng.';
        if ($this->totalPrice < 0) $errors['total_price'] = 'Tổng tiền không được âm.';
        if (!array_key_exists($this->status, self::STATUS_LABELS)) $errors['status'] = "Trạng thái '{$this->status}' không hợp lệ.";
        return $errors;
    }

    // =========================================================================
    // BUSINESS LOGIC
    // =========================================================================
    public function calculateTotal(): float
    {
        if (empty($this->items)) return 0.0;
        return array_reduce($this->items, fn($carry, $item) => $carry + $item->getSubtotal(), 0.0);
    }

    public function isCancellable(): bool { return in_array($this->status, [self::STATUS_PENDING, self::STATUS_CONFIRMED], true); }
    public function isCompleted(): bool { return $this->status === self::STATUS_COMPLETED; }
    public function isCancelled(): bool { return $this->status === self::STATUS_CANCELLED; }

    // =========================================================================
    // MỚI: PROMOTION
    // =========================================================================
    public function applyDiscount(float $discountAmount): void
    {
        $this->totalPrice = max(0, $this->totalPrice - $discountAmount);
    }

    public function applyPromotion(object $promotion): void
    {
        // PromotionEntity giả định có method isValid() và getDiscountAmount()
        if (method_exists($promotion, 'isValid') && $promotion->isValid()) {
            $this->applyDiscount($promotion->getDiscountAmount());
            if (method_exists($promotion, 'getId')) $this->promotionId = $promotion->getId();
        }
    }

    // =========================================================================
    // KIỂM TRA TRỄ GIAO
    // =========================================================================
    public function isLate(string $currentTime = null): bool
    {
        if ($this->status !== self::STATUS_SHIPPED || $this->createdAt === null) return false;
        $currentTime = $currentTime ?? date('Y-m-d H:i:s');
        $expectedDelivery = (new \DateTime($this->createdAt))->modify('+3 days');
        return new \DateTime($currentTime) > $expectedDelivery;
    }

    // =========================================================================
    //  LỊCH SỬ TRẠNG THÁI
    // =========================================================================
    public function changeStatus(string $newStatus): void
    {
        if (!array_key_exists($newStatus, self::STATUS_LABELS)) {
            throw new \InvalidArgumentException("Trạng thái không hợp lệ");
        }
        $this->statusHistory[] = [
            'from' => $this->status,
            'to'   => $newStatus,
            'at'   => date('Y-m-d H:i:s')
        ];
        $this->status = $newStatus;
    }

    // =========================================================================
    // KHÁCH HÀNG / ĐƠN HÀNG
    // =========================================================================
    public function isReturningCustomer(): bool
    {
        if ($this->customerId <= 0) return false;
        // Giả sử OrderModel::countByCustomer($id) trả về số lượng đơn trước đó
        return class_exists('OrderModel') && method_exists('OrderModel', 'countByCustomer') 
            ? OrderModel::countByCustomer($this->customerId) > 1
            : false;
    }

    public function isHighValueOrder(float $threshold = 1000000): bool
    {
        return $this->totalPrice >= $threshold;
    }

    // =========================================================================
    // HIỂN THỊ / VIEW
    // =========================================================================
    public function getStatusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function getStatusBadgeClass(): string
    {
        return self::STATUS_BADGE_CLASSES[$this->status] ?? 'badge bg-secondary';
    }

    public function getFormattedTotal(): string
    {
        return number_format($this->totalPrice, 0, ',', '.') . ' ₫';
    }

    public function getFormattedDate(string $format = 'd/m/Y H:i'): string
    {
        if ($this->createdAt === null) return '—';
        $dt = \DateTime::createFromFormat('Y-m-d H:i:s', $this->createdAt);
        return $dt !== false ? $dt->format($format) : $this->createdAt;
    }

    // =========================================================================
    // SERIALIZE / JSON
    // =========================================================================
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'customer_id' => $this->customerId,
            'user_id' => $this->userId,
            'total_price' => $this->totalPrice,
            'status' => $this->status,
            'promotion_id' => $this->promotionId,
            'note' => $this->note,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'customer_name' => $this->customerName,
            'customer_phone' => $this->customerPhone,
            'customer_address' => $this->customerAddress,
            'status_label' => $this->getStatusLabel(),
            'status_badge_class' => $this->getStatusBadgeClass(),
            'formatted_total' => $this->getFormattedTotal(),
            'formatted_date' => $this->getFormattedDate(),
            'items' => array_map(fn($item) => $item->toArray(), $this->items),
            'status_history' => $this->statusHistory,
            'is_late' => $this->isLate(),
            'is_returning_customer' => $this->isReturningCustomer(),
            'is_high_value_order' => $this->isHighValueOrder(),
        ];
    }

    public function toJson(): string { return json_encode($this->toArray(), JSON_UNESCAPED_UNICODE); }

    // =========================================================================
    // FACTORY – INSERT
    // =========================================================================
    public function toInsertData(): array
    {
        $data = [
            'customer_id' => $this->customerId,
            'total_price' => $this->totalPrice,
            'status'      => $this->status,
        ];
        if ($this->userId !== null) $data['user_id'] = $this->userId;
        if ($this->promotionId !== null) $data['promotion_id'] = $this->promotionId;
        if ($this->note !== null) $data['note'] = $this->note;
        return $data;
    }
}
