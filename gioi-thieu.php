<?php
session_start();
$pageTitle = 'Giới thiệu';
require_once 'bootstrap.php';
require_once 'models/CategoryModel.php';
$categories = (new CategoryModel())->getAll();
?>
<?php include $_SERVER['DOCUMENT_ROOT'] . '/views/layout/header.php'; ?>
    <style>
        /* ===== HERO GIỚI THIỆU ===== */
        .about-hero {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 60%, #0f3460 100%);
            padding: 80px 24px 90px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .about-hero::before {
            content: '';
            position: absolute; inset: 0;
            background: radial-gradient(ellipse 70% 60% at 60% 40%, rgba(233,69,96,.18) 0%, transparent 70%);
            pointer-events: none;
        }
        .about-hero__badge {
            display: inline-flex; align-items: center; gap: 8px;
            background: rgba(233,69,96,.15);
            border: 1px solid rgba(233,69,96,.35);
            border-radius: 50px;
            padding: 6px 18px; font-size: .8rem;
            color: #e94560; font-weight: 600;
            letter-spacing: .06em; text-transform: uppercase;
            margin-bottom: 22px;
        }
        .about-hero h1 {
            font-family: var(--font-display);
            font-size: clamp(2rem, 5vw, 3.2rem);
            font-weight: 700; color: #fff;
            margin-bottom: 18px; line-height: 1.2;
        }
        .about-hero h1 span { color: #e94560; }
        .about-hero p {
            max-width: 620px; margin: 0 auto;
            color: rgba(255,255,255,.6); font-size: 1rem; line-height: 1.8;
        }

        /* ===== STATS BAR ===== */
        .about-stats {
            background: #fff;
            box-shadow: 0 4px 32px rgba(26,26,46,.1);
            border-radius: 16px;
            max-width: 900px; margin: -36px auto 0;
            position: relative; z-index: 10;
            display: grid; grid-template-columns: repeat(4, 1fr);
            overflow: hidden;
        }
        .stat-item {
            padding: 28px 20px; text-align: center;
            border-right: 1px solid var(--border);
        }
        .stat-item:last-child { border-right: none; }
        .stat-item__number {
            font-family: var(--font-display);
            font-size: 2rem; font-weight: 700;
            color: var(--primary); line-height: 1;
            margin-bottom: 6px;
        }
        .stat-item__number span { color: #e94560; }
        .stat-item__label { font-size: .8rem; color: var(--text-light); font-weight: 500; }

        /* ===== SECTION LAYOUT ===== */
        .about-section {
            padding: 72px 0;
        }
        .about-section + .about-section {
            border-top: 1px solid var(--border);
        }
        .section-eyebrow {
            display: inline-flex; align-items: center; gap: 8px;
            font-size: .78rem; font-weight: 600;
            color: #e94560; text-transform: uppercase; letter-spacing: .1em;
            margin-bottom: 12px;
        }
        .section-eyebrow::before {
            content: ''; display: block;
            width: 24px; height: 2px; background: #e94560; border-radius: 2px;
        }
        .section-title {
            font-family: var(--font-display);
            font-size: clamp(1.5rem, 3vw, 2rem);
            font-weight: 700; color: var(--primary);
            margin-bottom: 16px; line-height: 1.3;
        }
        .section-desc {
            color: var(--text-mid); line-height: 1.8;
            font-size: .95rem; max-width: 520px;
        }

        /* ===== CÂU CHUYỆN ===== */
        .about-story__img {
            border-radius: 20px; overflow: hidden;
            box-shadow: var(--shadow-md);
            background: linear-gradient(135deg, #1a1a2e, #0f3460);
            aspect-ratio: 4/3;
            display: flex; align-items: center; justify-content: center;
            position: relative;
        }
        .about-story__img-inner {
            display: flex; flex-direction: column; align-items: center; gap: 16px;
        }
        .about-story__img-icon {
            width: 80px; height: 80px;
            background: linear-gradient(135deg, #e94560, #ff7043);
            border-radius: 20px;
            display: flex; align-items: center; justify-content: center;
            font-size: 2rem; color: #fff;
            box-shadow: 0 12px 36px rgba(233,69,96,.4);
        }
        .about-story__img p {
            color: rgba(255,255,255,.5); font-size: .85rem;
            text-align: center; font-style: italic;
        }
        .about-story__founded {
            position: absolute; bottom: 20px; right: 20px;
            background: rgba(255,255,255,.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,.2);
            border-radius: 12px; padding: 12px 18px; text-align: center;
        }
        .about-story__founded strong { display: block; font-size: 1.5rem; color: #fff; font-family: var(--font-display); }
        .about-story__founded span { font-size: .75rem; color: rgba(255,255,255,.5); }

        /* ===== GIÁ TRỊ CỐT LÕI ===== */
        .values-grid {
            display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px;
            margin-top: 48px;
        }
        .value-card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 32px 28px;
            transition: all .25s;
        }
        .value-card:hover {
            border-color: rgba(233,69,96,.3);
            box-shadow: 0 12px 36px rgba(233,69,96,.08);
            transform: translateY(-4px);
        }
        .value-card__icon {
            width: 52px; height: 52px;
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.3rem; margin-bottom: 18px;
        }
        .value-card__title {
            font-weight: 700; color: var(--primary);
            font-size: 1rem; margin-bottom: 8px;
        }
        .value-card__desc { font-size: .875rem; color: var(--text-mid); line-height: 1.7; }

        /* ===== ĐỘI NGŨ ===== */
        .team-grid {
            display: grid; grid-template-columns: repeat(4, 1fr); gap: 24px;
            margin-top: 48px;
        }
        .team-card {
            background: #fff; border: 1px solid var(--border);
            border-radius: 16px; padding: 28px 20px; text-align: center;
            transition: all .25s;
        }
        .team-card:hover { box-shadow: var(--shadow-md); transform: translateY(-4px); }
        .team-card__avatar {
            width: 72px; height: 72px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem; font-weight: 700; color: #fff;
            margin: 0 auto 14px;
            background: linear-gradient(135deg, #e94560, #ff7043);
            box-shadow: 0 6px 20px rgba(233,69,96,.3);
        }
        .team-card__name { font-weight: 700; color: var(--primary); margin-bottom: 4px; }
        .team-card__role { font-size: .8rem; color: var(--text-light); }

        /* ===== CAM KẾT ===== */
        .promise-section {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            padding: 72px 24px; border-top: none !important;
        }
        .promise-grid {
            display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;
            max-width: 860px; margin: 48px auto 0;
        }
        .promise-item {
            display: flex; gap: 18px; align-items: flex-start;
            background: rgba(255,255,255,.05);
            border: 1px solid rgba(255,255,255,.09);
            border-radius: 14px; padding: 24px;
            transition: background .2s;
        }
        .promise-item:hover { background: rgba(255,255,255,.09); }
        .promise-item__icon {
            width: 46px; height: 46px; flex-shrink: 0;
            background: linear-gradient(135deg, rgba(233,69,96,.25), rgba(233,69,96,.1));
            border: 1px solid rgba(233,69,96,.3);
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.1rem; color: #e94560;
        }
        .promise-item__title { font-weight: 700; color: #fff; margin-bottom: 5px; font-size: .95rem; }
        .promise-item__desc { font-size: .835rem; color: rgba(255,255,255,.45); line-height: 1.6; }

        /* ===== CTA ===== */
        .about-cta {
            background: var(--bg-page); padding: 72px 24px; text-align: center;
        }
        .about-cta h2 {
            font-family: var(--font-display);
            font-size: 1.9rem; font-weight: 700;
            color: var(--primary); margin-bottom: 12px;
        }
        .about-cta p { color: var(--text-mid); margin-bottom: 32px; }
        .btn-accent {
            background: #e94560; color: #fff;
            border: none; border-radius: 50px;
            padding: 14px 36px; font-weight: 600; font-size: .95rem;
            display: inline-flex; align-items: center; gap: 8px;
            text-decoration: none;
            box-shadow: 0 6px 20px rgba(233,69,96,.35);
            transition: all .2s;
        }
        .btn-accent:hover { background: #c73652; color: #fff; transform: translateY(-2px); }
        .btn-outline-dark-custom {
            background: transparent; color: var(--primary);
            border: 1.5px solid var(--border); border-radius: 50px;
            padding: 13px 32px; font-weight: 600; font-size: .95rem;
            display: inline-flex; align-items: center; gap: 8px;
            text-decoration: none;
            transition: all .2s;
        }
        .btn-outline-dark-custom:hover { border-color: var(--primary); color: var(--primary); background: rgba(26,26,46,.05); }

        /* RESPONSIVE */
        @media (max-width: 992px) {
            .about-stats { grid-template-columns: repeat(2, 1fr); }
            .values-grid { grid-template-columns: repeat(2, 1fr); }
            .team-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 600px) {
            .about-stats { grid-template-columns: 1fr 1fr; }
            .stat-item { padding: 20px 12px; }
            .values-grid, .team-grid, .promise-grid { grid-template-columns: 1fr; }
        }
    </style>

    <!-- ===== HERO ===== -->
    <div class="about-hero">
        <div class="about-hero__badge"><i class="fas fa-star"></i> Câu chuyện của chúng tôi</div>
        <h1>Đam mê công nghệ,<br>tận tâm <span>phục vụ</span></h1>
        <p>HàLinhTech ra đời từ niềm đam mê với công nghệ và mong muốn mang đến cho mọi người những sản phẩm linh kiện chất lượng cao, giá tốt và dịch vụ đáng tin cậy nhất.</p>
    </div>

    <!-- ===== STATS ===== -->
    <div class="container">
        <div class="about-stats">
            <div class="stat-item">
                <div class="stat-item__number">5<span>+</span></div>
                <div class="stat-item__label">Năm hoạt động</div>
            </div>
            <div class="stat-item">
                <div class="stat-item__number">50<span>K+</span></div>
                <div class="stat-item__label">Khách hàng tin tưởng</div>
            </div>
            <div class="stat-item">
                <div class="stat-item__number">10<span>K+</span></div>
                <div class="stat-item__label">Sản phẩm chính hãng</div>
            </div>
            <div class="stat-item">
                <div class="stat-item__number">99<span>%</span></div>
                <div class="stat-item__label">Khách hàng hài lòng</div>
            </div>
        </div>
    </div>

    <!-- ===== CÂU CHUYỆN ===== -->
    <section class="about-section">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-5">
                    <div class="about-story__img">
                        <div class="about-story__img-inner">
                            <div class="about-story__img-icon">
                                <i class="fas fa-bag-shopping"></i>
                            </div>
                            <p>Xây dựng từ đam mê<br>công nghệ thực thụ</p>
                        </div>
                        <div class="about-story__founded">
                            <strong>2019</strong>
                            <span>Năm thành lập</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-7">
                    <div class="section-eyebrow">Câu chuyện của chúng tôi</div>
                    <h2 class="section-title">Từ một cửa hàng nhỏ<br>đến thương hiệu uy tín</h2>
                    <p class="section-desc mb-4">
                        HàLinhTech được thành lập năm 2019 bởi những người trẻ đam mê công nghệ, với mong muốn tạo ra một điểm đến tin cậy cho người dùng Việt Nam khi tìm kiếm linh kiện và thiết bị công nghệ chính hãng.
                    </p>
                    <p class="section-desc mb-4">
                        Khởi đầu từ một cửa hàng nhỏ tại TP.HCM, chúng tôi không ngừng mở rộng, đầu tư vào chất lượng sản phẩm và trải nghiệm khách hàng. Đến nay, HàLinhTech tự hào phục vụ hơn <strong>50.000 khách hàng</strong> trên khắp cả nước.
                    </p>
                    <p class="section-desc">
                        Chúng tôi tin rằng công nghệ không nên là đặc quyền của số ít — và cam kết tiếp tục mang những sản phẩm tốt nhất với mức giá hợp lý nhất đến tay mọi người.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== GIÁ TRỊ CỐT LÕI ===== -->
    <section class="about-section" style="background: var(--bg-page);">
        <div class="container">
            <div class="text-center">
                <div class="section-eyebrow" style="justify-content: center;">Triết lý hoạt động</div>
                <h2 class="section-title">Giá trị cốt lõi của HàLinhTech</h2>
            </div>
            <div class="values-grid">
                <div class="value-card">
                    <div class="value-card__icon" style="background: rgba(233,69,96,.1); color: #e94560;">
                        <i class="fas fa-shield-halved"></i>
                    </div>
                    <div class="value-card__title">Chính hãng 100%</div>
                    <div class="value-card__desc">Tất cả sản phẩm đều có nguồn gốc rõ ràng, nhập khẩu trực tiếp từ nhà sản xuất hoặc đại lý ủy quyền. Cam kết không bán hàng giả, hàng nhái.</div>
                </div>
                <div class="value-card">
                    <div class="value-card__icon" style="background: rgba(245,166,35,.1); color: #f5a623;">
                        <i class="fas fa-handshake"></i>
                    </div>
                    <div class="value-card__title">Uy tín & Minh bạch</div>
                    <div class="value-card__desc">Chính sách giá rõ ràng, không ẩn phí. Mọi thông tin về sản phẩm, khuyến mãi và chính sách đều được công bố đầy đủ và trung thực.</div>
                </div>
                <div class="value-card">
                    <div class="value-card__icon" style="background: rgba(34,197,94,.1); color: #22c55e;">
                        <i class="fas fa-headset"></i>
                    </div>
                    <div class="value-card__title">Dịch vụ tận tâm</div>
                    <div class="value-card__desc">Đội ngũ tư vấn sẵn sàng hỗ trợ 24/7. Chúng tôi không chỉ bán hàng — chúng tôi đồng hành cùng bạn từ khi chọn sản phẩm đến sau khi mua.</div>
                </div>
                <div class="value-card">
                    <div class="value-card__icon" style="background: rgba(59,130,246,.1); color: #3b82f6;">
                        <i class="fas fa-truck-fast"></i>
                    </div>
                    <div class="value-card__title">Giao hàng nhanh chóng</div>
                    <div class="value-card__desc">Hệ thống logistics hiện đại, giao hàng trong ngày tại TP.HCM, toàn quốc 1–3 ngày. Đơn hàng trên 300.000đ miễn phí vận chuyển.</div>
                </div>
                <div class="value-card">
                    <div class="value-card__icon" style="background: rgba(168,85,247,.1); color: #a855f7;">
                        <i class="fas fa-rotate-left"></i>
                    </div>
                    <div class="value-card__title">Đổi trả dễ dàng</div>
                    <div class="value-card__desc">Chính sách đổi trả 30 ngày không điều kiện. Sản phẩm lỗi do nhà sản xuất được hỗ trợ bảo hành chính hãng toàn quốc.</div>
                </div>
                <div class="value-card">
                    <div class="value-card__icon" style="background: rgba(20,184,166,.1); color: #14b8a6;">
                        <i class="fas fa-leaf"></i>
                    </div>
                    <div class="value-card__title">Phát triển bền vững</div>
                    <div class="value-card__desc">Chúng tôi cam kết sử dụng bao bì thân thiện môi trường và đóng góp 1% doanh thu mỗi năm cho các chương trình tái chế thiết bị điện tử.</div>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== ĐỘI NGŨ ===== -->
    <section class="about-section">
        <div class="container">
            <div class="text-center">
                <div class="section-eyebrow" style="justify-content: center;">Con người</div>
                <h2 class="section-title">Đội ngũ sáng lập</h2>
                <p class="section-desc mx-auto text-center">Những con người đam mê công nghệ, quyết tâm xây dựng HàLinhTech từ những ngày đầu tiên.</p>
            </div>
            <div class="team-grid">
                <div class="team-card">
                    <div class="team-card__avatar">BM</div>
                    <div class="team-card__name">Bảo Minh</div>
                    <div class="team-card__role">Nhà sáng lập & CEO</div>
                </div>
                <div class="team-card">
                    <div class="team-card__avatar" style="background: linear-gradient(135deg, #3b82f6, #6366f1);">LV</div>
                    <div class="team-card__name">Lâm Vũ</div>
                    <div class="team-card__role">Giám đốc Kỹ thuật</div>
                </div>
                <div class="team-card">
                    <div class="team-card__avatar" style="background: linear-gradient(135deg, #22c55e, #14b8a6);">HG</div>
                    <div class="team-card__name">Hương Giang</div>
                    <div class="team-card__role">Trưởng phòng CSKH</div>
                </div>
                <div class="team-card">
                    <div class="team-card__avatar" style="background: linear-gradient(135deg, #f5a623, #f97316);">XN</div>
                    <div class="team-card__name">Xuân Nghi</div>
                    <div class="team-card__role">Trưởng phòng Logistics</div>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== CAM KẾT ===== -->
    <section class="about-section promise-section">
        <div class="container">
            <div class="text-center">
                <div class="section-eyebrow" style="justify-content:center; color:#e94560;">Lời hứa của chúng tôi</div>
                <h2 class="section-title" style="color:#fff;">Cam kết với khách hàng</h2>
                <p class="section-desc mx-auto text-center" style="color:rgba(255,255,255,.5);">Mỗi đơn hàng là một cam kết — chúng tôi không bao giờ thỏa hiệp với chất lượng.</p>
            </div>
            <div class="promise-grid">
                <div class="promise-item">
                    <div class="promise-item__icon"><i class="fas fa-certificate"></i></div>
                    <div>
                        <div class="promise-item__title">Hàng chính hãng có giấy tờ</div>
                        <div class="promise-item__desc">Mọi sản phẩm đều kèm hoá đơn VAT, phiếu bảo hành và tem nhãn đầy đủ. Sẵn sàng kiểm tra tại chỗ khi nhận hàng.</div>
                    </div>
                </div>
                <div class="promise-item">
                    <div class="promise-item__icon"><i class="fas fa-wallet"></i></div>
                    <div>
                        <div class="promise-item__title">Giá tốt — Hoàn tiền nếu rẻ hơn</div>
                        <div class="promise-item__desc">Nếu bạn tìm thấy sản phẩm tương tự giá rẻ hơn trong 7 ngày, chúng tôi hoàn trả phần chênh lệch không điều kiện.</div>
                    </div>
                </div>
                <div class="promise-item">
                    <div class="promise-item__icon"><i class="fas fa-rotate-left"></i></div>
                    <div>
                        <div class="promise-item__title">Đổi trả 30 ngày dễ dàng</div>
                        <div class="promise-item__desc">Không hài lòng? Đổi trả trong 30 ngày kể từ ngày nhận hàng. Không cần lý do, miễn phí vận chuyển chiều về.</div>
                    </div>
                </div>
                <div class="promise-item">
                    <div class="promise-item__icon"><i class="fas fa-headset"></i></div>
                    <div>
                        <div class="promise-item__title">Hỗ trợ suốt vòng đời sản phẩm</div>
                        <div class="promise-item__desc">Đội ngũ kỹ thuật sẵn sàng tư vấn lắp đặt, sử dụng và khắc phục sự cố miễn phí trong suốt thời gian bảo hành.</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== CTA ===== -->
    <section class="about-cta">
        <div class="container">
            <h2>Sẵn sàng trải nghiệm mua sắm thông minh?</h2>
            <p>Khám phá hàng nghìn sản phẩm linh kiện & công nghệ chính hãng tại HàLinhTech</p>
            <div class="d-flex gap-3 justify-content-center flex-wrap">
                <a href="/" class="btn-accent">
                    <i class="fas fa-bag-shopping"></i> Mua sắm ngay
                </a>

            </div>
        </div>
    </section>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/views/layout/footer.php'; ?>