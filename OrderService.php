<?php

require_once _DIR_ . "/../models/PromotionModel.php";
require_once _DIR_ . "/../helpers/SessionHelper.php";

class OrderService
{
    private PromotionModel $promotionModel;

    public function __construct()
    {
        $this->promotionModel = new PromotionModel();
    }

    public function calculate(?string $promoCode = null): array
    {
        $cart = SessionHelper::getCart();

        if (empty($cart)) {
            throw new Exception("Giỏ hàng trống");
        }

        $subtotal = 0;

        foreach ($cart as $item) {
            $subtotal += $item['product']->getPrice() * $item['quantity'];
        }

        $discount = 0;
        $total = $subtotal;

        if ($promoCode) {
            $promo = $this->promotionModel->getByCode($promoCode);

            if (!$promo) {
                throw new Exception("Mã không tồn tại");
            }

            if (!$promo->isValid()) {
                throw new Exception("Mã hết hạn");
            }

            if ($promo->getMinOrderValue() !== null &&
                $subtotal < $promo->getMinOrderValue()) {
                throw new Exception("Không đủ điều kiện áp dụng");
            }

            $discount = round(
                $subtotal * ($promo->getDiscountPercent() / 100),
                2
            );

            $total = max(0, $subtotal - $discount);
        }

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total' => $total
        ];
    }
}
