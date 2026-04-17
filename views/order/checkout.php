<?php include 'views/layout/header.php'; ?>

    <div class="container mt-4">
        <h2 class="mb-4 text-primary">XÁC NHẬN THANH TOÁN</h2>
        <div class="row">
            <div class="col-md-7">
                <div class="card shadow-sm p-4">
                    <h4>Thông tin giao hàng</h4>

                    <?php if (!isset($_SESSION['user'])): ?>
                        <div class="alert alert-info d-flex align-items-center mb-3" role="alert">
                            <i class="fas fa-info-circle me-2"></i>
                            <div>
                                Bạn đang mua với tư cách <strong>khách</strong>.
                                <a href="index.php?controller=auth&action=login" class="alert-link">Đăng nhập</a>
                                để tự động điền thông tin và xem lại đơn hàng sau.
                            </div>
                        </div>
                    <?php endif; ?>

                    <form action="index.php?controller=order&action=placeOrder" method="POST">
                        <div class="mb-3">
                            <label class="form-label">Họ và tên người nhận <span class="text-danger">*</span></label>
                            <input type="text" name="full_name" class="form-control"
                                   value="<?php echo htmlspecialchars($_SESSION['user']['fullname'] ?? ''); ?>"
                                   placeholder="Nhập họ và tên" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Số điện thoại <span class="text-danger">*</span></label>
                            <input type="text" name="phone" class="form-control"
                                   value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"
                                   placeholder="Nhập số điện thoại" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email nhận hóa đơn <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control"
                                   value="<?php echo htmlspecialchars($_SESSION['user']['email'] ?? ''); ?>"
                                   placeholder="Nhập địa chỉ email" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Địa chỉ nhận hàng <span class="text-danger">*</span></label>
                            <textarea name="address" class="form-control" rows="3"
                                      placeholder="Số nhà, đường, phường/xã, quận/huyện, tỉnh/thành phố" required><?php echo htmlspecialchars($_SESSION['user']['address'] ?? ''); ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Ghi chú <span class="text-muted">(tuỳ chọn)</span></label>
                            <textarea name="note" class="form-control" rows="2"
                                      placeholder="Giao giờ hành chính, gọi trước khi giao..."></textarea>
                        </div>

                        <!-- Hidden input gửi mã giảm giá lên server -->
                        <input type="hidden" name="applied_promo_code" id="applied-promo-code" value="">

                        <button type="submit" class="btn btn-success btn-lg w-100">
                            <i class="fas fa-check-circle me-2"></i>XÁC NHẬN ĐẶT HÀNG
                        </button>
                    </form>
                </div>
            </div>

            <div class="col-md-5">
                <div class="card shadow-sm p-3">
                    <h5>Tóm tắt đơn hàng</h5>
                    <hr>
                    <?php foreach ($cartItems as $item): ?>
                        <div class="d-flex justify-content-between mb-2">
                            <span>
                                <?php echo htmlspecialchars($item['product']['name']); ?>
                                <small class="text-muted">(x<?php echo $item['quantity']; ?>)</small>
                            </span>
                            <span><?php echo number_format($item['subtotal']); ?>đ</span>
                        </div>
                    <?php endforeach; ?>
                    <hr>

                    <!-- MÃ GIẢM GIÁ -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Mã giảm giá</label>
                        <div class="input-group">
                            <input type="text" id="promo-input" class="form-control"
                                   placeholder="Nhập mã..." style="text-transform:uppercase">
                            <button type="button" class="btn btn-primary" onclick="applyPromo()">
                                Áp dụng
                            </button>
                        </div>
                        <div id="promo-msg" class="mt-1" style="font-size:13px;"></div>
                    </div>

                    <!-- FIX: dùng d-none thay vì style="display:none !important" -->
                    <div id="discount-row" class="d-flex justify-content-between text-success mb-2 d-none">
                        <span>Giảm giá:</span>
                        <span>-<span id="discount-display">0</span>đ</span>
                    </div>

                    <hr>
                    <div class="d-flex justify-content-between fw-bold text-danger">
                        <span>TỔNG CỘNG:</span>
                        <span id="total-display"><?php echo number_format($totalPrice); ?>đ</span>
                    </div>
                </div>

                <?php if (!isset($_SESSION['user'])): ?>
                    <div class="card shadow-sm p-3 mt-3 border-warning">
                        <h6 class="text-warning"><i class="fas fa-star me-1"></i>Lợi ích khi có tài khoản</h6>
                        <ul class="small text-muted mb-2 ps-3">
                            <li>Xem lại lịch sử đơn hàng bất kỳ lúc nào</li>
                            <li>Theo dõi trạng thái giao hàng</li>
                            <li>Tự động điền thông tin lần sau</li>
                        </ul>
                        <a href="index.php?controller=auth&action=register" class="btn btn-outline-warning btn-sm w-100">
                            <i class="fas fa-user-plus me-1"></i>Đăng ký miễn phí
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        const originalTotal = <?php echo $totalPrice; ?>;

        async function applyPromo() {
            const code  = document.getElementById('promo-input').value.trim().toUpperCase();
            const msgEl = document.getElementById('promo-msg');

            if (!code) {
                msgEl.innerHTML = '<span class="text-danger">Vui lòng nhập mã giảm giá!</span>';
                return;
            }

            try {
                const res = await fetch('index.php?controller=order&action=applyPromotion', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `promo_code=${encodeURIComponent(code)}&order_total=${originalTotal}`
                });
                const data = await res.json();

                if (data.success) {
                    // FIX: dùng classList thay vì style trực tiếp
                    document.getElementById('discount-row').classList.remove('d-none');

                    document.getElementById('discount-display').textContent =
                        Number(data.discount).toLocaleString('vi-VN');
                    document.getElementById('total-display').textContent =
                        Number(data.new_total).toLocaleString('vi-VN') + 'đ';
                    document.getElementById('applied-promo-code').value = code;

                    msgEl.innerHTML = `<span class="text-success">✓ ${data.message}</span>`;
                } else {
                    document.getElementById('discount-row').classList.add('d-none');
                    document.getElementById('total-display').textContent =
                        Number(originalTotal).toLocaleString('vi-VN') + 'đ';
                    document.getElementById('applied-promo-code').value = '';

                    msgEl.innerHTML = `<span class="text-danger">✗ ${data.message}</span>`;
                }
            } catch (err) {
                msgEl.innerHTML = '<span class="text-danger">Lỗi kết nối, vui lòng thử lại!</span>';
            }
        }
    </script>

<?php include 'views/layout/footer.php'; ?>