<?php
// product-list.php
require_once __DIR__ . '/includes/functions.php';

// Thêm vào giỏ hàng từ trang danh sách
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_to_cart') {
    $productId = (int)($_POST['product_id'] ?? 0);
    $quantity  = (int)($_POST['quantity'] ?? 1);

    if ($productId > 0) {
        if ($quantity < 1) $quantity = 1;
        add_to_cart($productId, $quantity);
    }

    // Giữ lại query string khi redirect (để không mất bộ lọc)
    $query = $_GET;
    $qs = $query ? ('?' . http_build_query($query)) : '';
    redirect('product-list.php' . $qs);
}

require_once __DIR__ . '/includes/header.php';

$pdo = db();

// Lấy tham số filter & sort từ URL
$q           = trim($_GET['q'] ?? '');
$categorySlug = trim($_GET['category'] ?? '');
$brand       = trim($_GET['brand'] ?? '');       // apple, samsung, xiaomi, ...
$priceRange  = trim($_GET['price'] ?? '');       // under-5, 5-10, 10-20, over-20
$storage     = trim($_GET['storage'] ?? '');     // 64, 128, 256, 512
$screen      = trim($_GET['screen'] ?? '');      // amoled, ips, ltpo
$sort        = trim($_GET['sort'] ?? 'default'); // default, price_asc, price_desc, bestseller, newest

// Xây WHERE động
$where  = [];
$params = [];

// Search - match by name, description, slug, or specs
if ($q !== '') {
    $where[] = '(p.name LIKE ? OR p.description LIKE ? OR p.slug LIKE ? OR p.specs LIKE ?)';
    $params[] = '%' . $q . '%';
    $params[] = '%' . $q . '%';
    $params[] = '%' . $q . '%';
    $params[] = '%' . $q . '%';
}

// Category tổng quát (Điện thoại, Phụ kiện, iPhone, Samsung...)
if ($categorySlug !== '') {
    $where[] = 'c.slug = ?';
    $params[] = $categorySlug;
}

// Brand (Hãng sản xuất) → map sang slug danh mục
if ($brand !== '') {
    $brandMap = [
        'apple'   => 'iphone',
        'samsung' => 'samsung',
        'xiaomi'  => 'xiaomi',
        'oppo'    => 'oppo',
        'vivo'    => 'vivo',
        'realme'  => 'realme',
        'tecno'   => 'tecno',
    ];

    if (isset($brandMap[$brand])) {
        $where[] = 'c.slug = ?';
        $params[] = $brandMap[$brand];
    }
}

// Price range
switch ($priceRange) {
    case 'under-5':
        $where[] = 'p.price < 5000000';
        break;
    case '5-10':
        $where[] = 'p.price BETWEEN 5000000 AND 10000000';
        break;
    case '10-20':
        $where[] = 'p.price BETWEEN 10000000 AND 20000000';
        break;
    case 'over-20':
        $where[] = 'p.price > 20000000';
        break;
}

// Storage (dựa trên specs chứa 128GB, 256GB...)
if ($storage !== '') {
    $where[] = 'p.specs LIKE ?';
    $params[] = '%' . $storage . 'GB%';
}

// Screen type
if ($screen !== '') {
    $searchScreen = '';
    if ($screen === 'amoled') {
        $searchScreen = 'AMOLED';
    } elseif ($screen === 'ips') {
        $searchScreen = 'IPS';
    } elseif ($screen === 'ltpo') {
        $searchScreen = 'LTPO';
    }
    if ($searchScreen !== '') {
        $where[] = 'p.specs LIKE ?';
        $params[] = '%' . $searchScreen . '%';
    }
}

// Sắp xếp
$orderBy = 'p.created_at DESC'; // mặc định: mới nhất

// Có join bảng bán chạy
$joinBestSeller = true;

switch ($sort) {
    case 'price_asc':
        $orderBy = 'p.price ASC';
        break;
    case 'price_desc':
        $orderBy = 'p.price DESC';
        break;
    case 'newest':
        $orderBy = 'p.created_at DESC';
        break;
    case 'bestseller':
        $orderBy = 'COALESCE(s.sold_qty, 0) DESC, p.created_at DESC';
        break;
    default:
        // 'default' → mới nhất
        $orderBy = 'p.created_at DESC';
        break;
}

// SQL chính: join categories via product_categories (many-to-many) + subquery bán chạy
$sql = "
    SELECT p.*, c.name AS category_name, COALESCE(s.sold_qty, 0) AS sold_qty
    FROM products p
    JOIN product_categories pc ON p.id = pc.product_id
    JOIN categories c ON pc.category_id = c.id
    LEFT JOIN (
        SELECT product_id, SUM(quantity) AS sold_qty
        FROM order_items
        GROUP BY product_id
    ) s ON s.product_id = p.id
";

if (!empty($where)) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}

$sql .= ' GROUP BY p.id ORDER BY ' . $orderBy;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

/**
 * Hàm tính giảm giá
 */
function calc_discount_info_page($price, $old)
{
    $price = (float)$price;
    $old   = (float)$old;
    if ($old <= 0 || $old <= $price) {
        return [0, 0];
    }
    $amount = $old - $price;
    $percent = round($amount / $old * 100);
    return [$amount, $percent];
}
?>

<section class="section">
    <div class="section-header">
        <h2>Danh sách sản phẩm</h2>
        <span style="font-size:0.85rem;color:#6b7280;">
            Tìm thấy <?= count($products) ?> sản phẩm
        </span>
    </div>

    <div class="page-grid">
        <!-- CỘT TRÁI: BỘ LỌC -->
        <aside class="filter-sidebar">
            <form method="get" class="filter-form">
                <!-- Tìm kiếm -->
                <div class="filter-group">
                    <label class="filter-label" for="q">Từ khóa</label>
                    <input
                        type="text"
                        id="q"
                        name="q"
                        class="filter-input"
                        placeholder="Tên sản phẩm..."
                        value="<?= htmlspecialchars($q) ?>">
                </div>

                <!-- Hãng sản xuất -->
                <div class="filter-group">
                    <div class="filter-label">Hãng sản xuất</div>
                    <div class="filter-chips">
                        <?php
                        $brands = [
                            ''         => 'Tất cả',
                            'apple'    => 'Apple (iPhone)',
                            'samsung'  => 'Samsung',
                            'xiaomi'   => 'Xiaomi',
                            'oppo'     => 'OPPO',
                            'vivo'     => 'vivo',
                            'realme'   => 'realme',
                            'tecno'    => 'Tecno',
                        ];
                        foreach ($brands as $key => $label):
                            $active = ($brand === $key) ? 'chip--active' : '';
                        ?>
                            <button type="submit" name="brand" value="<?= htmlspecialchars($key) ?>" class="chip <?= $active ?>">
                                <?= htmlspecialchars($label) ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Mức giá -->
                <div class="filter-group">
                    <div class="filter-label">Mức giá</div>
                    <div class="filter-options">
                        <?php
                        $prices = [
                            'under-5' => 'Dưới 5 triệu',
                            '5-10'    => '5 - 10 triệu',
                            '10-20'   => '10 - 20 triệu',
                            'over-20' => 'Trên 20 triệu',
                        ];
                        foreach ($prices as $key => $label):
                        ?>
                            <label class="filter-option">
                                <input
                                    type="radio"
                                    name="price"
                                    value="<?= $key ?>"
                                    <?= $priceRange === $key ? 'checked' : '' ?>>
                                <span><?= htmlspecialchars($label) ?></span>
                            </label>
                        <?php endforeach; ?>
                        <label class="filter-option">
                            <input
                                type="radio"
                                name="price"
                                value=""
                                <?= $priceRange === '' ? 'checked' : '' ?>>
                            <span>Tất cả</span>
                        </label>
                    </div>
                </div>

                <!-- Dung lượng -->
                <div class="filter-group">
                    <div class="filter-label">Dung lượng lưu trữ</div>
                    <div class="filter-options filter-options--multi">
                        <?php
                        $storages = ['64', '128', '256', '512'];
                        foreach ($storages as $s):
                        ?>
                            <label class="filter-option">
                                <input
                                    type="radio"
                                    name="storage"
                                    value="<?= $s ?>"
                                    <?= $storage === $s ? 'checked' : '' ?>>
                                <span><?= $s ?>GB</span>
                            </label>
                        <?php endforeach; ?>
                        <label class="filter-option">
                            <input
                                type="radio"
                                name="storage"
                                value=""
                                <?= $storage === '' ? 'checked' : '' ?>>
                            <span>Tất cả</span>
                        </label>
                    </div>
                </div>

                <!-- Màn hình -->
                <div class="filter-group">
                    <div class="filter-label">Loại màn hình</div>
                    <div class="filter-options filter-options--multi">
                        <?php
                        $screens = [
                            'amoled' => 'AMOLED',
                            'ips'    => 'IPS LCD',
                            'ltpo'   => 'LTPO',
                        ];
                        foreach ($screens as $key => $label):
                        ?>
                            <label class="filter-option">
                                <input
                                    type="radio"
                                    name="screen"
                                    value="<?= $key ?>"
                                    <?= $screen === $key ? 'checked' : '' ?>>
                                <span><?= htmlspecialchars($label) ?></span>
                            </label>
                        <?php endforeach; ?>
                        <label class="filter-option">
                            <input
                                type="radio"
                                name="screen"
                                value=""
                                <?= $screen === '' ? 'checked' : '' ?>>
                            <span>Tất cả</span>
                        </label>
                    </div>
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn btn--primary btn--full">Áp dụng bộ lọc</button>
                    <a href="product-list.php" class="btn btn--ghost btn--full">Xóa bộ lọc</a>
                </div>
            </form>
        </aside>

        <!-- CỘT PHẢI: KẾT QUẢ + THANH SẮP XẾP -->
        <section class="product-list">
            <!-- Thanh sắp xếp -->
            <div class="sort-bar">
                <div class="sort-bar__left">
                    <?php if ($q !== ''): ?>
                        <span class="sort-chip">
                            Từ khóa: <strong><?= htmlspecialchars($q) ?></strong>
                        </span>
                    <?php endif; ?>
                    <?php if ($categorySlug !== ''): ?>
                        <span class="sort-chip">
                            Danh mục: <strong><?= htmlspecialchars($categorySlug) ?></strong>
                        </span>
                    <?php endif; ?>
                </div>
                <div class="sort-bar__right">
                    <span class="sort-bar__label">Sắp xếp:</span>
                    <?php
                    $sorts = [
                        'default'     => 'Mặc định',
                        'price_asc'   => 'Giá tăng dần',
                        'price_desc'  => 'Giá giảm dần',
                        'bestseller'  => 'Bán chạy nhất',
                        'newest'      => 'Mới nhất',
                    ];
                    // Giữ lại các tham số khác khi đổi sort
                    $baseQuery = $_GET;
                    unset($baseQuery['sort']);
                    foreach ($sorts as $key => $label):
                        $isActive = ($sort === $key || ($sort === '' && $key === 'default'));
                        $query = $baseQuery;
                        $query['sort'] = $key;
                        $url = 'product-list.php?' . http_build_query($query);
                    ?>
                        <a href="<?= htmlspecialchars($url) ?>" class="sort-link <?= $isActive ? 'sort-link--active' : '' ?>">
                            <?= htmlspecialchars($label) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Danh sách sản phẩm -->
            <?php if (empty($products)): ?>
                <p>Không tìm thấy sản phẩm phù hợp với bộ lọc hiện tại.</p>
            <?php else: ?>
                <div class="product-grid">
                    <?php foreach ($products as $p): ?>
                        <?php
                        $image = $p['image'] ?: 'assets/img/placeholder-product.jpg';
                        $hasOld = !is_null($p['old_price']) && (float)$p['old_price'] > 0;
                        [$discountAmount, $discountPercent] = $hasOld
                            ? calc_discount_info_page($p['price'], $p['old_price'])
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
                                <?php if ($p['sold_qty'] > 0): ?>
                                    <span class="badge-online">Đã bán <?= (int)$p['sold_qty'] ?></span>
                                <?php endif; ?>
                                <span class="badge-0p">Trả góp 0%</span>
                            </div>

                            <div class="product-card__actions">
                                <a href="product-detail.php?slug=<?= urlencode($p['slug']) ?>" class="product-card__link">
                                    Xem chi tiết
                                </a>
                                <form method="post" class="add-cart-form">
                                    <input type="hidden" name="action" value="add_to_cart">
                                    <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
                                    <input type="hidden" name="quantity" value="1">
                                    <button type="submit" class="btn btn--primary">
                                        <i class="fa-solid fa-cart-plus" style="margin-right:4px;"></i> Thêm giỏ
                                    </button>
                                </form>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
</section>
<?php
require_once __DIR__ . '/includes/footer.php';
