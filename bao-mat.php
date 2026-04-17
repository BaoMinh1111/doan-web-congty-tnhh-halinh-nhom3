<?php
session_start();
$pageTitle = 'Chính sách bảo mật';
require_once 'bootstrap.php';
require_once 'models/CategoryModel.php';
$categories = (new CategoryModel())->getAll();
?>
<?php include $_SERVER['DOCUMENT_ROOT'] . '/views/layout/header.php'; ?>
    <style>
        /* ===== PAGE HERO ===== */
        .policy-hero {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 60%, #0f3460 100%);
            padding: 60px 24px 52px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .policy-hero::before {
            content: '';
            position: absolute; inset: 0;
            background: radial-gradient(ellipse 60% 80% at 50% 120%, rgba(233,69,96,.18), transparent);
            pointer-events: none;
        }
        .policy-hero__badge {
            display: inline-flex; align-items: center; gap: 7px;
            background: rgba(233,69,96,.15);
            border: 1px solid rgba(233,69,96,.3);
            color: #e94560; border-radius: 50px;
            padding: 5px 16px; font-size: .78rem; font-weight: 600;
            letter-spacing: .06em; text-transform: uppercase;
            margin-bottom: 16px;
        }
        .policy-hero__title {
            font-family: var(--font-display);
            font-size: clamp(1.8rem, 4vw, 2.8rem);
            font-weight: 700; color: #fff;
            margin-bottom: 14px; line-height: 1.25;
        }
        .policy-hero__title span { color: #e94560; }
        .policy-hero__sub {
            color: rgba(255,255,255,.5); font-size: .9rem;
            max-width: 520px; margin: 0 auto 20px;
            line-height: 1.7;
        }
        .policy-hero__meta {
            display: inline-flex; align-items: center; gap: 6px;
            font-size: .78rem; color: rgba(255,255,255,.35);
        }
        .policy-hero__meta i { color: #f5a623; }

        /* ===== BREADCRUMB ===== */
        .policy-breadcrumb {
            background: var(--bg-page, #f8f8fc);
            border-bottom: 1px solid var(--border, #e8e8f0);
            padding: 10px 0;
        }
        .policy-breadcrumb .breadcrumb {
            margin: 0; font-size: .8rem;
        }
        .policy-breadcrumb .breadcrumb-item a {
            color: var(--text-mid, #4a4a6a); text-decoration: none;
            transition: color .2s;
        }
        .policy-breadcrumb .breadcrumb-item a:hover { color: #e94560; }
        .policy-breadcrumb .breadcrumb-item.active { color: var(--text-light, #9a9ab0); }

        /* ===== LAYOUT ===== */
        .policy-layout {
            display: grid;
            grid-template-columns: 260px 1fr;
            gap: 32px;
            max-width: 1140px;
            margin: 48px auto 64px;
            padding: 0 24px;
            align-items: start;
        }

        /* ===== SIDEBAR ===== */
        .policy-sidebar {
            position: sticky; top: calc(36px + 68px + 20px);
        }
        .policy-sidebar__card {
            background: #fff;
            border: 1px solid var(--border, #e8e8f0);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(26,26,46,.06);
        }
        .policy-sidebar__head {
            background: linear-gradient(135deg, #1a1a2e, #16213e);
            padding: 18px 20px;
        }
        .policy-sidebar__head h5 {
            color: #fff; font-size: .82rem; font-weight: 600;
            text-transform: uppercase; letter-spacing: .07em;
            margin: 0; display: flex; align-items: center; gap: 8px;
        }
        .policy-sidebar__head h5 i { color: #e94560; }
        .policy-sidebar__nav { padding: 10px 0; }
        .policy-sidebar__nav a {
            display: flex; align-items: center; gap: 10px;
            padding: 10px 20px;
            color: var(--text-mid, #4a4a6a);
            font-size: .84rem; text-decoration: none;
            border-left: 3px solid transparent;
            transition: all .2s;
        }
        .policy-sidebar__nav a i {
            width: 16px; text-align: center;
            color: var(--text-light, #9a9ab0);
            font-size: .75rem; transition: color .2s;
            flex-shrink: 0;
        }
        .policy-sidebar__nav a:hover,
        .policy-sidebar__nav a.active {
            color: #e94560;
            border-left-color: #e94560;
            background: rgba(233,69,96,.04);
            padding-left: 22px;
        }
        .policy-sidebar__nav a:hover i,
        .policy-sidebar__nav a.active i { color: #e94560; }

        .policy-sidebar__info {
            margin-top: 16px;
            background: #fff;
            border: 1px solid var(--border, #e8e8f0);
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 4px 20px rgba(26,26,46,.06);
        }
        .policy-sidebar__info p {
            font-size: .8rem; color: var(--text-light, #9a9ab0);
            margin: 0 0 12px; line-height: 1.6;
        }
        .policy-sidebar__info a {
            display: inline-flex; align-items: center; gap: 7px;
            background: #e94560; color: #fff;
            text-decoration: none; border-radius: 50px;
            padding: 9px 18px; font-size: .82rem; font-weight: 600;
            transition: background .2s;
        }
        .policy-sidebar__info a:hover { background: #c73652; }

        /* ===== CONTENT ===== */
        .policy-content {
            background: #fff;
            border: 1px solid var(--border, #e8e8f0);
            border-radius: 20px;
            padding: 44px 48px;
            box-shadow: 0 4px 24px rgba(26,26,46,.07);
        }

        /* Section */
        .policy-section { margin-bottom: 44px; padding-bottom: 44px; border-bottom: 1px solid var(--border, #e8e8f0); }
        .policy-section:last-child { margin-bottom: 0; padding-bottom: 0; border-bottom: none; }

        .policy-section__title {
            display: flex; align-items: center; gap: 12px;
            font-family: var(--font-display);
            font-size: 1.2rem; font-weight: 700;
            color: #1a1a2e; margin-bottom: 18px;
        }
        .policy-section__icon {
            width: 38px; height: 38px; border-radius: 10px;
            background: linear-gradient(135deg, rgba(233,69,96,.12), rgba(233,69,96,.05));
            border: 1px solid rgba(233,69,96,.2);
            display: flex; align-items: center; justify-content: center;
            color: #e94560; font-size: .85rem; flex-shrink: 0;
        }

        .policy-content p {
            color: var(--text-mid, #4a4a6a);
            font-size: .9rem; line-height: 1.85;
            margin-bottom: 14px;
        }
        .policy-content p:last-child { margin-bottom: 0; }

        /* List */
        .policy-list {
            list-style: none; padding: 0; margin: 0 0 16px;
            display: flex; flex-direction: column; gap: 10px;
        }
        .policy-list li {
            display: flex; align-items: flex-start; gap: 10px;
            color: var(--text-mid, #4a4a6a); font-size: .9rem; line-height: 1.7;
        }
        .policy-list li i {
            color: #e94560; font-size: .72rem;
            margin-top: 5px; flex-shrink: 0;
        }

        /* Highlight box */
        .policy-highlight {
            background: linear-gradient(135deg, rgba(233,69,96,.05), rgba(233,69,96,.02));
            border: 1px solid rgba(233,69,96,.15);
            border-left: 4px solid #e94560;
            border-radius: 0 12px 12px 0;
            padding: 18px 22px;
            margin: 20px 0;
        }
        .policy-highlight p {
            margin: 0; font-size: .88rem;
            color: var(--text-mid, #4a4a6a);
        }
        .policy-highlight strong { color: #1a1a2e; }

        /* Info grid */
        .policy-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 14px; margin: 20px 0;
        }
        .policy-info-card {
            background: var(--bg-page, #f8f8fc);
            border: 1px solid var(--border, #e8e8f0);
            border-radius: 12px; padding: 18px;
            text-align: center;
        }
        .policy-info-card i {
            font-size: 1.4rem; color: #e94560;
            display: block; margin-bottom: 8px;
        }
        .policy-info-card strong {
            display: block; font-size: .82rem; font-weight: 600;
            color: #1a1a2e; margin-bottom: 4px;
        }
        .policy-info-card span {
            font-size: .78rem; color: var(--text-light, #9a9ab0);
        }

        /* Contact card */
        .policy-contact-card {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            border-radius: 16px; padding: 28px 32px;
            display: flex; align-items: center; gap: 24px; flex-wrap: wrap;
            margin-top: 8px;
        }
        .policy-contact-card__icon {
            width: 54px; height: 54px;
            background: rgba(233,69,96,.2); border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            color: #e94560; font-size: 1.3rem; flex-shrink: 0;
        }
        .policy-contact-card__text { flex: 1; min-width: 180px; }
        .policy-contact-card__text h5 {
            color: #fff; font-size: 1rem; font-weight: 700;
            margin-bottom: 6px;
        }
        .policy-contact-card__text p {
            color: rgba(255,255,255,.5); font-size: .84rem; margin: 0; line-height: 1.5;
        }
        .policy-contact-card__actions { display: flex; gap: 10px; flex-wrap: wrap; }
        .policy-contact-card__actions a {
            display: inline-flex; align-items: center; gap: 7px;
            padding: 9px 18px; border-radius: 50px;
            font-size: .82rem; font-weight: 600;
            text-decoration: none; transition: all .2s;
        }
        .btn-contact-primary {
            background: #e94560; color: #fff !important;
        }
        .btn-contact-primary:hover { background: #c73652; transform: translateY(-1px); }
        .btn-contact-secondary {
            background: rgba(255,255,255,.08);
            border: 1px solid rgba(255,255,255,.15);
            color: rgba(255,255,255,.75) !important;
        }
        .btn-contact-secondary:hover { background: rgba(255,255,255,.15); color: #fff !important; }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 900px) {
            .policy-layout { grid-template-columns: 1fr; }
            .policy-sidebar { position: static; }
            .policy-content { padding: 28px 24px; }
        }
        @media (max-width: 600px) {
            .policy-content { padding: 22px 16px; border-radius: 14px; }
            .policy-contact-card { padding: 20px; }
        }
    </style>

    <!-- ===== HERO ===== -->
    <div class="policy-hero">
        <div class="policy-hero__badge"><i class="fas fa-shield-halved"></i> Chính sách</div>
        <h1 class="policy-hero__title">Chính sách <span>Bảo mật</span></h1>
        <p class="policy-hero__sub">Chúng tôi cam kết bảo vệ thông tin cá nhân của bạn và đảm bảo tính minh bạch trong mọi hoạt động xử lý dữ liệu.</p>
        <div class="policy-hero__meta"><i class="fas fa-calendar-check"></i> Cập nhật lần cuối: Tháng 1, 2025</div>
    </div>

    <!-- ===== BREADCRUMB ===== -->
    <div class="policy-breadcrumb">
        <div class="container">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/"><i class="fas fa-house me-1"></i>Trang chủ</a></li>
                    <li class="breadcrumb-item active">Chính sách bảo mật</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- ===== LAYOUT ===== -->
    <div class="policy-layout">

        <!-- SIDEBAR -->
        <aside class="policy-sidebar">
            <div class="policy-sidebar__card">
                <div class="policy-sidebar__head">
                    <h5><i class="fas fa-list-ul"></i> Mục lục</h5>
                </div>
                <nav class="policy-sidebar__nav">
                    <a href="#thu-thap" class="active"><i class="fas fa-circle-dot"></i> Thông tin thu thập</a>
                    <a href="#su-dung"><i class="fas fa-circle-dot"></i> Mục đích sử dụng</a>
                    <a href="#bao-ve"><i class="fas fa-circle-dot"></i> Bảo vệ thông tin</a>
                    <a href="#chia-se"><i class="fas fa-circle-dot"></i> Chia sẻ dữ liệu</a>
                    <a href="#cookie"><i class="fas fa-circle-dot"></i> Cookie & Tracking</a>
                    <a href="#quyen-han"><i class="fas fa-circle-dot"></i> Quyền của bạn</a>
                    <a href="#tre-em"><i class="fas fa-circle-dot"></i> Bảo vệ trẻ em</a>
                    <a href="#thay-doi"><i class="fas fa-circle-dot"></i> Thay đổi chính sách</a>
                    <a href="#lien-he"><i class="fas fa-circle-dot"></i> Liên hệ</a>
                </nav>
            </div>

        </aside>

        <!-- CONTENT -->
        <main class="policy-content">

            <!-- 1. THU THẬP -->
            <section class="policy-section" id="thu-thap">
                <h2 class="policy-section__title">
                    <div class="policy-section__icon"><i class="fas fa-database"></i></div>
                    1. Thông tin chúng tôi thu thập
                </h2>
                <p>HàLinhTech thu thập các thông tin cần thiết nhằm cung cấp dịch vụ mua sắm tốt nhất và đảm bảo trải nghiệm liền mạch cho quý khách hàng. Các thông tin chúng tôi có thể thu thập bao gồm:</p>
                <ul class="policy-list">
                    <li><i class="fas fa-check-circle"></i><span><strong>Thông tin cá nhân:</strong> Họ tên, địa chỉ email, số điện thoại, địa chỉ giao hàng được cung cấp khi đăng ký tài khoản hoặc đặt hàng.</span></li>
                    <li><i class="fas fa-check-circle"></i><span><strong>Thông tin thanh toán:</strong> Phương thức thanh toán, mã giao dịch (chúng tôi không lưu trữ số thẻ ngân hàng đầy đủ).</span></li>
                    <li><i class="fas fa-check-circle"></i><span><strong>Dữ liệu sử dụng:</strong> Lịch sử duyệt web, sản phẩm đã xem, giỏ hàng, đơn hàng và các tương tác trên website.</span></li>
                    <li><i class="fas fa-check-circle"></i><span><strong>Thông tin thiết bị:</strong> Địa chỉ IP, loại trình duyệt, hệ điều hành và thời gian truy cập.</span></li>
                    <li><i class="fas fa-check-circle"></i><span><strong>Phản hồi & đánh giá:</strong> Các bình luận, đánh giá sản phẩm mà bạn để lại trên website.</span></li>
                </ul>
                <div class="policy-highlight">
                    <p><strong>Lưu ý:</strong> Chúng tôi chỉ thu thập thông tin cần thiết và sẽ thông báo rõ ràng khi yêu cầu bạn cung cấp dữ liệu. Bạn có quyền từ chối cung cấp một số thông tin, tuy nhiên điều này có thể ảnh hưởng đến khả năng sử dụng một số tính năng.</p>
                </div>
            </section>

            <!-- 2. MỤC ĐÍCH -->
            <section class="policy-section" id="su-dung">
                <h2 class="policy-section__title">
                    <div class="policy-section__icon"><i class="fas fa-bullseye"></i></div>
                    2. Mục đích sử dụng thông tin
                </h2>
                <p>Dữ liệu thu thập được sử dụng với các mục đích cụ thể và hợp pháp. Chúng tôi cam kết không sử dụng thông tin của bạn ngoài phạm vi đã được thông báo:</p>
                <div class="policy-info-grid">
                    <div class="policy-info-card">
                        <i class="fas fa-cart-shopping"></i>
                        <strong>Xử lý đơn hàng</strong>
                        <span>Xác nhận, đóng gói và giao hàng đúng địa chỉ</span>
                    </div>
                    <div class="policy-info-card">
                        <i class="fas fa-headset"></i>
                        <strong>Hỗ trợ khách hàng</strong>
                        <span>Giải quyết khiếu nại, đổi trả và tư vấn sản phẩm</span>
                    </div>
                    <div class="policy-info-card">
                        <i class="fas fa-chart-line"></i>
                        <strong>Cải thiện dịch vụ</strong>
                        <span>Phân tích hành vi để tối ưu trải nghiệm mua sắm</span>
                    </div>
                    <div class="policy-info-card">
                        <i class="fas fa-bell"></i>
                        <strong>Thông báo ưu đãi</strong>
                        <span>Gửi khuyến mãi, tin tức sản phẩm (nếu bạn đồng ý)</span>
                    </div>
                    <div class="policy-info-card">
                        <i class="fas fa-shield-halved"></i>
                        <strong>Bảo mật hệ thống</strong>
                        <span>Phát hiện gian lận và bảo vệ tài khoản của bạn</span>
                    </div>
                    <div class="policy-info-card">
                        <i class="fas fa-gavel"></i>
                        <strong>Tuân thủ pháp luật</strong>
                        <span>Đáp ứng yêu cầu từ cơ quan nhà nước có thẩm quyền</span>
                    </div>
                </div>
            </section>

            <!-- 3. BẢO VỆ -->
            <section class="policy-section" id="bao-ve">
                <h2 class="policy-section__title">
                    <div class="policy-section__icon"><i class="fas fa-lock"></i></div>
                    3. Bảo vệ thông tin của bạn
                </h2>
                <p>Chúng tôi áp dụng các biện pháp kỹ thuật và tổ chức nghiêm ngặt để bảo vệ thông tin cá nhân khỏi truy cập trái phép, mất mát, tiết lộ hoặc phá hủy:</p>
                <ul class="policy-list">
                    <li><i class="fas fa-check-circle"></i><span><strong>Mã hóa SSL/TLS:</strong> Mọi dữ liệu truyền tải giữa trình duyệt và máy chủ đều được mã hóa bằng giao thức HTTPS tiêu chuẩn công nghiệp.</span></li>
                    <li><i class="fas fa-check-circle"></i><span><strong>Kiểm soát truy cập:</strong> Chỉ nhân viên có thẩm quyền mới được phép truy cập dữ liệu khách hàng, và chỉ khi cần thiết cho công việc.</span></li>
                    <li><i class="fas fa-check-circle"></i><span><strong>Sao lưu định kỳ:</strong> Hệ thống sao lưu dữ liệu tự động giúp phục hồi nhanh chóng trong trường hợp sự cố.</span></li>
                    <li><i class="fas fa-check-circle"></i><span><strong>Giám sát liên tục:</strong> Hệ thống giám sát 24/7 phát hiện và xử lý các hoạt động bất thường kịp thời.</span></li>
                    <li><i class="fas fa-check-circle"></i><span><strong>Mật khẩu băm:</strong> Mật khẩu người dùng được băm (hash) bằng thuật toán bcrypt, chúng tôi không lưu mật khẩu dạng văn bản.</span></li>
                </ul>
                <div class="policy-highlight">
                    <p>Dù chúng tôi áp dụng các biện pháp bảo mật cao, không có hệ thống nào đảm bảo an toàn 100%. Bạn cũng nên bảo vệ mật khẩu tài khoản và không chia sẻ thông tin đăng nhập với bất kỳ ai.</p>
                </div>
            </section>

            <!-- 4. CHIA SẺ -->
            <section class="policy-section" id="chia-se">
                <h2 class="policy-section__title">
                    <div class="policy-section__icon"><i class="fas fa-share-nodes"></i></div>
                    4. Chia sẻ dữ liệu với bên thứ ba
                </h2>
                <p>HàLinhTech <strong>không bán, cho thuê hoặc trao đổi</strong> thông tin cá nhân của bạn với bên thứ ba vì mục đích thương mại. Chúng tôi chỉ chia sẻ dữ liệu trong các trường hợp:</p>
                <ul class="policy-list">
                    <li><i class="fas fa-check-circle"></i><span><strong>Đối tác vận chuyển:</strong> Tên, số điện thoại và địa chỉ giao hàng được cung cấp cho đơn vị vận chuyển (GHN, GHTK, ViettelPost...) để thực hiện giao hàng.</span></li>
                    <li><i class="fas fa-check-circle"></i><span><strong>Cổng thanh toán:</strong> Thông tin giao dịch được chuyển đến cổng thanh toán (VNPay, MoMo, ZaloPay...) theo tiêu chuẩn bảo mật của từng đơn vị.</span></li>
                    <li><i class="fas fa-check-circle"></i><span><strong>Dịch vụ phân tích:</strong> Dữ liệu ẩn danh được sử dụng cho Google Analytics để cải thiện trải nghiệm website.</span></li>
                    <li><i class="fas fa-check-circle"></i><span><strong>Yêu cầu pháp lý:</strong> Cung cấp thông tin khi có yêu cầu hợp pháp từ cơ quan nhà nước có thẩm quyền.</span></li>
                </ul>
                <p>Tất cả đối tác đều bị ràng buộc bởi các thỏa thuận bảo mật và chỉ được phép sử dụng dữ liệu đúng mục đích được ủy quyền.</p>
            </section>

            <!-- 5. COOKIE -->
            <section class="policy-section" id="cookie">
                <h2 class="policy-section__title">
                    <div class="policy-section__icon"><i class="fas fa-cookie-bite"></i></div>
                    5. Cookie và công nghệ theo dõi
                </h2>
                <p>Chúng tôi sử dụng cookie và công nghệ tương tự để cải thiện trải nghiệm duyệt web của bạn trên HàLinhTech:</p>
                <ul class="policy-list">
                    <li><i class="fas fa-check-circle"></i><span><strong>Cookie thiết yếu:</strong> Cần thiết để website hoạt động (duy trì phiên đăng nhập, giỏ hàng). Không thể tắt.</span></li>
                    <li><i class="fas fa-check-circle"></i><span><strong>Cookie phân tích:</strong> Giúp chúng tôi hiểu cách người dùng tương tác với website (trang nào được xem nhiều, thời gian truy cập...).</span></li>
                    <li><i class="fas fa-check-circle"></i><span><strong>Cookie cá nhân hóa:</strong> Ghi nhớ tùy chọn của bạn như ngôn ngữ, sản phẩm đã xem để hiển thị nội dung phù hợp hơn.</span></li>
                </ul>
                <p>Bạn có thể kiểm soát cookie qua cài đặt trình duyệt. Tuy nhiên, tắt một số cookie có thể ảnh hưởng đến chức năng của website.</p>
            </section>

            <!-- 6. QUYỀN HẠN -->
            <section class="policy-section" id="quyen-han">
                <h2 class="policy-section__title">
                    <div class="policy-section__icon"><i class="fas fa-user-shield"></i></div>
                    6. Quyền của bạn đối với dữ liệu
                </h2>
                <p>Theo quy định pháp luật Việt Nam và các tiêu chuẩn quốc tế về bảo vệ dữ liệu, bạn có các quyền sau đây:</p>
                <ul class="policy-list">
                    <li><i class="fas fa-check-circle"></i><span><strong>Quyền truy cập:</strong> Yêu cầu xem thông tin cá nhân chúng tôi đang lưu giữ về bạn.</span></li>
                    <li><i class="fas fa-check-circle"></i><span><strong>Quyền chỉnh sửa:</strong> Yêu cầu cập nhật, sửa đổi thông tin không chính xác hoặc không đầy đủ.</span></li>
                    <li><i class="fas fa-check-circle"></i><span><strong>Quyền xóa:</strong> Yêu cầu xóa tài khoản và dữ liệu cá nhân (trừ thông tin cần lưu theo quy định pháp luật).</span></li>
                    <li><i class="fas fa-check-circle"></i><span><strong>Quyền phản đối:</strong> Từ chối nhận email marketing hoặc hủy đăng ký nhận thông báo bất kỳ lúc nào.</span></li>
                    <li><i class="fas fa-check-circle"></i><span><strong>Quyền di chuyển dữ liệu:</strong> Yêu cầu xuất dữ liệu của bạn ở định dạng có thể đọc được.</span></li>
                    <li><i class="fas fa-check-circle"></i><span><strong>Quyền khiếu nại:</strong> Liên hệ với chúng tôi hoặc cơ quan bảo vệ dữ liệu nếu bạn cho rằng quyền lợi bị vi phạm.</span></li>
                </ul>
                <p>Để thực hiện các quyền trên, vui lòng liên hệ qua email <strong>supporthalinhtech@gmail.com</strong>. Chúng tôi sẽ phản hồi trong vòng <strong>7 ngày làm việc</strong>.</p>
            </section>

            <!-- 7. TRẺ EM -->
            <section class="policy-section" id="tre-em">
                <h2 class="policy-section__title">
                    <div class="policy-section__icon"><i class="fas fa-child"></i></div>
                    7. Bảo vệ trẻ em
                </h2>
                <p>HàLinhTech không cố ý thu thập thông tin cá nhân từ trẻ em dưới 13 tuổi. Dịch vụ của chúng tôi không được thiết kế để hướng tới đối tượng này.</p>
                <p>Nếu bạn là phụ huynh hoặc người giám hộ và phát hiện con bạn đã cung cấp thông tin cá nhân cho chúng tôi mà không có sự đồng ý, vui lòng liên hệ ngay để chúng tôi xóa thông tin đó khỏi hệ thống.</p>
            </section>

            <!-- 8. THAY ĐỔI -->
            <section class="policy-section" id="thay-doi">
                <h2 class="policy-section__title">
                    <div class="policy-section__icon"><i class="fas fa-file-pen"></i></div>
                    8. Thay đổi chính sách
                </h2>
                <p>Chúng tôi có thể cập nhật Chính sách bảo mật này theo thời gian để phản ánh các thay đổi trong hoạt động kinh doanh hoặc quy định pháp luật. Khi có thay đổi quan trọng, chúng tôi sẽ:</p>
                <ul class="policy-list">
                    <li><i class="fas fa-check-circle"></i><span>Thông báo qua email đến địa chỉ đã đăng ký trong tài khoản của bạn.</span></li>
                    <li><i class="fas fa-check-circle"></i><span>Hiển thị thông báo nổi bật trên trang chủ website ít nhất 7 ngày trước khi có hiệu lực.</span></li>
                    <li><i class="fas fa-check-circle"></i><span>Cập nhật ngày "Cập nhật lần cuối" ở đầu trang này.</span></li>
                </ul>
                <p>Việc tiếp tục sử dụng dịch vụ sau khi chính sách được cập nhật đồng nghĩa với việc bạn chấp nhận các thay đổi đó.</p>
            </section>

            <!-- 9. LIÊN HỆ -->
            <section class="policy-section" id="lien-he">
                <h2 class="policy-section__title">
                    <div class="policy-section__icon"><i class="fas fa-envelope-open-text"></i></div>
                    9. Liên hệ về quyền riêng tư
                </h2>
                <p>Nếu bạn có bất kỳ câu hỏi, thắc mắc hoặc yêu cầu liên quan đến Chính sách bảo mật này hay cách chúng tôi xử lý dữ liệu của bạn, hãy liên hệ với chúng tôi:</p>
                <div class="policy-contact-card">
                    <div class="policy-contact-card__icon">
                        <i class="fas fa-headset"></i>
                    </div>
                    <div class="policy-contact-card__text">
                        <h5>Đội ngũ hỗ trợ HàLinhTech</h5>
                        <p>90, đường số 3, KDC 13E, Bình Hưng, TP.HCM<br>
                            Email: supporthalinhtech@gmail.com &nbsp;|&nbsp; Hotline: 1800 1904<br>
                            Thứ 2 – CN: 8:00 – 22:00 (miễn phí)</p>
                    </div>
                    <div class="policy-contact-card__actions">
                        <a href="https://mail.google.com/mail/?view=cm&fs=1&to=supporthalinhtech@gmail.com
                &su=T%C3%B4i%20c%E1%BA%A7n%20h%E1%BB%97%20tr%E1%BB%A3%20t%E1%BB%AB%20H%C3%A0LinhTech..."
                           class="btn-contact-primary">
                            <i class="fas fa-envelope"></i> Gửi email
                        </a>

                    </div>
                </div>
            </section>

        </main>
    </div><!-- /.policy-layout -->

    <script>
        // Active sidebar link on scroll
        (function () {
            const links = document.querySelectorAll('.policy-sidebar__nav a');
            const sections = document.querySelectorAll('.policy-section');
            const topOffset = 36 + 68 + 30;

            window.addEventListener('scroll', () => {
                let current = '';
                sections.forEach(sec => {
                    if (window.scrollY >= sec.offsetTop - topOffset - 10) {
                        current = sec.id;
                    }
                });
                links.forEach(a => {
                    a.classList.toggle('active', a.getAttribute('href') === '#' + current);
                });
            }, { passive: true });

            links.forEach(a => {
                a.addEventListener('click', e => {
                    e.preventDefault();
                    const target = document.querySelector(a.getAttribute('href'));
                    if (target) {
                        window.scrollTo({ top: target.offsetTop - topOffset, behavior: 'smooth' });
                    }
                });
            });
        })();
    </script>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/views/layout/footer.php'; ?>