
    <!DOCTYPE html>
    <html lang="vi">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Xác thực OTP — HàLinhTech</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
        <style>
            :root {
                --primary: #1a1a2e; --accent: #e94560; --gold: #f5a623;
                --text-dark: #1a1a2e; --text-light: #9a9ab0;
                --bg-page: #f8f8fc; --bg-white: #ffffff; --border: #e8e8f0;
                --font-display: 'Playfair Display', serif; --font-body: 'DM Sans', sans-serif;
                --transition: all .25s cubic-bezier(.4,0,.2,1);
            }
            *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
            html, body { height: 100%; }
            body { font-family: var(--font-body); background: var(--bg-page); color: var(--text-dark); }
            a { text-decoration: none; color: inherit; transition: var(--transition); }

            .auth-layout { display: grid; grid-template-columns: 1fr 1fr; min-height: 100vh; }

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
                width: 44px; height: 44px; background: linear-gradient(135deg, var(--accent), #ff7043);
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
            .brand-headline { font-family: var(--font-display); font-size: clamp(1.8rem,3vw,2.6rem); font-weight: 700; color: #fff; line-height: 1.2; margin-bottom: 20px; }
            .brand-headline em { font-style: normal; color: var(--gold); }
            .brand-desc { color: rgba(255,255,255,.5); font-size: .9rem; line-height: 1.75; max-width: 320px; }

            /* OTP visual */
            .otp-visual { margin-top: 36px; display: flex; flex-direction: column; gap: 12px; }
            .otp-box {
                display: flex; gap: 8px; align-items: center;
                padding: 16px 20px; background: rgba(255,255,255,.05);
                border: 1px solid rgba(255,255,255,.1); border-radius: 12px;
            }
            .otp-digit {
                width: 36px; height: 44px; background: rgba(255,255,255,.08);
                border: 1px solid rgba(255,255,255,.15); border-radius: 8px;
                display: flex; align-items: center; justify-content: center;
                font-family: var(--font-display); font-size: 1.2rem; color: #fff; font-weight: 700;
            }
            .otp-digit.active { background: rgba(233,69,96,.2); border-color: var(--accent); color: var(--accent); animation: pulse 1s infinite; }
            .otp-info { color: rgba(255,255,255,.5); font-size: .8rem; margin-left: 4px; }
            .timer-badge {
                display: inline-flex; align-items: center; gap: 6px;
                padding: 8px 14px; background: rgba(245,166,35,.15);
                border: 1px solid rgba(245,166,35,.2); border-radius: 50px;
                color: var(--gold); font-size: .8rem; font-weight: 600; width: fit-content;
            }

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
                display: flex; align-items: flex-start; gap: 10px;
                padding: 13px 16px; border-radius: 12px; font-size: .875rem; margin-bottom: 20px; line-height: 1.5;
            }
            .alert-error { background: rgba(239,68,68,.08); border: 1px solid rgba(239,68,68,.2); border-left: 3px solid #ef4444; color: #991b1b; }
            .alert-success { background: rgba(34,197,94,.08); border: 1px solid rgba(34,197,94,.2); border-left: 3px solid #22c55e; color: #166534; }

            .form-group { margin-bottom: 20px; }
            .form-label { display: block; font-size: .82rem; font-weight: 600; color: var(--text-dark); margin-bottom: 6px; }

            /* OTP Input lớn */
            .otp-input-wrap { position: relative; }
            .otp-input-wrap input {
                width: 100%; padding: 18px 16px; text-align: center;
                border: 2px solid var(--border); border-radius: 16px;
                font-family: var(--font-display); font-size: 2.2rem; font-weight: 700;
                letter-spacing: 16px; color: var(--accent); background: #fafafa;
                outline: none; transition: var(--transition);
            }
            .otp-input-wrap input:focus { border-color: var(--accent); box-shadow: 0 0 0 4px rgba(233,69,96,.1); background: var(--bg-white); }
            .otp-input-wrap input::placeholder { color: #ddd; letter-spacing: 8px; font-size: 1.6rem; }

            .otp-expire { text-align: center; font-size: .8rem; color: #ef4444; font-weight: 600; margin-top: 8px; }

            .btn-submit {
                width: 100%; padding: 13px; border: none; border-radius: 12px;
                background: linear-gradient(135deg, var(--accent), #c73652);
                color: #fff; font-family: var(--font-body); font-size: .95rem;
                font-weight: 600; cursor: pointer; transition: var(--transition);
                display: flex; align-items: center; justify-content: center; gap: 8px;
                box-shadow: 0 4px 16px rgba(233,69,96,.3); margin-top: 20px;
            }
            .btn-submit:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(233,69,96,.4); }

            .auth-switch { margin-top: 20px; text-align: center; font-size: .875rem; color: var(--text-light); }
            .auth-switch a { color: var(--accent); font-weight: 600; }
            .auth-switch a:hover { text-decoration: underline; }

            .mobile-logo { display: none; align-items: center; gap: 10px; margin-bottom: 32px; }
            .mobile-logo-icon { width: 36px; height: 36px; background: linear-gradient(135deg, var(--accent), #ff7043); border-radius: 9px; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 1rem; }
            .mobile-logo-text { font-family: var(--font-display); font-size: 1.4rem; font-weight: 700; color: var(--text-dark); }
            .mobile-logo-text span { color: var(--accent); }

            @media (max-width: 900px) { .auth-layout { grid-template-columns: 1fr; } .auth-brand { display: none; } .mobile-logo { display: flex; } }
            @keyframes pulse { 0%,100% { opacity: 1; } 50% { opacity: .4; } }
        </style>
    </head>
    <body>
    <div class="auth-layout">

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
                <div class="brand-tagline">Xác thực danh tính</div>
                <h2 class="brand-headline">Mã OTP đã được<br><em>gửi thành công.</em></h2>
                <p class="brand-desc">Nhập mã 6 số để xác nhận danh tính và tiến hành đặt lại mật khẩu an toàn.</p>
                <div class="otp-visual">
                    <div class="otp-box">
                        <div class="otp-digit">•</div>
                        <div class="otp-digit">•</div>
                        <div class="otp-digit">•</div>
                        <div class="otp-digit active">_</div>
                        <div class="otp-digit">•</div>
                        <div class="otp-digit">•</div>
                        <span class="otp-info">Mã 6 chữ số</span>
                    </div>
                    <div class="timer-badge"><i class="fas fa-clock"></i> Hết hạn sau 5 phút</div>
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

        <div class="auth-form-panel">
            <a href="index.php" class="mobile-logo">
                <div class="mobile-logo-icon"><i class="fas fa-bag-shopping"></i></div>
                <div class="mobile-logo-text">HàLinh<span>Tech</span></div>
            </a>

            <div class="auth-form-header">
                <h1>Nhập mã OTP 🔑</h1>
                <p>Kiểm tra Email hoặc SMS của bạn và nhập mã 6 số bên dưới.</p>
            </div>

            <div class="auth-form">

                <?php if (!empty($success)): ?>
                    <div class="alert-success">
                        <i class="fas fa-circle-info" style="margin-top:2px;flex-shrink:0"></i>
                        <span><strong>Demo:</strong> <?= htmlspecialchars($success) ?></span>
                    </div>
                <?php endif; ?>

                <?php if (!empty($error)): ?>
                    <div class="alert-error">
                        <i class="fas fa-circle-xmark" style="margin-top:2px;flex-shrink:0"></i>
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form action="index.php?controller=auth&action=postVerifyOTP" method="POST">
                    <div class="form-group">
                        <label class="form-label">Mã OTP</label>
                        <div class="otp-input-wrap">
                            <input type="number" name="otp" placeholder="000000" required autofocus
                                   min="100000" max="999999">
                        </div>
                        <p class="otp-expire"><i class="fas fa-hourglass-half"></i> Mã sẽ hết hạn sau 5 phút</p>
                    </div>

                    <button type="submit" class="btn-submit">
                        <i class="fas fa-check-circle"></i> Xác nhận mã OTP
                    </button>
                </form>

                <div class="auth-switch">
                    Không nhận được mã?
                    <a href="index.php?controller=auth&action=forgotPassword">Gửi lại</a>
                    &nbsp;·&nbsp;
                    <a href="index.php?controller=auth&action=login"><i class="fas fa-arrow-left"></i> Đăng nhập</a>
                </div>
            </div>
        </div>
    </div>
    </body>
    </html>