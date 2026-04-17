<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title>Hà Linh Tech - Quản trị hệ thống</title>
    <style>
        .sidebar { min-height: 100vh; background: #212529; color: white; }
        .sidebar .nav-link { color: #adb5bd; transition: 0.3s; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { color: #ffc107; background: rgba(255,255,255,0.1); }
        .main-content { background: #f8f9fa; min-height: 100vh; }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-2 d-none d-md-block sidebar shadow-sm p-3">
            <h4 class="text-warning text-center mb-4">HÀ LINH ADMIN</h4>
            <ul class="nav flex-column">
                <li class="nav-item mb-2">
                    <a class="nav-link active" href="index.php?controller=product">
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
                    <a class="nav-link" href="index.php?controller=order&action=index">
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

        <main class="col-md-10 ms-sm-auto px-md-4 main-content">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Quản lý sản phẩm</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="index.php?controller=product&action=create" class="btn btn-sm btn-success">
                        <i class="fas fa-plus"></i> Thêm sản phẩm mới
                    </a>
                </div>
            </div>

            <div class="table-responsive bg-white p-3 shadow-sm rounded">
                <table class="table table-hover align-middle">
                    <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Ảnh</th>
                        <th>Tên sản phẩm</th>
                        <th>Giá</th>
                        <th>Tồn kho</th>  <!-- ← THÊM -->
                        <th>Thao tác</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach($products as $p): ?>
                        <tr>
                            <td>#<?php echo $p['id']; ?></td>
                            <td><img src="../assets/images/products/<?php echo $p['image']; ?>" width="60" class="rounded shadow-sm"></td>
                            <td class="fw-bold"><?php echo $p['name']; ?></td>
                            <td class="text-danger fw-bold"><?php echo number_format($p['price']); ?>đ</td>

                            <!-- ← THÊM CỘT NÀY -->
                            <td>
                                <?php $stock = (int)($p['stock'] ?? 0); ?>

                                <!-- Badge cảnh báo -->
                                <?php if ($stock === 0): ?>
                                    <span class="badge bg-danger mb-1">Hết hàng</span>
                                <?php elseif ($stock < 10): ?>
                                    <span class="badge bg-warning text-dark mb-1">Sắp hết (<?= $stock ?>)</span>
                                <?php else: ?>
                                    <span class="badge bg-success mb-1">Còn <?= $stock ?></span>
                                <?php endif; ?>

                                <!-- Form cập nhật nhanh -->
                                <form action="index.php?controller=product&action=updateStock" method="POST"
                                      class="d-flex gap-1 mt-1" style="min-width:130px;">
                                    <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                    <input type="number" name="stock" value="<?= $stock ?>"
                                           min="0" class="form-control form-control-sm"
                                           style="width:70px;">
                                    <button type="submit" class="btn btn-sm btn-primary" title="Cập nhật">
                                        <i class="fas fa-save"></i>
                                    </button>
                                </form>
                            </td>
                            <!-- ← HẾT CỘT THÊM -->

                            <td>
                                <a href="index.php?controller=product&action=edit&id=<?php echo $p['id']; ?>" class="btn btn-warning btn-sm shadow-sm">
                                    <i class="fas fa-edit"></i> Sửa
                                </a>
                                <a href="index.php?controller=product&action=delete&id=<?php echo $p['id']; ?>"
                                   class="btn btn-danger btn-sm shadow-sm" onclick="return confirm('Bạn có chắc muốn xóa?')">
                                    <i class="fas fa-trash"></i> Xóa
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