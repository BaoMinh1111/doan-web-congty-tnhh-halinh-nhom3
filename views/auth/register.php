<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// Đã đăng nhập → redirect
if (isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$errors = [];
$old    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username        = trim($_POST['username']         ?? '');
    $fullname        = trim($_POST['fullname']         ?? '');
    $email           = trim($_POST['email']            ?? '');
    $phone           = trim($_POST['phone']            ?? '');
    $address         = trim($_POST['address']          ?? '');
    $password        = $_POST['password']              ?? '';
    $confirm_password = $_POST['confirm_password']     ?? '';
    $old = compact('username', 'fullname', 'email', 'phone', 'address');

    // Validate
    if (empty($username))
        $errors['username'] = 'Vui lòng nhập tên đăng nhập.';
    elseif (mb_strlen($username) < 3)
        $errors['username'] = 'Tên đăng nhập phải có ít nhất 3 ký tự.';
    elseif (!preg_match('/^[a-zA-Z0-9_\-]+$/', $username))
        $errors['username'] = 'Chỉ chữ cái, số, dấu _ và -.';

    if (empty($fullname))
        $errors['fullname'] = 'Vui lòng nhập họ và tên.';

    if (empty($email))
        $errors['email'] = 'Vui lòng nhập email.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL))
        $errors['email'] = 'Email không đúng định dạng.';

    if (empty($password))
        $errors['password'] = 'Vui lòng nhập mật khẩu.';
    elseif (mb_strlen($password) < 6)
        $errors['password'] = 'Mật khẩu phải có ít nhất 6 ký tự.';

    if (empty($confirm_password))
        $errors['confirm_password'] = 'Vui lòng xác nhận mật khẩu.';
    elseif ($password !== $confirm_password)
        $errors['confirm_password'] = 'Mật khẩu xác nhận không khớp.';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký — HàLinhTech</title>

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
            --success:      #22c55e;
            --danger:       #ef4444;
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

        /* ===== LAYOUT ===== */
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
                    radial-gradient(ellipse 70% 50% at 80% 90%, rgba(233,69,96,.2) 0%, transparent 60%),
                    radial-gradient(ellipse 50% 70% at 10% 20%, rgba(245,166,35,.1) 0%, transparent 50%);
        }
        .brand-grid {
            position: absolute; inset: 0;
            background-image:
                    linear-gradient(rgba(255,255,255,.025) 1px, transparent 1px),
                    linear-gradient(90deg, rgba(255,255,255,.025) 1px, transparent 1px);
            background-size: 48px 48px;
        }
        .brand-circle { position: absolute; border-radius: 50%; border: 1px solid rgba(255,255,255,.06); }
        .bc1 { width: 280px; height: 280px; bottom: -60px; right: -60px; }
        .bc2 { width: 160px; height: 160px; top: 80px; right: 40px; background: rgba(233,69,96,.04); border-color: rgba(233,69,96,.12); }
        .bc3 { width: 100px; height: 100px; top: 40%; left: -30px; background: rgba(245,166,35,.05); border-color: rgba(245,166,35,.12); }

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
            display: flex; flex-direction: column; justify-content: center; padding-block: 40px;
        }
        .brand-tagline {
            font-size: .72rem; font-weight: 700; letter-spacing: .16em;
            text-transform: uppercase; color: var(--gold); margin-bottom: 16px;
            display: flex; align-items: center; gap: 10px;
        }
        .brand-tagline::before { content: ''; width: 28px; height: 2px; background: var(--gold); }
        .brand-headline {
            font-family: var(--font-display);
            font-size: clamp(1.8rem, 3vw, 2.5rem); font-weight: 700;
            color: #fff; line-height: 1.2; margin-bottom: 16px;
        }
        .brand-headline em { font-style: normal; color: var(--accent); }
        .brand-desc { color: rgba(255,255,255,.48); font-size: .88rem; line-height: 1.75; max-width: 300px; }

        .brand-steps { margin-top: 36px; display: flex; flex-direction: column; gap: 14px; }
        .brand-step { display: flex; align-items: center; gap: 14px; }
        .step-num {
            width: 32px; height: 32px; flex-shrink: 0; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: .8rem; font-weight: 700;
            background: rgba(255,255,255,.08); color: rgba(255,255,255,.55);
            border: 1px solid rgba(255,255,255,.12);
        }
        .brand-step.done .step-num { background: rgba(34,197,94,.2); border-color: rgba(34,197,94,.3); color: var(--success); }
        .step-text { color: rgba(255,255,255,.55); font-size: .84rem; }
        .brand-step.done .step-text { color: rgba(255,255,255,.8); }

        .brand-bottom { position: relative; z-index: 1; }
        .brand-trust {
            display: flex; align-items: center; gap: 10px;
            padding: 14px 18px;
            background: rgba(255,255,255,.05);
            border: 1px solid rgba(255,255,255,.08);
            border-radius: 12px;
            color: rgba(255,255,255,.55); font-size: .82rem;
        }
        .brand-trust i { color: var(--success); font-size: 1rem; flex-shrink: 0; }

        /* ===== PANEL PHẢI ===== */
        .auth-form-panel {
            display: flex; flex-direction: column; justify-content: center;
            padding: 40px clamp(28px, 5vw, 72px);
            background: var(--bg-white); overflow-y: auto;
        }
        .auth-form-header { margin-bottom: 28px; }
        .auth-form-header h1 {
            font-family: var(--font-display);
            font-size: 1.9rem; font-weight: 700; color: var(--text-dark); margin-bottom: 6px;
        }
        .auth-form-header p { color: var(--text-light); font-size: .9rem; }
        .auth-form-header a { color: var(--accent); font-weight: 600; }
        .auth-form-header a:hover { text-decoration: underline; }

        .auth-form { max-width: 440px; width: 100%; }

        /* Global error */
        .global-error {
            display: flex; align-items: center; gap: 10px;
            padding: 12px 16px;
            background: rgba(239,68,68,.08);
            border: 1px solid rgba(239,68,68,.2);
            border-left: 3px solid var(--danger);
            border-radius: 12px; color: #991b1b; font-size: .875rem;
            margin-bottom: 18px;
        }

        /* Form fields */
        .form-group { margin-bottom: 16px; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .form-label { display: block; font-size: .82rem; font-weight: 600; color: var(--text-dark); margin-bottom: 5px; }
        .form-label .req { color: var(--accent); }

        .input-wrap { position: relative; }
        .input-wrap .icon-left {
            position: absolute; left: 13px; top: 50%;
            transform: translateY(-50%);
            color: var(--text-light); font-size: .875rem; pointer-events: none;
            transition: color .2s;
        }
        .input-wrap:focus-within .icon-left { color: var(--accent); }
        .input-wrap input,
        .input-wrap textarea {
            width: 100%; padding: 11px 16px 11px 40px;
            background: var(--bg-page); border: 1.5px solid var(--border);
            border-radius: 12px; font-family: var(--font-body); font-size: .875rem;
            color: var(--text-dark); outline: none; transition: var(--transition);
            resize: none;
        }
        .input-wrap input::placeholder,
        .input-wrap textarea::placeholder { color: var(--text-light); }
        .input-wrap input:focus,
        .input-wrap textarea:focus {
            border-color: var(--accent); background: var(--bg-white);
            box-shadow: 0 0 0 3px rgba(233,69,96,.1);
        }
        .input-wrap input.is-error { border-color: var(--danger); }
        .input-wrap textarea { padding-top: 10px; padding-bottom: 10px; }
        .input-wrap .icon-left.top { top: 14px; transform: none; }

        .toggle-pass {
            position: absolute; right: 11px; top: 50%; transform: translateY(-50%);
            color: var(--text-light); cursor: pointer; font-size: .85rem;
            padding: 4px; transition: color .2s;
        }
        .toggle-pass:hover { color: var(--accent); }

        .field-error {
            display: flex; align-items: center; gap: 5px;
            font-size: .77rem; color: var(--danger); font-weight: 500; margin-top: 4px;
        }
        .field-error i { font-size: .7rem; }

        /* Strength */
        .strength-bar { height: 4px; background: var(--border); border-radius: 99px; overflow: hidden; margin-top: 8px; }
        .strength-fill { height: 100%; width: 0; border-radius: 99px; transition: all .4s; }
        .strength-label { font-size: .73rem; font-weight: 600; margin-top: 3px; display: block; }

        /* Submit */
        .btn-submit {
            width: 100%; padding: 13px;
            background: var(--accent); color: #fff; border: none;
            border-radius: 12px; font-family: var(--font-body); font-size: .95rem; font-weight: 600;
            cursor: pointer; transition: var(--transition);
            display: flex; align-items: center; justify-content: center; gap: 8px;
            box-shadow: 0 4px 16px rgba(233,69,96,.3); margin-top: 4px;
        }
        .btn-submit:hover { background: var(--accent-hover); transform: translateY(-1px); box-shadow: 0 6px 20px rgba(233,69,96,.4); }
        .btn-submit:disabled { opacity: .65; cursor: not-allowed; transform: none; }

        .auth-switch {
            margin-top: 20px; padding-top: 18px;
            border-top: 1px solid var(--border);
            text-align: center; font-size: .875rem; color: var(--text-light);
        }
        .auth-switch a { color: var(--accent); font-weight: 600; }
        .auth-switch a:hover { text-decoration: underline; }

        /* Mobile logo */
        .mobile-logo { display: flex; align-items: center; gap: 10px; margin-bottom: 24px; }
        .mobile-logo-icon {
            width: 36px; height: 36px;
            background: linear-gradient(135deg, var(--accent), #ff7043);
            border-radius: 9px; display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 1rem;
        }
        .mobile-logo-text { font-family: var(--font-display); font-size: 1.4rem; font-weight: 700; color: var(--text-dark); }
        .mobile-logo-text span { color: var(--accent); }

        @media (max-width: 900px) {
            .auth-layout { grid-template-columns: 1fr; }
            .auth-brand  { display: none; }
            .auth-form-panel { justify-content: flex-start; padding: 36px 24px; min-height: 100vh; }
            .form-row { grid-template-columns: 1fr; }
        }
        @media (min-width: 901px) { .mobile-logo { display: none; } }

        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>

<div class="auth-layout">

    <!-- ===== PANEL TRÁI ===== -->
    <div class="auth-brand">
        <div class="brand-grid"></div>
        <div class="brand-circle bc1"></div>
        <div class="brand-circle bc2"></div>
        <div class="brand-circle bc3"></div>

        <div class="brand-top">
            <a href="index.php" class="brand-logo">
                <div class="brand-logo-icon"><i class="fas fa-bag-shopping"></i></div>
                <div class="brand-logo-text">HàLinh<span>Tech</span></div>
            </a>
        </div>

        <div class="brand-middle">
            <div class="brand-tagline">Tạo tài khoản</div>
            <h2 class="brand-headline">Bắt đầu hành trình<br>mua sắm <em>cùng chúng tôi.</em></h2>
            <p class="brand-desc">Chỉ mất 1 phút để tạo tài khoản và tận hưởng hàng nghìn ưu đãi độc quyền dành cho thành viên.</p>
            <div class="brand-steps">
                <div class="brand-step done">
                    <div class="step-num"><i class="fas fa-check" style="font-size:.7rem"></i></div>
                    <div class="step-text">Truy cập HàLinhTech</div>
                </div>
                <div class="brand-step done">
                    <div class="step-num"><i class="fas fa-check" style="font-size:.7rem"></i></div>
                    <div class="step-text">Điền thông tin đăng ký</div>
                </div>
                <div class="brand-step">
                    <div class="step-num">3</div>
                    <div class="step-text">Xác nhận email của bạn</div>
                </div>
                <div class="brand-step">
                    <div class="step-num">4</div>
                    <div class="step-text">Bắt đầu mua sắm!</div>
                </div>
            </div>
        </div>

        <div class="brand-bottom">
            <div class="brand-trust">
                <i class="fas fa-shield-halved"></i>
                Thông tin của bạn được mã hoá và bảo mật tuyệt đối.
            </div>
        </div>
    </div>

    <!-- ===== PANEL PHẢI ===== -->
    <div class="auth-form-panel">

        <!-- Logo mobile -->
        <a href="index.php" class="mobile-logo">
            <div class="mobile-logo-icon"><i class="fas fa-bag-shopping"></i></div>
            <div class="mobile-logo-text">HàLinh<span>Tech</span></div>
        </a>

        <div class="auth-form-header">
            <h1>Tạo tài khoản</h1>
            <p>Đã có tài khoản? <a href="index.php?controller=auth&action=login">Đăng nhập ngay</a></p>
        </div>

        <form class="auth-form" action="index.php?controller=auth&action=postRegister" method="POST" id="registerForm">

            <?php if (!empty($errors['_global'])): ?>
                <div class="global-error">
                    <i class="fas fa-circle-xmark"></i>
                    <?= htmlspecialchars($errors['_global']) ?>
                </div>
            <?php endif; ?>

            <!-- Username + Họ tên -->
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Tên đăng nhập <span class="req">*</span></label>
                    <div class="input-wrap">
                        <i class="fas fa-user icon-left"></i>
                        <input type="text" name="username" placeholder="vd: nguyenvana"
                               value="<?= htmlspecialchars($old['username'] ?? '') ?>"
                               class="<?= isset($errors['username']) ? 'is-error' : '' ?>"
                               autocomplete="username" required autofocus>
                    </div>
                    <?php if (isset($errors['username'])): ?>
                        <div class="field-error"><i class="fas fa-circle-exclamation"></i><?= htmlspecialchars($errors['username']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label class="form-label">Họ và tên <span class="req">*</span></label>
                    <div class="input-wrap">
                        <i class="fas fa-id-card icon-left"></i>
                        <input type="text" name="fullname" placeholder="Nhập họ tên đầy đủ"
                               value="<?= htmlspecialchars($old['fullname'] ?? '') ?>"
                               class="<?= isset($errors['fullname']) ? 'is-error' : '' ?>"
                               required>
                    </div>
                    <?php if (isset($errors['fullname'])): ?>
                        <div class="field-error"><i class="fas fa-circle-exclamation"></i><?= htmlspecialchars($errors['fullname']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Email + Số điện thoại -->
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Email <span class="req">*</span></label>
                    <div class="input-wrap">
                        <i class="fas fa-envelope icon-left"></i>
                        <input type="email" name="email" placeholder="email@gmail.com"
                               value="<?= htmlspecialchars($old['email'] ?? '') ?>"
                               class="<?= isset($errors['email']) ? 'is-error' : '' ?>"
                               autocomplete="email" required>
                    </div>
                    <?php if (isset($errors['email'])): ?>
                        <div class="field-error"><i class="fas fa-circle-exclamation"></i><?= htmlspecialchars($errors['email']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label class="form-label">Số điện thoại</label>
                    <div class="input-wrap">
                        <i class="fas fa-phone icon-left"></i>
                        <input type="tel" name="phone" placeholder="090xxxxxxx"
                               value="<?= htmlspecialchars($old['phone'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <!-- Địa chỉ -->
            <div class="form-group">
                <label class="form-label">Địa chỉ giao hàng</label>
                <div class="input-wrap">
                    <i class="fas fa-location-dot icon-left top"></i>
                    <textarea name="address" rows="2" placeholder="Số nhà, tên đường, phường/xã..."><?= htmlspecialchars($old['address'] ?? '') ?></textarea>
                </div>
            </div>

            <!-- Mật khẩu + Xác nhận -->
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Mật khẩu <span class="req">*</span></label>
                    <div class="input-wrap">
                        <i class="fas fa-lock icon-left"></i>
                        <input type="password" name="password" id="passwordInput"
                               placeholder="Ít nhất 6 ký tự"
                               class="<?= isset($errors['password']) ? 'is-error' : '' ?>"
                               autocomplete="new-password" required
                               oninput="checkStrength(this.value)">
                        <span class="toggle-pass" onclick="togglePass('passwordInput', this)">
                            <i class="fas fa-eye"></i>
                        </span>
                    </div>
                    <?php if (isset($errors['password'])): ?>
                        <div class="field-error"><i class="fas fa-circle-exclamation"></i><?= htmlspecialchars($errors['password']) ?></div>
                    <?php endif; ?>
                    <div id="strengthWrap" style="display:none">
                        <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
                        <span class="strength-label" id="strengthLabel"></span>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Xác nhận mật khẩu <span class="req">*</span></label>
                    <div class="input-wrap">
                        <i class="fas fa-lock-open icon-left"></i>
                        <input type="password" name="confirm_password" id="confirmInput"
                               placeholder="Nhập lại mật khẩu"
                               class="<?= isset($errors['confirm_password']) ? 'is-error' : '' ?>"
                               autocomplete="new-password" required
                               oninput="checkConfirm(this.value)">
                        <span class="toggle-pass" onclick="togglePass('confirmInput', this)">
                            <i class="fas fa-eye"></i>
                        </span>
                    </div>
                    <?php if (isset($errors['confirm_password'])): ?>
                        <div class="field-error"><i class="fas fa-circle-exclamation"></i><?= htmlspecialchars($errors['confirm_password']) ?></div>
                    <?php else: ?>
                        <div class="field-error" id="confirmError" style="display:none">
                            <i class="fas fa-circle-exclamation"></i> Mật khẩu không khớp.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Submit -->
            <button type="submit" class="btn-submit" id="submitBtn">
                <i class="fas fa-user-plus"></i> Tạo tài khoản ngay
            </button>

            <div class="auth-switch">
                Đã có tài khoản?
                <a href="index.php?controller=auth&action=login">Đăng nhập →</a>
            </div>

        </form>
    </div>
</div>

<script>
    function togglePass(id, btn) {
        const input = document.getElementById(id);
        const icon  = btn.querySelector('i');
        input.type  = input.type === 'password' ? 'text' : 'password';
        icon.className = input.type === 'password' ? 'fas fa-eye' : 'fas fa-eye-slash';
    }

    function checkStrength(val) {
        const wrap  = document.getElementById('strengthWrap');
        const fill  = document.getElementById('strengthFill');
        const label = document.getElementById('strengthLabel');
        if (!val) { wrap.style.display = 'none'; return; }
        wrap.style.display = 'block';

        let score = 0;
        if (val.length >= 6)           score++;
        if (/[a-z]/.test(val))         score++;
        if (/[A-Z]/.test(val))         score++;
        if (/[0-9]/.test(val))         score++;
        if (/[^a-zA-Z0-9]/.test(val)) score++;

        const levels = ['', '#ef4444', '#ef4444', '#f59e0b', '#3b82f6', '#22c55e'];
        const labels = ['', 'Yếu', 'Yếu', 'Trung bình', 'Khá', 'Mạnh'];
        const widths = ['', '25%', '25%', '50%', '75%', '100%'];
        fill.style.width      = widths[score]  || '25%';
        fill.style.background = levels[score]  || '#ef4444';
        label.textContent     = labels[score]  || 'Yếu';
        label.style.color     = levels[score]  || '#ef4444';
    }

    function checkConfirm(val) {
        const pw  = document.getElementById('passwordInput').value;
        const inp = document.getElementById('confirmInput');
        const err = document.getElementById('confirmError');
        if (err) {
            if (val && pw !== val) { err.style.display = 'flex'; inp.classList.add('is-error'); }
            else { err.style.display = 'none'; inp.classList.remove('is-error'); }
        }
    }

    document.getElementById('registerForm').addEventListener('submit', function() {
        const btn = document.getElementById('submitBtn');
        btn.innerHTML = '<span style="width:16px;height:16px;border:2px solid rgba(255,255,255,.4);border-top-color:#fff;border-radius:50%;animation:spin .7s linear infinite;display:inline-block"></span> Đang tạo tài khoản...';
        btn.disabled = true;
    });
</script>

</body>
</html>
