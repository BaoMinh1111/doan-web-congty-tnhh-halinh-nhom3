<?php

/**
 * Class OrderEntity
 *
 * Đại diện cho một đơn hàng hoàn chỉnh trong bộ nhớ ứng dụng.
 *
 * @package App\Entities
 * @author  Ha Linh Technology Solutions
 */
class OrderEntity
{
    // =========================================================================
    // HẰNG SỐ TRẠNG THÁI
    // =========================================================================

    /**
     * Danh sách trạng thái hợp lệ, nhất quán với OrderModel::VALID_STATUSES.
     * Khai báo ở Entity để Entity tự validate độc lập, không phụ thuộc Model.
     */
    public const STATUS_PENDING   = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_SHIPPED   = 'shipped';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Map trạng thái → nhãn tiếng Việt dùng để hiển thị trên View.
     * Tập trung ở Entity thay vì rải khắp View → dễ thay đổi sau này.
     *
     * @var array<string, string>
     */
    private const STATUS_LABELS = [
        self::STATUS_PENDING   => 'Đang chờ xác nhận',
        self::STATUS_CONFIRMED => 'Đã xác nhận',
        self::STATUS_SHIPPED   => 'Đang giao hàng',
        self::STATUS_COMPLETED => 'Hoàn thành',
        self::STATUS_CANCELLED => 'Đã huỷ',
    ];

    /**
     * Map trạng thái → class Bootstrap badge dùng trong View (admin).
     * Giúp View chỉ cần gọi $order->getStatusBadgeClass() mà không cần if/switch.
     *
     * @var array<string, string>
     */
    private const STATUS_BADGE_CLASSES = [
        self::STATUS_PENDING   => 'badge bg-warning text-dark',
        self::STATUS_CONFIRMED => 'badge bg-primary',
        self::STATUS_SHIPPED   => 'badge bg-info text-dark',
        self::STATUS_COMPLETED => 'badge bg-success',
        self::STATUS_CANCELLED => 'badge bg-secondary',
    ];


    // =========================================================================
    // THUỘC TÍNH – NHÓM 1: CỘT TRONG BẢNG orders
    // =========================================================================

    /**
     * ID đơn hàng (AUTO_INCREMENT PRIMARY KEY).
     * null nếu đơn chưa được lưu vào DB.
     *
     * @var int|null
     */
    private ?int $id;

    /**
     * FK → bảng customers (người nhận hàng).
     *
     * @var int
     */
    private int $customerId;

    /**
     * FK → bảng users (tài khoản đăng nhập, nullable nếu khách vãng lai).
     *
     * @var int|null
     */
    private ?int $userId;

    /**
     * Tổng tiền đơn hàng (sau khi áp dụng khuyến mãi nếu có).
     * Kiểu float để tính toán; khi hiển thị dùng getFormattedTotal().
     *
     * @var float
     */
    private float $totalPrice;

    /**
     * Trạng thái đơn hàng.
     * Một trong các hằng STATUS_* được định nghĩa ở trên.
     *
     * @var string
     */
    private string $status;

    /**
     * FK → bảng promotions (mã giảm giá đã áp dụng, nullable).
     *
     * @var int|null
     */
    private ?int $promotionId;

    /**
     * Ghi chú của khách khi đặt hàng (vd: "Giao giờ hành chính").
     *
     * @var string|null
     */
    private ?string $note;

    /**
     * Thời điểm tạo đơn hàng (DATETIME từ DB).
     *
     * @var string|null
     */
    private ?string $createdAt;

    /**
     * Thời điểm cập nhật gần nhất (DATETIME từ DB).
     *
     * @var string|null
     */
    private ?string $updatedAt;


    // =========================================================================
    // THUỘC TÍNH – NHÓM 2: TỪ JOIN customers
    // (có khi gọi OrderModel::getById() hoặc getWithDetails())
    // =========================================================================

    /**
     * Tên khách hàng (từ customers.name qua JOIN).
     * null nếu Entity được tạo từ dữ liệu không JOIN customers.
     *
     * @var string|null
     */
    private ?string $customerName;

    /**
     * Số điện thoại khách hàng (từ customers.phone qua JOIN).
     *
     * @var string|null
     */
    private ?string $customerPhone;

    /**
     * Địa chỉ giao hàng (từ customers.address qua JOIN).
     *
     * @var string|null
     */
    private ?string $customerAddress;

    /**
     * Email khách hàng (từ customers.email qua JOIN).
     *
     * @var string|null
     */
    private ?string $customerEmail;


    // =========================================================================
    // THUỘC TÍNH – NHÓM 3: DANH SÁCH CHI TIẾT ĐƠN
    // (có khi gọi OrderModel::getWithDetails())
    // =========================================================================

    /**
     * Danh sách sản phẩm trong đơn hàng.
     * Mảng các OrderItemEntity, rỗng nếu chưa load chi tiết.
     *
     * @var OrderItemEntity[]
     */
    private array $items;


    // =========================================================================
    // CONSTRUCTOR
    // =========================================================================

    /**
     * Khởi tạo OrderEntity từ mảng dữ liệu thô (thường là kết quả fetchAssoc từ DB).
     *
     * Thiết kế nhận array thay vì từng tham số riêng lẻ vì:
     *   1. OrderModel trả về array → dùng trực tiếp, không cần map thủ công.
     *   2. Các trường nullable (userId, promotionId...) không cần truyền đủ.
     *   3. Dễ mở rộng thêm cột mà không phải sửa signature constructor.
     *
     * Key của $data phải khớp với tên cột trong DB (snake_case).
     * Các trường từ JOIN customers (customer_name, customer_phone...)
     * được nhận luôn nếu có trong $data.
     *
     * @param array $data Mảng dữ liệu, thường là 1 dòng từ PDO::FETCH_ASSOC.
     */
    public function __construct(array $data)
    {
        // --- Nhóm 1: cột bảng orders ---
        $this->id          = isset($data['id'])           ? (int)   $data['id']           : null;
        $this->customerId  = isset($data['customer_id'])  ? (int)   $data['customer_id']  : 0;
        $this->userId      = isset($data['user_id'])      ? (int)   $data['user_id']      : null;
        $this->totalPrice  = isset($data['total_price'])  ? (float) $data['total_price']  : 0.0;
        $this->status      = $data['status']              ?? self::STATUS_PENDING;
        $this->promotionId = isset($data['promotion_id']) ? (int)   $data['promotion_id'] : null;
        $this->note        = $data['note']                ?? null;
        $this->createdAt   = $data['created_at']          ?? null;
        $this->updatedAt   = $data['updated_at']          ?? null;

        // --- Nhóm 2: từ JOIN customers (có thể không có) ---
        $this->customerName    = $data['customer_name']    ?? null;
        $this->customerPhone   = $data['customer_phone']   ?? null;
        $this->customerAddress = $data['customer_address'] ?? null;
        $this->customerEmail   = $data['customer_email']   ?? null;

        // --- Nhóm 3: danh sách chi tiết (mặc định rỗng, load sau nếu cần) ---
        $this->items = [];
    }


    // =========================================================================
    // GETTERS – NHÓM 1
    // =========================================================================

    /** @return int|null */
    public function getId(): ?int
    {
        return $this->id;
    }

    /** @return int */
    public function getCustomerId(): int
    {
        return $this->customerId;
    }

    /** @return int|null */
    public function getUserId(): ?int
    {
        return $this->userId;
    }

    /** @return float */
    public function getTotalPrice(): float
    {
        return $this->totalPrice;
    }

    /** @return string */
    public function getStatus(): string
    {
        return $this->status;
    }

    /** @return int|null */
    public function getPromotionId(): ?int
    {
        return $this->promotionId;
    }

    /** @return string|null */
    public function getNote(): ?string
    {
        return $this->note;
    }

    /** @return string|null */
    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    /** @return string|null */
    public function getUpdatedAt(): ?string
    {
        return $this->updatedAt;
    }


    // =========================================================================
    // GETTERS – NHÓM 2 (thông tin khách từ JOIN)
    // =========================================================================

    /** @return string|null */
    public function getCustomerName(): ?string
    {
        return $this->customerName;
    }

    /** @return string|null */
    public function getCustomerPhone(): ?string
    {
        return $this->customerPhone;
    }

    /** @return string|null */
    public function getCustomerAddress(): ?string
    {
        return $this->customerAddress;
    }

    /** @return string|null */
    public function getCustomerEmail(): ?string
    {
        return $this->customerEmail;
    }


    // =========================================================================
    // GETTERS – NHÓM 3 (items)
    // =========================================================================

    /**
     * Trả về danh sách sản phẩm trong đơn.
     *
     * @return OrderItemEntity[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * Gán danh sách OrderItemEntity vào đơn hàng.
     *
     * Dùng sau khi gọi OrderModel::getWithDetails() để bọc items vào Entity:
     *   $data  = $orderModel->getWithDetails($id);
     *   $order = new OrderEntity($data['order']);
     *   $order->setItems(array_map(
     *       fn($row) => new OrderItemEntity($row),
     *       $data['items']
     *   ));
     *
     * @param  OrderItemEntity[] $items
     * @return void
     */
    public function setItems(array $items): void
    {
        $this->items = $items;
    }


    // =========================================================================
    // VALIDATE
    // =========================================================================

    /**
     * Kiểm tra dữ liệu Entity có hợp lệ không trước khi lưu vào DB.
     *
     * Trả về mảng lỗi (rỗng = hợp lệ).
     * Controller/Service kiểm tra mảng này trước khi gọi OrderModel::createOrder().
     *
     * @return string[] Mảng thông báo lỗi (key = tên trường, value = nội dung lỗi).
     */
    public function validate(): array
    {
        $errors = [];

        if ($this->customerId <= 0) {
            $errors['customer_id'] = 'Thiếu thông tin khách hàng.';
        }

        if ($this->totalPrice < 0) {
            $errors['total_price'] = 'Tổng tiền không được âm.';
        }

        if (!array_key_exists($this->status, self::STATUS_LABELS)) {
            $errors['status'] = "Trạng thái '{$this->status}' không hợp lệ.";
        }

        return $errors;
    }


    // =========================================================================
    // BUSINESS LOGIC
    // =========================================================================

    /**
     * Tính lại tổng tiền từ danh sách items hiện tại.
     *
     * Dùng trong OrderService để verify tổng tiền trước khi lưu:
     *   $calculated = $order->calculateTotal();
     *   if ($calculated !== $order->getTotalPrice()) { ... xử lý sai lệch ... }
     *
     * Trả về 0.0 nếu $items chưa được load (chưa gọi setItems()).
     *
     * @return float
     */
    public function calculateTotal(): float
    {
        if (empty($this->items)) {
            return 0.0;
        }

        return array_reduce(
            $this->items,
            fn(float $carry, OrderItemEntity $item) => $carry + $item->getSubtotal(),
            0.0
        );
    }

    /**
     * Kiểm tra đơn hàng có thể huỷ không.
     *
     * Chỉ huỷ được khi đơn đang ở trạng thái pending hoặc confirmed.
     * Đơn đã giao (shipped) hoặc hoàn thành (completed) không huỷ được nữa.
     *
     * Dùng để ẩn/hiện nút "Huỷ đơn" trên View mà không cần if/else khắp nơi:
     *   @if ($order->isCancellable())
     *       <button>Huỷ đơn</button>
     *   @endif
     *
     * @return bool
     */
    public function isCancellable(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_CONFIRMED,
        ], true);
    }

    /**
     * Kiểm tra đơn hàng đã hoàn thành chưa.
     *
     * @return bool
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Kiểm tra đơn hàng đã bị huỷ chưa.
     *
     * @return bool
     */
    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }


    // =========================================================================
    // HIỂN THỊ – HỖ TRỢ VIEW
    // =========================================================================

    /**
     * Trả về nhãn trạng thái tiếng Việt.
     *
     * @return string  Vd: "Đang chờ xác nhận", "Hoàn thành", ...
     */
    public function getStatusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    /**
     * Trả về class Bootstrap badge tương ứng trạng thái.
     *
     * Dùng trực tiếp trong View (Bootstrap 5):
     *   <span class="<?= $order->getStatusBadgeClass() ?>">
     *       <?= $order->getStatusLabel() ?>
     *   </span>
     *
     * @return string  Vd: "badge bg-warning text-dark"
     */
    public function getStatusBadgeClass(): string
    {
        return self::STATUS_BADGE_CLASSES[$this->status] ?? 'badge bg-secondary';
    }

    /**
     * Trả về tổng tiền đã định dạng theo chuẩn tiền tệ Việt Nam.
     *
     * Dùng trong View thay vì format thủ công:
     *   <?= $order->getFormattedTotal() ?>  →  "3.500.000 ₫"
     *
     * @return string
     */
    public function getFormattedTotal(): string
    {
        return number_format($this->totalPrice, 0, ',', '.') . ' ₫';
    }

    /**
     * Trả về ngày tạo đơn đã định dạng thân thiện.
     *
     * @param  string $format Định dạng date PHP (mặc định: ngày/tháng/năm giờ:phút).
     * @return string         Vd: "25/12/2025 14:30" hoặc "—" nếu chưa có.
     */
    public function getFormattedDate(string $format = 'd/m/Y H:i'): string
    {
        if ($this->createdAt === null) {
            return '—';
        }

        $dt = \DateTime::createFromFormat('Y-m-d H:i:s', $this->createdAt);
        return $dt !== false ? $dt->format($format) : $this->createdAt;
    }


    // =========================================================================
    // SERIALIZE – DÙNG CHO AJAX / JSON RESPONSE
    // =========================================================================

    /**
     * Chuyển Entity thành mảng để truyền vào View hoặc json_encode().
     *
     * Bao gồm:
     *   - Tất cả thuộc tính cơ bản của đơn hàng
     *   - Thông tin khách hàng (nếu có, từ JOIN)
     *   - Nhãn trạng thái tiếng Việt + class badge (tiện cho View)
     *   - Tổng tiền đã định dạng
     *   - Danh sách items (nếu đã load)
     *
     * KHÔNG bao gồm thông tin nhạy cảm (email khách hàng bị loại khỏi
     * default toArray để tránh lộ thông tin qua AJAX công khai;
     * dùng getCustomerEmail() trực tiếp khi cần trong admin panel).
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            // Nhóm 1: cột bảng orders
            'id'           => $this->id,
            'customer_id'  => $this->customerId,
            'user_id'      => $this->userId,
            'total_price'  => $this->totalPrice,
            'status'       => $this->status,
            'promotion_id' => $this->promotionId,
            'note'         => $this->note,
            'created_at'   => $this->createdAt,
            'updated_at'   => $this->updatedAt,

            // Nhóm 2: thông tin khách (từ JOIN, có thể null)
            'customer_name'    => $this->customerName,
            'customer_phone'   => $this->customerPhone,
            'customer_address' => $this->customerAddress,
            // customer_email bị loại khỏi toArray mặc định (xem docblock)

            // Nhóm 3: tiện ích cho View (không có trong DB)
            'status_label'      => $this->getStatusLabel(),
            'status_badge_class'=> $this->getStatusBadgeClass(),
            'formatted_total'   => $this->getFormattedTotal(),
            'formatted_date'    => $this->getFormattedDate(),

            // Nhóm 4: items (mảng rỗng nếu chưa load)
            'items' => array_map(
                fn(OrderItemEntity $item) => $item->toArray(),
                $this->items
            ),
        ];
    }

    /**
     * Chuyển Entity thành chuỗi JSON.
     *
     * Dùng trong Controller khi trả AJAX response:
     *   echo $order->toJson();
     *
     * Hoặc dùng jsonResponse() của BaseController:
     *   $this->jsonResponse($order->toArray());
     *
     * @return string JSON string.
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_UNICODE);
    }


    // =========================================================================
    // FACTORY METHOD – TIỆN ÍCH TẠO ENTITY
    // =========================================================================

    /**
     * Tạo mảng dữ liệu sẵn sàng để truyền vào OrderModel::createOrder().
     *
     * Đây là chiều ngược lại của constructor:
     *   constructor : array (DB row) → Entity
     *   toInsertData: Entity         → array (để INSERT vào DB)
     *
     * Chỉ trả về các cột thực sự có trong bảng orders,
     * KHÔNG trả về status_label, formatted_total, items, ... (không phải cột DB).
     *
     * @return array Mảng [tên_cột => giá_trị] sẵn sàng cho BaseModel::insert().
     */
    public function toInsertData(): array
    {
        $data = [
            'customer_id' => $this->customerId,
            'total_price' => $this->totalPrice,
            'status'      => $this->status,
        ];

        // Chỉ thêm các trường nullable nếu có giá trị thực
        // → tránh INSERT NULL vào những cột không cần thiết
        if ($this->userId !== null)      $data['user_id']      = $this->userId;
        if ($this->promotionId !== null) $data['promotion_id'] = $this->promotionId;
        if ($this->note !== null)        $data['note']         = $this->note;

        return $data;
    }
}