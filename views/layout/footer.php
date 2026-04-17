</div><!-- /.container.mt-4 -->

<!-- ==================== FOOTER ==================== -->
<footer class="site-footer">

    <!-- NEWSLETTER -->
    <div class="footer__newsletter">
        <div class="footer__newsletter-inner">
            <div class="newsletter__text">
                <h3>Đăng ký nhận ưu đãi</h3>
                <p>Nhận ngay voucher <strong>50.000đ</strong> cho đơn đầu tiên + cập nhật khuyến mãi mới nhất!</p>
            </div>
            <form class="newsletter__form" onsubmit="handleNewsletter(event, this)">
                <div class="newsletter__input-group">
                    <input type="email" name="email" placeholder="Nhập email của bạn..." required autocomplete="email">
                    <button type="submit"><i class="fas fa-paper-plane"></i> Đăng ký</button>
                </div>
                <p class="newsletter__note"><i class="fas fa-lock"></i> Chúng tôi tôn trọng quyền riêng tư. Hủy bất kỳ lúc nào.</p>
            </form>
        </div>
    </div>

    <!-- FOOTER CHÍNH -->
    <div class="footer__main">
        <div class="footer__main-inner">

            <!-- CỘT 1: THƯƠNG HIỆU -->
            <div class="footer__col footer__col--brand">
                <div class="footer__logo">
                    <div class="footer__logo-icon"><i class="fas fa-bag-shopping"></i></div>
                    <span>HàLinh<strong>Tech</strong></span>
                </div>
                <p class="footer__tagline">Chuyên cung cấp linh kiện & thiết bị công nghệ chính hãng — Uy tín, giao hàng nhanh.</p>
                <div class="footer__social">

                </div>
            </div>

            <!-- CỘT 2: HỖ TRỢ -->
            <div class="footer__col">
                <h4 class="footer__col-title">Hỗ trợ khách hàng</h4>
                <ul class="footer__links">
                    <li><a href="faq.php"><i class="fas fa-angle-right"></i> Câu hỏi thường gặp</a></li>
                    <li><a href="index.php?controller=order&action=history"><i class="fas fa-angle-right"></i> Theo dõi đơn hàng</a></li>
                </ul>
            </div>

            <!-- CỘT 3: VỀ CHÚNG TÔI -->
            <div class="footer__col">
                <h4 class="footer__col-title">Về HàLinhTech</h4>
                <ul class="footer__links">
                    <li><a href="gioi-thieu.php"><i class="fas fa-angle-right"></i> Giới thiệu</a></li>
                    <li><a href="bao-mat.php"><i class="fas fa-angle-right"></i> Chính sách bảo mật</a></li>
                </ul>
            </div>

            <!-- CỘT 4: LIÊN HỆ -->
            <div class="footer__col">
                <h4 class="footer__col-title">Liên hệ</h4>
                <ul class="footer__contact">
                    <li><i class="fas fa-location-dot"></i><span>90, đường số 3, KDC 13E, Bình Hưng, TP.HCM</span></li>
                    <li><i class="fas fa-phone"></i><span>1800 1904 (miễn phí)</span></li>
                    <li><i class="fas fa-envelope"></i><span>supporthalinhtech@gmail.com</span></li>
                    <li><i class="fas fa-clock"></i><span>Thứ 2 – CN: 8:00 – 22:00</span></li>
                </ul>
                <div class="footer__payment">
                    <p>Phương thức thanh toán</p>
                    <div class="payment-icons">
                        <span class="payment-icon" title="Visa"><i class="fab fa-cc-visa"></i></span>
                        <span class="payment-icon" title="Mastercard"><i class="fab fa-cc-mastercard"></i></span>
                        <span class="payment-icon" title="PayPal"><i class="fab fa-cc-paypal"></i></span>
                        <span class="payment-icon" title="ZaloPay" style="font-size:.65rem;font-weight:700;color:#006af5;">ZaloPay</span>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- FOOTER BOTTOM -->
    <div class="footer__bottom">
        <div class="footer__bottom-inner">
            <p>© <?= date('Y') ?> HàLinhTech. Tất cả quyền được bảo lưu.</p>
            <div class="footer__bottom-links">
                <a href="#">Điều khoản</a>
                <a href="#">Bảo mật</a>
                <a href="#">Sitemap</a>
            </div>
            <p class="footer__bottom-note"><i class="fas fa-shield-halved"></i> Đã đăng ký Bộ Công Thương</p>
        </div>
    </div>

</footer>

<!-- SCROLL TO TOP -->
<button class="scroll-top" id="scrollTopBtn" aria-label="Lên đầu trang">
    <i class="fas fa-arrow-up"></i>
</button>

<!-- TOAST -->
<div class="toast-container" id="toastContainer"></div>

<!-- JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // ===== GIỎ HÀNG AJAX =====
    function addToCart(productId) {
        $.ajax({
            url: 'index.php?controller=cart&action=addAjax',
            type: 'POST',
            data: { product_id: productId, quantity: 1 },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    showToast('Đã thêm vào giỏ hàng!', 'success');
                    $('#cart-count').text(response.data.count);
                } else {
                    showToast(response.message, 'error');
                }
            },
            error: function() {
                showToast('Có lỗi xảy ra, vui lòng thử lại.', 'error');
            }
        });
    }

    // ===== AUTOCOMPLETE TÌM KIẾM =====
    $(document).ready(function() {
        $('#search-input').on('keyup', function() {
            let keyword = $(this).val();
            if (keyword.length >= 1) {
                $.ajax({
                    url: 'index.php?controller=product&action=suggestAjax',
                    type: 'GET',
                    data: { keyword: keyword },
                    success: function(data) {
                        $('#search-results-ajax').html(data).show();
                    }
                });
            } else {
                $('#search-results-ajax').hide();
            }
        });

        $(document).on('click', function(e) {
            if (!$(e.target).closest('.search-wrapper, .input-group').length) {
                $('#search-results-ajax').hide();
            }
        });
    });

    // ===== SCROLL TO TOP =====
    (function() {
        const btn = document.getElementById('scrollTopBtn');
        window.addEventListener('scroll', () => {
            btn.classList.toggle('show', window.scrollY > 400);
        }, { passive: true });
        btn?.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
    })();

    // ===== NEWSLETTER =====
    function handleNewsletter(e, form) {
        e.preventDefault();
        showToast('Đăng ký thành công! Kiểm tra email của bạn nhé.', 'success');
        form.reset();
    }

    // ===== TOAST =====
    function showToast(message, type = 'info', duration = 3500) {
        const container = document.getElementById('toastContainer');
        if (!container) return;
        const icons = { success: 'fa-circle-check', error: 'fa-circle-xmark', info: 'fa-circle-info' };
        const toast = document.createElement('div');
        toast.className = `toast-item ${type}`;
        toast.innerHTML = `<i class="fas ${icons[type]}"></i><span>${message}</span>`;
        container.appendChild(toast);
        requestAnimationFrame(() => requestAnimationFrame(() => toast.classList.add('show')));
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 400);
        }, duration);
    }
    window.showToast = showToast;
</script>

<style>
    /* ===== FOOTER ===== */
    .site-footer { font-family: var(--font-body, 'DM Sans', sans-serif); }

    /* NEWSLETTER */
    .footer__newsletter {
        background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
        padding: 48px 24px;
    }
    .footer__newsletter-inner {
        max-width: 1320px; margin: 0 auto;
        display: flex; align-items: center; gap: 48px; flex-wrap: wrap;
    }
    .newsletter__text { flex: 1; min-width: 240px; }
    .newsletter__text h3 {
        font-family: var(--font-display, 'Playfair Display', serif);
        font-size: 1.6rem; font-weight: 700; color: #fff; margin-bottom: 8px;
    }
    .newsletter__text p { color: rgba(255,255,255,.65); font-size: .875rem; }
    .newsletter__text strong { color: #f5a623; }
    .newsletter__form { flex: 1; min-width: 280px; }
    .newsletter__input-group {
        display: flex; gap: 8px;
        background: rgba(255,255,255,.1);
        border: 1px solid rgba(255,255,255,.2);
        border-radius: 50px;
        padding: 5px 5px 5px 20px;
    }
    .newsletter__input-group input {
        flex: 1; background: transparent; border: none; outline: none;
        color: #fff; font-family: inherit; font-size: .875rem;
    }
    .newsletter__input-group input::placeholder { color: rgba(255,255,255,.35); }
    .newsletter__input-group button {
        background: #e94560; color: #fff; border: none;
        border-radius: 50px; padding: 8px 20px;
        font-size: .85rem; font-weight: 600; cursor: pointer;
        display: flex; align-items: center; gap: 6px;
        transition: background .2s;
    }
    .newsletter__input-group button:hover { background: #c73652; }
    .newsletter__note {
        margin-top: 10px; font-size: .75rem;
        color: rgba(255,255,255,.35);
        display: flex; align-items: center; gap: 6px;
    }

    /* FOOTER MAIN */
    .footer__main { background: #12122a; padding: 52px 24px; }
    .footer__main-inner {
        max-width: 1320px; margin: 0 auto;
        display: grid;
        grid-template-columns: 1.4fr 1fr 1fr 1.2fr;
        gap: 48px;
    }

    /* BRAND */
    .footer__logo {
        display: flex; align-items: center; gap: 10px; margin-bottom: 14px;
    }
    .footer__logo-icon {
        width: 36px; height: 36px;
        background: linear-gradient(135deg, #e94560, #ff7043);
        border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 1rem;
    }
    .footer__logo span {
        font-family: var(--font-display, 'Playfair Display', serif);
        font-size: 1.35rem; color: rgba(255,255,255,.9);
    }
    .footer__logo strong { color: #e94560; }
    .footer__tagline { color: rgba(255,255,255,.4); font-size: .85rem; line-height: 1.7; margin-bottom: 20px; }

    .footer__social { display: flex; gap: 8px; }
    .social-btn {
        width: 36px; height: 36px; border-radius: 50%;
        border: 1px solid rgba(255,255,255,.15);
        display: flex; align-items: center; justify-content: center;
        color: rgba(255,255,255,.5); font-size: .85rem;
        text-decoration: none; transition: all .2s;
    }
    .social-btn:hover { background: #e94560; border-color: #e94560; color: #fff; transform: translateY(-2px); }

    /* COL TITLES */
    .footer__col-title {
        color: #fff; font-size: .8rem; font-weight: 600;
        text-transform: uppercase; letter-spacing: .08em;
        margin-bottom: 18px; padding-bottom: 10px;
        border-bottom: 2px solid #e94560; display: inline-block;
    }

    /* LINKS */
    .footer__links { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 2px; }
    .footer__links a {
        display: flex; align-items: center; gap: 8px;
        padding: 7px 0; color: rgba(255,255,255,.4);
        font-size: .85rem; text-decoration: none; transition: all .2s;
    }
    .footer__links a i { font-size: .6rem; color: rgba(255,255,255,.2); transition: color .2s; }
    .footer__links a:hover { color: rgba(255,255,255,.85); padding-left: 6px; }
    .footer__links a:hover i { color: #e94560; }

    /* CONTACT */
    .footer__contact { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 12px; }
    .footer__contact li { display: flex; gap: 10px; color: rgba(255,255,255,.4); font-size: .85rem; line-height: 1.5; }
    .footer__contact li i { color: #e94560; width: 16px; text-align: center; margin-top: 2px; flex-shrink: 0; }
    .footer__contact a { color: rgba(255,255,255,.6); text-decoration: none; transition: color .2s; }
    .footer__contact a:hover { color: #fff; }

    /* PAYMENT */
    .footer__payment { margin-top: 20px; }
    .footer__payment p { font-size: .72rem; color: rgba(255,255,255,.35); text-transform: uppercase; letter-spacing: .08em; margin-bottom: 8px; }
    .payment-icons { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
    .payment-icon {
        display: flex; align-items: center; justify-content: center;
        height: 28px; padding: 0 8px;
        background: rgba(255,255,255,.07);
        border: 1px solid rgba(255,255,255,.1);
        border-radius: 4px; color: rgba(255,255,255,.6); font-size: 1.2rem;
        transition: background .2s;
    }
    .payment-icon:hover { background: rgba(255,255,255,.14); }

    /* BOTTOM */
    .footer__bottom { background: #0e0e22; padding: 14px 24px; border-top: 1px solid rgba(255,255,255,.06); }
    .footer__bottom-inner {
        max-width: 1320px; margin: 0 auto;
        display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;
    }
    .footer__bottom p { color: rgba(255,255,255,.3); font-size: .8rem; margin: 0; }
    .footer__bottom-links { display: flex; gap: 16px; }
    .footer__bottom-links a { color: rgba(255,255,255,.3); font-size: .8rem; text-decoration: none; transition: color .2s; }
    .footer__bottom-links a:hover { color: rgba(255,255,255,.7); }
    .footer__bottom-note { display: flex; align-items: center; gap: 6px; }
    .footer__bottom-note i { color: #f5a623; font-size: .75rem; }

    /* SCROLL TOP */
    .scroll-top {
        position: fixed; bottom: 24px; right: 24px;
        width: 44px; height: 44px;
        background: #e94560; color: #fff; border: none;
        border-radius: 50%; cursor: pointer;
        box-shadow: 0 4px 16px rgba(233,69,96,.4);
        display: flex; align-items: center; justify-content: center;
        font-size: .9rem; opacity: 0; visibility: hidden;
        transform: translateY(12px); transition: all .25s; z-index: 900;
    }
    .scroll-top.show { opacity: 1; visibility: visible; transform: translateY(0); }
    .scroll-top:hover { background: #c73652; transform: translateY(-2px); }

    /* TOAST */
    .toast-container { position: fixed; bottom: 80px; right: 24px; display: flex; flex-direction: column; gap: 8px; z-index: 9999; pointer-events: none; }
    .toast-item {
        display: flex; align-items: center; gap: 10px;
        padding: 12px 16px; background: #fff;
        border-left: 3px solid #e94560; border-radius: 10px;
        box-shadow: 0 8px 24px rgba(0,0,0,.12);
        font-size: .875rem; color: #1a1a2e;
        opacity: 0; transform: translateX(24px); transition: all .3s;
        pointer-events: all; max-width: 300px;
    }
    .toast-item.show { opacity: 1; transform: translateX(0); }
    .toast-item.success { border-left-color: #22c55e; }
    .toast-item.success i { color: #22c55e; }
    .toast-item.error i { color: #e94560; }
    .toast-item.info { border-left-color: #3b82f6; }
    .toast-item.info i { color: #3b82f6; }

    /* RESPONSIVE */
    @media (max-width: 1024px) {
        .footer__main-inner { grid-template-columns: 1fr 1fr; gap: 32px; }
        .footer__col--brand { grid-column: 1 / -1; }
    }
    @media (max-width: 600px) {
        .footer__main-inner { grid-template-columns: 1fr; }
        .footer__newsletter-inner { flex-direction: column; gap: 24px; }
        .footer__bottom-inner { flex-direction: column; align-items: flex-start; }
    }
</style>

</body>
</html>