<?php include 'views/layout/header.php'; ?>

<div class="container mt-5 mb-5">
    <div class="row justify-content-center">
        <div class="col-md-8">

            <!-- Nút quay lại -->
            <a href="index.php" class="btn btn-outline-secondary btn-sm mb-4">
                <i class="fas fa-arrow-left me-1"></i> Quay lại trang chủ
            </a>

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <!-- Ảnh bài viết -->
                <?php if (!empty($post['image'])): ?>
                    <img src="assets/images/posts/<?= htmlspecialchars($post['image']) ?>"
                         class="card-img-top"
                         style="max-height: 400px; object-fit: cover;"
                         onerror="this.src='assets/images/default.jpg'">
                <?php endif; ?>

                <div class="card-body p-4">
                    <!-- Tiêu đề -->
                    <h2 class="fw-bold mb-2" style="color: #2d3436;">
                        <?= htmlspecialchars($post['title']) ?>
                    </h2>

                    <!-- Ngày đăng -->
                    <p class="text-muted small mb-4">
                        <i class="fas fa-clock me-1"></i>
                        <?= date('d/m/Y H:i', strtotime($post['created_at'])) ?>
                    </p>

                    <hr>

                    <!-- Nội dung -->
                    <div class="mt-3" style="line-height: 1.8; font-size: 1.05rem;">
                        <?= nl2br(htmlspecialchars($post['content'])) ?>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php include 'views/layout/footer.php'; ?>
