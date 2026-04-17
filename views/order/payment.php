<?php include 'views/layout/header.php'; ?>

    <style>
        .payment-option {
            border: 2px solid #dee2e6;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-bottom: 12px;
            overflow: hidden;
        }
        .payment-option:hover {
            border-color: #adb5bd;
            background-color: #f8f9fa;
        }
        .payment-option.selected {
            border-color: #198754;
            background-color: #f0fff4;
        }
        .payment-option .option-header {
            display: flex;
            align-items: center;
            padding: 16px 20px;
            gap: 12px;
        }
        .payment-option .option-header .form-check-input {
            width: 20px;
            height: 20px;
            margin: 0;
            flex-shrink: 0;
            cursor: pointer;
            accent-color: #198754;
        }
        .payment-option .option-header label {
            cursor: pointer;
            font-weight: 500;
            font-size: 15px;
            margin: 0;
            flex-grow: 1;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .payment-option .option-body {
            display: none;
            padding: 0 20px 20px 52px;
            border-top: 1px solid #e9ecef;
            background-color: #fff;
        }
        .payment-option.selected .option-body {
            display: block;
        }
        .payment-option.selected .option-header {
            background-color: #f0fff4;
        }
    </style>

    <div class="container mt-5 mb-5">
        <div class="row justify-content-center">
            <div class="col-md-8">

                <!-- Hóa đơn -->
                <div class="card shadow border-0 mb-4">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <h2 class="fw-bold text-success text-uppercase">Hóa Đơn Thanh Toán</h2>
                            <p class="text-muted">Mã đơn hàng: <span class="badge bg-dark">#<?php echo $orderId; ?></span></p>
                        </div>

                        <div class="row mb-4 bg-light p-4 rounded-3 border">
                            <div class="col-md-7">
                                <h5 class="border-bottom pb-2 mb-3"><i class="fas fa-user-check me-2"></i>Thông tin nhận hàng</h5>
                                <p class="mb-2"><strong>Họ và tên:</strong> <?php echo $order['full_name']; ?></p>
                                <p class="mb-2"><strong>Số điện thoại:</strong> <?php echo $order['phone']; ?></p>
                                <p class="mb-2"><strong>Email:</strong> <?php echo $order['email']; ?></p>
                                <p class="mb-2"><strong>Địa chỉ nhận hàng:</strong> <?php echo $order['address']; ?></p>
                            </div>
                            <div class="col-md-5 text-md-end border-start">
                                <h5 class="border-bottom pb-2 mb-3"><i class="fas fa-clock me-2"></i>Thời gian</h5>
                                <p class="mb-2"><strong>Ngày đặt hàng:</strong> <?php echo date('d/m/Y'); ?></p>
                                <p class="mb-2"><strong>Giờ đặt hàng:</strong> <?php echo date('H:i:s'); ?></p>
                                <p class="mb-2"><strong>Trạng thái:</strong> <span class="text-warning fw-bold">Chờ thanh toán</span></p>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-dark">
                                <tr>
                                    <th>Tên linh kiện</th>
                                    <th class="text-center">Số lượng</th>
                                    <th class="text-end">Đơn giá</th>
                                    <th class="text-end">Thành tiền</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($cartItems as $item): ?>
                                    <tr>
                                        <td><?php echo $item['product']['name']; ?></td>
                                        <td class="text-center"><?php echo $item['quantity']; ?></td>
                                        <td class="text-end"><?php echo number_format($item['product']['price']); ?>đ</td>
                                        <td class="text-end fw-bold"><?php echo number_format($item['subtotal']); ?>đ</td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                <?php if (!empty($discount) && $discount > 0): ?>
                                    <tr class="table-light">
                                        <td colspan="3" class="text-end text-success">
                                            <i class="fas fa-tag me-1"></i>Giảm giá (mã KM):
                                        </td>
                                        <td class="text-end text-success fw-bold">
                                            -<?php echo number_format($discount); ?>đ
                                        </td>
                                    </tr>
                                    <tr class="table-light">
                                        <td colspan="3" class="text-end text-muted" style="font-size:13px;">Tạm tính:</td>
                                        <td class="text-end text-muted" style="font-size:13px; text-decoration:line-through;">
                                            <?php echo number_format($discount + $finalTotal); ?>đ
                                        </td>
                                    </tr>
                                <?php endif; ?>
                                <tr class="table-light">
                                    <td colspan="3" class="text-end fw-bold fs-5">TỔNG CỘNG:</td>
                                    <td class="text-end text-danger fw-bold fs-5"><?php echo number_format($finalTotal); ?>đ</td>
                                </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Chọn phương thức thanh toán -->
                <div class="card shadow border-0">
                    <div class="card-header bg-dark text-white py-3">
                        <h5 class="mb-0"><i class="fas fa-credit-card me-2"></i>CHỌN PHƯƠNG THỨC THANH TOÁN</h5>
                    </div>
                    <div class="card-body p-4">

                        <!-- Option 1: QR -->
                        <div class="payment-option selected" id="opt-QR" onclick="selectMethod('QR')">
                            <div class="option-header">
                                <input class="form-check-input" type="radio" name="paymentMethod"
                                       id="radioQR" value="QR" checked>
                                <label for="radioQR">
                                    <i class="fas fa-qrcode text-success"></i>
                                    Chuyển khoản nhanh qua QR (VietQR)
                                </label>
                            </div>
                            <div class="option-body text-center pt-3">
                                <p class="small text-muted mb-3">Mở ứng dụng Ngân hàng để quét mã thanh toán</p>
                                <img src="https://img.vietqr.io/image/vcb-123456789-compact2.jpg?amount=<?php echo $finalTotal; ?>&addInfo=Thanh toan Don hang <?php echo $orderId; ?>"
                                     class="img-fluid rounded" style="max-width: 230px;">
                                <div class="alert alert-info mt-3 small mb-0">
                                    <i class="fas fa-university me-1"></i>
                                    STK: <strong>123456789</strong> | VCB | Chủ TK: <strong>HA LINH TECH</strong>
                                </div>
                            </div>
                        </div>

                        <!-- Option 2: COD -->
                        <div class="payment-option" id="opt-COD" onclick="selectMethod('COD')">
                            <div class="option-header">
                                <input class="form-check-input" type="radio" name="paymentMethod"
                                       id="radioCOD" value="COD">
                                <label for="radioCOD">
                                    <i class="fas fa-truck text-warning"></i>
                                    Thanh toán khi nhận hàng (COD)
                                </label>
                            </div>
                            <div class="option-body pt-3">
                                <div class="d-flex align-items-center gap-3 p-3 bg-light rounded-3">
                                    <i class="fas fa-2x text-warning"></i>
                                    <div>
                                        <p class="mb-1">Số tiền cần chuẩn bị:
                                            <span class="fs-5 fw-bold text-danger"><?php echo number_format($finalTotal); ?>đ</span>
                                        </p>
                                        <?php if (!empty($discount) && $discount > 0): ?>
                                            <p class="text-success small mb-0 mt-1">
                                                <i class="fas fa-check-circle me-1"></i>
                                                Đã áp dụng mã giảm giá: -<?php echo number_format($discount); ?>đ
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Nút xác nhận dùng chung -->
                    <div class="card-footer bg-white border-top p-4 text-center">
                        <a id="confirmBtn"
                           href="index.php?controller=order&action=confirmSuccess&id=<?php echo $orderId; ?>&method=QR"
                           class="btn btn-success btn-lg px-5 shadow-sm">
                            <i class="fas fa-check-circle me-2"></i>Xác nhận đặt hàng
                        </a>
                        <p class="text-muted small mt-2 mb-0">
                            Phương thức đã chọn: <strong id="methodLabel">Chuyển khoản QR</strong>
                        </p>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script>
        const baseUrl     = 'index.php?controller=order&action=confirmSuccess&id=<?php echo $orderId; ?>&method=';
        const methodNames = { QR: 'Chuyển khoản QR', COD: 'Thanh toán khi nhận hàng (COD)' };

        function selectMethod(method) {
            // Bỏ selected tất cả
            document.querySelectorAll('.payment-option').forEach(el => el.classList.remove('selected'));

            // Chọn cái mới
            document.getElementById('opt-' + method).classList.add('selected');
            document.getElementById('radio' + method).checked = true;

            // Cập nhật nút xác nhận
            document.getElementById('confirmBtn').href = baseUrl + method;
            document.getElementById('methodLabel').textContent = methodNames[method];
        }
    </script>

<?php include 'views/layout/footer.php'; ?>