<?php
class PromotionEntity {
    private int $id;
    private string $code;
    private float $discountValue;
    private string $expirationDate;
    private int $usageLimit;
    private int $usedCount;

    public function __construct(array $data) {
        $this->id = (int)($data['id'] ?? 0);
        $this->code = $data['code'] ?? '';
        $this->discountValue = (float)($data['discount_value'] ?? 0);
        $this->expirationDate = $data['expiration_date'] ?? '';
        $this->usageLimit = (int)($data['usage_limit'] ?? 0);
        $this->usedCount = (int)($data['used_count'] ?? 0);
    }

    /**
     * Kiểm tra mã còn hiệu lực không (Về mặt thời gian và số lượng)
     */
    public function isValid(): bool {
        $now = date('Y-m-d H:i:s');
        if ($this->expirationDate < $now) return false;
        if ($this->usedCount >= $this->usageLimit) return false;
        return true;
    }

    // Getters
    public function getCode() { return $this->code; }
    public function getDiscountValue() { return $this->discountValue; }
}