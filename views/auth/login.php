
<?php

// Lấy lỗi từ controller (nếu có)
$error = $_SESSION['login_error'] ?? '';
unset($_SESSION['login_error']);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập — HàLinhTech</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        :root {
            --primary:      #1a1a2e;
            --accent:       #e94560;
            --accent-hover: #c73652;
            --gold:         #f5a623;
            --text-dark:    #1a1a2e;
            --text-mid:     #4a4a6a;
            --text-light:   #9a9ab0;
            --bg-page:      #f8f8fc;
            --bg-white:     #ffffff;
            --border:       #e8e8f0;
            --font-display: 'Playfair Display', serif;
            --font-body:    'DM Sans', sans-serif;
            --transition:   all .25s cubic-bezier(.4,0,.2,1);
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { height: 100%; }
        body { font-family: var(--font-body); background: var(--bg-page); color: var(--text-dark); }
        a { text-decoration: none; color: inherit; transition: var(--transition); }

        /* ===== LAYOUT 2 CỘT ===== */
        .auth-layout {
            display: grid;
            grid-template-columns: 1fr 1fr;
            min-height: 100vh;
        }

        /* ===== PANEL TRÁI ===== */
        .auth-brand {
            position: relative;
            background: var(--primary);
            display: flex; flex-direction: column;
            justify-content: space-between;
            padding: 48px; overflow: hidden;
        }
        .auth-brand::before {
            content: ''; position: absolute; inset: 0;
            background:
                    radial-gradient(ellipse 80% 60% at 20% 80%, rgba(233,69,96,.25) 0%, transparent 60%),
                    radial-gradient(ellipse 60% 80% at 80% 10%, rgba(245,166,35,.12) 0%, transparent 50%);
        }
        .brand-grid {
            position: absolute; inset: 0;
            background-image:
                    linear-gradient(rgba(255,255,255,.03) 1px, transparent 1px),
                    linear-gradient(90deg, rgba(255,255,255,.03) 1px, transparent 1px);
            background-size: 48px 48px;
        }
        .brand-circle { position: absolute; border-radius: 50%; border: 1px solid rgba(255,255,255,.07); }
        .brand-circle-1 { width: 320px; height: 320px; top: -80px; right: -80px; }
        .brand-circle-2 { width: 200px; height: 200px; bottom: 100px; left: -60px; border-color: rgba(233,69,96,.15); background: rgba(233,69,96,.04); }
        .brand-circle-3 { width: 80px;  height: 80px;  top: 40%; right: 40px; background: rgba(245,166,35,.06); border-color: rgba(245,166,35,.15); }

        .brand-top { position: relative; z-index: 1; }
        .brand-logo { display: flex; align-items: center; gap: 12px; }
        .brand-logo-icon {
            width: 44px; height: 44px;
            background: linear-gradient(135deg, var(--accent), #ff7043);
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 1.2rem;
            box-shadow: 0 8px 24px rgba(233,69,96,.4);
        }
        .brand-logo-text { font-family: var(--font-display); font-size: 1.6rem; font-weight: 700; color: #fff; }
        .brand-logo-text span { color: var(--accent); }

        .brand-middle {
            position: relative; z-index: 1; flex: 1;
            display: flex; flex-direction: column; justify-content: center; padding-block: 48px;
        }
        .brand-tagline {
            font-size: .72rem; font-weight: 700; letter-spacing: .16em;
            text-transform: uppercase; color: var(--accent); margin-bottom: 20px;
            display: flex; align-items: center; gap: 10px;
        }
        .brand-tagline::before { content: ''; width: 32px; height: 2px; background: var(--accent); }
        .brand-headline {
            font-family: var(--font-display);
            font-size: clamp(1.8rem, 3vw, 2.6rem); font-weight: 700;
            color: #fff; line-height: 1.2; margin-bottom: 20px;
        }
        .brand-headline em { font-style: normal; color: var(--gold); }
        .brand-desc { color: rgba(255,255,255,.5); font-size: .9rem; line-height: 1.75; max-width: 320px; }

        .brand-features { display: flex; flex-direction: column; gap: 12px; margin-top: 36px; }
        .brand-feature {
            display: flex; align-items: center; gap: 12px;
            padding: 12px 16px;
            background: rgba(255,255,255,.05);
            border: 1px solid rgba(255,255,255,.08);
            border-radius: 50px; color: rgba(255,255,255,.75); font-size: .84rem;
        }
        .brand-feature i {
            width: 28px; height: 28px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: .8rem; flex-shrink: 0;
        }
        .brand-feature:nth-child(1) i { background: rgba(233,69,96,.2);  color: var(--accent); }
        .brand-feature:nth-child(2) i { background: rgba(245,166,35,.2); color: var(--gold); }
        .brand-feature:nth-child(3) i { background: rgba(34,197,94,.2);  color: #22c55e; }

        .brand-bottom { position: relative; z-index: 1; }
        .brand-stats { display: flex; gap: 32px; padding-top: 24px; border-top: 1px solid rgba(255,255,255,.08); }
        .brand-stat-value { font-family: var(--font-display); font-size: 1.4rem; font-weight: 700; color: #fff; }
        .brand-stat-label { font-size: .75rem; color: rgba(255,255,255,.4); margin-top: 2px; }

        /* ===== PANEL PHẢI ===== */
        .auth-form-panel {
            display: flex; flex-direction: column; justify-content: center;
            padding: 48px clamp(32px, 6vw, 80px);
            background: var(--bg-white); overflow-y: auto;
        }
        .auth-form-header { margin-bottom: 32px; }
        .auth-form-header h1 {
            font-family: var(--font-display);
            font-size: 2rem; font-weight: 700; color: var(--text-dark); margin-bottom: 8px;
        }
        .auth-form-header p { color: var(--text-light); font-size: .9rem; }
        .auth-form-header a { color: var(--accent); font-weight: 600; }
        .auth-form-header a:hover { text-decoration: underline; }

        .auth-form { max-width: 400px; width: 100%; }

        /* Alert lỗi */
        .login-error {
            display: flex; align-items: center; gap: 10px;
            padding: 13px 16px;
            background: rgba(239,68,68,.08);
            border: 1px solid rgba(239,68,68,.2);
            border-left: 3px solid #ef4444;
            border-radius: 12px; color: #991b1b; font-size: .875rem;
            margin-bottom: 20px;
        }

        /* Form fields */
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; font-size: .82rem; font-weight: 600; color: var(--text-dark); margin-bottom: 6px; }
        .form-label .req { color: var(--accent); }

        .input-wrap { position: relative; }
        .input-wrap .icon-left {
            position: absolute; left: 14px; top: 50%;
            transform: translateY(-50%);
            color: var(--text-light); font-size: .9rem; pointer-events: none;
            transition: color .2s;
        }
        .input-wrap:focus-within .icon-left { color: var(--accent); }
        .input-wrap input {
            width: 100%; padding: 12px 16px 12px 42px;
            background: var(--bg-page); border: 1.5px solid var(--border);
            border-radius: 12px; font-family: var(--font-body); font-size: .875rem;
            color: var(--text-dark); outline: none; transition: var(--transition);
        }
        .input-wrap input::placeholder { color: var(--text-light); }
        .input-wrap input:focus {
            border-color: var(--accent); background: var(--bg-white);
            box-shadow: 0 0 0 3px rgba(233,69,96,.1);
        }
        .toggle-pass {
            position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
            color: var(--text-light); cursor: pointer; font-size: .85rem;
            padding: 4px; transition: color .2s;
        }
        .toggle-pass:hover { color: var(--accent); }

        /* Options */
        .form-options {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 24px;
        }
        .form-check { display: flex; align-items: center; gap: 8px; cursor: pointer; }
        .form-check input[type="checkbox"] {
            width: 17px; height: 17px; border: 1.5px solid var(--border);
            border-radius: 4px; appearance: none; cursor: pointer;
            transition: var(--transition); flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
        }
        .form-check input[type="checkbox"]:checked { background: var(--accent); border-color: var(--accent); }
        .form-check input[type="checkbox"]:checked::after { content: '✓'; font-size: .6rem; color: #fff; font-weight: 700; display: block; text-align: center; }
        .form-check-label { font-size: .84rem; color: var(--text-mid); user-select: none; }
        .forgot-link { font-size: .84rem; color: var(--accent); font-weight: 500; }
        .forgot-link:hover { text-decoration: underline; }

        /* Submit */
        .btn-submit {
            width: 100%; padding: 13px;
            background: var(--accent); color: #fff;
            border: none; border-radius: 12px;
            font-family: var(--font-body); font-size: .95rem; font-weight: 600;
            cursor: pointer; transition: var(--transition);
            display: flex; align-items: center; justify-content: center; gap: 8px;
            box-shadow: 0 4px 16px rgba(233,69,96,.3);
        }
        .btn-submit:hover { background: var(--accent-hover); transform: translateY(-1px); box-shadow: 0 6px 20px rgba(233,69,96,.4); }
        .btn-submit:disabled { opacity: .7; cursor: not-allowed; transform: none; }

        /* Divider */
        .auth-divider {
            display: flex; align-items: center; gap: 14px;
            color: var(--text-light); font-size: .8rem; margin-block: 20px;
        }
        .auth-divider::before, .auth-divider::after { content: ''; flex: 1; height: 1px; background: var(--border); }

        /* Social */
        .social-logins { display: flex; gap: 10px; }
        .social-btn-auth {
            flex: 1; display: flex; align-items: center; justify-content: center; gap: 8px;
            padding: 11px 16px; border: 1.5px solid var(--border);
            border-radius: 12px; color: var(--text-mid); font-size: .84rem; font-weight: 500;
            transition: var(--transition);
        }
        .social-btn-auth:hover { border-color: var(--text-mid); color: var(--text-dark); }

        /* Switch */
        .auth-switch {
            margin-top: 24px; padding-top: 20px;
            border-top: 1px solid var(--border);
            text-align: center; font-size: .875rem; color: var(--text-light);
        }
        .auth-switch a { color: var(--accent); font-weight: 600; }
        .auth-switch a:hover { text-decoration: underline; }

        /* Responsive */
        @media (max-width: 900px) {
            .auth-layout { grid-template-columns: 1fr; }
            .auth-brand  { display: none; }
            .auth-form-panel { justify-content: flex-start; padding: 40px 24px; min-height: 100vh; }
            /* Logo nhỏ trên mobile */
            .mobile-logo { display: flex; align-items: center; gap: 10px; margin-bottom: 32px; }
            .mobile-logo-icon {
                width: 36px; height: 36px;
                background: linear-gradient(135deg, var(--accent), #ff7043);
                border-radius: 9px;
                display: flex; align-items: center; justify-content: center;
                color: #fff; font-size: 1rem;
            }
            .mobile-logo-text { font-family: var(--font-display); font-size: 1.4rem; font-weight: 700; color: var(--text-dark); }
            .mobile-logo-text span { color: var(--accent); }
        }
        @media (min-width: 901px) { .mobile-logo { display: none; } }

        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>

<div class="auth-layout">

    <!-- ===== PANEL TRÁI: BRAND ===== -->
    <div class="auth-brand">
        <div class="brand-grid"></div>
        <div class="brand-circle brand-circle-1"></div>
        <div class="brand-circle brand-circle-2"></div>
        <div class="brand-circle brand-circle-3"></div>

        <div class="brand-top">
            <a href="index.php" class="brand-logo">
                <div class="brand-logo-icon"><i class="fas fa-bag-shopping"></i></div>
                <div class="brand-logo-text">HàLinh<span>Tech</span></div>
            </a>
        </div>

        <div class="brand-middle">
            <div class="brand-tagline">Nền tảng công nghệ</div>
            <h2 class="brand-headline">Linh kiện chính hãng,<br><em>giá tốt nhất.</em></h2>
            <p class="brand-desc">Hàng chính hãng, giao hàng nhanh, đổi trả dễ dàng. Trải nghiệm mua sắm công nghệ không giới hạn.</p>
            <div class="brand-features">
                <div class="brand-feature"><i class="fas fa-shield-halved"></i> Thanh toán 100% bảo mật</div>
                <div class="brand-feature"><i class="fas fa-truck-fast"></i> Giao hàng toàn quốc trong 24h</div>
                <div class="brand-feature"><i class="fas fa-rotate-left"></i> Đổi trả miễn phí trong 30 ngày</div>
            </div>
        </div>

        <div class="brand-bottom">
            <div class="brand-stats">
                <div><div class="brand-stat-value">10K+</div><div class="brand-stat-label">Khách hàng</div></div>
                <div><div class="brand-stat-value">500+</div><div class="brand-stat-label">Sản phẩm</div></div>
                <div><div class="brand-stat-value">4.9★</div><div class="brand-stat-label">Đánh giá TB</div></div>
            </div>
        </div>
    </div>

    <!-- ===== PANEL PHẢI: FORM ===== -->
    <div class="auth-form-panel">
        <!-- Logo mobile -->
        <a href="index.php" class="mobile-logo">
            <div class="mobile-logo-icon"><i class="fas fa-bag-shopping"></i></div>
            <div class="mobile-logo-text">HàLinh<span>Tech</span></div>
        </a>

        <div class="auth-form-header">
            <h1>Chào mừng trở lại 👋</h1>
            <p>Chưa có tài khoản?
                <a href="index.php?controller=auth&action=register">Đăng ký ngay</a>
            </p>
        </div>

        <form class="auth-form" action="index.php?controller=auth&action=postLogin" method="POST" id="loginForm">

            <?php if (!empty($error)): ?>
                <div class="login-error">
                    <i class="fas fa-circle-xmark"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <!-- Username -->
            <div class="form-group">
                <label class="form-label">Tên đăng nhập <span class="req">*</span></label>
                <div class="input-wrap">
                    <i class="fas fa-user icon-left"></i>
                    <input type="text" name="username" placeholder="Nhập tên đăng nhập..."
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                           autocomplete="username" required autofocus>
                </div>
            </div>

            <!-- Password -->
            <div class="form-group">
                <label class="form-label">Mật khẩu <span class="req">*</span></label>
                <div class="input-wrap">
                    <i class="fas fa-lock icon-left"></i>
                    <input type="password" name="password" id="passwordInput"
                           placeholder="Nhập mật khẩu..."
                           autocomplete="current-password" required>
                    <span class="toggle-pass" onclick="togglePassword()">
                        <i class="fas fa-eye" id="toggleIcon"></i>
                    </span>
                </div>
            </div>

            <!-- Remember + Quên mật khẩu -->
            <div class="form-options">
                <label class="form-check">
                    <input type="checkbox" name="remember" <?= isset($_POST['remember']) ? 'checked' : '' ?>>
                    <span class="form-check-label">Ghi nhớ đăng nhập</span>
                </label>
                <a href="index.php?controller=auth&action=forgotPassword" class="forgot-link">Quên mật khẩu</a>
            </div>

            <!-- Submit -->
            <button type="submit" class="btn-submit" id="submitBtn">
                <i class="fas fa-right-to-bracket"></i> Đăng nhập
            </button>

            <!-- Divider -->
            <div class="auth-divider">hoặc tiếp tục với</div>

            <!-- Social -->
            <div class="social-logins">
                <a href="index.php?controller=auth&action=socialLogin&provider=google" class="social-btn-auth">
                    <svg width="18" height="18" viewBox="0 0 48 48">
                        <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                        <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                        <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
                        <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
                    </svg>
                    Google
                </a>
                <a href="index.php?controller=auth&action=socialLogin&provider=facebook" class="social-btn-auth">
                    <i class="fab fa-facebook-f" style="color:#1877f2;font-size:1rem"></i>
                    Facebook
                </a>
            </div>

            <div class="auth-switch">
                Chưa có tài khoản?
                <a href="index.php?controller=auth&action=register">Tạo tài khoản mới →</a>
            </div>

        </form>
    </div>
</div>

<script>
    function togglePassword() {
        const input = document.getElementById('passwordInput');
        const icon  = document.getElementById('toggleIcon');
        input.type  = input.type === 'password' ? 'text' : 'password';
        icon.className = input.type === 'password' ? 'fas fa-eye' : 'fas fa-eye-slash';
    }

    document.getElementById('loginForm').addEventListener('submit', function() {
        const btn = document.getElementById('submitBtn');
        btn.innerHTML = '<span style="width:16px;height:16px;border:2px solid rgba(255,255,255,.4);border-top-color:#fff;border-radius:50%;animation:spin .7s linear infinite;display:inline-block"></span> Đang xử lý...';
        btn.disabled = true;
    });
</script>

</body>
</html>