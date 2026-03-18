<?php

/**
 * Class OrderItemEntity
 *
 * Đại diện cho một dòng chi tiết trong đơn hàng (1 sản phẩm trong 1 đơn).
 *
 * QUAN HỆ VỚI CÁC LỚP KHÁC:
 *   - Tương ứng với 1 dòng trong bảng order_details (do OrderDetailModel quản lý).
 *   - Được OrderEntity chứa bên trong qua $items (array of OrderItemEntity).
 *   - OrderEntity::calculateTotal() gọi $item->getSubtotal() của lớp này.
 *   - OrderEntity::setItems() nhận vào mảng OrderItemEntity.
 *
 * THUỘC TÍNH:
 *   Nhóm 1 – từ bảng order_details (DB columns):
 *     orderId, productId, quantity, priceAtPurchase
 *
 *   Nhóm 2 – từ JOIN products (có khi OrderDetailModel::getDetailsByOrder()):
 *     productName, productImage
 *
 *   Nhóm 3 – tính toán (không lưu trong DB):
 *     subtotal = quantity * priceAtPurchase
 *
 *
 *   // AJAX response
 *   $this->jsonResponse(array_map(fn($i) => $i->toArray(), $items));
 *
 * @package App\Entities
 * @author  Ha Linh Technology Solutions
 */
class OrderItemEntity
{
    // =========================================================================
    // THUỘC TÍNH – NHÓM 1: CỘT TRONG BẢNG order_details
    // =========================================================================

    /**
     * FK → bảng orders — đơn hàng chứa dòng chi tiết này.
     * Cùng với $productId tạo thành composite key của bảng order_details.
     *
     * @var int
     */
    private int $orderId;

    /**
     * FK → bảng products — sản phẩm được mua.
     * Cùng với $orderId tạo thành composite key của bảng order_details.
     *
     * @var int
     */
    private int $productId;

    /**
     * Số lượng sản phẩm được mua trong đơn này.
     * Luôn > 0 (được validate trong constructor và OrderDetailModel).
     *
     * @var int
     */
    private int $quantity;

    /**
     * Giá của sản phẩm tại thời điểm đặt hàng (chụp từ products.price).
     *
     * Lý do lưu riêng thay vì JOIN products.price:
     *   Giá sản phẩm có thể thay đổi sau khi đơn đã đặt.
     *   price_at_purchase đảm bảo lịch sử đơn hàng luôn chính xác.
     *
     * @var float
     */
    private float $priceAtPurchase;


    // =========================================================================
    // THUỘC TÍNH – NHÓM 2: TỪ JOIN products
    // (có khi OrderDetailModel::getDetailsByOrder() hoặc OrderModel::getWithDetails())
    // =========================================================================

    /**
     * Tên sản phẩm (từ products.name qua JOIN).
     * null nếu Entity được tạo từ dữ liệu không JOIN products.
     *
     * @var string|null
     */
    private ?string $productName;

    /**
     * Đường dẫn ảnh sản phẩm (từ products.image qua JOIN).
     * null nếu Entity được tạo từ dữ liệu không JOIN products.
     *
     * @var string|null
     */
    private ?string $productImage;


    // =========================================================================
    // CONSTRUCTOR
    // =========================================================================

    /**
     * Khởi tạo OrderItemEntity từ mảng dữ liệu thô.
     *
     * Key của $data phải khớp với tên cột/alias trong kết quả SQL (snake_case).
     * Nhất quán với cách OrderDetailModel::getDetailsByOrder() trả về dữ liệu:
     *   order_id, product_id, quantity, price_at_purchase,
     *   subtotal, product_name, product_image
     *
     * subtotal từ DB (quantity * price_at_purchase) KHÔNG được lưu thành thuộc tính
     * riêng vì nó là giá trị tính toán — Entity tự tính lại qua getSubtotal()
     * để đảm bảo nhất quán, tránh trường hợp subtotal trong DB bị lệch.
     *
     * @param array $data Mảng dữ liệu, thường là 1 dòng từ PDO::FETCH_ASSOC.
     */
    public function __construct(array $data)
    {
        // --- Nhóm 1: cột bảng order_details ---
        $this->orderId         = isset($data['order_id'])          ? (int)   $data['order_id']          : 0;
        $this->productId       = isset($data['product_id'])        ? (int)   $data['product_id']        : 0;
        $this->quantity        = isset($data['quantity'])          ? (int)   $data['quantity']          : 0;
        $this->priceAtPurchase = isset($data['price_at_purchase']) ? (float) $data['price_at_purchase'] : 0.0;

        // --- Nhóm 2: từ JOIN products (có thể không có) ---
        // key 'product_name' và 'product_image' khớp với alias trong SQL của
        // OrderDetailModel::getDetailsByOrder() và OrderModel::getWithDetails()
        $this->productName  = $data['product_name']  ?? null;
        $this->productImage = $data['product_image'] ?? null;
    }


    // =========================================================================
    // GETTERS – NHÓM 1
    // =========================================================================

    /** @return int */
    public function getOrderId(): int
    {
        return $this->orderId;
    }

    /** @return int */
    public function getProductId(): int
    {
        return $this->productId;
    }

    /** @return int */
    public function getQuantity(): int
    {
        return $this->quantity;
    }

    /** @return float */
    public function getPriceAtPurchase(): float
    {
        return $this->priceAtPurchase;
    }


    // =========================================================================
    // GETTERS – NHÓM 2 (từ JOIN products)
    // =========================================================================

    /** @return string|null */
    public function getProductName(): ?string
    {
        return $this->productName;
    }

    /** @return string|null */
    public function getProductImage(): ?string
    {
        return $this->productImage;
    }


    // =========================================================================
    // TÍNH TOÁN – NHÓM 3
    // =========================================================================

    /**
     * Tính thành tiền của dòng chi tiết này (quantity × priceAtPurchase).
     *
     * Không lưu thành thuộc tính riêng mà tính lại mỗi lần gọi để đảm bảo
     * luôn nhất quán với quantity và priceAtPurchase hiện tại.
     *
     * Được OrderEntity::calculateTotal() gọi để tính tổng đơn hàng:
     *   array_reduce($items, fn($carry, $item) => $carry + $item->getSubtotal(), 0.0)
     *
     * @return float
     */
    public function getSubtotal(): float
    {
        return $this->quantity * $this->priceAtPurchase;
    }


    // =========================================================================
    // VALIDATE
    // =========================================================================

    /**
     * Kiểm tra dữ liệu Entity có hợp lệ không.
     *
     * Trả về mảng lỗi (rỗng = hợp lệ).
     * Dùng trong OrderService trước khi gọi OrderDetailModel::addDetail().
     *
     * @return string[] Mảng lỗi (key = tên trường, value = nội dung lỗi).
     */
    public function validate(): array
    {
        $errors = [];

        if ($this->orderId <= 0) {
            $errors['order_id'] = 'order_id không hợp lệ.';
        }

        if ($this->productId <= 0) {
            $errors['product_id'] = 'product_id không hợp lệ.';
        }

        if ($this->quantity <= 0) {
            $errors['quantity'] = 'Số lượng phải lớn hơn 0.';
        }

        if ($this->priceAtPurchase < 0) {
            $errors['price_at_purchase'] = 'Giá sản phẩm không được âm.';
        }

        return $errors;
    }


    // =========================================================================
    // HIỂN THỊ – HỖ TRỢ VIEW
    // =========================================================================

    /**
     * Trả về giá tại thời điểm mua đã định dạng tiền tệ Việt Nam.
     *
     * Dùng trong View thay vì format thủ công:
     *   <?= $item->getFormattedPrice() ?>  →  "500.000 ₫"
     *
     * @return string
     */
    public function getFormattedPrice(): string
    {
        return number_format($this->priceAtPurchase, 0, ',', '.') . ' ₫';
    }

    /**
     * Trả về thành tiền đã định dạng tiền tệ Việt Nam.
     *
     * Dùng trong View để hiển thị cột "Thành tiền" trong bảng chi tiết đơn:
     *   <?= $item->getFormattedSubtotal() ?>  →  "1.000.000 ₫"
     *
     * @return string
     */
    public function getFormattedSubtotal(): string
    {
        return number_format($this->getSubtotal(), 0, ',', '.') . ' ₫';
    }

    /**
     * Trả về đường dẫn ảnh sản phẩm, fallback về ảnh mặc định nếu không có.
     *
     * Tránh lỗi hiển thị ảnh vỡ trong View khi sản phẩm không có ảnh:
     *   <img src="<?= $item->getProductImageUrl() ?>" alt="...">
     *
     * @param  string $default Đường dẫn ảnh mặc định.
     * @return string
     */
    public function getProductImageUrl(string $default = '/assets/images/no-image.png'): string
    {
        return $this->productImage ?? $default;
    }


    // =========================================================================
    // SERIALIZE – DÙNG CHO AJAX / JSON RESPONSE
    // =========================================================================

    /**
     * Chuyển Entity thành mảng để truyền vào View hoặc json_encode().
     *
     * Nhất quán với OrderEntity::toArray() — khi OrderEntity gọi:
     *   'items' => array_map(fn($item) => $item->toArray(), $this->items)
     * thì mỗi phần tử trong 'items' có cấu trúc đúng như dưới đây.
     *
     * Bao gồm:
     *   - Tất cả thuộc tính cơ bản (cột DB)
     *   - Thông tin sản phẩm từ JOIN (nếu có)
     *   - subtotal và các giá trị đã format (tiện cho View/AJAX)
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            // Nhóm 1: cột bảng order_details
            'order_id'          => $this->orderId,
            'product_id'        => $this->productId,
            'quantity'          => $this->quantity,
            'price_at_purchase' => $this->priceAtPurchase,

            // Nhóm 2: từ JOIN products (có thể null)
            'product_name'  => $this->productName,
            'product_image' => $this->productImage,

            // Nhóm 3: tính toán + định dạng (không có trong DB, tiện cho View/AJAX)
            'subtotal'           => $this->getSubtotal(),
            'formatted_price'    => $this->getFormattedPrice(),
            'formatted_subtotal' => $this->getFormattedSubtotal(),
        ];
    }

    /**
     * Chuyển Entity thành chuỗi JSON.
     *
     * Dùng khi cần trả 1 item đơn lẻ qua AJAX.
     * Trong thực tế thường trả cả mảng items qua OrderEntity::toJson().
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
     * Tạo mảng dữ liệu sẵn sàng để truyền vào OrderDetailModel::addDetail().
     *
     * Chiều ngược lại của constructor:
     *   constructor   : array (DB row) → Entity
     *   toInsertData  : Entity         → array (để INSERT vào DB)
     *
     * Chỉ trả về 4 cột thực sự có trong bảng order_details.
     * KHÔNG trả về subtotal, product_name, product_image (không phải cột DB).
     *
     * @return array Mảng [tên_cột => giá_trị] sẵn sàng cho OrderDetailModel.
     */
    public function toInsertData(): array
    {
        return [
            'product_id'        => $this->productId,
            'quantity'          => $this->quantity,
            'price_at_purchase' => $this->priceAtPurchase,
            // order_id KHÔNG đưa vào đây vì OrderDetailModel::addDetails()
            // nhận $orderId riêng làm tham số đầu tiên, không nhúng vào $data
        ];
    }
}