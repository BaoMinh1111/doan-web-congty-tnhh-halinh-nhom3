<?php
// Ví dụ xử lý AJAX thêm vào giỏ
$cartService->addToCart($_POST['product_id'], 1);

// Trả về JSON để Frontend cập nhật giao diện mà không cần load lại trang
echo json_encode([
    'status' => 'success',
    'total_items' => $cartService->countItems(),
    'total_price' => number_format($cartService->getTotalPrice()) . ' VNĐ'
]);
?>