<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title>Quản lý danh mục</title>
    <style>
        .sidebar { min-height: 100vh; background: #212529; color: white; }
        .sidebar .nav-link { color: #adb5bd; transition: 0.3s; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { color: #ffc107; background: rgba(255,255,255,0.1); }
        .main-content { background: #f8f9fa; min-height: 100vh; }
        .product-card {
            transition: transform 0.3s, box-shadow 0.3s;
            border: none;
            border-radius: 12px;
        }
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .price-tag { color: #e44d26; font-weight: bold; }
        .empty-state { opacity: 0.5; margin-top: 100px; }
        .category-sidebar {
            background: white;
            border-radius: 12px;
            border: 1px solid #dee2e6;
        }
        .category-item {
            transition: 0.2s;
            border-left: 4px solid transparent;
        }
        .category-item:hover, .category-item.active {
            background: #f0f4ff;
            border-left: 4px solid #0d6efd;
            color: #0d6efd;
        }
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
                    <a class="nav-link active" href="index.php?controller=category">
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
                <h1 class="h2">Quản lý danh mục</h1>
                <a href="index.php?controller=category&action=create" class="btn btn-success btn-sm">
                    <i class="fas fa-plus me-1"></i> Thêm danh mục
                </a>
            </div>

            <div class="row g-4">
                <!-- Danh sách danh mục -->
                <div class="col-md-3">
                    <div class="category-sidebar p-3 shadow-sm">
                        <h6 class="fw-bold text-muted mb-3">
                            <i class="fas fa-tags me-2 text-primary"></i>Danh mục
                        </h6>
                        <div class="list-group list-group-flush">
                            <?php foreach($categories as $cat): ?>
                                <a href="index.php?controller=category&action=index&cat_id=<?php echo $cat['id']; ?>"
                                   class="list-group-item list-group-item-action category-item py-2 px-2 border-0 <?php echo (isset($_GET['cat_id']) && $_GET['cat_id'] == $cat['id']) ? 'active' : ''; ?>">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span><i class="fas fa-folder me-2 text-warning"></i><?php echo htmlspecialchars($cat['name']); ?></span>
                                        <form action="index.php?controller=category&action=delete&id=<?php echo $cat['id']; ?>" method="POST" class="d-inline">
                                            <button type="submit" class="btn btn-link btn-sm text-danger p-0"
                                                    onclick="return confirm('Xóa danh mục này?')">
                                                <i class="fas fa-times-circle"></i>
                                            </button>
                                        </form>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Sản phẩm theo danh mục -->
                <div class="col-md-9">
                    <?php if(isset($products_by_cat) && count($products_by_cat) > 0): ?>
                        <h5 class="mb-3 fw-bold">
                            Sản phẩm: <span class="text-primary"><?php echo htmlspecialchars($current_cat_name); ?></span>
                        </h5>
                        <div class="row row-cols-1 row-cols-md-3 g-4">
                            <?php foreach($products_by_cat as $p): ?>
                                <div class="col">
                                    <div class="card h-100 product-card shadow-sm">
                                        <img src="../assets/images/products/<?php echo $p['image']; ?>"
                                             class="card-img-top p-3" style="height: 180px; object-fit: contain;">
                                        <div class="card-body">
                                            <h6 class="card-title fw-bold text-truncate"><?php echo htmlspecialchars($p['name']); ?></h6>
                                            <p class="price-tag mb-3"><?php echo number_format($p['price']); ?> VNĐ</p>
                                            <div class="d-flex justify-content-between">
                                                <a href="index.php?controller=product&action=edit&id=<?php echo $p['id']; ?>&cat_id=<?php echo $_GET['cat_id']; ?>"
                                                   class="btn btn-sm btn-outline-warning">
                                                    <i class="fas fa-edit me-1"></i>Sửa
                                                </a>
                                                <span class="badge bg-light text-dark border align-self-center">
                                                    Tồn kho: <?php echo rand(5, 20); ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center empty-state">
                            <i class="fas fa-box-open fa-5x mb-3 text-muted"></i>
                            <h5><?php echo isset($_GET['cat_id']) ? "Danh mục này chưa có linh kiện nào." : "Chọn một danh mục bên trái để xem sản phẩm."; ?></h5>
                            <p class="text-muted">Hãy thêm sản phẩm mới vào danh mục này để quản lý.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>
</body>
</html>