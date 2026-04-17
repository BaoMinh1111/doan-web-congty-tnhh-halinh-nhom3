<?php include 'views/layout/header.php'; ?>

<?php
$specs = json_decode($product['specifications'] ?? '{}', true);
$stock = (int)($product['stock'] ?? 0);
?>

<?php if (!empty($specs)): ?>
    <div class="product-specs mt-4">
        <h5>Thông số kỹ thuật</h5>
        <table class="table table-bordered">
            <?php foreach ($specs as $key => $value): ?>
                <tr>
                    <td width="40%" class="text-muted"><?= htmlspecialchars($key) ?></td>
                    <td><strong><?= htmlspecialchars($value) ?></strong></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
<?php endif; ?>

<div class="container mt-5 mb-5">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb bg-transparent p-0">
            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none text-muted">Trang chủ</a></li>
            <li class="breadcrumb-item active text-danger fw-bold" aria-current="page">
                <?php echo htmlspecialchars($product['name'] ?? 'Sản phẩm'); ?>
            </li>
        </ol>
    </nav>

    <div class="row g-5">
        <!-- ==================== PHẦN HÌNH ẢNH ==================== -->
        <div class="col-md-6">
            <div class="product-image-card p-4 bg-white rounded-4 shadow-sm border d-flex align-items-center justify-content-center"
                 style="min-height: 450px;">
                <?php if (!empty($product['image'])): ?>
                    <img src="/assets/images/products/<?php echo htmlspecialchars($product['image']); ?>"
                         class="img-fluid animate__animated animate__zoomIn"
                         alt="<?php echo htmlspecialchars($product['name']); ?>"
                         style="max-height: 400px; object-fit: contain;">
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-image fa-7x text-muted opacity-25"></i>
                        <p class="mt-3 text-muted">Không có hình ảnh sản phẩm</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ==================== PHẦN THÔNG TIN ==================== -->
        <div class="col-md-6">
            <div class="ps-md-3">
                <span class="badge bg-danger px-3 py-2 mb-3 rounded-pill">Mã SP: #<?php echo $product['id']; ?></span>
                <h1 class="fw-bold text-dark display-6 mb-3"><?php echo htmlspecialchars($product['name']); ?></h1>

                <!-- GIÁ + TRẠNG THÁI KHO -->
                <div class="price-container p-4 bg-light rounded-4 mb-4 border-start border-4 border-danger">
                    <div class="d-flex align-items-baseline">
                        <h2 class="text-danger fw-bold mb-0 display-5"><?php echo number_format($product['price'] ?? 0); ?></h2>
                        <span class="ms-2 fw-bold text-danger fs-4">đ</span>
                    </div>
                    <hr class="my-3 opacity-10">

                    <!-- BADGE TRẠNG THÁI KHO -->
                    <div class="d-flex gap-4 align-items-center">
                        <?php if ($stock === 0): ?>
                            <small class="text-danger fw-bold">
                                <i class="fas fa-times-circle me-1"></i> Hết hàng
                            </small>
                        <?php elseif ($stock < 10): ?>
                            <small class="text-warning fw-bold">
                                <i class="fas fa-exclamation-triangle me-1"></i> Chỉ còn <?= $stock ?> sản phẩm!
                            </small>
                        <?php else: ?>
                            <small class="text-success fw-bold">
                                <i class="fas fa-box me-1"></i> Còn hàng (<?= $stock ?> chiếc)
                            </small>
                        <?php endif; ?>
                        <small class="text-muted"><i class="fas fa-shield-alt me-1"></i> Bảo hành 24 tháng</small>
                    </div>
                </div>

                <!-- ALERT KHI HẾT HÀNG HOẶC SẮP HẾT -->
                <?php if ($stock === 0): ?>
                    <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
                        <i class="fas fa-times-circle me-2 fs-5"></i>
                        <div><strong>Sản phẩm đã hết hàng!</strong> Vui lòng quay lại sau.</div>
                    </div>
                <?php elseif ($stock < 10): ?>
                    <div class="alert alert-warning d-flex align-items-center mb-4" role="alert">
                        <i class="fas fa-exclamation-triangle me-2 fs-5"></i>
                        <div><strong>Chỉ còn <?= $stock ?> sản phẩm!</strong> Mua ngay kẻo hết!</div>
                    </div>
                <?php endif; ?>

                <!-- MÔ TẢ -->
                <div class="specs-box mb-4">
                    <h5 class="fw-bold mb-3 d-flex align-items-center text-primary">
                        <i class="fas fa-info-circle me-2"></i> Thông tin sản phẩm
                    </h5>
                    <div class="p-3 bg-white border rounded-3 shadow-xs">
                        <p class="text-secondary mb-0 leading-relaxed">
                            <?php echo nl2br(htmlspecialchars($product['description'] ?? 'Sản phẩm chính hãng chất lượng cao tại Hà Linh Tech.')); ?>
                        </p>
                    </div>
                </div>

                <!-- FORM THÊM VÀO GIỎ -->
                <form action="index.php?controller=cart&action=add" method="POST">
                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">

                    <?php if ($stock > 0): ?>
                        <div class="row g-3 align-items-center mb-4">
                            <div class="col-auto">
                                <label class="small fw-bold text-muted d-block mb-2">Số lượng:</label>
                                <div class="input-group border rounded-pill overflow-hidden" style="width: 130px;">
                                    <button class="btn btn-light border-0 px-3" type="button" onclick="decreaseQuantity()">-</button>
                                    <input type="number" name="quantity" id="quantityInput"
                                           value="1" min="1" max="<?= $stock ?>"
                                           class="form-control border-0 text-center fw-bold bg-transparent shadow-none">
                                    <button class="btn btn-light border-0 px-3" type="button" onclick="increaseQuantity()">+</button>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="d-grid gap-2">
                        <?php if ($stock === 0): ?>
                            <button type="button" class="btn btn-secondary btn-lg fw-bold py-3 rounded-pill" disabled>
                                <i class="fas fa-times me-2"></i> HẾT HÀNG
                            </button>
                        <?php else: ?>
                            <button type="submit" class="btn btn-danger btn-lg fw-bold py-3 rounded-pill shadow hover-lift">
                                <i class="fas fa-shopping-cart me-2"></i> THÊM VÀO GIỎ HÀNG
                            </button>
                        <?php endif; ?>
                    </div>
                </form>

                <div class="mt-4 p-3 bg-warning bg-opacity-10 border border-warning border-dashed rounded-4 d-flex align-items-center justify-content-center">
                    <div class="text-center">
                        <span class="d-block fw-bold text-dark text-uppercase small mb-1">🎁 Quà tặng Đại lễ 30/04</span>
                        <span class="text-muted small">Tặng kèm lót chuột và bộ vệ sinh máy tính cao cấp.</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'views/layout/footer.php'; ?>

<script>
    function increaseQuantity() {
        let input = document.getElementById('quantityInput');
        let max = parseInt(input.getAttribute('max'));
        let newValue = parseInt(input.value) + 1;
        if (newValue <= max) {
            input.value = newValue;
        } else {
            alert('Số lượng không được vượt quá ' + max);
        }
    }

    function decreaseQuantity() {
        let input = document.getElementById('quantityInput');
        let min = parseInt(input.getAttribute('min'));
        let newValue = parseInt(input.value) - 1;
        if (newValue >= min) {
            input.value = newValue;
        }
    }
</script>