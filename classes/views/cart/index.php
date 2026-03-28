<?php
/**
 * ================= VIEW: CART =================
 *
 * Chức năng:
 * - Hiển thị danh sách sản phẩm trong giỏ hàng
 * - Cho phép cập nhật số lượng (AJAX)
 * - Cho phép xoá từng sản phẩm / toàn bộ giỏ
 * - Hiển thị tổng tiền
 *
 */
?>

<h2> Giỏ hàng của bạn</h2>

<table border="1" width="100%" cellpadding="10" cellspacing="0">
    <thead>
        <tr>
            <th>Tên sản phẩm</th>
            <th>Giá</th>
            <th>Số lượng</th>
            <th>Thành tiền</th>
            <th>Hành động</th>
        </tr>
    </thead>

    <tbody>

        <?php if (empty($cart)): ?>
            <!-- Giỏ hàng trống -->
            <tr>
                <td colspan="5" style="text-align:center;">
                    Giỏ hàng trống
                </td>
            </tr>
        <?php else: ?>

            <?php foreach ($cart as $item): ?>

                <?php
                // ===== XỬ LÝ AN TOÀN DỮ LIỆU =====
                $id       = (int) ($item['id'] ?? 0);
                $name     = htmlspecialchars($item['name'] ?? '', ENT_QUOTES, 'UTF-8');
                $price    = (float) ($item['price'] ?? 0);
                $quantity = (int) ($item['quantity'] ?? 0);

                // Tính thành tiền (chỉ phục vụ hiển thị)
                $subtotal = $price * $quantity;
                ?>

                <tr>
                    <!-- Tên -->
                    <td><?= $name ?></td>

                    <!-- Giá -->
                    <td><?= number_format($price) ?> VND</td>

                    <!-- Số lượng -->
                    <td>
                        <input 
                            type="number"
                            min="0"
                            value="<?= $quantity ?>"
                            onchange="updateCart(<?= $id ?>, this.value)"
                            style="width:60px;"
                        >
                    </td>

                    <!-- Thành tiền -->
                    <td><?= number_format($subtotal) ?> VND</td>

                    <!-- Xoá -->
                    <td>
                        <button onclick="removeItem(<?= $id ?>)">
                            Xoá
                        </button>
                    </td>
                </tr>

            <?php endforeach; ?>

        <?php endif; ?>

    </tbody>
</table>

<!-- Tổng tiền -->
<h3 id="total">
     Tổng tiền: <?= number_format((float)($total ?? 0)) ?> VND
</h3>

<hr>

<!-- Xoá toàn bộ -->
<button onclick="clearCart()">
    🗑 Xoá toàn bộ giỏ hàng
</button>


<!-- ================= AJAX ================= -->
<script>

/**
 * UPDATE: cập nhật số lượng
 * - Gửi request đến CartController (cart_update)
 * - Server xử lý → trả JSON
 * - Reload lại UI
 */
function updateCart(productId, quantity) {

    quantity = parseInt(quantity);

    // Validate phía client
    if (isNaN(quantity) || quantity < 0) {
        alert('Số lượng không hợp lệ');
        return;
    }

    fetch(?action=cart_update&product_id=${productId}&quantity=${quantity})
        .then(res => res.json())
        .then(data => {

            if (!data.success) {
                alert(data.message);
                return;
            }

            location.reload();
        })
        .catch(() => alert('Lỗi server'));
}


/**
 * REMOVE: xoá 1 sản phẩm
 */
function removeItem(productId) {

    if (!confirm('Bạn có chắc muốn xoá?')) return;

    fetch(?action=cart_remove&product_id=${productId})
        .then(res => res.json())
        .then(data => {

            if (!data.success) {
                alert(data.message);
                return;
            }

            location.reload();
        })
        .catch(() => alert('Lỗi server'));
}


/**
 * CLEAR: xoá toàn bộ giỏ
 */
function clearCart() {

    if (!confirm('Xoá toàn bộ giỏ hàng?')) return;

    fetch(?action=cart_clear)
        .then(res => res.json())
        .then(data => {

            if (!data.success) {
                alert(data.message);
                return;
            }

            location.reload();
        })
        .catch(() => alert('Lỗi server'));
}

</script>
