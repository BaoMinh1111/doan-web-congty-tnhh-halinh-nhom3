<?php
session_start();
$pageTitle = 'Câu hỏi thường gặp';
require_once 'bootstrap.php';
require_once 'models/CategoryModel.php';
$categories = (new CategoryModel())->getAll();
?>
<?php include $_SERVER['DOCUMENT_ROOT'] . '/views/layout/header.php'; ?>

<style>
    /* ===== FAQ PAGE ===== */
    .faq-hero {
        background: linear-gradient(135deg, #1a1a2e 0%, #16213e 60%, #0f3460 100%);
        padding: 64px 24px 72px;
        text-align: center;
        position: relative;
        overflow: hidden;
    }
    .faq-hero::before {
        content: '';
        position: absolute; inset: 0;
        background: radial-gradient(ellipse at 70% 50%, rgba(233,69,96,.18) 0%, transparent 60%);
        pointer-events: none;
    }
    .faq-hero__badge {
        display: inline-flex; align-items: center; gap: 8px;
        background: rgba(233,69,96,.15);
        border: 1px solid rgba(233,69,96,.3);
        border-radius: 50px;
        padding: 6px 18px;
        color: #e94560; font-size: .8rem; font-weight: 600;
        letter-spacing: .06em; text-transform: uppercase;
        margin-bottom: 20px;
    }
    .faq-hero h1 {
        font-family: var(--font-display);
        font-size: clamp(1.8rem, 4vw, 2.8rem);
        color: #fff; font-weight: 700;
        margin-bottom: 14px; line-height: 1.2;
    }
    .faq-hero h1 span { color: #e94560; }
    .faq-hero p {
        color: rgba(255,255,255,.55);
        font-size: 1rem; max-width: 520px; margin: 0 auto 32px;
        line-height: 1.7;
    }
    .faq-search-bar {
        max-width: 520px; margin: 0 auto;
        display: flex;
        background: rgba(255,255,255,.08);
        border: 1.5px solid rgba(255,255,255,.18);
        border-radius: 50px;
        padding: 6px 6px 6px 22px;
        transition: border-color .25s, box-shadow .25s;
    }
    .faq-search-bar:focus-within {
        border-color: #e94560;
        box-shadow: 0 0 0 3px rgba(233,69,96,.2);
    }
    .faq-search-bar input {
        flex: 1; background: transparent; border: none; outline: none;
        color: #fff; font-family: var(--font-body); font-size: .9rem;
    }
    .faq-search-bar input::placeholder { color: rgba(255,255,255,.35); }
    .faq-search-bar button {
        background: #e94560; color: #fff; border: none;
        border-radius: 50px; padding: 9px 22px;
        font-size: .85rem; font-weight: 600; cursor: pointer;
        display: flex; align-items: center; gap: 6px;
        transition: background .2s; white-space: nowrap;
    }
    .faq-search-bar button:hover { background: #c73652; }

    /* BREADCRUMB */
    .faq-breadcrumb {
        background: #fff;
        border-bottom: 1px solid var(--border);
        padding: 12px 0;
    }
    .faq-breadcrumb .breadcrumb {
        margin: 0; font-size: .82rem;
        --bs-breadcrumb-divider-color: var(--text-light);
    }
    .faq-breadcrumb .breadcrumb-item a {
        color: var(--text-mid); text-decoration: none;
        transition: color .2s;
    }
    .faq-breadcrumb .breadcrumb-item a:hover { color: var(--accent); }
    .faq-breadcrumb .breadcrumb-item.active { color: var(--accent); font-weight: 500; }

    /* LAYOUT */
    .faq-layout {
        max-width: 1200px; margin: 0 auto;
        padding: 52px 24px 80px;
        display: grid;
        grid-template-columns: 240px 1fr;
        gap: 40px;
        align-items: start;
    }

    /* SIDEBAR */
    .faq-sidebar {
        position: sticky; top: 120px;
    }
    .faq-sidebar__title {
        font-size: .72rem; font-weight: 700; text-transform: uppercase;
        letter-spacing: .1em; color: var(--text-light);
        margin-bottom: 12px;
    }
    .faq-category-list { list-style: none; padding: 0; margin: 0; }
    .faq-category-list li { margin-bottom: 4px; }
    .faq-category-list a {
        display: flex; align-items: center; gap: 10px;
        padding: 9px 14px; border-radius: 10px;
        color: var(--text-mid); font-size: .875rem;
        text-decoration: none; transition: all .2s;
        font-weight: 500;
    }
    .faq-category-list a i {
        width: 18px; text-align: center;
        color: var(--text-light); font-size: .85rem;
        transition: color .2s;
    }
    .faq-category-list a .count {
        margin-left: auto;
        background: var(--bg-page);
        border: 1px solid var(--border);
        border-radius: 50px;
        font-size: .7rem; font-weight: 600;
        padding: 1px 8px; color: var(--text-light);
        transition: all .2s;
    }
    .faq-category-list a:hover,
    .faq-category-list a.active {
        background: rgba(233,69,96,.06);
        color: var(--accent);
    }
    .faq-category-list a:hover i,
    .faq-category-list a.active i { color: var(--accent); }
    .faq-category-list a:hover .count,
    .faq-category-list a.active .count {
        background: rgba(233,69,96,.1);
        border-color: rgba(233,69,96,.2);
        color: var(--accent);
    }

    .faq-sidebar__contact {
        margin-top: 28px;
        background: linear-gradient(135deg, #1a1a2e, #16213e);
        border-radius: 16px; padding: 22px;
        text-align: center;
    }
    .faq-sidebar__contact .icon {
        width: 48px; height: 48px;
        background: rgba(233,69,96,.15);
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        margin: 0 auto 14px;
        color: #e94560; font-size: 1.1rem;
    }
    .faq-sidebar__contact h5 {
        color: #fff; font-size: .9rem; font-weight: 600; margin-bottom: 6px;
    }
    .faq-sidebar__contact p {
        color: rgba(255,255,255,.45); font-size: .78rem;
        line-height: 1.6; margin-bottom: 16px;
    }
    .faq-sidebar__contact a {
        display: inline-flex; align-items: center; gap: 7px;
        background: #e94560; color: #fff;
        border-radius: 50px; padding: 9px 20px;
        font-size: .82rem; font-weight: 600; text-decoration: none;
        transition: background .2s;
    }
    .faq-sidebar__contact a:hover { background: #c73652; }

    /* MAIN CONTENT */
    .faq-main {}
    .faq-section { margin-bottom: 44px; }
    .faq-section__header {
        display: flex; align-items: center; gap: 12px;
        margin-bottom: 20px;
    }
    .faq-section__icon {
        width: 42px; height: 42px;
        background: linear-gradient(135deg, rgba(233,69,96,.12), rgba(233,69,96,.06));
        border: 1px solid rgba(233,69,96,.2);
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        color: #e94560; font-size: 1rem; flex-shrink: 0;
    }
    .faq-section__title {
        font-family: var(--font-display);
        font-size: 1.2rem; font-weight: 700;
        color: var(--primary); margin: 0;
    }

    /* ACCORDION */
    .faq-accordion { display: flex; flex-direction: column; gap: 10px; }
    .faq-item {
        background: #fff;
        border: 1.5px solid var(--border);
        border-radius: 14px;
        overflow: hidden;
        transition: border-color .2s, box-shadow .2s;
    }
    .faq-item:hover { border-color: rgba(233,69,96,.3); }
    .faq-item.open {
        border-color: rgba(233,69,96,.4);
        box-shadow: 0 4px 24px rgba(233,69,96,.08);
    }
    .faq-question {
        display: flex; align-items: center; gap: 14px;
        padding: 18px 20px;
        cursor: pointer; user-select: none;
        transition: background .2s;
    }
    .faq-item.open .faq-question { background: rgba(233,69,96,.03); }
    .faq-q-num {
        width: 28px; height: 28px; flex-shrink: 0;
        background: var(--bg-page);
        border: 1.5px solid var(--border);
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: .7rem; font-weight: 700;
        color: var(--text-light);
        transition: all .2s;
    }
    .faq-item.open .faq-q-num {
        background: #e94560; border-color: #e94560; color: #fff;
    }
    .faq-question h3 {
        flex: 1; margin: 0;
        font-size: .925rem; font-weight: 600;
        color: var(--primary); line-height: 1.45;
        transition: color .2s;
    }
    .faq-item.open .faq-question h3 { color: #e94560; }
    .faq-chevron {
        width: 28px; height: 28px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center;
        color: var(--text-light); font-size: .8rem;
        transition: transform .3s, color .2s;
    }
    .faq-item.open .faq-chevron { transform: rotate(180deg); color: #e94560; }
    .faq-answer {
        max-height: 0; overflow: hidden;
        transition: max-height .35s ease, padding .35s ease;
    }
    .faq-item.open .faq-answer { max-height: 600px; }
    .faq-answer-inner {
        padding: 0 20px 20px 62px;
        color: var(--text-mid); font-size: .875rem; line-height: 1.8;
    }
    .faq-answer-inner p { margin: 0 0 10px; }
    .faq-answer-inner p:last-child { margin-bottom: 0; }
    .faq-answer-inner ul {
        padding-left: 18px; margin: 8px 0;
    }
    .faq-answer-inner ul li { margin-bottom: 6px; }
    .faq-answer-inner strong { color: var(--primary); }
    .faq-answer-inner .highlight-box {
        background: rgba(245,166,35,.08);
        border-left: 3px solid var(--gold);
        border-radius: 0 8px 8px 0;
        padding: 10px 14px; margin-top: 12px;
        font-size: .83rem; color: #7a5a10;
    }
    .faq-answer-inner .highlight-box i { color: var(--gold); margin-right: 6px; }

    /* NO RESULT */
    .faq-no-result {
        text-align: center; padding: 60px 20px;
        display: none;
    }
    .faq-no-result i { font-size: 2.5rem; color: var(--border); margin-bottom: 16px; display: block; }
    .faq-no-result p { color: var(--text-light); font-size: .9rem; }

    /* RESPONSIVE */
    @media (max-width: 900px) {
        .faq-layout { grid-template-columns: 1fr; }
        .faq-sidebar { position: static; }
    }
    @media (max-width: 600px) {
        .faq-hero { padding: 44px 20px 52px; }
        .faq-layout { padding: 32px 16px 60px; }
        .faq-answer-inner { padding-left: 20px; }
    }
</style>

<!-- HERO -->
<section class="faq-hero">
    <div class="faq-hero__badge"><i class="fas fa-circle-question"></i> Hỗ trợ khách hàng</div>
    <h1>Câu hỏi <span>thường gặp</span></h1>
    <p>Tìm nhanh câu trả lời cho những thắc mắc phổ biến nhất về đơn hàng, vận chuyển, thanh toán và chính sách của chúng tôi.</p>
    <div class="faq-search-bar" id="faqSearchBar">
        <input type="text" id="faqSearchInput" placeholder="Tìm kiếm câu hỏi..." autocomplete="off">
        <button type="button" onclick="searchFAQ()">
            <i class="fas fa-magnifying-glass"></i> Tìm kiếm
        </button>
    </div>
</section>

<!-- BREADCRUMB -->
<div class="faq-breadcrumb">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="/"><i class="fas fa-house me-1"></i>Trang chủ</a></li>
                <li class="breadcrumb-item active">Câu hỏi thường gặp</li>
            </ol>
        </nav>
    </div>
</div>

<!-- CONTENT -->
<div class="faq-layout" id="faqLayout">

    <!-- SIDEBAR -->
    <aside class="faq-sidebar">
        <p class="faq-sidebar__title">Danh mục</p>
        <ul class="faq-category-list">
            <li><a href="#" class="active" data-section="all" onclick="filterSection('all',this)"><i class="fas fa-th-large"></i> Tất cả<span class="count">24</span></a></li>
            <li><a href="#" data-section="order" onclick="filterSection('order',this)"><i class="fas fa-box"></i> Đặt hàng<span class="count">6</span></a></li>
            <li><a href="#" data-section="shipping" onclick="filterSection('shipping',this)"><i class="fas fa-truck"></i> Vận chuyển<span class="count">5</span></a></li>
            <li><a href="#" data-section="payment" onclick="filterSection('payment',this)"><i class="fas fa-credit-card"></i> Thanh toán<span class="count">5</span></a></li>
            <li><a href="#" data-section="return" onclick="filterSection('return',this)"><i class="fas fa-rotate-left"></i> Đổi trả<span class="count">4</span></a></li>
            <li><a href="#" data-section="account" onclick="filterSection('account',this)"><i class="fas fa-user-circle"></i> Tài khoản<span class="count">4</span></a></li>
        </ul>

        <div class="faq-sidebar__contact">
            <div class="icon"><i class="fas fa-headset"></i></div>
            <h5>Chưa tìm được câu trả lời?</h5>
            <p>Đội ngũ hỗ trợ của chúng tôi luôn sẵn sàng giúp bạn.</p>
            <a href="https://mail.google.com/mail/?view=cm&fs=1&to=supporthalinhtech@gmail.com
                &su=T%C3%B4i%20c%E1%BA%A7n%20h%E1%BB%97%20tr%E1%BB%A3%20t%E1%BB%AB%20H%C3%A0LinhTech..."
               target="_blank">
                <i class="fas fa-paper-plane"></i> Liên hệ ngay
            </a>
        </div>
    </aside>

    <!-- MAIN -->
    <main class="faq-main">
        <div id="faqNoResult" class="faq-no-result">
            <i class="fas fa-magnifying-glass-minus"></i>
            <p>Không tìm thấy câu hỏi phù hợp. Hãy thử từ khóa khác hoặc <a href="mailto:supporthalinhtech@gmail.com" style="color:var(--accent)">liên hệ hỗ trợ</a>.</p>
        </div>

        <!-- ĐẶT HÀNG -->
        <div class="faq-section" data-section="order">
            <div class="faq-section__header">
                <div class="faq-section__icon"><i class="fas fa-box-open"></i></div>
                <h2 class="faq-section__title">Đặt hàng</h2>
            </div>
            <div class="faq-accordion">

                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFAQ(this)">
                        <span class="faq-q-num">1</span>
                        <h3>Làm thế nào để đặt hàng trên HàLinhTech?</h3>
                        <span class="faq-chevron"><i class="fas fa-chevron-down"></i></span>
                    </div>
                    <div class="faq-answer">
                        <div class="faq-answer-inner">
                            <p>Bạn có thể đặt hàng theo các bước đơn giản sau:</p>
                            <ul>
                                <li>Tìm kiếm sản phẩm qua thanh tìm kiếm hoặc duyệt theo danh mục.</li>
                                <li>Chọn sản phẩm, số lượng rồi nhấn <strong>"Thêm vào giỏ"</strong>.</li>
                                <li>Vào giỏ hàng, kiểm tra và nhấn <strong>"Thanh toán"</strong>.</li>
                                <li>Điền địa chỉ giao hàng, chọn phương thức thanh toán rồi xác nhận đơn.</li>
                            </ul>
                            <div class="highlight-box"><i class="fas fa-lightbulb"></i> Đăng ký tài khoản để lưu địa chỉ và theo dõi đơn hàng dễ dàng hơn!</div>
                        </div>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFAQ(this)">
                        <span class="faq-q-num">2</span>
                        <h3>Tôi có thể đặt hàng mà không cần tài khoản không?</h3>
                        <span class="faq-chevron"><i class="fas fa-chevron-down"></i></span>
                    </div>
                    <div class="faq-answer">
                        <div class="faq-answer-inner">
                            <p>Hiện tại HàLinhTech cho phép khách hàng không cần đăng ký tài khoản vẫn có thể đặt hàng. Nhưng chúng tôi vẫn khuyến khích bạn tạo tài khoản.
                                Việc này giúp dễ dàng theo dõi đơn hàng, lưu địa chỉ giao hàng và nhận thông báo khuyến mãi.</p>
                            <p>Đăng ký hoàn toàn miễn phí và chỉ mất chưa đến 1 phút!</p>
                        </div>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFAQ(this)">
                        <span class="faq-q-num">3</span>
                        <h3>Tôi có thể hủy hoặc chỉnh sửa đơn hàng sau khi đặt không?</h3>
                        <span class="faq-chevron"><i class="fas fa-chevron-down"></i></span>
                    </div>
                    <div class="faq-answer">
                        <div class="faq-answer-inner">
                            <p>Bạn có thể hủy hoặc chỉnh sửa đơn hàng <strong>trong vòng 1 giờ</strong> sau khi đặt, miễn là đơn chưa được xác nhận xử lý.</p>
                            <p>Để hủy/chỉnh sửa, vào <strong>Lịch sử mua hàng</strong> → chọn đơn → nhấn "Hủy đơn". Nếu đơn đã được xử lý, hãy liên hệ hotline <strong>1800 1904</strong> để được hỗ trợ.</p>
                        </div>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFAQ(this)">
                        <span class="faq-q-num">4</span>
                        <h3>Làm sao để kiểm tra trạng thái đơn hàng của tôi?</h3>
                        <span class="faq-chevron"><i class="fas fa-chevron-down"></i></span>
                    </div>
                    <div class="faq-answer">
                        <div class="faq-answer-inner">
                            <p>Bạn có thể theo dõi đơn hàng qua:</p>
                            <ul>
                                <li>Đăng nhập tài khoản → mục <strong>"Lịch sử mua hàng"</strong>.</li>
                                <li>Email xác nhận đơn hàng (có mã theo dõi vận chuyển).</li>
                                <li>Liên hệ hotline <strong>1800 1904</strong> để kiểm tra trực tiếp.</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFAQ(this)">
                        <span class="faq-q-num">5</span>
                        <h3>Tôi có thể đặt hàng số lượng lớn (buôn/sỉ) không?</h3>
                        <span class="faq-chevron"><i class="fas fa-chevron-down"></i></span>
                    </div>
                    <div class="faq-answer">
                        <div class="faq-answer-inner">
                            <p>Có! HàLinhTech hỗ trợ mua sỉ cho cá nhân và doanh nghiệp. Vui lòng liên hệ qua email <strong>supporthalinhtech@gmail.com</strong> hoặc hotline <strong>1800 1904</strong> để nhận báo giá ưu đãi theo số lượng.</p>
                        </div>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFAQ(this)">
                        <span class="faq-q-num">6</span>
                        <h3>Sản phẩm trên HàLinhTech có chính hãng không?</h3>
                        <span class="faq-chevron"><i class="fas fa-chevron-down"></i></span>
                    </div>
                    <div class="faq-answer">
                        <div class="faq-answer-inner">
                            <p>Tất cả sản phẩm trên HàLinhTech đều <strong>100% chính hãng</strong>, có tem bảo hành chính thức từ nhà sản xuất hoặc nhà phân phối được ủy quyền.</p>
                            <div class="highlight-box"><i class="fas fa-shield-halved"></i> Nếu phát hiện hàng giả, chúng tôi hoàn tiền 100% và bồi thường theo chính sách bảo vệ người mua.</div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- VẬN CHUYỂN -->
        <div class="faq-section" data-section="shipping">
            <div class="faq-section__header">
                <div class="faq-section__icon"><i class="fas fa-truck-fast"></i></div>
                <h2 class="faq-section__title">Vận chuyển</h2>
            </div>
            <div class="faq-accordion">

                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFAQ(this)">
                        <span class="faq-q-num">1</span>
                        <h3>Thời gian giao hàng là bao lâu?</h3>
                        <span class="faq-chevron"><i class="fas fa-chevron-down"></i></span>
                    </div>
                    <div class="faq-answer">
                        <div class="faq-answer-inner">
                            <p>Thời gian giao hàng dự kiến:</p>
                            <ul>
                                <li><strong>Nội thành TP.HCM & Hà Nội:</strong> 1–2 ngày làm việc.</li>
                                <li><strong>Các tỉnh thành khác:</strong> 3–5 ngày làm việc.</li>
                                <li><strong>Vùng sâu, vùng xa:</strong> 5–7 ngày làm việc.</li>
                            </ul>
                            <div class="highlight-box"><i class="fas fa-clock"></i> Thời gian tính từ khi đơn hàng được xác nhận, không tính ngày lễ/Tết.</div>
                        </div>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFAQ(this)">
                        <span class="faq-q-num">2</span>
                        <h3>Phí vận chuyển được tính như thế nào?</h3>
                        <span class="faq-chevron"><i class="fas fa-chevron-down"></i></span>
                    </div>
                    <div class="faq-answer">
                        <div class="faq-answer-inner">
                            <p><strong>Miễn phí vận chuyển</strong> cho đơn hàng từ <strong>300.000đ</strong> toàn quốc.</p>
                            <p>Đơn dưới 300.000đ: phí giao hàng từ 15.000đ–35.000đ tùy khu vực, được hiển thị rõ khi thanh toán.</p>
                        </div>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFAQ(this)">
                        <span class="faq-q-num">3</span>
                        <h3>HàLinhTech có giao hàng nhanh trong ngày không?</h3>
                        <span class="faq-chevron"><i class="fas fa-chevron-down"></i></span>
                    </div>
                    <div class="faq-answer">
                        <div class="faq-answer-inner">
                            <p>Có! Dịch vụ <strong>Giao hỏa tốc</strong> áp dụng cho khu vực nội thành TP.HCM và Hà Nội, đặt hàng trước 14:00 sẽ được giao trong ngày. Phụ phí hỏa tốc từ 30.000đ tùy khoảng cách.</p>
                        </div>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFAQ(this)">
                        <span class="faq-q-num">4</span>
                        <h3>Tôi có thể thay đổi địa chỉ giao hàng sau khi đặt không?</h3>
                        <span class="faq-chevron"><i class="fas fa-chevron-down"></i></span>
                    </div>
                    <div class="faq-answer">
                        <div class="faq-answer-inner">
                            <p>Bạn có thể thay đổi địa chỉ giao hàng <strong>trước khi đơn hàng được giao cho đơn vị vận chuyển</strong>. Hãy liên hệ hotline <strong>1800 1904</strong> hoặc chat hỗ trợ càng sớm càng tốt.</p>
                        </div>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFAQ(this)">
                        <span class="faq-q-num">5</span>
                        <h3>Nếu tôi không nhận được hàng thì liên hệ ai?</h3>
                        <span class="faq-chevron"><i class="fas fa-chevron-down"></i></span>
                    </div>
                    <div class="faq-answer">
                        <div class="faq-answer-inner">
                            <p>Nếu quá thời gian dự kiến mà chưa nhận được hàng, vui lòng:</p>
                            <ul>
                                <li>Kiểm tra mã vận đơn qua email xác nhận đơn hàng.</li>
                                <li>Liên hệ hotline <strong>1800 1904</strong> (miễn phí, 8:00–22:00 mỗi ngày).</li>
                                <li>Gửi email tới <strong>supporthalinhtech@gmail.com</strong> kèm mã đơn hàng.</li>
                            </ul>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- THANH TOÁN -->
        <div class="faq-section" data-section="payment">
            <div class="faq-section__header">
                <div class="faq-section__icon"><i class="fas fa-credit-card"></i></div>
                <h2 class="faq-section__title">Thanh toán</h2>
            </div>
            <div class="faq-accordion">

                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFAQ(this)">
                        <span class="faq-q-num">1</span>
                        <h3>HàLinhTech hỗ trợ những hình thức thanh toán nào?</h3>
                        <span class="faq-chevron"><i class="fas fa-chevron-down"></i></span>
                    </div>
                    <div class="faq-answer">
                        <div class="faq-answer-inner">
                            <p>Chúng tôi hỗ trợ đa dạng phương thức thanh toán:</p>
                            <ul>
                                <li><strong>Thẻ nội địa/quốc tế:</strong> Visa, Mastercard, JCB.</li>
                                <li><strong>Ví điện tử:</strong> VNPay.</li>

                                <li><strong>Thanh toán khi nhận hàng (COD)</strong> cho đơn dưới 5.000.000đ.</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFAQ(this)">
                        <span class="faq-q-num">2</span>
                        <h3>Thanh toán trên HàLinhTech có an toàn không?</h3>
                        <span class="faq-chevron"><i class="fas fa-chevron-down"></i></span>
                    </div>
                    <div class="faq-answer">
                        <div class="faq-answer-inner">
                            <p>Hoàn toàn an toàn! Mọi giao dịch đều được mã hóa SSL 256-bit. Chúng tôi không lưu trữ thông tin thẻ ngân hàng của bạn — tất cả được xử lý qua cổng thanh toán bảo mật được cấp phép.</p>
                            <div class="highlight-box"><i class="fas fa-shield-halved"></i> HàLinhTech đã đăng ký với Bộ Công Thương và tuân thủ đầy đủ quy định về bảo vệ người tiêu dùng.</div>
                        </div>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFAQ(this)">
                        <span class="faq-q-num">3</span>
                        <h3>Tôi bị tính tiền nhưng đơn hàng chưa được xác nhận thì sao?</h3>
                        <span class="faq-chevron"><i class="fas fa-chevron-down"></i></span>
                    </div>
                    <div class="faq-answer">
                        <div class="faq-answer-inner">
                            <p>Trường hợp này thường xảy ra khi kết nối bị gián đoạn trong lúc thanh toán. Vui lòng kiểm tra email (kể cả hộp spam) để tìm xác nhận thanh toán. Nếu vẫn chưa có, liên hệ hotline <strong>1800 1904</strong> — chúng tôi sẽ xử lý trong vòng <strong>24 giờ</strong>.</p>
                        </div>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFAQ(this)">
                        <span class="faq-q-num">4</span>
                        <h3>Tôi có thể sử dụng voucher và mã giảm giá như thế nào?</h3>
                        <span class="faq-chevron"><i class="fas fa-chevron-down"></i></span>
                    </div>
                    <div class="faq-answer">
                        <div class="faq-answer-inner">
                            <p>Tại trang thanh toán, bạn sẽ thấy ô <strong>"Mã giảm giá"</strong>. Nhập mã voucher và nhấn <strong>"Áp dụng"</strong> để được trừ tiền ngay. Lưu ý:</p>
                            <ul>
                                <li>Mỗi đơn hàng chỉ áp dụng 1 mã voucher.</li>
                                <li>Voucher có thời hạn sử dụng, hãy kiểm tra ngày hết hạn.</li>
                                <li>Một số mã có giá trị đơn hàng tối thiểu.</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFAQ(this)">
                        <span class="faq-q-num">5</span>
                        <h3>Tôi có thể thanh toán trả góp không?</h3>
                        <span class="faq-chevron"><i class="fas fa-chevron-down"></i></span>
                    </div>
                    <div class="faq-answer">
                        <div class="faq-answer-inner">
                            <p>Có! HàLinhTech hỗ trợ trả góp <strong>0% lãi suất</strong> qua thẻ tín dụng Visa/Mastercard cho đơn hàng từ <strong>3.000.000đ</strong> trở lên, kỳ hạn 3–12 tháng. Chọn phương thức thanh toán trả góp khi checkout.</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- ĐỔI TRẢ -->
        <div class="faq-section" data-section="return">
            <div class="faq-section__header">
                <div class="faq-section__icon"><i class="fas fa-rotate-left"></i></div>
                <h2 class="faq-section__title">Đổi trả & Hoàn tiền</h2>
            </div>
            <div class="faq-accordion">

                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFAQ(this)">
                        <span class="faq-q-num">1</span>
                        <h3>Chính sách đổi trả của HàLinhTech như thế nào?</h3>
                        <span class="faq-chevron"><i class="fas fa-chevron-down"></i></span>
                    </div>
                    <div class="faq-answer">
                        <div class="faq-answer-inner">
                            <p>HàLinhTech hỗ trợ đổi trả trong <strong>30 ngày</strong> kể từ ngày nhận hàng với các điều kiện:</p>
                            <ul>
                                <li>Sản phẩm còn nguyên vẹn, chưa qua sử dụng.</li>
                                <li>Còn đầy đủ hộp, phụ kiện và tem bảo hành.</li>
                                <li>Có hóa đơn mua hàng hoặc mã đơn hàng.</li>
                            </ul>
                            <div class="highlight-box"><i class="fas fa-info-circle"></i> Sản phẩm lỗi do nhà sản xuất được đổi mới 1:1 trong vòng 7 ngày.</div>
                        </div>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFAQ(this)">
                        <span class="faq-q-num">2</span>
                        <h3>Tôi cần làm gì để yêu cầu đổi trả?</h3>
                        <span class="faq-chevron"><i class="fas fa-chevron-down"></i></span>
                    </div>
                    <div class="faq-answer">
                        <div class="faq-answer-inner">
                            <ul>
                                <li>Liên hệ hotline <strong>1800 1904</strong> hoặc email <strong>supporthalinhtech@gmail.com</strong>.</li>
                                <li>Cung cấp mã đơn hàng và mô tả lý do đổi trả (kèm ảnh nếu sản phẩm bị lỗi).</li>
                                <li>Đội ngũ sẽ xác nhận và hướng dẫn bạn gửi hàng lại trong <strong>24 giờ</strong>.</li>
                                <li>Chi phí vận chuyển đổi trả do HàLinhTech chịu nếu lỗi từ phía chúng tôi.</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFAQ(this)">
                        <span class="faq-q-num">3</span>
                        <h3>Tiền hoàn lại được xử lý trong bao lâu?</h3>
                        <span class="faq-chevron"><i class="fas fa-chevron-down"></i></span>
                    </div>
                    <div class="faq-answer">
                        <div class="faq-answer-inner">
                            <p>Sau khi nhận được hàng trả, chúng tôi sẽ kiểm tra và hoàn tiền trong:</p>
                            <ul>
                                <li><strong>Ví điện tử:</strong> 1–3 ngày làm việc.</li>
                                <li><strong>Thẻ tín dụng/ngân hàng:</strong> 5–10 ngày làm việc.</li>
                                <li><strong>COD:</strong> Chuyển khoản ngân hàng trong 3–5 ngày.</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFAQ(this)">
                        <span class="faq-q-num">4</span>
                        <h3>Sản phẩm nào không thuộc diện đổi trả?</h3>
                        <span class="faq-chevron"><i class="fas fa-chevron-down"></i></span>
                    </div>
                    <div class="faq-answer">
                        <div class="faq-answer-inner">
                            <p>Một số sản phẩm không áp dụng chính sách đổi trả:</p>
                            <ul>
                                <li>Sản phẩm đã được kích hoạt bảo hành hoặc đã sử dụng.</li>
                                <li>Phần mềm, license key, thẻ nạp điện thoại.</li>
                                <li>Sản phẩm có dấu hiệu tác động vật lý từ phía người dùng.</li>
                                <li>Hàng khuyến mãi được ghi rõ "Không đổi trả".</li>
                            </ul>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- TÀI KHOẢN -->
        <div class="faq-section" data-section="account">
            <div class="faq-section__header">
                <div class="faq-section__icon"><i class="fas fa-user-circle"></i></div>
                <h2 class="faq-section__title">Tài khoản</h2>
            </div>
            <div class="faq-accordion">

                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFAQ(this)">
                        <span class="faq-q-num">1</span>
                        <h3>Làm sao để đăng ký tài khoản?</h3>
                        <span class="faq-chevron"><i class="fas fa-chevron-down"></i></span>
                    </div>
                    <div class="faq-answer">
                        <div class="faq-answer-inner">
                            <p>Nhấn nút <strong>"Đăng ký"</strong> trên góc phải trên của trang, điền email, tên và mật khẩu. Xác nhận email trong hộp thư và bạn đã có tài khoản ngay lập tức!</p>
                        </div>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFAQ(this)">
                        <span class="faq-q-num">2</span>
                        <h3>Tôi quên mật khẩu, phải làm thế nào?</h3>
                        <span class="faq-chevron"><i class="fas fa-chevron-down"></i></span>
                    </div>
                    <div class="faq-answer">
                        <div class="faq-answer-inner">
                            <p>Tại trang đăng nhập, nhấn <strong>"Quên mật khẩu?"</strong> và nhập email đăng ký. Chúng tôi sẽ gửi mã OTP ngay tại trang đăng nhập để bạn tạm thời đăng nhập
                                và cập nhật lại mật khẩu sau.</p>
                        </div>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFAQ(this)">
                        <span class="faq-q-num">3</span>
                        <h3>Thông tin cá nhân của tôi có được bảo mật không?</h3>
                        <span class="faq-chevron"><i class="fas fa-chevron-down"></i></span>
                    </div>
                    <div class="faq-answer">
                        <div class="faq-answer-inner">
                            <p>HàLinhTech cam kết bảo vệ thông tin cá nhân của bạn. Chúng tôi <strong>không bán, không chia sẻ</strong> dữ liệu khách hàng cho bên thứ ba. Mọi dữ liệu được mã hóa và lưu trữ an toàn theo tiêu chuẩn bảo mật quốc tế.</p>
                            <div class="highlight-box"><i class="fas fa-lock"></i> Bạn có thể yêu cầu xóa tài khoản và toàn bộ dữ liệu bất kỳ lúc nào.</div>
                        </div>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFAQ(this)">
                        <span class="faq-q-num">4</span>
                        <h3>Tôi có thể đổi email đăng ký không?</h3>
                        <span class="faq-chevron"><i class="fas fa-chevron-down"></i></span>
                    </div>
                    <div class="faq-answer">
                        <div class="faq-answer-inner">
                            <p>Hiện tại chức năng đổi email chưa có trên giao diện tự phục vụ. Vui lòng liên hệ <strong>supporthalinhtech@gmail.com</strong> kèm thông tin xác minh danh tính để được hỗ trợ đổi email trong vòng 1–2 ngày làm việc.</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </main>
</div>

<script>
    // Toggle FAQ item
    function toggleFAQ(questionEl) {
        const item = questionEl.closest('.faq-item');
        const isOpen = item.classList.contains('open');
        // Close all in same accordion
        const accordion = item.closest('.faq-accordion');
        accordion.querySelectorAll('.faq-item.open').forEach(i => i.classList.remove('open'));
        if (!isOpen) item.classList.add('open');
    }

    // Filter by category
    function filterSection(section, linkEl) {
        event.preventDefault();
        // Update active link
        document.querySelectorAll('.faq-category-list a').forEach(a => a.classList.remove('active'));
        linkEl.classList.add('active');

        const sections = document.querySelectorAll('.faq-section');
        sections.forEach(sec => {
            if (section === 'all' || sec.dataset.section === section) {
                sec.style.display = '';
            } else {
                sec.style.display = 'none';
            }
        });
    }

    // Search FAQ
    document.getElementById('faqSearchInput').addEventListener('keydown', function(e) {
        if (e.key === 'Enter') searchFAQ();
    });

    function searchFAQ() {
        const keyword = document.getElementById('faqSearchInput').value.trim().toLowerCase();
        if (!keyword) {
            // Reset
            document.querySelectorAll('.faq-item').forEach(item => item.style.display = '');
            document.querySelectorAll('.faq-section').forEach(sec => sec.style.display = '');
            document.getElementById('faqNoResult').style.display = 'none';
            return;
        }

        let found = 0;
        document.querySelectorAll('.faq-section').forEach(sec => {
            let sectionHasResult = false;
            sec.querySelectorAll('.faq-item').forEach(item => {
                const text = item.innerText.toLowerCase();
                if (text.includes(keyword)) {
                    item.style.display = '';
                    sectionHasResult = true;
                    found++;
                } else {
                    item.style.display = 'none';
                }
            });
            sec.style.display = sectionHasResult ? '' : 'none';
        });

        document.getElementById('faqNoResult').style.display = found === 0 ? 'block' : 'none';
        // Reset sidebar
        document.querySelectorAll('.faq-category-list a').forEach(a => a.classList.remove('active'));
    }
</script>
<?php include $_SERVER['DOCUMENT_ROOT'] . '/views/layout/footer.php'; ?>
