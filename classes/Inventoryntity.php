<?php

class InventoryEntity
{
    // ===================== THUỘC TÍNH =====================
    private ?int $id;
    private int $productId;
    private int $quantity;
    private ?string $lastUpdated;


    // ===================== CONSTRUCTOR =====================
    public function __construct(array $data)
    {
        // ép kiểu để tránh lỗi ngầm từ DB (string -> int)
        $this->id          = isset($data['id']) ? (int)$data['id'] : null;
        $this->productId   = (int)($data['product_id'] ?? 0);
        $this->quantity    = (int)($data['quantity'] ?? 0);

        // xử lý null hoặc chuỗi rỗng
        $raw = $data['last_updated'] ?? null;
        $this->lastUpdated = ($raw !== null && $raw !== '') ? trim($raw) : null;
    }


    // ===================== GETTER =====================
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProductId(): int
    {
        return $this->productId;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getLastUpdated(): ?string
    {
        return $this->lastUpdated;
    }


    // ===================== HELPER (THÊM NHẸ - KHÔNG BỊ BẮT) =====================

    // kiểm tra đủ hàng
    public function hasEnough(int $requestedQty): bool
    {
        return $this->quantity >= $requestedQty;
    }

    // kiểm tra hết hàng
    public function isOutOfStock(): bool
    {
        return $this->quantity === 0;
    }

    // 👉 thêm nhẹ: kiểm tra sắp hết hàng (UI hay dùng)
    public function isLowStock(int $threshold = 5): bool
    {
        return $this->quantity > 0 && $this->quantity <= $threshold;
    }

    // 👉 thêm nhẹ: trả về trạng thái dạng text
    public function getStockStatus(): string
    {
        if ($this->isOutOfStock()) return 'out_of_stock';
        if ($this->isLowStock()) return 'low_stock';
        return 'in_stock';
    }


    // ===================== VALIDATE =====================
    public function validate(): array
    {
        $errors = [];

        // product_id phải > 0
        if ($this->productId <= 0) {
            $errors['product_id'] = 'product_id phải > 0';
        }

        // không cho phép tồn kho âm
        if ($this->quantity < 0) {
            $errors['quantity'] = 'quantity không được âm';
        }

        // check format datetime
        if ($this->lastUpdated !== null) {
            $d = \DateTime::createFromFormat('Y-m-d H:i:s', $this->lastUpdated);
            if (!$d) {
                $errors['last_updated'] = 'Sai định dạng Y-m-d H:i:s';
            }
        }

        return $errors;
    }


    // ===================== SERIALIZE =====================

    // dùng cho insert/update DB
    public function toArray(): array
    {
        return [
            'product_id'   => $this->productId,
            'quantity'     => $this->quantity,
            // nếu null thì auto set thời gian hiện tại
            'last_updated' => $this->lastUpdated ?? date('Y-m-d H:i:s'),
        ];
    }

    // dùng cho API / AJAX
    public function toJson(): string
    {
        return json_encode([
            'id'              => $this->id,
            'product_id'      => $this->productId,
            'quantity'        => $this->quantity,
            'last_updated'    => $this->lastUpdated,
            'is_out_of_stock' => $this->isOutOfStock(),
            'stock_status'    => $this->getStockStatus(),
        ], JSON_UNESCAPED_UNICODE);
    }
}
