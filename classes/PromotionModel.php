
<?php
require_once "BaseModel.php";
require_once "PromotionEntity.php";

class PromotionModel extends BaseModel
{
    protected string $table = "promotions";

    public function getByCode(string $code): ?PromotionEntity
    {
        $sql = "SELECT * FROM {$this->table} WHERE code = ?";
        $row = $this->fetchOne($sql, [$code]);

        if (!$row) return null;

        return new PromotionEntity($row);
    }
}
