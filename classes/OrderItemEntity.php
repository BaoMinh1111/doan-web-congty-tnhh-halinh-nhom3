<?php
// ==========================
// OrderItemEntity
// Đại diện cho một dòng sản phẩm trong đơn hàng
// Thêm tính năng: giảm giá dòng, tính điểm, kiểm tra tồn kho
// ==========================
class OrderItemEntity
{
    // --- Nhóm 1: cột trong bảng order_details ---
    // orderId, productId, quantity, priceAtPurchase
    private int $orderId;
    private int $productId;
    private int $quantity;
    private float $priceAtPurchase;

    // --- Nhóm 2: từ JOIN products ---
    // productName, productImage để hiển thị View
    private ?string $productName;
    private ?string $productImage;

    // --- Nhóm 3: business logic ---
    // discount: giảm giá dòng
    // pointsEarned: điểm tích lũy
    // inStock: trạng thái kho
    private float $discount = 0.0;
    private int $pointsEarned = 0;
    private bool $inStock = true;

    // ==========================
    // Constructor: khởi tạo từ mảng dữ liệu
    // ==========================
    public function __construct(array $data)
    {
        $this->orderId         = $data['order_id'] ?? 0;
        $this->productId       = $data['product_id'] ?? 0;
        $this->quantity        = $data['quantity'] ?? 0;
        $this->priceAtPurchase = $data['price_at_purchase'] ?? 0.0;

        $this->productName  = $data['product_name']  ?? null;
        $this->productImage = $data['product_image'] ?? null;
    }

    // ==========================
    // Getters cơ bản
    // ==========================
    public function getOrderId(): int { return $this->orderId; }
    public function getProductId(): int { return $this->productId; }
    public function getQuantity(): int { return $this->quantity; }
    public function getPriceAtPurchase(): float { return $this->priceAtPurchase; }
    public function getProductName(): ?string { return $this->productName; }
    public function getProductImage(): ?string { return $this->productImage; }

    // ==========================
    // Business logic
    // ==========================
    // Tổng tiền trước giảm giá
    // subtotal = quantity * priceAtPurchase
    public function getSubtotal(): float
    {
        return $this->quantity * $this->priceAtPurchase;
    }

    // Áp dụng giảm giá cho dòng
    // Lưu ý: discount không được vượt quá subtotal
    public function applyDiscount(float $amount): void
    {
        $this->discount = min($amount, $this->getSubtotal());
    }

    // Tổng tiền sau giảm giá
    public function getSubtotalAfterDiscount(): float
    {
        return $this->getSubtotal() - $this->discount;
    }

    // Tính điểm thưởng
    // Lưu ý: ratePerCurrency = 1000 ₫ → 1 điểm cho mỗi 1000 ₫ thanh toán
    public function calculatePoints(float $ratePerCurrency = 1000): void
    {
        $this->pointsEarned = (int) floor($this->getSubtotalAfterDiscount() / $ratePerCurrency);
    }

    public function getPointsEarned(): int
    {
        return $this->pointsEarned;
    }

    // Trạng thái tồn kho
    // setInStock: gán true/false
    // isInStock: kiểm tra xem còn hàng hay không
    public function setInStock(bool $value): void { $this->inStock = $value; }
    public function isInStock(): bool { return $this->inStock; }

    // ==========================
    // Hiển thị / formatting
    // ==========================
    public function getFormattedPrice(): string
    {
        return number_format($this->priceAtPurchase, 0, ',', '.') . ' ₫';
    }

    public function getFormattedSubtotal(): string
    {
        return number_format($this->getSubtotal(), 0, ',', '.') . ' ₫';
    }

    public function getFormattedSubtotalAfterDiscount(): string
    {
        return number_format($this->getSubtotalAfterDiscount(), 0, ',', '.') . ' ₫';
    }

    public function getProductImageUrl(string $default = '/assets/images/no-image.png'): string
    {
        return $this->productImage ?? $default;
    }

    // ==========================
    // Validate dữ liệu
    // ==========================
    // Trả về mảng lỗi nếu dữ liệu không hợp lệ
    public function validate(): array
    {
        $errors = [];
        if ($this->orderId <= 0) $errors['order_id'] = 'order_id không hợp lệ.';
        if ($this->productId <= 0) $errors['product_id'] = 'product_id không hợp lệ.';
        if ($this->quantity <= 0) $errors['quantity'] = 'Số lượng phải lớn hơn 0.';
        if ($this->priceAtPurchase < 0) $errors['price_at_purchase'] = 'Giá sản phẩm không được âm.';
        return $errors;
    }

    // ==========================
    // Serialize cho AJAX / JSON
    // ==========================
    public function toArray(): array
    {
        return [
            'order_id'                        => $this->orderId,
            'product_id'                      => $this->productId,
            'quantity'                         => $this->quantity,
            'price_at_purchase'                => $this->priceAtPurchase,
            'product_name'                     => $this->productName,
            'product_image'                    => $this->productImage,
            'subtotal'                         => $this->getSubtotal(),
            'formatted_price'                  => $this->getFormattedPrice(),
            'formatted_subtotal'               => $this->getFormattedSubtotal(),
            'discount'                         => $this->discount,
            'subtotal_after_discount'          => $this->getSubtotalAfterDiscount(),
            'formatted_subtotal_after_discount'=> $this->getFormattedSubtotalAfterDiscount(),
            'points_earned'                    => $this->pointsEarned,
            'in_stock'                         => $this->inStock,
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_UNICODE);
    }

    // ==========================
    // Dữ liệu insert vào DB
    // ==========================
    public function toInsertData(): array
    {
        return [
            'product_id'        => $this->productId,
            'quantity'          => $this->quantity,
            'price_at_purchase' => $this->priceAtPurchase,
        ];
    }
}
