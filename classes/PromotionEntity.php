<?php

/**
 * Class PromotionEntity
 *
 * Entity đại diện bảng promotions
 */
class PromotionEntity
{
    private ?int $id;
    private string $code;
    private string $type;
    private float $value;
    private float $minOrder;
    private ?string $expiredAt;
    private bool $isActive;


    // ================= CONSTRUCTOR =================

    public function __construct(
        ?int $id,
        string $code,
        string $type,
        float $value,
        float $minOrder = 0,
        ?string $expiredAt = null,
        bool $isActive = true
    ) {
        $this->id        = $id;
        $this->code      = trim($code);
        $this->type      = $type;
        $this->value     = $value;
        $this->minOrder  = $minOrder;
        $this->expiredAt = $expiredAt;
        $this->isActive  = $isActive;
    }


    // ================= GETTER =================

    public function getId(): ?int { return $this->id; }
    public function getCode(): string { return $this->code; }
    public function getType(): string { return $this->type; }
    public function getValue(): float { return $this->value; }
    public function getMinOrder(): float { return $this->minOrder; }
    public function getExpiredAt(): ?string { return $this->expiredAt; }
    public function isActive(): bool { return $this->isActive; }


    // ================= BUSINESS =================

    public function isExpired(): bool
    {
        if ($this->expiredAt === null) {
            return false;
        }

        return strtotime($this->expiredAt) < time();
    }

    public function validate(): array
    {
        $errors = [];

        if ($this->code === '') {
            $errors['code'] = 'Code không được rỗng';
        }

        if (!in_array($this->type, ['percent', 'fixed'])) {
            $errors['type'] = 'Type không hợp lệ';
        }

        if ($this->value <= 0) {
            $errors['value'] = 'Value phải > 0';
        }

        if ($this->type === 'percent' && $this->value > 100) {
            $errors['value'] = 'Percent <= 100';
        }

        if ($this->minOrder < 0) {
            $errors['min_order'] = 'Min order không hợp lệ';
        }

        return $errors;
    }
}

/* Các vấn đề cần sửa:
* Không nhất quán với phong cách Entity của toàn dự án:
Constructor nhận 7 tham số rời rạc → rất khó dùng, dễ sai thứ tự.
Không có toArray() / toJson() → không thể trả JSON cho AJAX hoặc truyền cho View dễ dàng.
Không map từ array của DB → PromotionModel phải viết hàm map() thủ công
* Constructor quá cứng:
Phải truyền đủ 7 tham số mỗi lần new PromotionEntity(...)
Không linh hoạt khi DB trả về array (phải map thủ công).
Không có fallback giá trị mặc định hợp lý (ví dụ $minOrder = 0, $isActive = true chỉ là default ở tham số, không xử lý trong thân hàm).
* Thiếu các method quan trọng
* Validate còn yếu:
code nên uppercase và kiểm tra độ dài.
Chưa kiểm tra expiredAt có phải datetime hợp lệ không.
Chưa kiểm tra nếu type = percent thì value phải là số thập phân hợp lý.
*/
