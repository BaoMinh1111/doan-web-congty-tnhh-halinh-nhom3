<?php

/**
 * Class PromotionEntity
 *
 * Ý tưởng thiết kế:
 * - Lớp này đại diện cho 1 mã khuyến mãi (promotion)
 * - Chỉ xử lý dữ liệu + logic nội tại (không gọi DB)
 * - Dùng chung cho nhiều nơi: Model, Controller, Service
 *
 * Mục tiêu:
 * - Tránh crash khi dữ liệu DB lỗi
 * - Dễ validate khi thêm / sửa
 * - Gom toàn bộ logic áp mã vào 1 class
 */
class PromotionEntity
{
    // =========================================================================
    // THUỘC TÍNH CƠ BẢN
    // =========================================================================
    private ?int $id;           // id trong database (có thể null nếu chưa insert)

    private string $code;       // mã giảm giá (vd: SALE10)
    private string $type;       // loại giảm: percent | fixed
    private float $value;       // giá trị giảm (% hoặc tiền)
    private float $minOrder;    // giá trị đơn tối thiểu để áp mã

    private ?string $expiredAt; // thời gian hết hạn (có thể null nếu không giới hạn)

    private bool $isActive;     // trạng thái hoạt động (true = dùng được)

    private string $createdAt;  // ngày tạo
    private string $updatedAt;  // ngày cập nhật


    // =========================================================================
    // GIỚI HẠN SỬ DỤNG
    // =========================================================================
    /**
     * Một số mã giảm giá có giới hạn số lần dùng:
     * - maxUses = null → không giới hạn
     * - usedCount → đã dùng bao nhiêu lần
     */
    private ?int $maxUses;
    private int $usedCount;


    // =========================================================================
    // BIẾN LƯU LỖI
    // =========================================================================
    /**
     * Dùng để lưu lý do lỗi khi validate hoặc apply
     * → Controller có thể lấy ra để hiển thị cho user
     */
    private ?string $failMessage = null;


    // =========================================================================
    // CONSTRUCTOR
    // =========================================================================
    /**
     * Nhận dữ liệu từ DB (array)
     *
     * Cách làm:
     * - Mapping trực tiếp từ array sang object
     * - Không validate tại đây để tránh crash khi dữ liệu DB không hợp lệ
     */
    public function __construct(array $data)
    {
        // ép kiểu để đảm bảo đúng type
        $this->id = isset($data['id']) ? (int)$data['id'] : null;

        // chuẩn hóa code: bỏ khoảng trắng + viết hoa
        $this->code = strtoupper(trim($data['code'] ?? ''));

        // chuẩn hóa type: viết thường để so sánh dễ
        $this->type = strtolower(trim($data['type'] ?? 'fixed'));

        $this->value    = (float)($data['value'] ?? 0);
        $this->minOrder = (float)($data['min_order'] ?? 0);


        /**
         * Xử lý expired_at:
         * - DB có thể trả về null hoặc '0000-00-00 00:00:00'
         * - Nếu không xử lý → strtotime lỗi → sai logic hết hạn
         */
        if (!empty($data['expired_at']) 
            && $data['expired_at'] !== '0000-00-00 00:00:00'
            && strtotime($data['expired_at']) !== false
        ) {
            $this->expiredAt = date('Y-m-d H:i:s', strtotime($data['expired_at']));
        } else {
            $this->expiredAt = null;
        }

        // trạng thái hoạt động
        $this->isActive = isset($data['is_active']) ? (bool)$data['is_active'] : true;

        // thời gian
        $this->createdAt = $data['created_at'] ?? date('Y-m-d H:i:s');
        $this->updatedAt = $data['updated_at'] ?? date('Y-m-d H:i:s');


        /**
         * Thông tin giới hạn sử dụng
         */
        $this->maxUses   = isset($data['max_uses']) ? (int)$data['max_uses'] : null;
        $this->usedCount = isset($data['used_count']) ? (int)$data['used_count'] : 0;
    }


    // =========================================================================
    // VALIDATE
    // =========================================================================
    /**
     * Kiểm tra dữ liệu hợp lệ
     *
     * Cách làm:
     * - Trả về true/false
     * - Không throw exception để tránh crash
     * - Lưu lỗi vào failMessage
     */
    public function isValid(): bool
    {
        // reset lỗi trước mỗi lần kiểm tra
        $this->failMessage = null;

        if ($this->code === '' || strlen($this->code) < 3) {
            $this->failMessage = "Code không hợp lệ: '{$this->code}'";
            return false;
        }

        if (!in_array($this->type, ['percent', 'fixed'])) {
            $this->failMessage = "Type không hợp lệ: '{$this->type}'";
            return false;
        }

        if ($this->value <= 0) {
            $this->failMessage = "Value phải > 0, hiện tại: {$this->value}";
            return false;
        }

        if ($this->type === 'percent' && $this->value > 100) {
            $this->failMessage = "Percent không được > 100";
            return false;
        }

        if ($this->minOrder < 0) {
            $this->failMessage = "Min order không hợp lệ";
            return false;
        }

        return true;
    }

    /**
     * Lấy thông báo lỗi
     */
    public function getFailMessage(): ?string
    {
        return $this->failMessage;
    }


    // =========================================================================
    // LOGIC ÁP DỤNG MÃ
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
     * Kiểm tra đã hết lượt sử dụng chưa
     */
    public function hasReachedUsageLimit(): bool
    {
        if ($this->maxUses === null) return false;
        return $this->usedCount >= $this->maxUses;
    }

    /**
     * Kiểm tra có thể dùng cho đơn hàng không
     *
     * Ý tưởng:
     * - Gom tất cả điều kiện vào 1 function
     * - Tránh viết rải rác nhiều nơi
     */
    public function canUse(float $total): bool
    {
        // reset lỗi
        $this->failMessage = null;

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
            $this->failMessage = "Chưa đủ điều kiện áp dụng";
            return false;
        }

        return true;
    }

    /**
     * Tính số tiền được giảm
     *
     * Cách làm:
     * - Kiểm tra điều kiện trước
     * - Nếu percent → tính %
     * - Nếu fixed → giảm trực tiếp
     * - Không cho giảm quá tổng tiền
     */
    public function calculateDiscount(float $total): float
    {
        if (!$this->canUse($total)) return 0;

        $discount = $this->type === 'percent'
            ? ($total * $this->value) / 100
            : $this->value;

        return min($discount, $total);
    }


    // =========================================================================
    // HELPER
    // =========================================================================
    /**
     * Format để hiển thị UI
     */
    public function getFormattedValue(): string
    {
        return $this->type === 'percent'
            ? $this->value . '%'
            : number_format($this->value, 0, ',', '.') . ' VNĐ';
    }


    // =========================================================================
    // CHUYỂN DỮ LIỆU
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
        return json_encode($this->toArray());
    }
}
