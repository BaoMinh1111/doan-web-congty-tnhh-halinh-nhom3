<?php

/**
 * Class PromotionEntity
 *
 * Lớp này đại diện cho 1 mã khuyến mãi
 * Dùng để:
 * - Lưu thông tin mã giảm giá
 * - Kiểm tra điều kiện áp dụng
 * - Tính số tiền được giảm
 * 
 */
class PromotionEntity
{
    // =========================================================================
    // THUỘC TÍNH
    // =========================================================================
    private ?int $id;           // id trong DB
    private string $code;       // mã giảm giá, vd SALE10
    private string $type;       // percent | fixed
    private float $value;       // số tiền giảm hoặc %
    private float $minOrder;    // đơn tối thiểu để áp dụng
    private ?string $expiredAt; // ngày hết hạn
    private bool $isActive;     // còn hoạt động không
    private string $createdAt;  // ngày tạo
    private string $updatedAt;  // ngày update cuối


    // =========================================================================
    // CONSTRUCTOR
    // =========================================================================
    /**
     * Nhận dữ liệu từ DB (array)
     * Mình làm kiểu giống ProductEntity, dễ map từ DB array
     */
    public function __construct(array $data)
    {
        // map dữ liệu từ DB hoặc default
        $this->id   = isset($data['id']) ? (int)$data['id'] : null;

        $this->code = strtoupper(trim($data['code'] ?? ''));
        $this->type = strtolower(trim($data['type'] ?? 'fixed'));

        $this->value    = (float)($data['value'] ?? 0);
        $this->minOrder = (float)($data['min_order'] ?? 0);

        // format ngày hết hạn
        $this->expiredAt = isset($data['expired_at']) 
            ? date('Y-m-d H:i:s', strtotime($data['expired_at'])) 
            : null;

        $this->isActive  = isset($data['is_active']) ? (bool)$data['is_active'] : true;

        // ngày tạo / cập nhật
        $this->createdAt = $data['created_at'] ?? date('Y-m-d H:i:s');
        $this->updatedAt = $data['updated_at'] ?? date('Y-m-d H:i:s');

        // kiểm tra dữ liệu hợp lệ ngay khi tạo
        $this->validate();
    }


    // =========================================================================
    // VALIDATE
    // =========================================================================
    /**
     * Kiểm tra dữ liệu có hợp lệ không
     */
    private function validate(): void
    {
        if ($this->code === '' || strlen($this->code) < 3) {
            throw new InvalidArgumentException("Code không hợp lệ");
        }

        if (!in_array($this->type, ['percent', 'fixed'])) {
            throw new InvalidArgumentException("Type sai");
        }

        if ($this->value <= 0) {
            throw new InvalidArgumentException("Value phải > 0");
        }

        if ($this->type === 'percent' && $this->value > 100) {
            throw new InvalidArgumentException("Percent <= 100");
        }

        if ($this->minOrder < 0) {
            throw new InvalidArgumentException("Min order sai");
        }

        if ($this->expiredAt && strtotime($this->expiredAt) === false) {
            throw new InvalidArgumentException("Ngày sai");
        }
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


    // =========================================================================
    // SETTER (nếu muốn chỉnh sửa trong code)
    // =========================================================================
    public function setValue(float $value): void
    {
        if ($value <= 0) {
            throw new InvalidArgumentException("Value phải > 0");
        }
        $this->value = $value;
        $this->touchUpdatedAt();
    }

    public function setIsActive(bool $isActive): void
    {
        $this->isActive = $isActive;
        $this->touchUpdatedAt();
    }

    // cập nhật thời gian mỗi khi set
    private function touchUpdatedAt(): void
    {
        $this->updatedAt = date('Y-m-d H:i:s');
    }


    // =========================================================================
    // LOGIC DÙNG TRONG CART
    // =========================================================================
    /**
     * Kiểm tra mã có thể dùng không (có active, chưa hết hạn, đủ minOrder)
     */
    public function canUse(float $total): bool
    {
        if (!$this->isActive) return false;
        if ($this->isExpired()) return false;
        if ($total < $this->minOrder) return false;

        return true;
    }

    /**
     * Kiểm tra hết hạn
     */
    public function isExpired(): bool
    {
        if ($this->expiredAt === null) return false;
        return strtotime($this->expiredAt) < time();
    }

    /**
     * Tính số tiền được giảm
     */
    public function calculateDiscount(float $total): float
    {
        if (!$this->canUse($total)) return 0;

        if ($this->type === 'percent') {
            $discount = ($total * $this->value) / 100;
        } else {
            $discount = $this->value;
        }

        // không giảm quá tổng tiền
        return min($discount, $total);
    }


    // =========================================================================
    // HELPER
    // =========================================================================
    /**
     * Lý do không áp dụng được, dùng để debug hoặc hiển thị
     */
    public function getError(float $total): ?string
    {
        if (!$this->isActive) return "Mã không hoạt động";
        if ($this->isExpired()) return "Mã đã hết hạn";
        if ($total < $this->minOrder) return "Chưa đủ điều kiện";

        return null;
    }

    /**
     * Format giá trị giảm cho hiển thị
     */
    public function getFormattedValue(): string
    {
        if ($this->type === 'percent') {
            return $this->value . '%';
        }
        return number_format($this->value, 0, ',', '.') . ' VNĐ';
    }

    /**
     * Debug nhanh
     */
    public function debug(): string
    {
        return "Promotion[{$this->code} - {$this->getFormattedValue()}]";
    }


    // =========================================================================
    // CHUYỂN SANG ARRAY / JSON
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
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->toArray());
    }
}

/* Các vấn đề cần sửa:
* validate() được gọi trong constructor — nếu dữ liệu từ DB bị thiếu code thì throw exception, không thể tạo Entity để hiển thị lỗi thân thiện: Khi Model 
gọi new PromotionEntity($row) từ DB mà dữ liệu cũ/thiếu → throw InvalidArgumentException → crash cả trang admin. Nên đổi validate() thành public, gọi 
thủ công từ Model trước khi insert/update, không gọi trong constructor.
* PromotionModel dùng $promotion->getFailMessage() nhưng Entity không có method này — chỉ có getError(): 
Tên không khớp giữa Model và Entity → Fatal Error khi áp mã giảm giá. Đổi tên getError() thành getFailMessage() hoặc ngược lại, chọn 1 tên dùng nhất quán.
* PromotionModel dùng $promotion->isValid() nhưng Entity không có method này: Model gọi method không tồn tại → crash khi tạo/cập nhật promotion. 
Cần thêm public function isValid(): bool hoặc đổi Model dùng validate() và bắt exception.
* expiredAt dùng strtotime() để format — nếu DB lưu NULL thì strtotime(null) trả false, date('Y-m-d H:i:s', false) ra ngày sai: Đã có check isset() nhưng 
nếu DB trả về chuỗi '0000-00-00 00:00:00' (MySQL default) thì strtotime() ra timestamp âm → isExpired() luôn trả true. Nên thêm check: 
if ($data['expired_at'] === '0000-00-00 00:00:00') $this->expiredAt = null;
* validate() message lỗi quá ngắn — "Type sai", "Ngày sai" không đủ thông tin để debug: 
Nên ghi rõ giá trị nhận được: "Type không hợp lệ: '{$this->type}', chỉ chấp nhận 'percent' hoặc 'fixed'". Tiết kiệm thời gian debug rất nhiều.
* . Thiếu $maxUses và $usedCount — PromotionModel gọi getUsedCount() nhưng Entity không có thuộc tính này: Thiếu 2 thuộc tính quan trọng trong thiết kế ban đầu. 
hasReachedUsageLimit() và canUse() không check được giới hạn lần dùng. Cần thêm $maxUses, $usedCount và getUsedCount(), hasReachedUsageLimit().
*/
