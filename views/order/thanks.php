<?php include 'views/layout/header.php'; ?>

<div class="container mt-5 mb-5 text-center">
    <div class="py-5">
        <div class="display-1 text-success mb-4"><i class="fas fa-check-circle"></i></div>
        <h1 class="fw-bold">CẢM ƠN BẠN ĐÃ TIN TƯỞNG HÀ LINH TECH!</h1>
        <p class="lead text-muted">Đơn hàng của bạn đã được tiếp nhận và đang trong quá trình đóng gói.</p>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-9 text-start">
            <div class="card border-0 shadow-lg">
                <div class="card-header bg-dark text-white p-3">
                    <h5 class="mb-0 text-center">CHI TIẾT ĐƠN HÀNG ĐÃ XÁC NHẬN</h5>
                </div>
                <div class="card-body p-4">
                    <div class="row mb-4">
                        <div class="row mb-4">
    <div class="col-sm-6 text-start">
        <p class="mb-2 text-muted small fw-bold">THÔNG TIN NGƯỜI NHẬN:</p>
        
        <div class="d-flex align-items-center mb-2">
            <i class="fas fa-user text-primary me-2"></i>
            <h5 class="fw-bold mb-0"><?php echo $order['customer_name']; ?></h5>
        </div>

        <div class="d-flex align-items-center mb-1">
            <i class="fas fa-phone-alt text-muted me-2" style="width: 16px;"></i>
            <span class="fw-semibold">SĐT: </span>
            <span class="ms-1 text-dark"><?php echo $order['phone'] ?? 'Chưa cập nhật'; ?></span>
        </div>

        <div class="d-flex align-items-start">
            <i class="fas fa-map-marker-alt text-muted me-2 mt-1" style="width: 16px;"></i>
            <div>
                <span class="fw-semibold">Địa chỉ: </span>
                <span class="ms-1 text-dark"><?php echo $order['customer_address']; ?></span>
            </div>
        </div>
    </div>

    <div class="col-sm-6 text-md-end">
        <p class="mb-1 text-muted small fw-bold">PHƯƠNG THỨC THANH TOÁN:</p>
        <span class="badge bg-primary fs-6 mb-3">
            <i class="fas fa-money-bill-wave me-1"></i>
            <?php echo ($method == 'COD') ? 'Thanh toán khi nhận hàng' : 'Chuyển khoản'; ?>
        </span>
        
        <p class="mb-1 text-muted small fw-bold">TRẠNG THÁI ĐƠN HÀNG:</p>
        <span class="badge bg-success py-2 px-3">
            <i class="fas fa-check me-1"></i> Đã xác nhận
        </span>
    </div>
</div>
                    </div>

                    <table class="table">
                        <thead>
                            <tr>
                                <th>Linh kiện</th>
                                <th class="text-center">SL</th>
                                <th class="text-end">Thành tiền</th>
                            </tr>
                        </thead>
                        <tbody>
                        <!-- PHẦN TỔNG TIỀN -->
                        <tfoot class="table-group-divider">
                        <tr>
                            <td colspan="2" class="text-end text-muted">Tạm tính:</td>
                            <td class="text-end fw-semibold">
                                <?php echo number_format($originalTotal); ?>đ
                            </td>
                        </tr>
                        <?php if ($discount > 0): ?>
                            <tr class="text-success">
                                <td colspan="2" class="text-end">
                                    <i class="fas fa-tag me-1"></i>Giảm giá:
                                </td>
                                <td class="text-end fw-semibold">
                                    -<?php echo number_format($discount); ?>đ
                                </td>
                            </tr>
                        <?php endif; ?>
                        <tr class="table-dark">
                            <td colspan="2" class="text-end fw-bold">TỔNG THANH TOÁN:</td>
                            <td class="text-end fw-bold fs-5">
                                <?php echo number_format($finalTotal); ?>đ
                            </td>
                        </tr>
                        </tfoot>

                            <?php foreach ($orderItems as $item): ?>
                            <tr>
                                <td><?php echo $item['product_name']; ?></td>
                                <td class="text-center"><?php echo $item['quantity']; ?></td>
                                <td class="text-end"><?php echo number_format($item['price_at_purchase'] * $item['quantity']); ?>đ</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <div class="alert alert-info border-0 mt-4">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Lưu ý:</strong> Nhân viên Hà Linh Tech sẽ gọi điện xác nhận đơn hàng với bạn trong vòng 15 phút.
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-4">
                <a href="index.php" class="btn btn-outline-dark px-5">Quay lại trang chủ</a>
                <button onclick="window.print()" class="btn btn-primary px-5 ms-2">
                    <i class="fas fa-print me-2"></i>In hóa đơn
                </button>
            </div>
        </div>
    </div>
</div>

<?php include 'views/layout/footer.php'; ?>
<style>
@media print {
    /* Ẩn tất cả những thứ không liên quan khi in */
    .navbar, footer, .btn, .alert, .breadcrumb {
        display: none !important;
    }
    
    /* Căn chỉnh lại hóa đơn cho đẹp trên giấy */
    .container {
        width: 100% !important;
        max-width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    
    .card {
        border: 1px solid #eee !important;
        box-shadow: none !important;
    }
    
    body {
        background-color: #fff !important;
    }

    /* Hiển thị tiêu đề Hóa Đơn Điện Tử khi in */
    .print-header {
        display: block !important;
        text-align: center;
        margin-bottom: 20px;
    }
}

/* Ẩn tiêu đề in khi xem trên màn hình */
.print-header {
    display: none;
}
</style>

<div class="print-header">
    <h1 style="color: #000;">HÓA ĐƠN ĐIỆN TỬ</h1>
    <p>Cửa hàng Linh kiện Máy tính Hà Linh Tech</p>
    <hr>
</div>