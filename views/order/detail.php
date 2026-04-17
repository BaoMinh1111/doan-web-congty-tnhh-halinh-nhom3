<div class="container mt-4 mb-5">
    <h3>Chi tiết đơn hàng #<?php echo $order['id']; ?></h3>
    <p>Trạng thái: <?php
        $statusMap = [
                'pending'    => ['class' => 'bg-warning text-dark', 'text' => 'Chờ xử lý'],
                'processing' => ['class' => 'bg-primary',           'text' => 'Đang xử lý'],
                'shipped'    => ['class' => 'bg-purple',            'text' => 'Đang vận chuyển'],
                'delivered'  => ['class' => 'bg-success',           'text' => 'Đã giao hàng'],
                'cancelled'  => ['class' => 'bg-danger',            'text' => 'Đã hủy'],
        ];
        $s = $statusMap[$order['status']] ?? ['class' => 'bg-secondary', 'text' => $order['status']];
        ?>
    <span class="badge <?= $s['class'] ?>"><?= $s['text'] ?></span>
    <hr>
    <div class="row">
        <div class="col-md-8">
            <h5>Sản phẩm đã đặt</h5>
            <table class="table table-bordered align-middle">
                <thead>
                <tr>
                    <th style="width: 80px;">Ảnh</th>  <!-- Thêm cột ảnh -->
                    <th>Sản phẩm</th>
                    <th>Giá</th>
                    <th>Số lượng</th>
                    <th>Thành tiền</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($orderItems as $item): ?>
                    <tr>
                        <!-- Thêm ô ảnh -->
                        <td>
                            <img src="assets/images/products/<?php echo htmlspecialchars($item['product_image'] ?? 'no-image.jpg'); ?>"
                                 alt="<?php echo htmlspecialchars($item['product_name']); ?>"
                                 style="width: 60px; height: 60px; object-fit: cover; border-radius: 6px;">
                        </td>
                        <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                        <td><?php echo number_format($item['price_at_purchase'], 0, ',', '.'); ?>đ</td>
                        <td><?php echo $item['quantity']; ?></td>
                        <td><?php echo number_format($item['price_at_purchase'] * $item['quantity'], 0, ',', '.'); ?>đ</td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="col-md-4">
            <h5>Thông tin giao hàng</h5>
            <div class="card p-3">
                <p><strong>Người nhận:</strong> <?php echo $order['customer_name']; ?></p>
                <p><strong>Điện thoại:</strong> <?php echo $order['phone']; ?></p>
                <p><strong>Địa chỉ:</strong> <?php echo $order['customer_address']; ?></p>
            </div>
        </div>
    </div>
</div>