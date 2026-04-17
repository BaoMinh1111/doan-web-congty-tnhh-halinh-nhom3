<?php require_once __DIR__ . '/../layout/header.php'; ?>
    <div class="container mt-5 mb-5">
        <div class="row justify-content-center">
            <div class="col-md-4">
                <div class="card shadow-sm border-0 text-center p-4">
                    <div class="mb-3">
                        <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center shadow" style="width: 100px; height: 100px;">
                            <i class="fas fa-user fa-4x"></i>
                        </div>
                    </div>
                    <h4 class="fw-bold mb-1"><?php echo htmlspecialchars($user['fullname'] ?? $user['username']); ?></h4>
                    <p class="text-muted small mb-3">Thành viên từ: 2026</p>
                    <div class="list-group list-group-flush text-start">
                        <a href="#" class="list-group-item list-group-item-action border-0 active rounded mb-1">
                            <i class="fas fa-user-circle me-2"></i> Thông tin tài khoản
                        </a>
                        <a href="index.php?controller=order&action=history" class="list-group-item list-group-item-action border-0 rounded mb-1">
                            <i class="fas fa-shopping-bag me-2"></i> Đơn hàng của tôi
                        </a>
                        <a href="index.php?controller=auth&action=logout" class="list-group-item list-group-item-action border-0 rounded text-danger">
                            <i class="fas fa-sign-out-alt me-2"></i> Đăng xuất
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card shadow-sm border-0 p-4">
                    <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-4">
                        <h4 class="mb-0 fw-bold text-primary">CHI TIẾT HỒ SƠ</h4>
                        <a href="index.php?controller=user&action=edit" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                            <i class="fas fa-edit me-1"></i> Chỉnh sửa
                        </a>
                    </div>

                    <div class="row mb-4">
                        <div class="col-sm-4 text-muted">Tên đăng nhập</div>
                        <div class="col-sm-8 fw-bold text-dark"><?php echo htmlspecialchars($user['username']); ?></div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-sm-4 text-muted">Họ và tên</div>
                        <div class="col-sm-8 fw-bold text-dark"><?php echo htmlspecialchars($user['fullname'] ?? 'Chưa cập nhật'); ?></div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-sm-4 text-muted">Địa chỉ Email</div>
                        <div class="col-sm-8 fw-bold text-dark"><?php echo htmlspecialchars($user['email']); ?></div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-sm-4 text-muted">Số điện thoại</div>
                        <div class="col-sm-8 fw-bold text-dark"><?php echo htmlspecialchars($user['phone'] ?? 'Chưa cập nhật'); ?></div>
                    </div>

                    <div class="row mb-0">
                        <div class="col-sm-4 text-muted">Địa chỉ giao hàng</div>
                        <div class="col-sm-8 fw-bold text-dark"><?php echo htmlspecialchars($user['address'] ?? 'Chưa cập nhật'); ?></div>
                    </div>
                </div>

                <div class="text-center mt-4">
                    <a href="index.php" class="text-decoration-none text-muted">
                        <i class="fas fa-chevron-left me-1"></i> Quay lại trang chủ
                    </a>
                </div>
            </div>
        </div>
    </div>
<?php require_once __DIR__ . '/../layout/footer.php'; ?>