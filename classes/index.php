<?php

require_once 'controllers/CartController.php';

$action = $_GET['action'] ?? '';

switch ($action) {

    case 'cart':
        (new CartController())->index();
        exit;

    case 'cart_add':
        (new CartController())->add();
        exit;

    case 'cart_update':
        (new CartController())->update();
        exit;

    case 'cart_remove':
        (new CartController())->remove();
        exit;

    case 'cart_clear':
        (new CartController())->clear();
        exit;

    case 'cart_total':
        (new CartController())->total();
        exit;

    default:
        header("Location: ?action=cart");
        exit;
}
