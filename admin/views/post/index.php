<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title>Quản lý bài viết - Hà Linh Admin</title>
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
                    <a class="nav-link active" href="index.php?controller=post">
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

        <!-- Main Content -->
        <main class="col-md-10 ms-sm-auto px-md-4 main-content">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Quản lý bài viết</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="index.php?controller=post&action=create" class="btn btn-sm btn-success">
                        <i class="fas fa-plus"></i> Viết bài mới
                    </a>
                </div>
            </div>

            <div class="table-responsive bg-white p-3 shadow-sm rounded">
                <table class="table table-hover align-middle">
                    <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Ảnh</th>
                        <th>Tiêu đề bài viết</th>
                        <th>Ngày đăng</th>
                        <th>Thao tác</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach($posts as $post): ?>
                        <tr>
                            <td>#<?php echo $post['id']; ?></td>
                            <td>
                                <img src="../assets/images/posts/<?php echo $post['image']; ?>"
                                     width="80" height="55"
                                     class="rounded shadow-sm"
                                     style="object-fit: cover;">
                            </td>
                            <td class="fw-bold"><?php echo $post['title']; ?></td>
                            <td class="text-muted">
                                <i class="far fa-clock me-1"></i>
                                <?php echo date('d/m/Y', strtotime($post['created_at'])); ?>
                            </td>
                            <td>
                                <a href="index.php?controller=post&action=edit&id=<?php echo $post['id']; ?>"
                                   class="btn btn-warning btn-sm shadow-sm me-1">
                                    <i class="fas fa-edit"></i> Sửa
                                </a>
                                <a href="index.php?controller=post&action=delete&id=<?php echo $post['id']; ?>"
                                   class="btn btn-danger btn-sm shadow-sm"
                                   onclick="return confirm('Bạn có chắc muốn xóa bài viết này?')">
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