<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' — HàLinhTech' : 'HàLinhTech' ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/style.css">

    <style>
        :root {
            --primary:      #1a1a2e;
            --accent:       #e94560;
            --accent-hover: #c73652;
            --gold:         #f5a623;
            --text-mid:     #4a4a6a;
            --text-light:   #9a9ab0;
            --bg-page:      #f8f8fc;
            --border:       #e8e8f0;
            --shadow-md:    0 8px 32px rgba(26,26,46,.12);
            --font-display: 'Playfair Display', serif;
            --font-body:    'DM Sans', sans-serif;
            --topbar-h:     36px;
            --header-h:     68px;
        }

        body {
            font-family: var(--font-body);
            padding-top: calc(var(--topbar-h) + var(--header-h));
        }

        /* ===== TOP BAR ===== */
        .topbar {
            position: fixed; top: 0; left: 0; right: 0;
            height: var(--topbar-h);
            background: var(--primary);
            color: rgba(255,255,255,.75);
            font-size: .75rem;
            z-index: 1100;
            display: flex; align-items: center;
        }
        .topbar__inner {
            display: flex; justify-content: space-between; align-items: center;
            max-width: 1320px; width: 100%; margin: 0 auto; padding: 0 24px;
        }
        .topbar__left { display: flex; align-items: center; gap: 20px; }
        .topbar__left span { display: flex; align-items: center; gap: 5px; }
        .topbar__left i { color: var(--gold); font-size: .7rem; }
        .topbar__right { display: flex; align-items: center; gap: 14px; }
        .topbar__right a { color: rgba(255,255,255,.7); text-decoration: none; display: flex; align-items: center; gap: 5px; transition: color .2s; }
        .topbar__right a:hover { color: #fff; }
        .topbar__divider { width: 1px; height: 12px; background: rgba(255,255,255,.2); }

        /* ===== NAVBAR ===== */
        .navbar {
            position: fixed !important;
            top: var(--topbar-h); left: 0; right: 0;
            height: var(--header-h);
            background: #fff !important;
            border-bottom: 1px solid var(--border);
            box-shadow: 0 2px 8px rgba(26,26,46,.08);
            z-index: 1099;
            transition: box-shadow .25s;

            /* QUAN TRỌNG: cần position relative để #navbarNav absolute được tính từ đây */
            position: fixed !important;
        }
        .navbar.scrolled { box-shadow: var(--shadow-md); }

        /* Logo */
        .navbar-brand {
            font-family: var(--font-display) !important;
            font-size: 1.4rem !important;
            font-weight: 700 !important;
            color: var(--primary) !important;
            display: flex; align-items: center; gap: 10px;
        }
        .navbar-brand .logo-icon {
            width: 36px; height: 36px;
            background: linear-gradient(135deg, var(--accent), #ff7043);
            border-radius: 9px;
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 1rem; flex-shrink: 0;
        }
        .navbar-brand .logo-text span { color: var(--accent); }

        /* Nav links */
        .navbar-nav .nav-link {
            font-family: var(--font-body);
            font-weight: 500; font-size: .875rem;
            color: var(--text-mid) !important;
            padding: 8px 14px !important;
            border-radius: 6px;
            transition: all .2s;
        }
        .navbar-nav .nav-link:hover { color: var(--accent) !important; background: var(--bg-page); }

        /* Dropdown */
        .dropdown-menu {
            border: 1px solid var(--border) !important;
            border-radius: 12px !important;
            box-shadow: 0 16px 48px rgba(26,26,46,.16) !important;
            padding: 8px !important;
            font-family: var(--font-body);
        }
        .dropdown-item {
            border-radius: 6px; font-size: .875rem;
            color: var(--text-mid); padding: 9px 14px;
            transition: all .2s;
        }
        .dropdown-item:hover { background: var(--bg-page) !important; color: var(--accent) !important; }

        /* Search */
        .search-wrapper { position: relative; }
        .search-wrapper .input-group {
            background: var(--bg-page);
            border: 1.5px solid var(--border);
            border-radius: 50px;
            overflow: hidden;
            transition: all .25s;
            padding: 3px 3px 3px 16px;
            flex-wrap: nowrap;
        }
        .search-wrapper .input-group:focus-within {
            border-color: var(--accent);
            background: #fff;
            box-shadow: 0 0 0 3px rgba(233,69,96,.1);
        }
        .search-wrapper .form-control {
            border: none !important; background: transparent !important;
            box-shadow: none !important; font-family: var(--font-body);
            font-size: .875rem; color: var(--primary);
            padding: 6px 8px 6px 0;
        }
        .search-wrapper .form-control::placeholder { color: var(--text-light); }
        .search-wrapper .btn-search {
            background: var(--accent); color: #fff; border: none;
            border-radius: 50px !important; padding: 7px 18px;
            font-family: var(--font-body); font-size: .85rem; font-weight: 600;
            display: flex; align-items: center; gap: 6px;
            transition: background .2s; white-space: nowrap; flex-shrink: 0;
        }
        .search-wrapper .btn-search:hover { background: var(--accent-hover); }

        /* Cart */
        .cart-btn {
            position: relative; color: var(--text-mid) !important;
            padding: 8px 12px; border-radius: 10px; text-decoration: none;
            transition: all .2s; display: flex; flex-direction: column;
            align-items: center; gap: 2px; font-size: .7rem; font-weight: 500;
        }
        .cart-btn i { font-size: 1.15rem; }
        .cart-btn:hover { background: var(--bg-page); color: var(--accent) !important; }
        .cart-btn #cart-count {
            position: absolute; top: 2px; right: 4px;
            background: var(--accent) !important;
            font-size: .6rem; min-width: 17px; height: 17px;
            display: flex; align-items: center; justify-content: center;
            padding: 0 3px; border-radius: 50px; line-height: 1;
        }

        /* User */
        .user-trigger {
            display: flex; align-items: center; gap: 8px;
            padding: 6px 10px; border-radius: 10px;
            cursor: pointer; transition: background .2s;
        }
        .user-trigger:hover { background: var(--bg-page); }
        .user-avatar-sm {
            width: 32px; height: 32px;
            background: linear-gradient(135deg, var(--accent), #ff7043);
            color: #fff; border-radius: 50%;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: .8rem; font-weight: 700; flex-shrink: 0;
        }
        .user-dropdown-menu {
            width: 240px;
            border: 1px solid var(--border) !important;
            border-radius: 12px !important;
            box-shadow: 0 16px 48px rgba(26,26,46,.16) !important;
            padding: 8px !important;
            font-family: var(--font-body);
        }
        .user-info-block {
            padding: 10px 14px 12px;
            border-bottom: 1px solid var(--border);
            margin-bottom: 6px;
        }

        /* Btn đăng nhập */
        .btn-login {
            background: transparent; color: var(--text-mid) !important;
            border: 1.5px solid var(--border); border-radius: 50px;
            padding: 8px 20px; font-family: var(--font-body);
            font-size: .875rem; font-weight: 600; text-decoration: none;
            transition: all .2s; display: inline-flex; align-items: center; gap: 6px;
        }
        .btn-login:hover { border-color: var(--accent); color: var(--accent) !important; }

        .btn-register {
            background: var(--accent); color: #fff !important;
            border: none; border-radius: 50px;
            padding: 8px 20px; font-family: var(--font-body);
            font-size: .875rem; font-weight: 600; text-decoration: none;
            transition: all .2s; display: inline-flex; align-items: center; gap: 6px;
            box-shadow: 0 4px 12px rgba(233,69,96,.3);
        }
        .btn-register:hover { background: var(--accent-hover); transform: translateY(-1px); }

        /* Card hover (dùng chung) */
        .card-product:hover { transform: translateY(-5px); transition: 0.3s; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }

        /* ===== MOBILE RESPONSIVE (gộp 1 chỗ duy nhất) ===== */
        @media (max-width: 991.98px) {

            /* Topbar ẩn left */
            .topbar__left { display: none; }

            /* Logo nhỏ lại */
            .navbar-brand { font-size: 1.1rem !important; }

            /*
             * KEY FIX: navbar collapse mở xuống dưới navbar
             * dùng position absolute + top: 100%
             * để KHÔNG đè lên slide bên dưới
             */
            #navbarNav {
                position: absolute;
                top: 100%;           /* ngay dưới navbar */
                left: 0;
                right: 0;
                background: #fff;
                border-top: 1px solid var(--border);
                box-shadow: 0 8px 24px rgba(26,26,46,.12);
                z-index: 1098;
                padding: 12px 16px 16px;
                flex-direction: column;
            }

            /* Search full width, xuống hàng riêng */
            .search-wrapper {
                width: 100%;
                margin: 8px 0 !important;
                order: 3;
            }

            /* Nav links dễ bấm hơn */
            .navbar-nav .nav-link {
                padding: 10px 16px !important;
            }

            /* Dropdown danh mục full width */
            .dropdown-menu {
                width: 100% !important;
                position: static !important;
                box-shadow: none !important;
                border: 1px solid var(--border) !important;
            }

            /* Actions (giỏ hàng, đăng nhập) full width, căn giữa */
            .d-flex.align-items-center.gap-1 {
                width: 100%;
                justify-content: center;
                padding-top: 10px;
                margin-top: 4px;
                border-top: 1px solid var(--border);
                flex-wrap: wrap;
                gap: 8px !important;
                order: 4;
            }

            /* Nút đăng nhập / đăng ký gọn hơn */
            .btn-login, .btn-register {
                padding: 8px 14px;
                font-size: .8rem;
            }
        }

        @media (max-width: 480px) {
            .topbar__right { display: none; }
        }
    </style>
</head>
<body>

<!-- ===== TOP BAR ===== -->
<div class="topbar">
    <div class="topbar__inner">
        <div class="topbar__left">
            <span><i class="fas fa-truck-fast"></i> Miễn phí vận chuyển đơn từ 300k</span>
            <span><i class="fas fa-shield-halved"></i> Đổi trả 30 ngày</span>
            <span><i class="fas fa-headset"></i> Hỗ trợ 24/7</span>
        </div>
        <div class="topbar__right">
            <a href="#"><i class="fas fa-globe"></i> Tiếng Việt</a>
            <div class="topbar__divider"></div>

        </div>
    </div>
</div>



<!-- ===== NAVBAR ===== -->
<nav class="navbar navbar-expand-lg" id="mainNavbar">
    <div class="container">

        <!-- Logo -->
        <a class="navbar-brand" href="index.php">
            <div class="logo-icon"><i class="fas fa-bag-shopping"></i></div>
            <div class="logo-text">HàLinh<span>Tech</span></div>
        </a>

        <!-- Toggler mobile -->
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">

            <!-- Nav -->
            <ul class="navbar-nav me-3">
                <li class="nav-item">
                    <a class="nav-link" href="index.php">Trang chủ</a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Danh mục</a>
                    <ul class="dropdown-menu">
                        <?php foreach ($categories as $cat): ?>
                            <li>
                                <a class="dropdown-item" href="index.php?controller=product&action=category&id=<?= $cat['id'] ?>">
                                    <?= htmlspecialchars($cat['name']) ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </li>
            </ul>

            <!-- Search -->
            <form class="search-wrapper flex-grow-1 me-3" action="index.php" method="GET">
                <input type="hidden" name="controller" value="product">
                <input type="hidden" name="action" value="search">
                <div class="input-group">
                    <input class="form-control" type="search" name="keyword" id="search-input"
                           placeholder="Tìm linh kiện..." autocomplete="off"
                           value="<?= htmlspecialchars($_GET['keyword'] ?? '') ?>">
                    <button class="btn-search" type="submit">
                        <i class="fas fa-magnifying-glass"></i> Tìm
                    </button>
                </div>
                <div id="search-results-ajax" class="list-group position-absolute w-100 shadow"
                     style="top:100%;left:0;z-index:9999;display:none;max-height:300px;overflow-y:auto;border-radius:12px;margin-top:6px;"></div>
            </form>

            <!-- Actions -->
            <div class="d-flex align-items-center gap-1">

                <!-- Giỏ hàng -->
                <a href="index.php?controller=cart" class="cart-btn">
                    <i class="fas fa-cart-shopping"></i>
                    <span>Giỏ hàng</span>
                    <span id="cart-count" class="badge rounded-pill">
                        <?= count($_SESSION['cart'] ?? []) ?>
                    </span>
                </a>

                <?php if (isset($_SESSION['user'])): ?>
                    <!-- Đã đăng nhập -->
                    <div class="dropdown">
                        <div class="user-trigger" data-bs-toggle="dropdown">
                            <div class="user-avatar-sm">
                                <?= mb_strtoupper(mb_substr($_SESSION['user']['username'] ?? 'U', 0, 1)) ?>
                            </div>
                            <span class="small fw-semibold text-dark d-none d-lg-inline">
                                <?= htmlspecialchars($_SESSION['user']['fullname'] ?? $_SESSION['user']['username']) ?>
                            </span>
                            <i class="fas fa-chevron-down d-none d-lg-inline" style="font-size:.6rem;color:var(--text-light)"></i>
                        </div>

                        <ul class="dropdown-menu dropdown-menu-end user-dropdown-menu mt-2">
                            <li>
                                <div class="user-info-block">
                                    <p class="mb-0 fw-bold text-dark small"><?= htmlspecialchars($_SESSION['user']['fullname'] ?? 'Thành viên') ?></p>
                                    <p class="mb-0 text-muted" style="font-size:.78rem"><?= htmlspecialchars($_SESSION['user']['email'] ?? '') ?></p>
                                </div>
                            </li>
                            <?php if (($_SESSION['user']['role'] ?? '') === 'admin'): ?>
                                <li>
                                    <a class="dropdown-item fw-semibold" href="admin/index.php" style="color:var(--accent)">
                                        <i class="fas fa-gauge-high me-2"></i>Quản trị hệ thống
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider my-1"></li>
                            <?php endif; ?>
                            <li><a class="dropdown-item" href="index.php?controller=user&action=profile"><i class="fas fa-user-edit me-2"></i>Hồ sơ cá nhân</a></li>
                            <li><a class="dropdown-item" href="index.php?controller=order&action=history"><i class="fas fa-receipt me-2"></i>Lịch sử mua hàng</a></li>
                            <li><hr class="dropdown-divider my-1"></li>
                            <li><a class="dropdown-item text-danger" href="index.php?controller=auth&action=logout"><i class="fas fa-right-from-bracket me-2"></i>Đăng xuất</a></li>
                        </ul>
                    </div>

                <?php else: ?>
                    <!-- Chưa đăng nhập -->
                    <a href="index.php?controller=auth&action=login" class="btn-login ms-2">
                        <i class="fas fa-right-to-bracket"></i> Đăng nhập
                    </a>
                    <a href="index.php?controller=auth&action=register" class="btn-register ms-1">
                        <i class="fas fa-user-plus"></i> Đăng ký
                    </a>
                <?php endif; ?>

            </div>
        </div>
    </div>
</nav>

<script>
    const mainNavbar = document.getElementById('mainNavbar');
    window.addEventListener('scroll', () => {
        mainNavbar.classList.toggle('scrolled', window.scrollY > 10);
    }, { passive: true });
</script>

<div class="container mt-4">