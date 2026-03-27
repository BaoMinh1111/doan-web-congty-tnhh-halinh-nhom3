<?php

/**
 * Class PromotionEntity
 */
class PromotionEntity
{
    // =========================================================================
    // THUỘC TÍNH
    // =========================================================================
    private ?int $id;
    private string $code;
    private string $type;       // percent | fixed
    private float $value;
    private float $minOrder;
    private ?string $expiredAt;
    private bool $isActive;
    private string $createdAt;
    private string $updatedAt;

    // Giới hạn số lần sử dụng
    private ?int $maxUses;
    private int $usedCount;

    // Lưu thông báo lỗi để sử dụng khi validate hoặc apply
    private ?string $failMessage = null;


    // =========================================================================
    // CONSTRUCTOR
    // =========================================================================
    /**
     * Nhận dữ liệu dạng array từ database
     * Mapping trực tiếp sang thuộc tính của object
     */
    public function __construct(array $data)
    {
        $this->id   = isset($data['id']) ? (int)$data['id'] : null;

        $this->code = strtoupper(trim($data['code'] ?? ''));
        $this->type = strtolower(trim($data['type'] ?? 'fixed'));

        $this->value    = (float)($data['value'] ?? 0);
        $this->minOrder = (float)($data['min_order'] ?? 0);

        /**
         * Xử lý ngày hết hạn:
         * - Nếu NULL hoặc '0000-00-00 00:00:00' thì xem như không giới hạn
         * - Tránh lỗi strtotime khi dữ liệu không hợp lệ
         */
        if (!empty($data['expired_at']) && $data['expired_at'] !== '0000-00-00 00:00:00') {
            $this->expiredAt = date('Y-m-d H:i:s', strtotime($data['expired_at']));
        } else {
            $this->expiredAt = null;
        }

        $this->isActive = isset($data['is_active']) ? (bool)$data['is_active'] : true;

        $this->createdAt = $data['created_at'] ?? date('Y-m-d H:i:s');
        $this->updatedAt = $data['updated_at'] ?? date('Y-m-d H:i:s');

        /**
         * Thông tin giới hạn lượt sử dụng
         * - max_uses có thể NULL (không giới hạn)
         * - used_count mặc định = 0
         */
        $this->maxUses   = isset($data['max_uses']) ? (int)$data['max_uses'] : null;
        $this->usedCount = isset($data['used_count']) ? (int)$data['used_count'] : 0;
    }


    // =========================================================================
    // VALIDATE
    // =========================================================================
    /**
     * Kiểm tra dữ liệu hợp lệ
     * Trả về true/false thay vì throw exception để dễ kiểm soát luồng xử lý
     */
    public function isValid(): bool
    {
        if ($this->code === '' || strlen($this->code) < 3) {
            $this->failMessage = "Code không hợp lệ: '{$this->code}'";
            return false;
        }

        if (!in_array($this->type, ['percent', 'fixed'])) {
            $this->failMessage = "Type không hợp lệ: '{$this->type}'";
            return false;
        }

        if ($this->value <= 0) {
            $this->failMessage = "Giá trị giảm phải > 0, hiện tại: {$this->value}";
            return false;
        }

        if ($this->type === 'percent' && $this->value > 100) {
            $this->failMessage = "Giảm theo % không được vượt quá 100";
            return false;
        }

        if ($this->minOrder < 0) {
            $this->failMessage = "Đơn tối thiểu không hợp lệ";
            return false;
        }

        if ($this->expiredAt && strtotime($this->expiredAt) === false) {
            $this->failMessage = "Ngày hết hạn không hợp lệ";
            return false;
        }

        return true;
    }

    /**
     * Lấy thông báo lỗi gần nhất
     */
    public function getFailMessage(): ?string
    {
        return $this->failMessage;
    }


    // =========================================================================
    // GETTER
    // =========================================================================
    public function getId(): ?int { return $this->id; }
    public function getCode(): string { return $this->code; }
    public function getType(): string { return $this->type; }
    public function getValue(): float { return $this->value; }
    public function getMinOrder(): float { return $this->minOrder; }
    public function getExpiredAt(): ?string { return $this->expiredAt; }
    public function isActive(): bool { return $this->isActive; }
    public function getUsedCount(): int { return $this->usedCount; }
    public function getMaxUses(): ?int { return $this->maxUses; }


    // =========================================================================
    // LOGIC NGHIỆP VỤ
    // =========================================================================
    /**
     * Kiểm tra mã đã hết hạn chưa
     */
    public function isExpired(): bool
    {
        if ($this->expiredAt === null) return false;
        return strtotime($this->expiredAt) < time();
    }

    /**
     * Kiểm tra đã đạt giới hạn sử dụng chưa
     */
    public function hasReachedUsageLimit(): bool
    {
        if ($this->maxUses === null) return false;
        return $this->usedCount >= $this->maxUses;
    }

    /**
     * Kiểm tra có thể áp dụng cho đơn hàng hay không
     */
    public function canUse(float $total): bool
    {
        if (!$this->isActive) {
            $this->failMessage = "Mã không hoạt động";
            return false;
        }

        if ($this->isExpired()) {
            $this->failMessage = "Mã đã hết hạn";
            return false;
        }

        if ($this->hasReachedUsageLimit()) {
            $this->failMessage = "Mã đã hết lượt sử dụng";
            return false;
        }

        if ($total < $this->minOrder) {
            $this->failMessage = "Đơn hàng chưa đạt giá trị tối thiểu";
            return false;
        }

        return true;
    }

    /**
     * Tính số tiền được giảm
     */
    public function calculateDiscount(float $total): float
    {
        if (!$this->canUse($total)) return 0;

        $discount = $this->type === 'percent'
            ? ($total * $this->value) / 100
            : $this->value;

        // đảm bảo không giảm quá tổng tiền
        return min($discount, $total);
    }


    // =========================================================================
    // HELPER
    // =========================================================================
    public function getFormattedValue(): string
    {
        return $this->type === 'percent'
            ? $this->value . '%'
            : number_format($this->value, 0, ',', '.') . ' VNĐ';
    }


    // =========================================================================
    // SERIALIZE
    // =========================================================================
    public function toArray(): array
    {
        return [
            'id'         => $this->id,
            'code'       => $this->code,
            'type'       => $this->type,
            'value'      => $this->value,
            'min_order'  => $this->minOrder,
            'expired_at' => $this->expiredAt,
            'is_active'  => $this->isActive ? 1 : 0,
            'max_uses'   => $this->maxUses,
            'used_count' => $this->usedCount,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_UNICODE);
    }
}
