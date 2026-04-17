<?php require_once __DIR__ . '/../layout/header.php'; ?>
    <div class="container mt-5 mb-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-primary text-white py-3">
                        <h5 class="mb-0"><i class="fas fa-user-edit me-2"></i>CHỈNH SỬA HỒ SƠ</h5>
                    </div>
                    <div class="card-body p-4">
                        <form action="index.php?controller=user&action=update" method="POST">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Tên đăng nhập (Không thể sửa)</label>
                                <input type="text" class="form-control bg-light" value="<?php echo $user['username']; ?>" readonly>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Họ và tên</label>
                                <input type="text" name="fullname" class="form-control" value="<?php echo htmlspecialchars($user['fullname'] ?? ''); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Email</label>
                                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Số điện thoại</label>
                                <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Địa chỉ giao hàng</label>
                                <textarea name="address" class="form-control" rows="3"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                            </div>

                            <div class="d-flex justify-content-between mt-4">
                                <a href="index.php?controller=user&action=profile" class="btn btn-outline-secondary px-4">Hủy bỏ</a>
                                <button type="submit" class="btn btn-success px-5 shadow-sm">Lưu thay đổi</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php require_once __DIR__ . '/../layout/footer.php'; ?>