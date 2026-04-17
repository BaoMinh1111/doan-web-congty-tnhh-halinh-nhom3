<?php if (!empty($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show mx-3 mt-3" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        <?= $_SESSION['success']; unset($_SESSION['success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (!empty($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show mx-3 mt-3" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>
        <?= $_SESSION['error']; unset($_SESSION['error']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<div class="container mt-5 mb-5">
    <div class="row">
        <div class="col-md-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-history me-2"></i>ĐƠN HÀNG CỦA TÔI</h5>
                    <a href="index.php" class="btn btn-light btn-sm fw-bold">Tiếp tục mua sắm</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Mã đơn</th>
                                    <th>Ngày đặt</th>
                                    <th>Tổng tiền</th>
                                    <th>Trạng thái</th>
                                    <th class="text-center">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($orders)): ?>
                                    <?php foreach ($orders as $order): ?>
                                        <tr>
                                            <td class="ps-4 fw-bold text-primary">#<?php echo $order['id']; ?></td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                                            <td class="fw-bold"><?php echo number_format($order['total_price'], 0, ',', '.'); ?>đ</td>
                                            <td>
                                                <?php
                                                $statusMap = [
                                                        'pending'    => ['class' => 'bg-warning text-dark', 'text' => 'Chờ xử lý'],
                                                        'processing' => ['class' => 'bg-primary',           'text' => 'Đang xử lý'],
                                                        'shipped'    => ['class' => 'bg-purple text-white', 'text' => 'Đang vận chuyển'],
                                                        'delivered'  => ['class' => 'bg-success',           'text' => 'Đã giao hàng'],
                                                        'cancelled'  => ['class' => 'bg-danger',            'text' => 'Đã hủy'],
                                                ];
                                                $s = $statusMap[$order['status']] ?? ['class' => 'bg-secondary', 'text' => $order['status']];
                                                ?>
                                                <span class="badge <?= $s['class'] ?> rounded-pill">
                                                <?= $s['text'] ?>
                                                </span>
                                            </td>
                                            <td class="text-center d-flex gap-2 justify-content-center">
                                                <a href="index.php?controller=order&action=detail&id=<?php echo $order['id']; ?>"
                                                   class="btn btn-outline-primary btn-sm rounded-pill px-3">
                                                    Xem chi tiết
                                                </a>

                                                <?php if (in_array($order['status'], ['pending'])): ?>
                                                    <button onclick="confirmCancel(<?= $order['id'] ?>)"
                                                            class="btn btn-outline-danger btn-sm rounded-pill px-3">
                                                        <i class="fas fa-times me-1"></i>Hủy đơn
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-muted">
                                            <i class="fas fa-shopping-cart fa-3x mb-3"></i>
                                            <p>Bạn chưa có đơn hàng nào.</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        function confirmCancel(orderId) {
            if (confirm('Bạn có chắc muốn hủy đơn hàng #' + orderId + ' không?')) {
                window.location.href = 'index.php?controller=order&action=cancel&id=' + orderId;
            }
        }
    </script>
</div>