<?php
// Xử lý logic trước khi xuất HTML
require_once __DIR__ . '/includes/functions.php';

// ⚠️ BỎ HOÀN TOÀN XỬ LÝ THÊM GIỎ HÀNG Ở TRANG CHỦ
// Từ giờ chỉ được thêm giỏ hàng ở product-detail.php

require_once __DIR__ . '/includes/header.php';

// Sau header, ta đã có $pdo = db() trong header.php
$pdo = db();

/**
 * Lấy 8 sản phẩm "Điện thoại nổi bật"
 * - Dựa trên slug danh mục: iphone, samsung, xiaomi, dien-thoai
 */
$sqlPhones = "
    SELECT p.*, c.slug AS cat_slug
    FROM products p
    JOIN categories c ON p.category_id = c.id
    WHERE c.slug IN ('dien-thoai', 'iphone', 'samsung', 'xiaomi')
    ORDER BY p.created_at DESC
    LIMIT 8
";
$phonesStmt = $pdo->query($sqlPhones);
$phones = $phonesStmt->fetchAll();

/**
 * Lấy 8 sản phẩm "Phụ kiện hot"
 */
$sqlAccessories = "
    SELECT p.*, c.slug AS cat_slug
    FROM products p
    JOIN categories c ON p.category_id = c.id
    WHERE c.slug = 'phu-kien'
    ORDER BY p.created_at DESC
    LIMIT 8
";
$accStmt = $pdo->query($sqlAccessories);
$accessories = $accStmt->fetchAll();

// Hàm calc_discount_info() đã được move vào includes/functions.php
// Không cần định nghĩa lại ở đây
?>

<!-- HERO: SLIDER + CAM KẾT -->
<section class="hero">
    <div class="hero-grid">
        <!-- Slider bên trái -->
        <div class="hero-slider">
            <div class="hero-slide is-active">
                <div>
                    <div class="hero-slide__headline">
                        Thu cũ đổi mới – trợ giá đến 5.000.000₫
                    </div>
                    <div class="hero-slide__sub">
                        Đem máy cũ, lên đời iPhone 15 Pro Max, hỗ trợ định giá nhanh, thêm quà tặng hấp dẫn.
                    </div>
                    <div class="hero-slide__badges">
                        <span class="hero-slide__badge">Trợ giá cao</span>
                        <span class="hero-slide__badge">Hỗ trợ lên đời</span>
                        <span class="hero-slide__badge">Apple chính hãng</span>
                    </div>
                    <a href="product-list.php?category=iphone" class="btn btn--primary">
                        Xem iPhone ưu đãi
                    </a>
                </div>
                <div class="hero-slide__image">
                    <img src="assets/img/banner-1.jpg" alt="Thu cũ đổi mới iPhone">
                </div>
            </div>

            <div class="hero-slide">
                <div>
                    <div class="hero-slide__headline">
                        Trả góp 0% – duyệt nhanh trong 1 phút
                    </div>
                    <div class="hero-slide__sub">
                        Áp dụng cho nhiều dòng điện thoại Samsung, Xiaomi, Oppo... thủ tục online cực dễ.
                    </div>
                    <div class="hero-slide__badges">
                        <span class="hero-slide__badge">Lãi suất 0%</span>
                        <span class="hero-slide__badge">Duyệt online</span>
                        <span class="hero-slide__badge">Không cần thế chấp</span>
                    </div>
                    <a href="product-list.php" class="btn btn--primary">
                        Xem sản phẩm hỗ trợ trả góp
                    </a>
                </div>
                <div class="hero-slide__image">
                    <img src="assets/img/banner-2.jpg" alt="Trả góp 0%">
                </div>
            </div>

            <div class="hero-slide">
                <div>
                    <div class="hero-slide__headline">
                        Sale cuối tuần – giảm sốc đến 50%
                    </div>
                    <div class="hero-slide__sub">
                        Săn deal phụ kiện, tai nghe, sạc nhanh, ốp lưng với giá tốt nhất trong tuần.
                    </div>
                    <div class="hero-slide__badges">
                        <span class="hero-slide__badge">Giảm sâu</span>
                        <span class="hero-slide__badge">Số lượng có hạn</span>
                        <span class="hero-slide__badge">Online giá rẻ</span>
                    </div>
                    <a href="product-list.php?category=phu-kien" class="btn btn--primary">
                        Mua phụ kiện giá sốc
                    </a>
                </div>
                <div class="hero-slide__image">
                    <img src="assets/img/banner-3.jpg" alt="Sale phụ kiện cuối tuần">
                </div>
            </div>

            <div class="hero-slider__dots">
                <button type="button" class="hero-slider__dot is-active" aria-label="Slide 1"></button>
                <button type="button" class="hero-slider__dot" aria-label="Slide 2"></button>
                <button type="button" class="hero-slider__dot" aria-label="Slide 3"></button>
            </div>
        </div>

        <!-- Card cam kết bên phải -->
        <div class="promo-cards">
            <article class="promo-card">
                <i class="fa-solid fa-shield-halved"></i>
                <div>
                    <div class="promo-card__title">Sản phẩm chính hãng</div>
                    <div class="promo-card__desc">Full box, mới 100%, hóa đơn VAT đầy đủ.</div>
                </div>
            </article>
            <article class="promo-card">
                <i class="fa-solid fa-truck-fast"></i>
                <div>
                    <div class="promo-card__title">Giao nhanh 2H</div>
                    <div class="promo-card__desc">Miễn phí cho đơn từ 300.000₫ trong bán kính hỗ trợ.</div>
                </div>
            </article>
            <article class="promo-card">
                <i class="fa-solid fa-rotate"></i>
                <div>
                    <div class="promo-card__title">Thu cũ giá tốt</div>
                    <div class="promo-card__desc">Định giá minh bạch, hỗ trợ lên đời tiết kiệm hơn.</div>
                </div>
            </article>
            <article class="promo-card">
                <i class="fa-solid fa-money-check-dollar"></i>
                <div>
                    <div class="promo-card__title">Trả góp linh hoạt</div>
                    <div class="promo-card__desc">Nhiều gói trả góp, kỳ hạn đa dạng, duyệt nhanh.</div>
                </div>
            </article>
        </div>
    </div>
</section>

<!-- DANH MỤC NỔI BẬT -->
<section class="section">
    <div class="section-header">
        <h1>Pham Anh Dung_Ca cuoi</h1>
        <h2>Danh mục nổi bật</h2>
        <a href="product-list.php">Xem tất cả sản phẩm</a>
    </div>
    <div class="featured-cats">
        <a href="product-list.php?category=dien-thoai" class="featured-cat">
            <div class="featured-cat__icon">
                <i class="fa-solid fa-mobile-screen"></i>
            </div>
            <div class="featured-cat__info">
                <h3>Điện thoại</h3>
                <p>Smartphone mới nhất, nhiều ưu đãi hấp dẫn.</p>
            </div>
        </a>
        <a href="product-list.php?category=phu-kien" class="featured-cat">
            <div class="featured-cat__icon">
                <i class="fa-solid fa-headphones"></i>
            </div>
            <div class="featured-cat__info">
                <h3>Phụ kiện</h3>
                <p>Tai nghe, sạc nhanh, ốp lưng, dán màn hình...</p>
            </div>
        </a>
        <a href="product-list.php?category=iphone" class="featured-cat">
            <div class="featured-cat__icon">
                <i class="fa-brands fa-apple"></i>
            </div>
            <div class="featured-cat__info">
                <h3>iPhone</h3>
                <p>iPhone chính hãng VN/A, nhiều bản dung lượng.</p>
            </div>
        </a>
        <a href="product-list.php?category=samsung" class="featured-cat">
            <div class="featured-cat__icon">
                <i class="fa-solid fa-star"></i>
            </div>
            <div class="featured-cat__info">
                <h3>Samsung Galaxy</h3>
                <p>Galaxy S, Z, A series với giá tốt, trả góp 0%.</p>
            </div>
        </a>
    </div>
</section>

<!-- ĐIỆN THOẠI NỔI BẬT -->
<section class="section">
    <div class="section-header">
        <h2>Điện thoại nổi bật</h2>
        <a href="product-list.php?category=dien-thoai">Xem tất cả</a>
    </div>

    <?php if (empty($phones)): ?>
        <p>Hiện chưa có sản phẩm điện thoại trong hệ thống.</p>
    <?php else: ?>
        <div class="product-grid">
            <?php foreach ($phones as $p): ?>
                <?php
                $image  = $p['image'] ?: 'assets/img/placeholder-product.jpg';
                $hasOld = !is_null($p['old_price']) && $p['old_price'] > 0;
                [$discountAmount, $discountPercent] = $hasOld
                    ? calc_discount_info($p['price'], $p['old_price'])
                    : [0, 0];
                ?>
                <article class="product-card">
                    <a href="product-detail.php?slug=<?= urlencode($p['slug']) ?>" class="product-card__thumb">
                        <img src="<?= htmlspecialchars($image) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
                    </a>
                    <a href="product-detail.php?slug=<?= urlencode($p['slug']) ?>" class="product-card__name">
                        <?= htmlspecialchars($p['name']) ?>
                    </a>

                    <div class="product-card__prices">
                        <span class="product-card__price"><?= format_price($p['price']) ?></span>
                        <?php if ($hasOld): ?>
                            <span class="product-card__old"><?= format_price($p['old_price']) ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="product-card__badge-row">
                        <?php if ($discountAmount > 0): ?>
                            <span class="badge-sale">
                                Giảm <?= number_format($discountAmount, 0, ',', '.') ?>₫
                                <?php if ($discountPercent > 0): ?>
                                    (<?= $discountPercent ?>%)
                                <?php endif; ?>
                            </span>
                        <?php endif; ?>
                        <span class="badge-0p">Trả góp 0%</span>
                        <span class="badge-online">Online giá rẻ</span>
                    </div>

                    <!-- BỎ HẲN NÚT THÊM GIỎ & XEM CHI TIẾT Ở ĐÂY -->
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<!-- PHỤ KIỆN HOT -->
<section class="section">
    <div class="section-header">
        <h2>Phụ kiện hot</h2>
        <a href="product-list.php?category=phu-kien">Xem tất cả</a>
    </div>

    <?php if (empty($accessories)): ?>
        <p>Hiện chưa có phụ kiện trong hệ thống.</p>
    <?php else: ?>
        <div class="product-grid">
            <?php foreach ($accessories as $p): ?>
                <?php
                $image  = $p['image'] ?: 'assets/img/placeholder-product.jpg';
                $hasOld = !is_null($p['old_price']) && $p['old_price'] > 0;
                [$discountAmount, $discountPercent] = $hasOld
                    ? calc_discount_info($p['price'], $p['old_price'])
                    : [0, 0];
                ?>
                <article class="product-card">
                    <a href="product-detail.php?slug=<?= urlencode($p['slug']) ?>" class="product-card__thumb">
                        <img src="<?= htmlspecialchars($image) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
                    </a>
                    <a href="product-detail.php?slug=<?= urlencode($p['slug']) ?>" class="product-card__name">
                        <?= htmlspecialchars($p['name']) ?>
                    </a>

                    <div class="product-card__prices">
                        <span class="product-card__price"><?= format_price($p['price']) ?></span>
                        <?php if ($hasOld): ?>
                            <span class="product-card__old"><?= format_price($p['old_price']) ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="product-card__badge-row">
                        <?php if ($discountAmount > 0): ?>
                            <span class="badge-sale">
                                Giảm <?= number_format($discountAmount, 0, ',', '.') ?>₫
                                <?php if ($discountPercent > 0): ?>
                                    (<?= $discountPercent ?>%)
                                <?php endif; ?>
                            </span>
                        <?php endif; ?>
                        <span class="badge-online">Online giá rẻ</span>
                    </div>

                    <!-- BỎ HẲN NÚT THÊM GIỎ & XEM CHI TIẾT Ở ĐÂY -->
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php
require_once __DIR__ . '/includes/footer.php';
