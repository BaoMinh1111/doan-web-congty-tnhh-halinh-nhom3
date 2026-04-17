<?php include 'views/layout/header.php'; ?>

<div class="container mt-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Trang chủ</a></li>
            <li class="breadcrumb-item active">Kết quả tìm kiếm</li>
        </ol>
    </nav>

    <h2 class="text-primary mb-4 border-bottom pb-2">
        Kết quả cho: "<span class="text-dark"><?php echo htmlspecialchars($keyword); ?></span>"
    </h2>

    <div class="row g-4 mb-5">
        <?php if (!empty($products)): ?>
            <?php foreach ($products as $p): ?>
                <div class="col-md-3">
                    <div class="card h-100 card-product border-0 shadow-sm product-hover">
                        <div class="position-relative p-3 text-center">
                            <img src="assets/images/products/<?php echo $p['image']; ?>"
                                 class="card-img-top img-fluid"
                                 style="height: 200px; object-fit: contain;"
                                 alt="<?php echo $p['name']; ?>">
                            <?php if (!empty($p['stock']) && $p['stock'] < 10 && $p['stock'] > 0): ?>
                                <span class="badge bg-warning text-dark position-absolute top-0 start-0 m-2">Chỉ còn <?php echo $p['stock']; ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="card-body text-center d-flex flex-column">
                            <h6 class="card-title fw-bold text-dark text-truncate-2" style="height: 40px;"><?php echo $p['name']; ?></h6>
                            <p class="text-danger fw-bold fs-5 my-2"><?php echo number_format($p['price']); ?>đ</p>

                            <div class="mt-auto d-grid gap-2">
                                <a href="index.php?controller=product&action=detail&id=<?php echo $p['id']; ?>"
                                   class="btn btn-outline-dark btn-sm rounded-pill">Chi tiết</a>
                                <button onclick="addToCart(<?php echo $p['id']; ?>)"
                                        class="btn btn-danger btn-sm rounded-pill shadow-sm">
                                    <i class="fas fa-cart-plus me-1"></i> Thêm vào giỏ
                                </button>
                                <button onclick="openBuyNowPopup(<?php echo $p['id']; ?>, '<?php echo htmlspecialchars($p['name'], ENT_QUOTES); ?>', '<?php echo number_format($p['price']); ?>')"
                                        class="btn btn-warning btn-sm rounded-pill shadow-sm fw-bold">
                                    <i class="fas fa-bolt me-1"></i> Mua ngay
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <i class="fas fa-search-minus fa-4x text-muted mb-3"></i>
                <p class="fs-5 text-muted">Rất tiếc, Hà Linh Tech không tìm thấy linh kiện nào khớp với "<?php echo htmlspecialchars($keyword); ?>".</p>
                <a href="index.php" class="btn btn-primary mt-2">Quay lại trang chủ</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- POPUP MUA NGAY (copy từ trang chủ) -->
<div id="buyNowOverlay" onclick="if(event.target===this)closeBuyNowPopup()"
     style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:16px; padding:32px 28px; width:380px; text-align:center; box-shadow:0 8px 32px rgba(0,0,0,0.2);">
        <div style="font-size:48px; margin-bottom:12px;">🛍️</div>
        <h5 class="fw-bold mb-1">Xác nhận mua hàng</h5>
        <p class="text-muted small mb-3">Bạn muốn mua sản phẩm:</p>
        <p id="popupProductName" class="fw-bold fs-6 mb-1"></p>
        <p id="popupProductPrice" class="text-danger fw-bold fs-5 mb-4"></p>

        <div class="d-flex gap-2 justify-content-center">
            <button onclick="closeBuyNowPopup()" class="btn btn-outline-secondary rounded-pill px-4">
                Hủy
            </button>
            <a id="popupConfirmBtn" href="#" class="btn btn-danger rounded-pill px-4 fw-bold">
                <i class="fas fa-bolt me-1"></i> Xác nhận mua
            </a>
        </div>
    </div>
</div>

<script>
    function openBuyNowPopup(productId, productName, productPrice) {
        document.getElementById('popupProductName').textContent = productName;
        document.getElementById('popupProductPrice').textContent = productPrice + 'đ';
        document.getElementById('popupConfirmBtn').href =
            'index.php?controller=cart&action=buyNow&id=' + productId;
        document.getElementById('buyNowOverlay').style.display = 'flex';
    }

    function closeBuyNowPopup() {
        document.getElementById('buyNowOverlay').style.display = 'none';
    }
</script>

<?php include 'views/layout/footer.php'; ?>