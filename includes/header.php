<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/functions.php';

$pdo = db();
$cartCount = get_cart_count();

// Lấy categories để dùng cho menu phụ / filter
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>TechPhone - Cửa hàng điện thoại & phụ kiện</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="TechPhone - Website thương mại điện tử bán điện thoại & phụ kiện công nghệ, giao nhanh, trả góp 0%, thu cũ đổi mới.">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= ASSET_VERSION ?>">
    <!-- Icons (FontAwesome) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>
    <header class="site-header">
        <!-- Thanh trên cùng: Logo + Search + Hành động -->
        <div class="top-bar">
            <div class="container top-bar__inner">
                <!-- Logo -->
                <a href="index.php" class="logo">
                    <span class="logo__text">Tech<span>Phone</span></span>
                </a>

                <!-- Ô tìm kiếm -->
                <form class="search-bar" action="product-list.php" method="get">
                    <input
                        type="text"
                        name="q"
                        class="search-bar__input"
                        placeholder="Bạn cần tìm gì hôm nay?"
                        value="<?= htmlspecialchars($_GET['q'] ?? '') ?>"
                        autocomplete="off">
                    <button type="submit" class="search-bar__btn">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </button>
                </form>

                <!-- Hành động header (desktop) -->
                <div class="top-actions">
                    <a href="store-locator.php" class="top-actions__item">
                        <i class="fa-solid fa-location-dot"></i>
                        <span>Cửa hàng gần bạn</span>
                    </a>

                    <a href="order-tracking.php" class="top-actions__item">
                        <i class="fa-solid fa-truck-fast"></i>
                        <span>Tra cứu đơn hàng</span>
                    </a>

                    <a href="cart.php" class="top-actions__item">
                        <i class="fa-solid fa-cart-shopping"></i>
                        <span>Giỏ hàng</span>
                        <?php if ($cartCount > 0): ?>
                            <span class="badge badge--cart"><?= $cartCount ?></span>
                        <?php endif; ?>
                    </a>

                    <div class="top-actions__item top-actions__item--hotline">
                        <i class="fa-solid fa-headset"></i>
                        <div>
                            <span class="top-actions__label">Hotline</span>
                            <a href="tel:18000000" class="top-actions__hotline">1800 0000</a>
                        </div>
                    </div>

                    <!-- User -->
                    <div class="user-area">
                        <?php if (is_logged_in()): ?>
                            <div class="user-area__info">
                                <i class="fa-solid fa-circle-user"></i>
                                <span><?= htmlspecialchars($_SESSION['user']['username']) ?></span>
                                <?php if (is_admin()): ?>
                                    <span class="badge badge--role">Admin</span>
                                <?php endif; ?>
                            </div>
                            <div class="user-area__links">
                                <?php if (is_admin()): ?>
                                    <a href="admin/index.php" class="link-inline">Trang quản trị</a>
                                <?php endif; ?>
                                <a href="login.php?action=logout" class="link-inline link-inline--danger">Đăng xuất</a>
                            </div>
                        <?php else: ?>
                            <a href="login.php" class="btn btn--ghost">Đăng nhập</a>
                            <a href="register.php" class="btn btn--primary">Đăng ký</a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Nút menu mobile -->
                <button type="button" class="icon-btn icon-btn--menu js-menu-toggle" aria-label="Mở menu">
                    <i class="fa-solid fa-bars"></i>
                </button>
            </div>
        </div>

        <!-- Thanh menu danh mục -->
        <nav class="main-nav">
            <div class="container main-nav__inner">
                <ul class="main-nav__list main-nav__list--desktop">
                    <li><a href="product-list.php?category=dien-thoai"><i class="fa-solid fa-mobile-screen"></i> Điện thoại</a></li>
                    <li><a href="product-list.php?category=phu-kien"><i class="fa-solid fa-headphones"></i> Phụ kiện</a></li>
                    <li><a href="product-list.php">Tất cả sản phẩm</a></li>
                    <li><a href="lab.php"><i class="fa-solid fa-flask"></i> Lab</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <!-- MENU MOBILE SLIDE TỪ TRÁI -->
    <div class="mobile-drawer js-mobile-drawer">
        <div class="mobile-drawer__overlay js-close-drawer"></div>
        <aside class="mobile-drawer__panel">
            <div class="mobile-drawer__header">
                <div class="mobile-drawer__logo">
                    <span>Tech<span>Phone</span></span>
                </div>
                <button type="button" class="icon-btn js-close-drawer" aria-label="Đóng">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="mobile-drawer__body">
                <?php if (is_logged_in()): ?>
                    <div class="mobile-user">
                        <i class="fa-solid fa-circle-user"></i>
                        <div>
                            <strong><?= htmlspecialchars($_SESSION['user']['username']) ?></strong><br>
                            <?php if (is_admin()): ?>
                                <span class="badge badge--role">Admin</span>
                            <?php else: ?>
                                <span class="badge">Thành viên</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="mobile-user mobile-user--guest">
                        <i class="fa-solid fa-circle-user"></i>
                        <div>
                            <strong>Xin chào!</strong><br>
                            <span>Đăng nhập để trải nghiệm tốt hơn</span>
                        </div>
                    </div>
                    <div class="mobile-user__actions">
                        <a href="login.php" class="btn btn--ghost btn--full">Đăng nhập</a>
                        <a href="register.php" class="btn btn--primary btn--full">Đăng ký</a>
                    </div>
                <?php endif; ?>

                <div class="mobile-links">
                    <a href="store-locator.php"><i class="fa-solid fa-location-dot"></i> Cửa hàng gần bạn</a>
                    <a href="order-tracking.php"><i class="fa-solid fa-truck-fast"></i> Tra cứu đơn hàng</a>
                    <a href="cart.php">
                        <i class="fa-solid fa-cart-shopping"></i>
                        Giỏ hàng
                        <?php if ($cartCount > 0): ?>
                            <span class="badge badge--cart"><?= $cartCount ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="tel:18000000"><i class="fa-solid fa-headset"></i> Hotline: 1800 0000</a>
                </div>

                <hr class="divider">

                <div class="mobile-nav">
                    <h4>Danh mục</h4>
                    <a href="product-list.php?category=dien-thoai"><i class="fa-solid fa-mobile-screen"></i> Điện thoại</a>
                    <a href="product-list.php?category=phu-kien"><i class="fa-solid fa-headphones"></i> Phụ kiện</a>
                    <a href="product-list.php"><i class="fa-solid fa-list"></i> Tất cả sản phẩm</a>
                    <a href="lab.php"><i class="fa-solid fa-flask"></i> Lab</a>
                </div>

                <?php if (!empty($categories)): ?>
                    <hr class="divider">
                    <div class="mobile-nav">
                        <h4>Hãng sản xuất</h4>
                        <?php foreach ($categories as $cat): ?>
                            <a href="product-list.php?category=<?= urlencode($cat['slug']) ?>">
                                <i class="fa-solid fa-angle-right"></i> <?= htmlspecialchars($cat['name']) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (is_logged_in()): ?>
                <div class="mobile-drawer__footer">
                    <a href="login.php?action=logout" class="link-inline link-inline--danger">
                        <i class="fa-solid fa-right-from-bracket"></i> Đăng xuất
                    </a>
                </div>
            <?php endif; ?>
        </aside>
    </div>

    <!-- MINI-CART OVERLAY -->
    <div class="mini-cart js-mini-cart">
        <div class="mini-cart__overlay js-close-mini-cart"></div>
        <aside class="mini-cart__panel">
            <div class="mini-cart__header">
                <h3>Giỏ hàng của bạn</h3>
                <button type="button" class="icon-btn js-close-mini-cart" aria-label="Đóng giỏ hàng">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="mini-cart__body">
                <?php
                // LẤY GIỎ
                $cartItems = get_cart_items();
                $totalMini = 0;

                if (empty($cartItems)):
                ?>
                    <p class="mini-cart__empty">Chưa có sản phẩm nào trong giỏ hàng.</p>
                <?php
                else:
                    // Dùng connection hiện tại
                    $pdoMini = $pdo;

                    // Lấy danh sách product_id từ giỏ hàng (cấu trúc mới: có product_id trong mảng)
                    $productIds = [];
                    foreach ($cartItems as $key => $item) {
                        if (isset($item['product_id'])) {
                            $pid = (int)$item['product_id'];
                        } else {
                            // fallback kiểu cũ: key chính là product_id
                            $pid = (int)$key;
                        }
                        if ($pid > 0) {
                            $productIds[] = $pid;
                        }
                    }
                    $productIds = array_unique($productIds);

                    $productsMap = [];
                    if (!empty($productIds)) {
                        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
                        $stmtP = $pdoMini->prepare(
                            "SELECT id, name, price, image, slug 
                         FROM products 
                         WHERE id IN ($placeholders)"
                        );
                        $stmtP->execute($productIds);
                        $rowsP = $stmtP->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($rowsP as $rowP) {
                            $productsMap[(int)$rowP['id']] = $rowP;
                        }
                    }

                    // Statement cho variant: lấy cả price + image
                    $stmtVariant = $pdoMini->prepare("
                    SELECT price, image
                    FROM product_variants
                    WHERE product_id = ? AND color = ? AND storage = ?
                    LIMIT 1
                ");
                ?>
                    <ul class="mini-cart__list">
                        <?php foreach ($cartItems as $key => $item): ?>
                            <?php
                            $pid     = isset($item['product_id']) ? (int)$item['product_id'] : (int)$key;
                            $qty     = isset($item['quantity']) ? (int)$item['quantity'] : 1;
                            $color   = $item['color']   ?? '';
                            $storage = $item['storage'] ?? '';

                            if ($pid <= 0 || !isset($productsMap[$pid]) || $qty <= 0) {
                                continue;
                            }

                            $p        = $productsMap[$pid];
                            $price    = (int)$p['price'];           // giá mặc định
                            $prodImg  = $p['image'] ?? '';          // ảnh sản phẩm chung
                            $variantImg = '';                        // ảnh theo biến thể, nếu có

                            // Nếu có màu/dung lượng, thử lấy variant
                            if ($color !== '' || $storage !== '') {
                                $stmtVariant->execute([$pid, $color, $storage]);
                                $vRow = $stmtVariant->fetch(PDO::FETCH_ASSOC);
                                if ($vRow) {
                                    if (isset($vRow['price'])) {
                                        $price = (int)$vRow['price'];
                                    }
                                    if (!empty($vRow['image'])) {
                                        $variantImg = $vRow['image'];
                                    }
                                }
                            }

                            // Ảnh ưu tiên: ảnh biến thể → ảnh sản phẩm → placeholder
                            $imageToUse = $variantImg ?: $prodImg;
                            $lineTotal  = $price * $qty;
                            $totalMini += $lineTotal;
                            ?>
                            <li class="mini-cart__item">
                                <a href="product-detail.php?slug=<?= urlencode($p['slug']) ?>" class="mini-cart__thumb">
                                    <img src="<?= htmlspecialchars($imageToUse ?: 'assets/img/placeholder-product.jpg') ?>"
                                        alt="<?= htmlspecialchars($p['name']) ?>">
                                </a>
                                <div class="mini-cart__info">
                                    <a href="product-detail.php?slug=<?= urlencode($p['slug']) ?>" class="mini-cart__name">
                                        <?= htmlspecialchars($p['name']) ?>
                                    </a>
                                    <div class="mini-cart__meta">
                                        <span class="mini-cart__price"><?= format_price($price) ?></span>
                                        <span class="mini-cart__qty">x <?= $qty ?></span>
                                    </div>
                                    <?php if ($color || $storage): ?>
                                        <div style="font-size:0.8rem;color:#6b7280;margin-top:2px;">
                                            <?php if ($color): ?>
                                                <span>Màu: <strong><?= htmlspecialchars($color) ?></strong></span>
                                            <?php endif; ?>
                                            <?php if ($storage): ?>
                                                <span style="margin-left:6px;">Dung lượng: <strong><?= htmlspecialchars($storage) ?></strong></span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="mini-cart__line-total"><?= format_price($lineTotal) ?></div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <div class="mini-cart__footer">
                <div class="mini-cart__summary">
                    <span>Tạm tính</span>
                    <strong><?= format_price($totalMini ?? 0) ?></strong>
                </div>
                <div class="mini-cart__actions">
                    <a href="cart.php" class="btn btn--ghost btn--full">Xem giỏ hàng</a>
                    <a href="checkout.php" class="btn btn--primary btn--full">Tiến hành đặt hàng</a>
                </div>
            </div>
        </aside>
    </div>

    <main class="site-main">
        <div class="container">
