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
    // THUỘC TÍNH CƠ BẢN
    // =========================================================================
    protected string $table = 'promotions';         // tên bảng trong DB
    protected string $primaryKey = 'id';            // primary key của bảng
    protected string $defaultOrder = 'created_at DESC'; // sắp xếp mặc định khi lấy danh sách

    // =========================================================================
    // LẤY DỮ LIỆU
    // =========================================================================

    /** Lấy promotion theo ID */
    public function getById(int $id): ?PromotionEntity
    {
        $row = $this->find($id);                     // dùng BaseModel::find để lấy 1 dòng theo id
        return $row ? new PromotionEntity($row) : null; // map sang Entity để dùng method validate, calculate...
    }

    /** Lấy promotion theo code (dành cho checkout) */
    public function getByCode(string $code): ?PromotionEntity
    {
        $code = strtoupper(trim($code));            // chuẩn hoá code: remove space + uppercase
        $row = $this->fetchOne(
            "SELECT * FROM {$this->table} WHERE code = ? LIMIT 1",
            [$code]                                   // dùng prepared statement tránh SQL Injection
        );
        return $row ? new PromotionEntity($row) : null;
    }

    /** Lấy tất cả promotion (dành cho admin) */
    public function getAll(): array
    {
        $rows = $this->fetchAll("SELECT * FROM {$this->table} ORDER BY {$this->defaultOrder}");
        return array_map(fn($r) => new PromotionEntity($r), $rows); // map tất cả thành Entity
    }

    /** Lấy tất cả promotion đang active (chưa expired và đã start) */
    public function getActivePromotions(): array
    {
        $now = date('Y-m-d H:i:s'); // thời điểm hiện tại
        $rows = $this->fetchAll(
            "SELECT * FROM {$this->table} 
             WHERE is_active=1 
               AND (start_date IS NULL OR start_date <= ?) 
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
        $entity = new PromotionEntity($data);       // tạo Entity từ input

        if (!$entity->isValid()) {                  // validate dữ liệu cơ bản
            throw new RuntimeException("Dữ liệu promotion không hợp lệ");
        }

        return $this->insert($entity->toArray());   // insert vào DB dùng BaseModel
    }

    /** Cập nhật promotion */
    public function updatePromotion(int $id, array $data): bool
    {
        $existing = $this->getById($id);           // lấy promotion hiện tại
        if (!$existing) return false;

        $entity = new PromotionEntity(array_merge($existing->toArray(), $data)); // merge data mới + cũ

        if (!$entity->isValid()) {                 // validate
            throw new RuntimeException("Dữ liệu promotion không hợp lệ");
        }

        return $this->update($id, $entity->toArray()); // update vào DB
    }

    /** Xoá promotion */
    public function deletePromotion(int $id): bool
    {
        return $this->delete($id);                 // xoá đơn giản bằng BaseModel
    }

    // =========================================================================
    // BUSINESS LOGIC (ÁP DỤNG MÃ)
    // =========================================================================

    /**
     * Áp dụng mã promotion cho tổng tiền
     * @param string $code Mã khuyến mãi
     * @param float $total Tổng tiền đơn hàng
     * @param bool $increaseUsed Có tăng số lần đã dùng hay không
     * @return array kết quả gồm success, message, discount, final
     */
    public function applyCode(string $code, float $total, bool $increaseUsed = true): array
    {
        return $this->transaction(function () use ($code, $total, $increaseUsed) { // wrap transaction

            $promotion = $this->getByCode($code);  // lấy promotion theo code

            if (!$promotion) {
                return ['success' => false, 'message' => 'Mã không tồn tại', 'discount' => 0, 'final' => $total];
            }

            // kiểm tra đủ điều kiện áp dụng
            if (!$promotion->canUse($total)) {
                return [
                    'success'  => false,
                    'message'  => $promotion->getFailMessage(), // message chi tiết từ Entity
                    'discount' => 0,
                    'final'    => $total
                ];
            }

            // tính discount (Entity chịu trách nhiệm)
            $discount = $promotion->calculateDiscount($total);
            $final = max(0, $total - $discount);       // final không được âm

            // tăng số lần đã dùng nếu cần
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

            $promotion = $this->getById($id);       // lấy entity
            if (!$promotion) return false;

            $used = $promotion->getUsedCount() + $count;  // cộng thêm số lần sử dụng
            return $this->update($id, ['used_count' => $used]); // lưu lại DB
        });
    }

    // =========================================================================
    // HELPER / CHECK
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
            $results[$code] = $this->applyCode($code, $total, false); // check nhưng không tăng used_count
        }
        return $results;
    }
}
