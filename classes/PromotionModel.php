<?php

require_once 'PromotionEntity.php';
require_once 'BaseModel.php';

/**
 * Class PromotionModel
 *
 * Quản lý dữ liệu khuyến mãi / mã giảm giá
 * - Lấy thông tin, tạo mới, cập nhật, xóa
 * - Áp dụng mã giảm giá cho tổng tiền
 * - Tăng số lần đã sử dụng, check điều kiện
 */
class PromotionModel extends BaseModel
{
    protected string $table = 'promotions';        // bảng promotions
    protected string $primaryKey = 'id';           // khóa chính
    protected string $defaultOrder = 'created_at DESC'; // sắp xếp mặc định

    // =========================================================================
    // LẤY DỮ LIỆU
    // =========================================================================

    // Lấy mã khuyến mãi theo ID
    public function getById(int $id): ?PromotionEntity
    {
        $row = $this->find($id); // dùng BaseModel tìm theo id
        return $row ? new PromotionEntity($row) : null; // trả về Entity hoặc null
    }

    // Lấy mã khuyến mãi theo code
    public function getByCode(string $code): ?PromotionEntity
    {
        $code = strtoupper(trim($code)); // bỏ khoảng trắng, viết hoa
        $row = $this->fetchOne(
            "SELECT * FROM {$this->table} WHERE code = ? LIMIT 1",
            [$code]
        );
        return $row ? new PromotionEntity($row) : null;
    }

    // Lấy tất cả mã
    public function getAll(): array
    {
        $rows = $this->fetchAll("SELECT * FROM {$this->table} ORDER BY {$this->defaultOrder}");
        return array_map(fn($r) => new PromotionEntity($r), $rows);
    }

    // Lấy các mã đang còn hiệu lực
    public function getActivePromotions(): array
    {
        $now = date('Y-m-d H:i:s');
        $rows = $this->fetchAll(
            "SELECT * FROM {$this->table} 
             WHERE is_active=1 AND (expired_at IS NULL OR expired_at > ?)
             ORDER BY {$this->defaultOrder}",
            [$now]
        );
        return array_map(fn($r) => new PromotionEntity($r), $rows);
    }

    // =========================================================================
    // CRUD (tạo / sửa / xóa)
    // =========================================================================

    // Tạo mới mã
    public function create(array $data): int
    {
        $entity = new PromotionEntity($data);        // tạo Entity từ mảng dữ liệu
        return $this->insert($entity->toArray());   // insert vào DB
    }

    // Cập nhật mã
    public function updatePromotion(int $id, array $data): bool
    {
        $existing = $this->getById($id);            // lấy thông tin hiện tại
        if (!$existing) return false;

        $entity = new PromotionEntity(array_merge($existing->toArray(), $data)); // merge dữ liệu mới
        return $this->update($id, $entity->toArray()); // lưu DB
    }

    // Xóa mã
    public function deletePromotion(int $id): bool
    {
        return $this->delete($id);
    }

    // =========================================================================
    // BUSINESS / ÁP DỤNG MÃ
    // =========================================================================

    /**
     * Áp dụng mã giảm giá cho tổng tiền
     * - Tự check điều kiện trong PromotionEntity
     * - Có thể tăng số lần đã dùng luôn
     */
    public function applyCode(string $code, float $total, bool $increaseUsed = true): array
    {
        // dùng transaction để đảm bảo an toàn
        return $this->transaction(function () use ($code, $total, $increaseUsed) {

            $promotion = $this->getByCode($code);     // lấy mã theo code
            if (!$promotion) {
                return ['success' => false, 'message' => 'Mã không tồn tại', 'discount' => 0, 'final' => $total];
            }

            if (!$promotion->canUse($total)) {        // check điều kiện áp dụng
                return ['success' => false, 'message' => $promotion->getFailMessage(), 'discount' => 0, 'final' => $total];
            }

            $discount = $promotion->calculateDiscount($total); // tính giảm giá
            $final = max(0, $total - $discount);              // tránh âm

            if ($increaseUsed) {                   // tăng số lần dùng nếu cần
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

    /**
     * Tăng số lần đã sử dụng của 1 mã
     */
    public function increaseUsedCount(int $id, int $count = 1): bool
    {
        return $this->transaction(function () use ($id, $count) {
            $promotion = $this->getById($id); // lấy mã
            if (!$promotion) return false;

            $used = $promotion->getUsedCount() + $count; // cộng thêm
            return $this->update($id, ['used_count' => $used]); // lưu DB
        });
    }

    // =========================================================================
    // HELPER CHECK (giúp kiểm tra nhanh)
    // =========================================================================

    public function isActive(int $id): bool
    {
        $promotion = $this->getById($id);
        return $promotion ? $promotion->isActive() : false;
    }

    public function isExpired(int $id): bool
    {
        $promotion = $this->getById($id);
        return $promotion ? $promotion->isExpired() : true;
    }

    public function hasReachedUsageLimit(int $id): bool
    {
        $promotion = $this->getById($id);
        return $promotion ? $promotion->hasReachedUsageLimit() : true;
    }

    // =========================================================================
    // BATCH APPLY (áp dụng nhiều mã)
    // =========================================================================

    public function applyMultipleCodes(array $codes, float $total): array
    {
        $results = [];
        foreach ($codes as $code) {
            // không tăng used_count khi check thử
            $results[$code] = $this->applyCode($code, $total, false);
        }
        return $results;
    }
}
