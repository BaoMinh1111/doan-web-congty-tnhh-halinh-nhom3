<?php

require_once 'PromotionEntity.php';
require_once 'BaseModel.php';

/**
 * Class PromotionModel
 *
 * Ý tưởng thiết kế:
 * - Model chịu trách nhiệm làm việc với Database (CRUD)
 * - Entity chịu trách nhiệm validate + business logic
 * - Model không viết lại logic tính toán → chỉ gọi Entity
 *
 * Mục tiêu:
 * - Tách rõ trách nhiệm (Single Responsibility)
 * - Tránh duplicate logic
 * - Dễ bảo trì và mở rộng
 */
class PromotionModel extends BaseModel
{
    // =========================================================================
    // THUỘC TÍNH CƠ BẢN
    // =========================================================================
    protected string $table = 'promotions';
    protected string $primaryKey = 'id';
    protected string $defaultOrder = 'created_at DESC';


    // =========================================================================
    // LẤY DỮ LIỆU
    // =========================================================================

    /**
     * Lấy promotion theo ID
     *
     * Cách làm:
     * - Không dùng method không tồn tại (find)
     * - Dùng fetchOne để đảm bảo tương thích BaseModel
     */
    public function getById(int $id): ?PromotionEntity
    {
        $row = $this->fetchOne(
            "SELECT * FROM {$this->table} WHERE id = ? LIMIT 1",
            [$id]
        );

        // nếu có dữ liệu thì map sang Entity
        return $row ? new PromotionEntity($row) : null;
    }


    /**
     * Lấy promotion theo code
     *
     * Ý tưởng:
     * - Chuẩn hoá code trước khi query (tránh lỗi do nhập thường/hoa)
     * - Dùng prepared statement để chống SQL Injection
     */
    public function getByCode(string $code): ?PromotionEntity
    {
        $code = strtoupper(trim($code));

        $row = $this->fetchOne(
            "SELECT * FROM {$this->table} WHERE code = ? LIMIT 1",
            [$code]
        );

        return $row ? new PromotionEntity($row) : null;
    }


    /**
     * Lấy toàn bộ promotion
     */
    public function getAll(): array
    {
        $rows = $this->fetchAll(
            "SELECT * FROM {$this->table} ORDER BY {$this->defaultOrder}"
        );

        // map toàn bộ sang Entity để đảm bảo dùng chung logic
        return array_map(fn($r) => new PromotionEntity($r), $rows);
    }


    /**
     * Lấy promotion đang hoạt động
     *
     * Ý tưởng:
     * - Filter ngay tại DB để giảm dữ liệu trả về
     * - Tránh phải filter lại ở PHP
     */
    public function getActivePromotions(): array
    {
        $now = date('Y-m-d H:i:s');

        $rows = $this->fetchAll(
            "SELECT * FROM {$this->table}
             WHERE is_active = 1
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

    /**
     * Tạo promotion
     *
     * Cách làm:
     * - Tạo Entity → validate → insert
     * - Không insert trực tiếp array để tránh dữ liệu bẩn
     */
    public function create(array $data): int
    {
        $entity = new PromotionEntity($data);

        if (!$entity->isValid()) {
            // không trả lỗi thô → đảm bảo an toàn
            throw new RuntimeException("Dữ liệu promotion không hợp lệ");
        }

        return $this->insert($entity->toArray());
    }


    /**
     * Cập nhật promotion
     *
     * Ý tưởng:
     * - Merge dữ liệu cũ + mới
     * - Tránh mất dữ liệu chưa update
     */
    public function updatePromotion(int $id, array $data): bool
    {
        $existing = $this->getById($id);
        if (!$existing) return false;

        $entity = new PromotionEntity(
            array_merge($existing->toArray(), $data)
        );

        if (!$entity->isValid()) {
            throw new RuntimeException("Dữ liệu promotion không hợp lệ");
        }

        return $this->update($id, $entity->toArray());
    }


    /**
     * Xoá promotion
     */
    public function deletePromotion(int $id): bool
    {
        return $this->delete($id);
    }


    // =========================================================================
    // BUSINESS LOGIC
    // =========================================================================

    /**
     * Áp dụng mã khuyến mãi
     *
     * Ý tưởng chính:
     * - Tất cả nằm trong 1 transaction → đảm bảo dữ liệu nhất quán
     * - Không gọi transaction lồng nhau
     * - Không đọc rồi ghi → dùng SQL atomic
     */
    public function applyCode(string $code, float $total, bool $increaseUsed = true): array
    {
        return $this->transaction(function () use ($code, $total, $increaseUsed) {

            // B1: Lấy promotion
            $promotion = $this->getByCode($code);

            if (!$promotion) {
                return [
                    'success' => false,
                    'message' => 'Mã không tồn tại',
                    'discount' => 0,
                    'final' => $total
                ];
            }

            // B2: Kiểm tra điều kiện (Entity xử lý)
            if (!$promotion->canUse($total)) {
                return [
                    'success'  => false,
                    'message'  => $promotion->getFailMessage(),
                    'discount' => 0,
                    'final'    => $total
                ];
            }

            // B3: Tính discount
            $discount = $promotion->calculateDiscount($total);
            $final = max(0, $total - $discount);

            /**
             * B4: Tăng số lần sử dụng
             *
             * Cách làm:
             * - Không gọi method khác để tránh transaction lồng nhau
             * - Không đọc used_count trước
             * - Dùng SQL atomic → tránh race condition
             */
            if ($increaseUsed) {
                $this->execute(
                    "UPDATE {$this->table}
                     SET used_count = used_count + 1
                     WHERE id = ?",
                    [$promotion->getId()]
                );
            }

            return [
                'success'  => true,
                'message'  => 'Áp dụng thành công',
                'discount' => $discount,
                'final'    => $final
            ];
        });
    }


    // =========================================================================
    // HELPER
    // =========================================================================

    /**
     * Lấy trạng thái promotion (gom lại 1 lần query)
     *
     * Ý tưởng:
     * - Tránh gọi nhiều method gây nhiều query
     * - Lấy Entity 1 lần → dùng lại
     */
    public function getStatus(int $id): ?array
    {
        $promotion = $this->getById($id);
        if (!$promotion) return null;

        return [
            'isActive' => $promotion->isActive(),
            'isExpired' => $promotion->isExpired(),
            'isLimit' => $promotion->hasReachedUsageLimit()
        ];
    }


    /**
     * Áp dụng nhiều mã
     *
     * Ý tưởng:
     * - Chỉ check logic
     * - Không tăng used_count
     */
    public function applyMultipleCodes(array $codes, float $total): array
    {
        $results = [];

        foreach ($codes as $code) {
            $results[$code] = $this->applyCode($code, $total, false);
        }

        return $results;
    }
}
