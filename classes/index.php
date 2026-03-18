$action = $_GET['action'] ?? '';

switch ($action) {
    case 'cart_add':
        (new CartController())->add();
        break;

    case 'cart_remove':
        (new CartController())->remove();
        break;

    case 'cart_total':
        (new CartController())->total();
        break;
}
