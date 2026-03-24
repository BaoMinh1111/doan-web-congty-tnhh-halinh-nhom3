<?php

require_once 'PromotionEntity.php';

class PromotionModel
{
    private mysqli $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    // ===== FIND =====

    public function findByCode(string $code): ?PromotionEntity
    {
        $sql = "SELECT * FROM promotions WHERE code = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);

        if (!$stmt) return null;

        $stmt->bind_param("s", $code);
        $stmt->execute();

        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        return $row ? $this->map($row) : null;
    }

    private function map(array $row): PromotionEntity
    {
        return new PromotionEntity(
            (int)$row['id'],
            $row['code'],
            $row['type'],
            (float)$row['value'],
            (float)$row['min_order'],
            $row['expired_at'],
            (bool)$row['is_active']
        );
    }

    // ===== BUSINESS =====

    public function apply(string $code, float $total): array
    {
        $promotion = $this->findByCode($code);

        if (!$promotion) {
            return ['success' => false, 'message' => 'Mã không tồn tại'];
        }

        if (!$promotion->isActive()) {
            return ['success' => false, 'message' => 'Mã đã bị khóa'];
        }

        if ($promotion->isExpired()) {
            return ['success' => false, 'message' => 'Mã đã hết hạn'];
        }

        if ($total < $promotion->getMinOrder()) {
            return ['success' => false, 'message' => 'Chưa đủ điều kiện áp dụng'];
        }

        $discount = $this->calculate($promotion, $total);

        return [
            'success'  => true,
            'discount' => $discount,
            'final'    => $total - $discount
        ];
    }

    private function calculate(PromotionEntity $p, float $total): float
    {
        if ($p->getType() === 'percent') {
            return ($total * $p->getValue()) / 100;
        }

        return min($p->getValue(), $total);
    }
}
