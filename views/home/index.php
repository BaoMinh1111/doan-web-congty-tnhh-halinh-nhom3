<?php include 'views/layout/header.php'; ?>

<style>
    /* ===== SLIDE RESPONSIVE ===== */
    .slide-inner {
        height: 450px;
    }
    .slide-inner h1 {
        font-size: 2.5rem;
    }
    .slide-inner p {
        font-size: 1.1rem;
    }

    @media (max-width: 768px) {
        .slide-inner {
            height: auto;
            min-height: 300px;
            padding: 40px 16px;
        }
        .slide-inner h1 {
            font-size: 1.5rem !important;
        }
        .slide-inner p {
            font-size: 0.9rem !important;
        }
        .slide-inner .btn {
            font-size: 0.85rem;
            padding: 8px 20px;
        }

        /* Sản phẩm 2 cột trên mobile thay vì 1 */
        .col-md-3 {
            flex: 0 0 50%;
            max-width: 50%;
        }

        /* Popup mua ngay full width hơn */
        #buyNowOverlay > div {
            width: 90% !important;
            padding: 24px 16px !important;
        }

        /* Carousel slide */
        .carousel-item > div {
            min-height: 300px !important;
            height: auto !important;
            padding: 40px 16px !important;
        }

        /* Chữ tiêu đề nhỏ lại */
        .carousel-item h1.display-3 {
            font-size: 1.6rem !important;
        }

        .carousel-item .fs-4 {
            font-size: 1rem !important;
        }

        /* Icon nền to không bị tràn */
        .carousel-item .fa-microchip,
        .carousel-item .fa-truck-fast,
        .carousel-item .fa-star {
            font-size: 150px !important;
        }
    }

    @media (max-width: 400px) {
        .slide-inner h1 {
            font-size: 1.2rem !important;
        }
        .col-md-3 {
            flex: 0 0 100%;
            max-width: 100%;
        }
    }

    /* ===== FIX NÚT CAROUSEL TRÊN MOBILE ===== */
    .carousel-control-prev,
    .carousel-control-next {
        /* Tăng vùng chạm lên 15% (Bootstrap mặc định 10%) */
        width: 15%;
        /* Đảm bảo nút luôn nằm trên các element khác */
        z-index: 10;
        /* Tăng kích thước vùng chạm thực tế */
        padding: 0;
        opacity: 1;
    }

    /* Icon mũi tên to hơn và dễ bấm hơn */
    .carousel-control-prev-icon,
    .carousel-control-next-icon {
        width: 2.5rem;
        height: 2.5rem;
        background-color: rgba(0, 0, 0, 0.4);
        border-radius: 50%;
        background-size: 60%;
    }

    @media (max-width: 768px) {
        /* Vùng chạm tối thiểu 44x44px theo chuẩn mobile UX */
        .carousel-control-prev,
        .carousel-control-next {
            width: 20%;
            min-width: 44px;
        }

        .carousel-control-prev-icon,
        .carousel-control-next-icon {
            width: 2.8rem;
            height: 2.8rem;
        }
    }

    #newsCarousel {
        position: relative;
        overflow: visible !important;
    }

    /* Đảm bảo card không bị nút đè lên */
    #newsCarousel .carousel-inner {
        overflow: visible;
    }

    #newsCarousel .card {
        z-index: 2;
        position: relative;
    }

    #newsCarousel .carousel-control-prev,
    #newsCarousel .carousel-control-next {
        z-index: 3;
    }
</style>

<div class="container-fluid px-0 mb-5">
    <div id="banner304" class="carousel slide shadow-lg" data-bs-ride="carousel" data-bs-interval="4000">

        <!-- Chấm chỉ vị trí -->
        <div class="carousel-indicators">
            <button type="button" data-bs-target="#banner304" data-bs-slide-to="0" class="active"></button>
            <button type="button" data-bs-target="#banner304" data-bs-slide-to="1"></button>
            <button type="button" data-bs-target="#banner304" data-bs-slide-to="2"></button>
        </div>

        <div class="carousel-inner">

            <!-- Slide 1: Banner 30/4 hiện tại -->
            <div class="carousel-item active">
                <div class="d-flex align-items-center justify-content-center text-white"
                     style="min-height: 320px; padding: 60px 20px; background: linear-gradient(135deg, #C8102E 0%, #8b0000 100%); position: relative; overflow: hidden;">
                    <i class="fas fa-star text-warning opacity-25" style="position: absolute; top: -20px; left: -20px; font-size: 200px; transform: rotate(-15deg);"></i>
                    <div class="text-center" style="z-index: 2;">
                        <span class="badge bg-warning text-dark mb-3 px-3 py-2 fw-bold rounded-pill">ƯU ĐÃI ĐẠI LỄ 30/04 - 01/05</span>
                        <h1 class="display-3 fw-bold text-white mb-2">MỪNG ĐẠI LỄ 30/4</h1>
                        <p class="fs-4 text-warning fw-light">GIẢM GIÁ SẬP SÀN - LINH KIỆN CỰC CHẤT</p>
                        <a href="#products-list" class="btn btn-deal-flash mt-4">
                            <i class="fas fa-shopping-cart me-2"></i>SĂN DEAL NGAY
                        </a>
                    </div>
                </div>
            </div>

            <!-- Slide 2: Linh kiện chính hãng -->
            <div class="carousel-item">
                <div class="d-flex align-items-center justify-content-center text-white"
                     style="min-height: 320px; padding: 60px 20px; background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); position: relative; overflow: hidden;">
                    <i class="fas fa-microchip opacity-10" style="position: absolute; font-size: 300px; right: -50px; color: #e94560;"></i>
                    <div class="text-center" style="z-index: 2;">
                        <span class="badge bg-danger mb-3 px-3 py-2 fw-bold rounded-pill">CAM KẾT CHÍNH HÃNG</span>
                        <h1 class="display-3 fw-bold text-white mb-2">LINH KIỆN XỊN SÒ</h1>
                        <p class="fs-4 fw-light" style="color: #e8e3e4;">BẢO HÀNH 12 THÁNG – ĐỔI TRẢ 30 NGÀY</p>
                        <a href="#products-list" class="btn btn-danger mt-4 px-4 py-2 rounded-pill fw-bold">
                            <i class="fas fa-bolt me-2"></i>KHÁM PHÁ NGAY
                        </a>
                    </div>
                </div>
            </div>

            <!-- Slide 3: Miễn phí vận chuyển -->
            <div class="carousel-item">
                <div class="d-flex align-items-center justify-content-center text-white"
                     style="min-height: 320px; padding: 60px 20px; background: linear-gradient(135deg, #f5a623 0%, #e67e22 100%); position: relative; overflow: hidden;">
                    <i class="fas fa-truck-fast opacity-10" style="position: absolute; font-size: 300px; left: -50px; color: #fff;"></i>
                    <div class="text-center" style="z-index: 2;">
                        <span class="badge bg-dark mb-3 px-3 py-2 fw-bold rounded-pill">ƯU ĐÃI VẬN CHUYỂN</span>
                        <h1 class="display-3 fw-bold mb-2" style="color: #0e0e0e;">MIỄN PHÍ SHIP</h1>
                        <p class="fs-4 fw-light text-dark">ĐƠN HÀNG TỪ 300.000Đ TOÀN QUỐC</p>
                        <a href="#products-list" class="btn btn-dark mt-4 px-4 py-2 rounded-pill fw-bold">
                            <i class="fas fa-cart-shopping me-2"></i>MUA NGAY
                        </a>
                    </div>
                </div>
            </div>

        </div>

        <!-- Nút prev/next -->
        <button class="carousel-control-prev" type="button" data-bs-target="#banner304" data-bs-slide="prev">
            <span class="carousel-control-prev-icon"></span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#banner304" data-bs-slide="next">
            <span class="carousel-control-next-icon"></span>
        </button>

    </div>
</div>

<div class="container" id="products-list">
    <div class="d-flex align-items-center mb-4 border-bottom pb-3">
        <div class="p-2 bg-danger text-white rounded-3 me-3">
            <i class="fas fa-bolt"></i>
        </div>
        <h3 class="fw-bold text-dark mb-0">SẢN PHẨM MỚI NHẤT</h3>
    </div>

    <div class="row g-4 mb-5">
        <?php if(!empty($products)): ?>
            <?php foreach($products as $p): ?>
                <div class="col-md-3">
                    <div class="card h-100 card-product border-0 shadow-sm product-hover">
                        <div class="position-relative p-3 text-center">
                            <img src="assets/images/products/<?php echo $p['image']; ?>" class="card-img-top img-fluid" style="height: 200px; object-fit: contain;" alt="<?php echo $p['name']; ?>">
                            <?php if($p['stock'] == 0): ?>
                                <span class="badge bg-secondary position-absolute top-0 start-0 m-2">Hết hàng</span>
                            <?php elseif($p['stock'] < 10): ?>
                                <span class="badge bg-warning text-dark position-absolute top-0 start-0 m-2">Chỉ còn <?php echo $p['stock']; ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="card-body text-center d-flex flex-column">
                            <h6 class="card-title fw-bold text-dark text-truncate-2" style="height: 40px;"><?php echo $p['name']; ?></h6>
                            <p class="text-danger fw-bold fs-5 my-2"><?php echo number_format($p['price']); ?>đ</p>

                            <div class="mt-auto d-grid gap-2">
                                <a href="index.php?controller=product&action=detail&id=<?php echo $p['id']; ?>"
                                   class="btn btn-outline-dark btn-sm rounded-pill">Chi tiết</a>

                                <?php if($p['stock'] == 0): ?>
                                    <button class="btn btn-secondary btn-sm rounded-pill" disabled>
                                        <i class="fas fa-ban me-1"></i> Hết hàng
                                    </button>
                                    <button class="btn btn-secondary btn-sm rounded-pill fw-bold" disabled>
                                        <i class="fas fa-ban me-1"></i> Hết hàng
                                    </button>
                                <?php else: ?>
                                    <button onclick="addToCart(<?php echo $p['id']; ?>)"
                                            class="btn btn-danger btn-sm rounded-pill shadow-sm">
                                        <i class="fas fa-cart-plus me-1"></i> Thêm vào giỏ
                                    </button>
                                    <button onclick="openBuyNowPopup(<?php echo $p['id']; ?>, '<?php echo htmlspecialchars($p['name'], ENT_QUOTES); ?>', '<?php echo number_format($p['price']); ?>')"
                                            class="btn btn-warning btn-sm rounded-pill shadow-sm fw-bold">
                                        <i class="fas fa-bolt me-1"></i> Mua ngay
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <p class="text-muted italic">Đang cập nhật sản phẩm mới cho bạn...</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- ===== PHÂN TRANG ===== -->
    <?php if ($totalPages > 1): ?>
        <div class="d-flex justify-content-center align-items-center gap-2 my-4">

            <?php if ($currentPage > 1): ?>
                <a href="?page=<?= $currentPage - 1 ?>" class="btn btn-outline-danger rounded-pill px-3">
                    <i class="fas fa-chevron-left"></i>
                </a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?= $i ?>"
                   class="btn rounded-pill px-3 <?= $i === $currentPage ? 'btn-danger' : 'btn-outline-secondary' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>

            <?php if ($currentPage < $totalPages): ?>
                <a href="?page=<?= $currentPage + 1 ?>" class="btn btn-outline-danger rounded-pill px-3">
                    <i class="fas fa-chevron-right"></i>
                </a>
            <?php endif; ?>

        </div>
    <?php endif; ?>

    <section class="py-5" style="background: #fff5f5; position: relative; overflow: hidden; border-radius: 30px; margin: 20px 0;">

        <div style="position: absolute; bottom: -50px; right: -50px; font-size: 300px; color: #ffcd00; opacity: 0.1; transform: rotate(15deg); z-index: 0;">
            <i class="fas fa-star"></i>
        </div>

        <div class="container" style="position: relative; z-index: 1;">
            <h2 class="text-center mb-5 fw-bold" style="color: #c8102e;">
                <i class="far fa-newspaper me-2"></i>TIN TỨC & CÔNG NGHỆ
            </h2>

            <div id="newsCarousel" class="carousel slide" data-bs-ride="carousel">
                <div class="carousel-inner">
                    <?php
                    $chunks = array_chunk($posts, 3);
                    foreach($chunks as $index => $postGroup):
                        ?>
                        <div class="carousel-item <?php echo ($index === 0) ? 'active' : ''; ?>">
                            <div class="row">
                                <?php foreach($postGroup as $post): ?>
                                    <div class="col-md-4 mb-3">
                                        <div class="card h-100 border-0 shadow-sm" style="border-radius: 20px; transition: 0.3s; box-shadow: 0 10px 20px rgba(200, 16, 46, 0.05) !important;">
                                            <img src="assets/images/posts/<?php echo $post['image']; ?>" class="card-img-top" style="height:180px; object-fit:cover; border-radius: 20px 20px 0 0;" onerror="this.src='assets/images/default.jpg'">
                                            <div class="card-body">
                                                <h6 class="fw-bold" style="color: #2d3436;"><?php echo $post['title']; ?></h6>
                                                <p class="small text-muted"><?php echo substr(strip_tags($post['content']), 0, 80); ?>...</p>
                                                <a href="index.php?controller=post&action=detail&id=<?= $post['id'] ?>"
                                                   class="text-danger fw-bold small text-decoration-none">
                                                    Đọc thêm →
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <button class="carousel-control-prev" type="button" data-bs-target="#newsCarousel" data-bs-slide="prev"
                        style="width: 44px; height: 44px; top: 50%; transform: translateY(-50%);
               left: -22px; border-radius: 50%; background: #c8102e;
               opacity: 1; position: absolute;">
                    <span class="carousel-control-prev-icon" style="width: 20px; height: 20px;"></span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#newsCarousel" data-bs-slide="next"
                        style="width: 44px; height: 44px; top: 50%; transform: translateY(-50%);
               right: -22px; border-radius: 50%; background: #c8102e;
               opacity: 1; position: absolute;">
                    <span class="carousel-control-next-icon" style="width: 20px; height: 20px;"></span>
                </button>
            </div>
        </div>

    </section>
</div>

<!-- POPUP MUA NGAY -->
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
