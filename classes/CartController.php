<?php

require_once _DIR_ . "/../services/CartService.php";
require_once _DIR_ . "/../services/OrderService.php";
require_once _DIR_ . "/../models/ProductModel.php";

class CartController
{
    private CartService $cartService;
    private OrderService $orderService;

    public function __construct()
    {
        $this->cartService = new CartService(new ProductModel());
        $this->orderService = new OrderService();
    }

    // ADD
    public function add()
    {
        try {
            $id = (int)$_POST['product_id'];
            $qty = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

            $cart = $this->cartService->add($id, $qty);

            echo json_encode([
                'success' => true,
                'cart' => $cart
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    // REMOVE
    public function remove()
    {
        $id = (int)$_POST['product_id'];

        $cart = $this->cartService->remove($id);

        echo json_encode([
            'success' => true,
            'cart' => $cart
        ]);
    }

    // TOTAL + PROMO
    public function total()
    {
        try {
            $promoCode = $_POST['promo_code'] ?? null;

            $result = $this->orderService->calculate($promoCode);

            echo json_encode([
                'success' => true,
                'data' => $result
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}
