<?php

class PromotionEntity
{
    private int $id;
    private string $code;
    private float $discount; // %
    private string $type; // percent | fixed
    private ?float $maxDiscount;

    public function __construct(array $data = [])
    {
        $this->id = $data['id'] ?? 0;
        $this->code = trim($data['code'] ?? '');
        $this->discount = (float)($data['discount'] ?? 0);
        $this->type = $data['type'] ?? 'percent';
        $this->maxDiscount = $data['max_discount'] ?? null;
    }

    // ===== GET =====
    public function getCode(): string { return $this->code; }
    public function getDiscount(): float { return $this->discount; }
    public function getType(): string { return $this->type; }
    public function getMaxDiscount(): ?float { return $this->maxDiscount; }

    // ===== VALIDATE =====
    public function validate(): array
    {
        $errors = [];

        if ($this->code === '') {
            $errors[] = "Mã giảm giá không được rỗng";
        }

        if ($this->discount <= 0) {
            $errors[] = "Giảm giá phải > 0";
        }

        if (!in_array($this->type, ['percent', 'fixed'])) {
            $errors[] = "Loại không hợp lệ";
        }

        return $errors;
    }
}
