
    <!DOCTYPE html>
    <html lang="vi">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Quên mật khẩu — HàLinhTech</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
        <style>
            :root {
                --primary: #1a1a2e; --accent: #e94560; --accent-hover: #c73652;
                --gold: #f5a623; --text-dark: #1a1a2e; --text-mid: #4a4a6a;
                --text-light: #9a9ab0; --bg-page: #f8f8fc; --bg-white: #ffffff;
                --border: #e8e8f0; --font-display: 'Playfair Display', serif;
                --font-body: 'DM Sans', sans-serif; --transition: all .25s cubic-bezier(.4,0,.2,1);
            }
            *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
            html, body { height: 100%; }
            body { font-family: var(--font-body); background: var(--bg-page); color: var(--text-dark); }
            a { text-decoration: none; color: inherit; transition: var(--transition); }

            .auth-layout { display: grid; grid-template-columns: 1fr 1fr; min-height: 100vh; }

            /* PANEL TRÁI */
            .auth-brand {
                position: relative; background: var(--primary);
                display: flex; flex-direction: column; justify-content: space-between;
                padding: 48px; overflow: hidden;
            }
            .auth-brand::before {
                content: ''; position: absolute; inset: 0;
                background: radial-gradient(ellipse 80% 60% at 20% 80%, rgba(233,69,96,.25) 0%, transparent 60%),
                radial-gradient(ellipse 60% 80% at 80% 10%, rgba(245,166,35,.12) 0%, transparent 50%);
            }
            .brand-grid {
                position: absolute; inset: 0;
                background-image: linear-gradient(rgba(255,255,255,.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,.03) 1px, transparent 1px);
                background-size: 48px 48px;
            }
            .brand-circle { position: absolute; border-radius: 50%; border: 1px solid rgba(255,255,255,.07); }
            .brand-circle-1 { width: 320px; height: 320px; top: -80px; right: -80px; }
            .brand-circle-2 { width: 200px; height: 200px; bottom: 100px; left: -60px; border-color: rgba(233,69,96,.15); background: rgba(233,69,96,.04); }
            .brand-circle-3 { width: 80px; height: 80px; top: 40%; right: 40px; background: rgba(245,166,35,.06); border-color: rgba(245,166,35,.15); }

            .brand-top { position: relative; z-index: 1; }
            .brand-logo { display: flex; align-items: center; gap: 12px; }
            .brand-logo-icon {
                width: 44px; height: 44px;
                background: linear-gradient(135deg, var(--accent), #ff7043);
                border-radius: 12px; display: flex; align-items: center; justify-content: center;
                color: #fff; font-size: 1.2rem; box-shadow: 0 8px 24px rgba(233,69,96,.4);
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
                font-family: var(--font-display); font-size: clamp(1.8rem, 3vw, 2.6rem);
                font-weight: 700; color: #fff; line-height: 1.2; margin-bottom: 20px;
            }
            .brand-headline em { font-style: normal; color: var(--gold); }
            .brand-desc { color: rgba(255,255,255,.5); font-size: .9rem; line-height: 1.75; max-width: 320px; }

            .brand-steps { display: flex; flex-direction: column; gap: 16px; margin-top: 36px; }
            .brand-step {
                display: flex; align-items: center; gap: 14px;
                padding: 14px 16px;
                background: rgba(255,255,255,.05); border: 1px solid rgba(255,255,255,.08);
                border-radius: 12px; color: rgba(255,255,255,.8); font-size: .84rem;
            }
            .step-num {
                width: 28px; height: 28px; border-radius: 50%; flex-shrink: 0;
                display: flex; align-items: center; justify-content: center;
                font-size: .78rem; font-weight: 700;
            }
            .brand-step:nth-child(1) .step-num { background: rgba(233,69,96,.25); color: var(--accent); }
            .brand-step:nth-child(2) .step-num { background: rgba(245,166,35,.25); color: var(--gold); }
            .brand-step:nth-child(3) .step-num { background: rgba(34,197,94,.2); color: #22c55e; }

            .brand-bottom { position: relative; z-index: 1; }
            .brand-stats { display: flex; gap: 32px; padding-top: 24px; border-top: 1px solid rgba(255,255,255,.08); }
            .brand-stat-value { font-family: var(--font-display); font-size: 1.4rem; font-weight: 700; color: #fff; }
            .brand-stat-label { font-size: .75rem; color: rgba(255,255,255,.4); margin-top: 2px; }

            /* PANEL PHẢI */
            .auth-form-panel {
                display: flex; flex-direction: column; justify-content: center;
                padding: 48px clamp(32px, 6vw, 80px); background: var(--bg-white); overflow-y: auto;
            }
            .auth-form-header { margin-bottom: 32px; }
            .auth-form-header h1 { font-family: var(--font-display); font-size: 2rem; font-weight: 700; color: var(--text-dark); margin-bottom: 8px; }
            .auth-form-header p { color: var(--text-light); font-size: .9rem; line-height: 1.6; }

            .auth-form { max-width: 400px; width: 100%; }

            .alert-error, .alert-success {
                display: flex; align-items: center; gap: 10px;
                padding: 13px 16px; border-radius: 12px; font-size: .875rem; margin-bottom: 20px;
            }
            .alert-error { background: rgba(239,68,68,.08); border: 1px solid rgba(239,68,68,.2); border-left: 3px solid #ef4444; color: #991b1b; }
            .alert-success { background: rgba(34,197,94,.08); border: 1px solid rgba(34,197,94,.2); border-left: 3px solid #22c55e; color: #166534; }

            .form-group { margin-bottom: 20px; }
            .form-label { display: block; font-size: .82rem; font-weight: 600; color: var(--text-dark); margin-bottom: 6px; }
            .form-label .req { color: var(--accent); }

            .input-wrap { position: relative; }
            .input-wrap .icon-left {
                position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
                color: var(--text-light); font-size: .9rem; pointer-events: none;
            }
            .input-wrap input {
                width: 100%; padding: 12px 16px 12px 42px;
                border: 1.5px solid var(--border); border-radius: 12px;
                font-family: var(--font-body); font-size: .95rem; color: var(--text-dark);
                background: var(--bg-white); outline: none; transition: var(--transition);
            }
            .input-wrap input:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(233,69,96,.1); }
            .input-wrap input::placeholder { color: var(--text-light); }

            .btn-submit {
                width: 100%; padding: 13px; border: none; border-radius: 12px;
                background: linear-gradient(135deg, var(--accent), #c73652);
                color: #fff; font-family: var(--font-body); font-size: .95rem;
                font-weight: 600; cursor: pointer; transition: var(--transition);
                display: flex; align-items: center; justify-content: center; gap: 8px;
                box-shadow: 0 4px 16px rgba(233,69,96,.3);
            }
            .btn-submit:hover { background: linear-gradient(135deg, #c73652, var(--accent)); transform: translateY(-1px); box-shadow: 0 6px 20px rgba(233,69,96,.4); }

            .auth-switch { margin-top: 24px; text-align: center; font-size: .875rem; color: var(--text-light); }
            .auth-switch a { color: var(--accent); font-weight: 600; }
            .auth-switch a:hover { text-decoration: underline; }

            .mobile-logo { display: none; align-items: center; gap: 10px; margin-bottom: 32px; }
            .mobile-logo-icon {
                width: 36px; height: 36px; background: linear-gradient(135deg, var(--accent), #ff7043);
                border-radius: 9px; display: flex; align-items: center; justify-content: center;
                color: #fff; font-size: 1rem;
            }
            .mobile-logo-text { font-family: var(--font-display); font-size: 1.4rem; font-weight: 700; color: var(--text-dark); }
            .mobile-logo-text span { color: var(--accent); }

            @media (max-width: 900px) {
                .auth-layout { grid-template-columns: 1fr; }
                .auth-brand { display: none; }
                .mobile-logo { display: flex; }
            }
        </style>
    </head>
    <body>
    <div class="auth-layout">

        <!-- PANEL TRÁI -->
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
                <div class="brand-tagline">Khôi phục tài khoản</div>
                <h2 class="brand-headline">Lấy lại mật khẩu<br><em>chỉ 3 bước đơn giản.</em></h2>
                <p class="brand-desc">Hệ thống sẽ xác minh danh tính qua OTP và giúp bạn thiết lập mật khẩu mới an toàn.</p>
                <div class="brand-steps">
                    <div class="brand-step"><div class="step-num">1</div> Nhập Email hoặc Số điện thoại</div>
                    <div class="brand-step"><div class="step-num">2</div> Xác nhận mã OTP 6 số</div>
                    <div class="brand-step"><div class="step-num">3</div> Thiết lập mật khẩu mới</div>
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

        <!-- PANEL PHẢI -->
        <div class="auth-form-panel">
            <a href="index.php" class="mobile-logo">
                <div class="mobile-logo-icon"><i class="fas fa-bag-shopping"></i></div>
                <div class="mobile-logo-text">HàLinh<span>Tech</span></div>
            </a>

            <div class="auth-form-header">
                <h1>Quên mật khẩu? 🔐</h1>
                <p>Nhập Email hoặc Số điện thoại đã đăng ký.<br>Chúng tôi sẽ gửi mã OTP để xác nhận danh tính.</p>
            </div>

            <form class="auth-form" action="index.php?controller=auth&action=postForgotPassword" method="POST">

                <?php if (!empty($error)): ?>
                    <div class="alert-error">
                        <i class="fas fa-circle-xmark"></i> <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <div class="form-group">
                    <label class="form-label">Thông tin liên hệ <span class="req">*</span></label>
                    <div class="input-wrap">
                        <i class="fas fa-envelope icon-left"></i>
                        <input type="text" name="contact" placeholder="Nhập Email hoặc SĐT..." required autofocus>
                    </div>
                </div>

                <button type="submit" class="btn-submit">
                    <i class="fas fa-paper-plane"></i> Gửi mã OTP
                </button>
            </form>

            <div class="auth-switch">
                <a href="index.php?controller=auth&action=login"><i class="fas fa-arrow-left"></i> Quay lại Đăng nhập</a>
            </div>
        </div>
    </div>
    </body>
    </html>