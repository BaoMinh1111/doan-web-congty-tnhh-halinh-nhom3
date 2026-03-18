<?php

require_once _DIR_ . "/../models/ProductModel.php";
require_once _DIR_ . "/../helpers/SessionHelper.php";

class CartService
{
    private ProductModel $productModel;

    public function __construct(ProductModel $pm)
    {
        $this->productModel = $pm;
    }

    public function add(int $productId, int $quantity = 1): array
    {
        if ($quantity <= 0) {
            throw new Exception("Số lượng không hợp lệ");
        }

        $cart = SessionHelper::getCart();

        $product = $this->productModel->getById($productId);

        if (!$product) {
            throw new Exception("Sản phẩm không tồn tại");
        }

        // Check tồn kho
        if ($product->getStock() !== null && $product->getStock() < $quantity) {
            throw new Exception("Không đủ hàng trong kho");
        }

        if (isset($cart[$productId])) {
            $newQty = $cart[$productId]['quantity'] + $quantity;

            if ($product->getStock() !== null && $product->getStock() < $newQty) {
                throw new Exception("Vượt quá tồn kho");
            }

            $cart[$productId]['quantity'] = $newQty;
        } else {
            $cart[$productId] = [
                'product' => $product,
                'quantity' => $quantity
            ];
        }

        SessionHelper::setCart($cart);
        return $cart;
    }

    public function remove(int $productId): array
    {
        $cart = SessionHelper::getCart();

        if (isset($cart[$productId])) {
            unset($cart[$productId]);
        }

        SessionHelper::setCart($cart);
        return $cart;
    }

    public function getItems(): array
    {
        return SessionHelper::getCart();
    }

    public function getSubtotal(): float
    {
        $total = 0;

        foreach (SessionHelper::getCart() as $item) {
            $total += $item['product']->getPrice() * $item['quantity'];
        }

        return $total;
    }
}
