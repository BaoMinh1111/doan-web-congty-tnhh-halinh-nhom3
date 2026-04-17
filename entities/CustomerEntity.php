<?php
class CustomerEntity {
    private int $id;
    private int $userId; // Liên kết với bảng users
    private string $fullName;
    private string $phone;
    private string $address;

    public function __construct(array $data) {
        $this->id = (int)($data['id'] ?? 0);
        $this->userId = (int)($data['user_id'] ?? 0);
        $this->fullName = $data['full_name'] ?? '';
        $this->phone = $data['phone'] ?? '';
        $this->address = $data['address'] ?? '';
    }

    /**
     * Validate thông tin khách hàng
     * Trong Fintech, số điện thoại và địa chỉ là bắt buộc để xác thực giao dịch
     */
    public function validate(): array {
        $errors = [];
        if (empty($this->fullName)) $errors[] = "Họ tên không được để trống.";
        if (!preg_match('/^[0-9]{10,11}$/', $this->phone)) {
            $errors[] = "Số điện thoại phải có 10-11 chữ số.";
        }
        if (empty($this->address)) $errors[] = "Địa chỉ giao hàng không được để trống.";
        return $errors;
    }

    public function toArray(): array {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'full_name' => $this->fullName,
            'phone' => $this->phone,
            'address' => $this->address
        ];
    }

    // Getters
    public function getId(): int { return $this->id; }
    public function getUserId(): int { return $this->userId; }
    public function getFullName(): string { return $this->fullName; }
    public function getPhone(): string { return $this->phone; }
    public function getAddress(): string { return $this->address; }
}