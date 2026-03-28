<?php

/**
 * Class InventoryEntity
 *
 * Đại diện cho một dòng dữ liệu trong bảng `inventory`.
 * Bọc mảng thô từ DB thành object có type-safe, getter rõ ràng,
 * và validate trước khi ghi xuống DB.
 * 
 * @package App\Entities
 * @author  Ha Linh Technology Solutions
 */
class InventoryEntity
{
    // =========================================================================
    // THUỘC TÍNH (private — chỉ đọc qua getter)
    // =========================================================================

    /**
     * Khoá chính của bảng inventory (auto-increment).
     * Nullable khi tạo Entity từ dữ liệu chưa lưu DB (chưa có ID).
     *
     * @var int|null
     */
    private ?int $id;

    /**
     * FK → products.id
     * Mỗi bản ghi inventory gắn với đúng 1 sản phẩm.
     *
     * @var int
     */
    private int $productId;

    /**
     * Số lượng tồn kho hiện tại.
     * Luôn >= 0 — được đảm bảo bởi validate() và InventoryModel::decreaseStock().
     *
     * @var int
     */
    private int $quantity;

    /**
     * Thời điểm cập nhật tồn kho gần nhất.
     * Nullable vì bản ghi mới tạo có thể chưa có giá trị này.
     *
     * @var string|null  Định dạng 'Y-m-d H:i:s' hoặc null.
     */
    private ?string $lastUpdated;


    // =========================================================================
    // CONSTRUCTOR
    // =========================================================================

    /**
     * Khởi tạo Entity từ mảng dữ liệu (thường là row từ fetchOne/fetchAll).
     *
     * Dùng null-coalescing (??) để tránh lỗi "undefined index" khi $data
     * chỉ có một phần trường (vd: khi tạo Entity thủ công trong test).
     *
     * Ép kiểu tường minh (int, trim) ngay tại constructor — tránh bug âm thầm
     * khi PDO trả về string '5' thay vì int 5 (phụ thuộc PDO::ATTR_STRINGIFY_FETCHES).
     *
     * @param array $data Mảng key-value, key là tên cột trong bảng inventory.
     *                    Các key được chấp nhận: id, product_id, quantity, last_updated.
     */
    public function __construct(array $data)
    {
        // id có thể null nếu Entity được tạo trước khi INSERT vào DB
        $this->id          = isset($data['id']) ? (int) $data['id'] : null;

        // product_id bắt buộc — nếu thiếu thì validate() sẽ bắt
        $this->productId   = (int) ($data['product_id']  ?? 0);

        // quantity mặc định 0 nếu thiếu key, không cho phép âm ở tầng này
        $this->quantity    = (int) ($data['quantity']     ?? 0);

        // last_updated có thể null (bản ghi mới chưa có giá trị)
        $raw = $data['last_updated'] ?? null;
        $this->lastUpdated = ($raw !== null && $raw !== '') ? trim((string) $raw) : null;
    }


    // =========================================================================
    // GETTERS (public — interface duy nhất để đọc dữ liệu từ bên ngoài)
    // =========================================================================

    /**
     * Trả về khoá chính của bản ghi inventory.
     * Trả null nếu Entity chưa được lưu vào DB.
     *
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Trả về ID sản phẩm liên kết (FK → products.id).
     * Đây là trường OrderService dùng để gọi getByProductId() và decreaseStock().
     *
     * @return int
     */
    public function getProductId(): int
    {
        return $this->productId;
    }

    /**
     * Trả về số lượng tồn kho hiện tại.
     *
     * Đây là getter quan trọng nhất — OrderService::checkStock() dùng
     * giá trị này để so sánh với quantity khách đặt hàng:
     *   if ($inventory->getQuantity() < $requestedQty) { ... }
     *
     * @return int  Luôn >= 0 nếu dữ liệu DB hợp lệ và validate() đã pass.
     */
    public function getQuantity(): int
    {
        return $this->quantity;
    }

    /**
     * Trả về thời điểm cập nhật tồn kho gần nhất.
     *
     * @return string|null  'Y-m-d H:i:s' hoặc null nếu chưa có.
     */
    public function getLastUpdated(): ?string
    {
        return $this->lastUpdated;
    }

    /**
     * Kiểm tra tồn kho có đủ cho số lượng yêu cầu không
     * 
     * @param  int  $requestedQty Số lượng cần kiểm tra.
     * @return bool true nếu đủ hàng.
     */
    public function hasEnough(int $requestedQty): bool
    {
        return $this->quantity >= $requestedQty;
    }

    /**
     * Kiểm tra tồn kho có đang hết hàng không (quantity = 0).
     *
     * Dùng để hiển thị badge "Hết hàng" trên trang sản phẩm
     * mà không cần kiểm tra quantity thủ công ở View.
     *
     * @return bool
     */
    public function isOutOfStock(): bool
    {
        return $this->quantity === 0;
    }


    // =========================================================================
    // VALIDATE
    // =========================================================================

    /**
     * Kiểm tra tính hợp lệ của dữ liệu Entity.
     *
     * Trả về mảng lỗi (rỗng = hợp lệ) thay vì throw exception,
     * cho phép caller hiển thị nhiều lỗi cùng lúc nếu cần.
     *
     * @return array<string, string> ['tên_trường' => 'thông báo lỗi']
     */
    public function validate(): array
    {
        $errors = [];

        // product_id bắt buộc và phải là số nguyên dương
        if ($this->productId <= 0) {
            $errors['product_id'] = 'product_id phải là số nguyên dương (> 0).';
        }

        // quantity không được âm — tồn kho thấp nhất là 0 (hết hàng)
        if ($this->quantity < 0) {
            $errors['quantity'] = 'Số lượng tồn kho không được âm.';
        }

        // last_updated nếu có thì phải đúng định dạng datetime
        if ($this->lastUpdated !== null) {
            $parsed = \DateTime::createFromFormat('Y-m-d H:i:s', $this->lastUpdated);
            if ($parsed === false) {
                $errors['last_updated'] = "Định dạng last_updated không hợp lệ "
                    . "(cần 'Y-m-d H:i:s', nhận được: '{$this->lastUpdated}').";
            }
        }

        return $errors;
    }


    // =========================================================================
    // SERIALIZE
    // =========================================================================

    /**
     * Chuyển Entity thành mảng để truyền vào BaseModel::insert() / update().
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'product_id'   => $this->productId,
            'quantity'     => $this->quantity,
            'last_updated' => $this->lastUpdated ?? date('Y-m-d H:i:s'),
        ];
    }

    /**
     * Chuyển Entity thành JSON string — dùng cho AJAX response.
     *
     * Bao gồm 'id' vì response trả về client cần ID để cập nhật UI.
     * Ví dụ: admin panel hiển thị số tồn kho realtime qua AJAX.
     *
     * @return string JSON string.
     */
    public function toJson(): string
    {
        return json_encode([
            'id'           => $this->id,
            'product_id'   => $this->productId,
            'quantity'     => $this->quantity,
            'last_updated' => $this->lastUpdated,
            'is_out_of_stock' => $this->isOutOfStock(),
        ], JSON_UNESCAPED_UNICODE);
    }
}