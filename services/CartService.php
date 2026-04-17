<?php
class CartService {
    private $productModel;

    public function __construct(ProductModel $productModel) {
        $this->productModel = $productModel;
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
    }

    public function addToCart(int $productId, int $quantity = 1): void {
        if (isset($_SESSION['cart'][$productId])) {
            $_SESSION['cart'][$productId] += $quantity;
        } else {
            $_SESSION['cart'][$productId] = $quantity;
        }
    }

    public function updateQuantity(int $productId, int $quantity): void {
        if ($quantity <= 0) {
            $this->removeFromCart($productId);
        } else {
            $_SESSION['cart'][$productId] = $quantity;
        }
    }

    public function removeFromCart(int $productId): void {
        unset($_SESSION['cart'][$productId]);
    }

    public function getCartDetails(): array {
        $details = [];
        foreach ($_SESSION['cart'] as $productId => $quantity) {
            $product = $this->productModel->getById($productId);
            if ($product) {
                $details[] = [
                    'product'  => $product,
                    'quantity' => (int)$quantity,
                    'subtotal' => (float)$product['price'] * (int)$quantity
                ];
            }
        }
        return $details;
    }

    public function getTotalPrice(): float {
        $total = 0;
        foreach ($this->getCartDetails() as $item) {
            $total += $item['subtotal'];
        }
        return $total;
    }

    public function countItems(): int {
        return count($_SESSION['cart']);
    }

    public function clearCart(): void {
        $_SESSION['cart'] = [];
    }
}