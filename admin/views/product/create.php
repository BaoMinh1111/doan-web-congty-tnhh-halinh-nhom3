<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title>Thêm sản phẩm - Hà Linh Admin</title>
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
        <!-- Sidebar -->
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
                    <a class="nav-link" href="index.php?controller=order">
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
                <h1 class="h2">Thêm sản phẩm mới</h1>
                <a href="index.php?controller=product&action=index" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left me-1"></i> Quay lại
                </a>
            </div>

            <div class="row">
                <div class="col-md-8">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-success text-white fw-bold">
                            <i class="fas fa-plus-circle me-2"></i> Thông tin sản phẩm
                        </div>
                        <div class="card-body p-4">
                            <form action="index.php?controller=product&action=store" method="POST" enctype="multipart/form-data">

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Tên linh kiện</label>
                                    <input type="text" name="name" class="form-control"
                                           placeholder="Ví dụ: VGA RTX 4090" required>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Giá bán (VNĐ)</label>
                                        <input type="number" name="price" class="form-control"
                                               placeholder="Ví dụ: 25000000" min="0" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Danh mục</label>
                                        <select name="category_id" class="form-select" required>
                                            <option value="">-- Chọn danh mục --</option>
                                            <?php foreach ($categories as $cat): ?>
                                                <option value="<?php echo $cat['id']; ?>">
                                                    <?php echo htmlspecialchars($cat['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">
                                        <i class="fas fa-boxes me-1"></i> Số lượng tồn kho
                                    </label>
                                    <input type="number" name="stock" class="form-control"
                                           placeholder="Nhập số lượng..." min="0" value="0" required>
                                    <div class="form-text">Số lượng sản phẩm hiện có trong kho.</div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Ảnh sản phẩm</label>
                                    <input type="file" name="image" class="form-control" accept="image/*">
                                    <div class="form-text">Để trống nếu chưa có ảnh.</div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Mô tả chi tiết</label>
                                    <textarea name="description" class="form-control" rows="4"
                                              placeholder="Mô tả thông số, tính năng sản phẩm..."></textarea>
                                </div>

                                <div class="d-flex justify-content-between">
                                    <a href="index.php?controller=product&action=index"
                                       class="btn btn-outline-secondary px-4">
                                        <i class="fas fa-arrow-left me-1"></i> Quay lại
                                    </a>
                                    <button type="submit" class="btn btn-success px-5 fw-bold">
                                        <i class="fas fa-save me-2"></i> Lưu linh kiện
                                    </button>
                                </div>

                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
</body>
</html>