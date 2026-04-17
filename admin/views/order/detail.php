<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title>Chi tiết đơn hàng #<?= $order['id'] ?></title>
    <style>
        .sidebar { min-height: 100vh; background: #212529; color: white; }
        .sidebar .nav-link { color: #adb5bd; transition: 0.3s; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { color: #ffc107; background: rgba(255,255,255,0.1); }
        .main-content { background: #f8f9fa; min-height: 100vh; }
        .badge-pending    { background: #ffc107; color: #000; }
        .badge-processing { background: #0d6efd; color: #fff; }
        .badge-shipped    { background: #6f42c1; color: #fff; }
        .badge-delivered  { background: #198754; color: #fff; }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <nav class="col-md-2 d-none d-md-block sidebar shadow-sm p-3">
            <h4 class="text-warning text-center mb-4">HÀ LINH ADMIN</h4>
            <ul class="nav flex-column">
                <li class="nav-item mb-2">
                    <a class="nav-link" href="index.php?controller=product">
                        <i class="fas fa-microchip me-2"></i> Quản lý sản phẩm
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link" href="index.php?controller=category">
                        <i class="fas fa-list me-2"></i> Quản lý danh mục
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link" href="index.php?controller=post">
                        <i class="fas fa-newspaper me-2"></i> Quản lý bài viết
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link active" href="index.php?controller=order">
                        <i class="fas fa-shopping-cart me-2"></i> Quản lý đơn hàng
                    </a>
                </li>
                <li class="nav-item mt-4 border-top pt-3">
                    <a class="nav-link text-info" href="../index.php" target="_blank">
                        <i class="fas fa-external-link-alt me-2"></i> Xem Website
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-danger fw-bold" href="index.php?controller=auth&action=logout">
                        <i class="fas fa-sign-out-alt me-2"></i> Đăng xuất
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Main content -->
        <main class="col-md-10 ms-sm-auto px-md-4 main-content">
            <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Chi tiết đơn hàng #<?= $order['id'] ?></h1>
                <a href="index.php?controller=order&action=index" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left me-1"></i> Quay lại
                </a>
            </div>

            <div class="row g-4">
                <!-- Thông tin khách hàng -->
                <div class="col-md-6">
                    <div class="card shadow-sm">
                        <div class="card-header bg-dark text-white">
                            <i class="fas fa-user me-2"></i> Thông tin khách hàng
                        </div>
                        <div class="card-body">
                            <p><strong>Họ tên:</strong> <?= htmlspecialchars($order['customer_name']) ?></p>
                            <p><strong>Email:</strong> <?= htmlspecialchars($order['email']) ?></p>
                            <p><strong>SĐT:</strong> <?= htmlspecialchars($order['phone'] ?? 'N/A') ?></p>
                            <p><strong>Địa chỉ:</strong> <?= htmlspecialchars($order['customer_address']) ?></p>
                            <p><strong>Ghi chú:</strong> <?= htmlspecialchars($order['note'] ?? 'Không có') ?></p>
                            <p><strong>Ngày đặt:</strong> <?= $order['created_at'] ?></p>
                            <p><strong>Tổng tiền:</strong>
                                <span class="text-danger fw-bold"><?= number_format($order['total_price']) ?>đ</span>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Cập nhật trạng thái -->
                <div class="col-md-6">
                    <div class="card shadow-sm">
                        <div class="card-header bg-dark text-white">
                            <i class="fas fa-edit me-2"></i> Cập nhật trạng thái
                        </div>
                        <div class="card-body">
                            <?php
                            $labels = [
                                    'pending'    => ['text' => 'Chờ xử lý',       'class' => 'badge-pending'],
                                    'processing' => ['text' => 'Đang xử lý',      'class' => 'badge-processing'],
                                    'shipped'    => ['text' => 'Đang vận chuyển', 'class' => 'badge-shipped'],
                                    'delivered'  => ['text' => 'Đã giao hàng',    'class' => 'badge-delivered'],
                            ];
                            $s = $order['status'] ?? 'pending';
                            ?>
                            <p><strong>Trạng thái hiện tại:</strong>
                                <span class="badge <?= $labels[$s]['class'] ?>">
                                    <?= $labels[$s]['text'] ?>
                                </span>
                            </p>
                            <form method="POST" action="index.php?controller=order&action=updateStatus">
                                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Chọn trạng thái mới:</label>
                                    <select name="status" class="form-select">
                                        <option value="pending"    <?= $s === 'pending'    ? 'selected' : '' ?>>⏳ Chờ xử lý</option>
                                        <option value="processing" <?= $s === 'processing' ? 'selected' : '' ?>>🔄 Đang xử lý</option>
                                        <option value="shipped"    <?= $s === 'shipped'    ? 'selected' : '' ?>>🚚 Đang vận chuyển</option>
                                        <option value="delivered"  <?= $s === 'delivered'  ? 'selected' : '' ?>>✅ Đã giao hàng</option>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-warning fw-bold w-100">
                                    <i class="fas fa-save me-2"></i> Cập nhật
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Danh sách sản phẩm -->
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-header bg-dark text-white">
                            <i class="fas fa-box me-2"></i> Sản phẩm trong đơn
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-secondary">
                                <tr>
                                    <th>Ảnh</th>
                                    <th>Tên sản phẩm</th>
                                    <th>Số lượng</th>
                                    <th>Giá mua</th>
                                    <th>Thành tiền</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach($orderDetails as $item): ?>
                                    <tr>
                                        <td>
                                            <img src="../assets/images/products/<?= $item['product_image'] ?>"
                                                 width="60" class="rounded shadow-sm">
                                        </td>
                                        <td class="fw-bold"><?= htmlspecialchars($item['product_name']) ?></td>
                                        <td><?= $item['quantity'] ?></td>
                                        <td><?= number_format($item['price_at_purchase']) ?>đ</td>
                                        <td class="text-danger fw-bold">
                                            <?= number_format($item['price_at_purchase'] * $item['quantity']) ?>đ
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
</body>
</html>