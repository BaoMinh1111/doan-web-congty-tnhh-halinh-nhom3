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

/* Các vấn đề cần sửa: 
* Không tuân thủ kiến trúc chung của dự án:
Toàn bộ dự án em đang dùng PDO + BaseModel + Entity + Service Layer.
Lớp này đột ngột dùng mysqli + viết SQL thủ công + không kế thừa BaseModel
Constructor nhận mysqli $conn → phá vỡ nguyên tắc Dependency Injection và Singleton DB mà nhóm đã làm rất tốt trước đó.
Không có PromotionEntity được inject hay sử dụng đúng cách (chỉ map thủ công).
* Không sử dụng BaseModel + PDO:
Mất hết các lợi ích: prepared statement an toàn, transaction, fetchAll/fetchOne, pagination, logging lỗi…
Dùng mysqli trực tiếp → lặp lại code, khó bảo trì, dễ SQL Injection nếu sau này mở rộng.
* PromotionEntity bị lạm dụng sai:
Constructor của PromotionEntity nhận quá nhiều tham số rời rạc → không nhất quán với phong cách new Entity($row) như các Entity khác (OrderEntity, OrderItemEntity, InventoryEntity…).
Method map() thủ công → nên để trong Entity
* Thiếu nhiều chức năng cần thiết cho Promotion:
Không có getAll(), getActivePromotions(), create(), update(), delete(), increaseUsedCount(), checkUsageLimit()…
apply() chỉ kiểm tra cơ bản, chưa xử lý max_uses, used_count, start_date…
Không có transaction khi áp dụng mã
* Code style & bảo mật:
Không xử lý lỗi prepare statement tốt (if (!$stmt) return null; quá thô).
Không có validate input (code nên trim, uppercase…).
calculate() nên nằm trong PromotionEntity thay vì Model (business logic thuộc Entity).
*/
