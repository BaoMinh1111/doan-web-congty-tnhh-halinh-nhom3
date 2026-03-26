<?php

require_once 'PromotionEntity.php';
require_once 'BaseModel.php';

/**
 * Class PromotionModel
 *
 * Quản lý các mã khuyến mãi trong hệ thống
 * - Lấy, tạo, sửa, xoá promotion
 * - Kiểm tra điều kiện áp dụng, tính discount
 * - Áp dụng cho đơn hàng (có transaction)
 */
class PromotionModel extends BaseModel
{
    // =========================================================================
    // THUỘC TÍNH
    // =========================================================================
    protected string $table = 'promotions';
    protected string $primaryKey = 'id';
    protected string $defaultOrder = 'created_at DESC';

    // =========================================================================
    // LẤY DỮ LIỆU
    // =========================================================================

    /** Lấy promotion theo ID */
    public function getById(int $id): ?PromotionEntity
    {
        $row = $this->find($id);
        return $row ? new PromotionEntity($row) : null;
    }

    /** Lấy promotion theo code */
    public function getByCode(string $code): ?PromotionEntity
    {
        $code = strtoupper(trim($code));
        $row = $this->fetchOne(
            "SELECT * FROM {$this->table} WHERE code = ? LIMIT 1",
            [$code]
        );
        return $row ? new PromotionEntity($row) : null;
    }

    /** Lấy tất cả promotion (dành cho admin) */
    public function getAll(): array
    {
        $rows = $this->fetchAll("SELECT * FROM {$this->table} ORDER BY {$this->defaultOrder}");
        return array_map(fn($r) => new PromotionEntity($r), $rows);
    }

    /** Lấy tất cả promotion đang active */
    public function getActivePromotions(): array
    {
        $now = date('Y-m-d H:i:s');
        $rows = $this->fetchAll(
            "SELECT * FROM {$this->table} 
             WHERE is_active=1 AND (start_date IS NULL OR start_date <= ?) 
             AND (expired_at IS NULL OR expired_at > ?)
             ORDER BY {$this->defaultOrder}",
            [$now, $now]
        );
        return array_map(fn($r) => new PromotionEntity($r), $rows);
    }

    // =========================================================================
    // CRUD
    // =========================================================================

    /** Tạo mới promotion */
    public function create(array $data): int
    {
        $entity = new PromotionEntity($data);

        // validate cơ bản
        if (!$entity->isValid()) {
            throw new RuntimeException("Dữ liệu promotion không hợp lệ");
        }

        return $this->insert($entity->toArray());
    }

    /** Cập nhật promotion */
    public function updatePromotion(int $id, array $data): bool
    {
        $existing = $this->getById($id);
        if (!$existing) return false;

        $entity = new PromotionEntity(array_merge($existing->toArray(), $data));

        if (!$entity->isValid()) {
            throw new RuntimeException("Dữ liệu promotion không hợp lệ");
        }

        return $this->update($id, $entity->toArray());
    }

    /** Xoá promotion */
    public function deletePromotion(int $id): bool
    {
        return $this->delete($id);
    }

    // =========================================================================
    // BUSINESS LOGIC
    // =========================================================================

    /**
     * Áp dụng mã promotion cho tổng tiền
     * - Kiểm tra active / expired / start_date / min_order / max_uses
     * - Tính discount
     * - Tăng used_count nếu cần
     */
    public function applyCode(string $code, float $total, bool $increaseUsed = true): array
    {
        return $this->transaction(function () use ($code, $total, $increaseUsed) {

            $promotion = $this->getByCode($code);

            if (!$promotion) {
                return ['success' => false, 'message' => 'Mã không tồn tại', 'discount' => 0, 'final' => $total];
            }

            // kiểm tra điều kiện áp dụng
            if (!$promotion->canUse($total)) {
                return [
                    'success'  => false,
                    'message'  => $promotion->getFailMessage(),
                    'discount' => 0,
                    'final'    => $total
                ];
            }

            // tính giảm giá
            $discount = $promotion->calculateDiscount($total);
            $final = max(0, $total - $discount);

            // tăng used_count nếu muốn
            if ($increaseUsed) {
                $this->increaseUsedCount($promotion->getId());
            }

            return [
                'success'  => true,
                'message'  => 'Áp dụng thành công',
                'discount' => $discount,
                'final'    => $final
            ];
        });
    }

    /** Tăng số lần đã dùng của mã */
    public function increaseUsedCount(int $id, int $count = 1): bool
    {
        return $this->transaction(function () use ($id, $count) {
            $promotion = $this->getById($id);
            if (!$promotion) return false;

            $used = $promotion->getUsedCount() + $count;
            return $this->update($id, ['used_count' => $used]);
        });
    }

    // =========================================================================
    // CHECK / HELPER
    // =========================================================================

    /** Kiểm tra promotion còn active */
    public function isActive(int $id): bool
    {
        $promotion = $this->getById($id);
        return $promotion ? $promotion->isActive() : false;
    }

    /** Kiểm tra promotion đã expired */
    public function isExpired(int $id): bool
    {
        $promotion = $this->getById($id);
        return $promotion ? $promotion->isExpired() : true;
    }

    /** Kiểm tra promotion đã đạt max_uses */
    public function hasReachedUsageLimit(int $id): bool
    {
        $promotion = $this->getById($id);
        return $promotion ? $promotion->hasReachedUsageLimit() : true;
    }

    /** Áp dụng nhiều mã cùng lúc, trả về từng kết quả */
    public function applyMultipleCodes(array $codes, float $total): array
    {
        $results = [];
        foreach ($codes as $code) {
            $results[$code] = $this->applyCode($code, $total, false); // không tăng used_count
        }
        return $results;
    }
}
