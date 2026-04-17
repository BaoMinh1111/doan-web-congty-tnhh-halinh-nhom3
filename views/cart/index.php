<?php include 'views/layout/header.php'; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <?php
        echo $_SESSION['error'];
        unset($_SESSION['error']);
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

    <h2 class="mb-4 fw-bold text-primary"><i class="fas fa-shopping-cart me-2"></i>Giỏ hàng của bạn</h2>

    <div class="row">
        <div class="col-md-8">
            <table class="table align-middle shadow-sm bg-white rounded">
                <thead class="table-light">
                <tr>
                    <th style="width:40px;">
                        <input type="checkbox" id="select-all" title="Chọn tất cả"
                               style="width:18px;height:18px;cursor:pointer;">
                    </th>
                    <th>Sản phẩm</th>
                    <th>Giá</th>
                    <th>Số lượng</th>
                    <th>Thành tiền</th>
                    <th>Xóa</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach($cartItems as $item): ?>
                    <tr id="row-<?php echo $item['product']['id']; ?>">
                        <td>
                            <input type="checkbox"
                                   class="item-checkbox"
                                   data-id="<?php echo $item['product']['id']; ?>"
                                   style="width:18px;height:18px;cursor:pointer;"
                                   checked>
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                <img src="assets/images/products/<?php echo $item['product']['image']; ?>" width="60" class="me-3 rounded border">
                                <span class="fw-bold"><?php echo $item['product']['name']; ?></span>
                            </div>
                        </td>
                        <td id="price-<?php echo $item['product']['id']; ?>"
                            data-price="<?php echo $item['product']['price']; ?>">
                            <?php echo number_format($item['product']['price']); ?>
                        </td>
                        <td>
                            <input type="number"
                                   id="qty-<?php echo $item['product']['id']; ?>"
                                   value="<?php echo $item['quantity']; ?>"
                                   class="form-control"
                                   style="width:80px;"
                                   min="1"
                                   onchange="updateQuantity(<?php echo $item['product']['id']; ?>, this.value)">
                        </td>
                        <td class="fw-bold text-primary">
                        <span id="subtotal-<?php echo $item['product']['id']; ?>">
                            <?php echo number_format($item['subtotal']); ?>
                        </span> VNĐ
                        </td>
                        <td>
                            <a href="index.php?controller=cart&action=delete&id=<?php echo $item['product']['id']; ?>"
                               class="btn btn-sm btn-outline-danger"
                               onclick="return confirm('Bạn có chắc chắn muốn xóa linh kiện này không?');">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="col-md-4">
            <div class="card p-4 shadow-sm border-0 bg-light">
                <h4 class="fw-bold mb-3">Tóm tắt đơn hàng</h4>
                <hr>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Đã chọn:</span>
                    <span id="selected-count" class="fw-bold">0 sản phẩm</span>
                </div>
                <div class="d-flex justify-content-between mb-4">
                    <span class="fs-5">Tổng tiền:</span>
                    <h4 class="text-danger fw-bold" id="total-amount">0 VNĐ</h4>
                </div>

                <?php if (empty($cartItems)): ?>
                    <div class="text-center py-3">
                        <p class="text-muted small">Chưa có sản phẩm nào để thanh toán.</p>
                        <a href="index.php" class="btn btn-warning w-100 fw-bold">QUAY LẠI CỬA HÀNG</a>
                    </div>
                <?php else: ?>
                    <!-- Form gửi danh sách sản phẩm đã chọn -->
                    <form id="checkout-form" action="index.php" method="GET">
                        <input type="hidden" name="controller" value="order">
                        <input type="hidden" name="action" value="checkoutSelected">
                        <input type="hidden" name="selected_ids" id="selected-ids-input" value="">
                        <button type="submit" id="checkout-btn" class="btn btn-success btn-lg w-100 fw-bold shadow-sm" disabled>
                            <i class="fas fa-check-circle me-2"></i>THANH TOÁN NGAY
                        </button>
                    </form>
                    <p id="no-selection-msg" class="text-muted small text-center mt-2">
                        Vui lòng chọn ít nhất 1 sản phẩm
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // ── Tính lại tổng khi tick/untick ──────────────────────────────────────────
        function recalcTotal() {
            let total    = 0;
            let count    = 0;
            let selected = [];

            document.querySelectorAll('.item-checkbox').forEach(function(cb) {
                const id    = cb.dataset.id;
                const price = parseFloat(document.getElementById('price-' + id).dataset.price);
                const qty   = parseInt(document.getElementById('qty-' + id).value) || 1;

                if (cb.checked) {
                    total += price * qty;
                    count++;
                    selected.push(id);
                }
            });

            document.getElementById('total-amount').textContent =
                new Intl.NumberFormat('vi-VN').format(total) + ' VNĐ';

            document.getElementById('selected-count').textContent = count + ' sản phẩm';

            const btn = document.getElementById('checkout-btn');
            const msg = document.getElementById('no-selection-msg');
            if (btn) {
                btn.disabled = (count === 0);
                if (msg) msg.style.display = (count === 0) ? 'block' : 'none';
            }

            document.getElementById('selected-ids-input').value = selected.join(',');
        }

        // ── Checkbox từng dòng ─────────────────────────────────────────────────────
        document.querySelectorAll('.item-checkbox').forEach(function(cb) {
            cb.addEventListener('change', function() {
                // Đồng bộ trạng thái "chọn tất cả"
                const all   = document.querySelectorAll('.item-checkbox').length;
                const checked = document.querySelectorAll('.item-checkbox:checked').length;
                document.getElementById('select-all').checked = (all === checked);
                document.getElementById('select-all').indeterminate = (checked > 0 && checked < all);
                recalcTotal();
            });
        });

        // ── Checkbox "Chọn tất cả" ─────────────────────────────────────────────────
        document.getElementById('select-all').addEventListener('change', function() {
            document.querySelectorAll('.item-checkbox').forEach(function(cb) {
                cb.checked = document.getElementById('select-all').checked;
            });
            recalcTotal();
        });

        // ── Cập nhật số lượng qua AJAX ─────────────────────────────────────────────
        function updateQuantity(productId, newQty) {
            if (newQty < 1) {
                alert("Số lượng phải lớn hơn hoặc bằng 1!");
                return;
            }

            $.ajax({
                url: 'index.php?controller=cart&action=updateAjax',
                type: 'POST',
                data: { product_id: productId, quantity: newQty },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        // Cập nhật thành tiền dòng
                        let price    = parseFloat($('#price-' + productId).data('price'));
                        let subtotal = price * newQty;
                        $('#subtotal-' + productId).text(new Intl.NumberFormat('vi-VN').format(subtotal));

                        // Cập nhật badge header
                        $('#cart-count').text(response.data.count);

                        // Tính lại tổng theo checkbox
                        recalcTotal();
                    } else {
                        alert(response.message);
                    }
                },
                error: function() {
                    alert('Lỗi kết nối. Không thể cập nhật số lượng.');
                }
            });
        }

        // ── Chạy lần đầu để tính tổng ban đầu (tất cả đã checked) ─────────────────
        recalcTotal();
    </script>

<?php include 'views/layout/footer.php'; ?>