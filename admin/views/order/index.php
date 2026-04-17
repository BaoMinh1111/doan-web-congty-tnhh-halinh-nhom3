<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title>Quản lý đơn hàng</title>
    <style>
        .sidebar { min-height: 100vh; background: #212529; color: white; }
        .sidebar .nav-link { color: #adb5bd; transition: 0.3s; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { color: #ffc107; background: rgba(255,255,255,0.1); }
        .main-content { background: #f8f9fa; min-height: 100vh; }
        .badge-pending   { background: #ffc107; color: #000; }
        .badge-confirmed { background: #0d6efd; color: #fff; }
        .badge-shipped   { background: #6f42c1; color: #fff; }
        .badge-completed { background: #198754; color: #fff; }
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
                <h1 class="h2">Quản lý đơn hàng</h1>
            </div>

            <?php
            // Lọc theo tab
            $filterStatus = $_GET['status'] ?? 'all';
            $tabs = [
                    'all'        => ['label' => 'Tất cả',          'class' => 'secondary'],
                    'pending'    => ['label' => 'Chờ xử lý',       'class' => 'warning'],
                    'processing' => ['label' => 'Đang xử lý',      'class' => 'primary'],
                    'shipped'    => ['label' => 'Đang vận chuyển', 'class' => 'purple'],
                    'delivered'  => ['label' => 'Đã giao hàng',    'class' => 'success'],
                    'cancelled'  => ['label' => 'Đã hủy',          'class' => 'danger'],
            ];

            // Đếm số đơn theo từng trạng thái
            $countByStatus = array_fill_keys(array_keys($tabs), 0);
            $countByStatus['all'] = count($orders);
            foreach ($orders as $o) {
                $s = $o['status'] ?? 'pending';
                if (isset($countByStatus[$s])) $countByStatus[$s]++;
            }
            ?>

            <!-- Tab lọc -->
            <ul class="nav nav-tabs mb-3">
                <?php foreach ($tabs as $key => $tab): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $filterStatus === $key ? 'active fw-bold' : '' ?>"
                           href="?controller=order&status=<?= $key ?>">
                            <?= $tab['label'] ?>
                            <span class="badge bg-<?= $tab['class'] === 'purple' ? 'secondary' : $tab['class'] ?> ms-1">
                        <?= $countByStatus[$key] ?>
                    </span>
                            <?php if ($key === 'cancelled' && $countByStatus['cancelled'] > 0): ?>
                                <span class="badge bg-danger ms-1 animate__animated animate__pulse">!</span>
                            <?php endif; ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>

            <div class="table-responsive bg-white p-3 shadow-sm rounded">
                <table class="table table-hover align-middle">
                    <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Khách hàng</th>
                        <th>Email</th>
                        <th>Tổng tiền</th>
                        <th>Trạng thái</th>
                        <th>Ngày đặt</th>
                        <th>Thao tác</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $statusLabels = [
                            'pending'    => ['text' => 'Chờ xử lý',       'badge' => 'warning text-dark'],
                            'processing' => ['text' => 'Đang xử lý',      'badge' => 'primary'],
                            'shipped'    => ['text' => 'Đang vận chuyển', 'badge' => 'info text-dark'],
                            'delivered'  => ['text' => 'Đã giao hàng',    'badge' => 'success'],
                            'cancelled'  => ['text' => 'Đã hủy',          'badge' => 'danger'],
                    ];

                    $filteredOrders = $filterStatus === 'all'
                            ? $orders
                            : array_filter($orders, fn($o) => ($o['status'] ?? '') === $filterStatus);

                    foreach ($filteredOrders as $o):
                        $isCancelled = ($o['status'] === 'cancelled');
                        ?>
                        <tr class="<?= $isCancelled ? 'table-danger' : '' ?>">
                            <td>#<?= $o['id'] ?></td>
                            <td><?= htmlspecialchars($o['fullname'] ?? $o['customer_name']) ?></td>
                            <td><?= htmlspecialchars($o['email']) ?></td>
                            <td class="fw-bold <?= $isCancelled ? 'text-muted text-decoration-line-through' : 'text-danger' ?>">
                                <?= number_format($o['total_price']) ?>đ
                            </td>
                            <td>
                                <?php if ($isCancelled): ?>
                                    <!-- Đơn đã hủy: chỉ hiển thị badge, không cho đổi trạng thái -->
                                    <span class="badge bg-danger rounded-pill px-3 py-2">
                                <i class="fas fa-times-circle me-1"></i>Đã hủy
                            </span>
                                <?php else: ?>
                                    <form method="POST" action="index.php?controller=order&action=updateStatus">
                                        <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                        <select name="status" onchange="this.form.submit()"
                                                class="form-select form-select-sm" style="min-width:150px">
                                            <?php
                                            $statuses = [
                                                    'pending'    => 'Chờ xử lý',
                                                    'processing' => 'Đang xử lý',
                                                    'shipped'    => 'Đang vận chuyển',
                                                    'delivered'  => 'Đã giao hàng',
                                            ];
                                            $current = $o['status'] ?? 'pending';
                                            foreach ($statuses as $val => $label): ?>
                                                <option value="<?= $val ?>" <?= $current === $val ? 'selected' : '' ?>>
                                                    <?= $label ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </form>
                                <?php endif; ?>
                            </td>
                            <td><?= $o['created_at'] ?></td>
                            <td>
                                <a href="index.php?controller=order&action=detail&id=<?= $o['id'] ?>"
                                   class="btn btn-<?= $isCancelled ? 'secondary' : 'primary' ?> btn-sm">
                                    <i class="fas fa-eye"></i> Xem
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>
</body>
</html>