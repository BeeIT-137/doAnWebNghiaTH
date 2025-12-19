<?php
// product-detail.php
require_once __DIR__ . '/includes/functions.php';

$pdo = db();

// XỬ LÝ THÊM VÀO GIỎ HÀNG
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_to_cart') {
    $productId = (int)($_POST['product_id'] ?? 0);
    $quantity  = (int)($_POST['quantity'] ?? 1);
    $color     = trim($_POST['color'] ?? '');
    $storage   = trim($_POST['storage'] ?? '');

    if ($productId > 0) {
        if ($quantity < 1) $quantity = 1;
        add_to_cart($productId, $quantity, $color, $storage);
    }


    if (isset($_POST['buy_now']) && $_POST['buy_now'] === '1') {
        redirect('checkout.php');
        exit;
    }

    $slug = $_GET['slug'] ?? '';
    $qs   = $slug ? ('?slug=' . urlencode($slug)) : '';
    redirect('product-detail.php' . $qs);
    exit;
}

// LẤY SẢN PHẨM THEO SLUG
$slug = trim($_GET['slug'] ?? '');
if ($slug === '') {
    require_once __DIR__ . '/includes/header.php';
    echo '<p>Không tìm thấy sản phẩm.</p>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$stmt = $pdo->prepare("
    SELECT p.*, c.name AS category_name
    FROM products p
    JOIN categories c ON p.category_id = c.id
    WHERE p.slug = ?
    LIMIT 1
");
$stmt->execute([$slug]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

require_once __DIR__ . '/includes/header.php';

if (!$product) {
    echo '<p>Không tìm thấy sản phẩm.</p>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// SKU giả lập: SP + id
$sku = 'SP' . str_pad($product['id'], 6, '0', STR_PAD_LEFT);

// LẤY BIẾN THỂ TỪ BẢNG product_variants
$stmtVar = $pdo->prepare("
    SELECT id, product_id, color, storage, image, price, old_price, stock
    FROM product_variants
    WHERE product_id = ?
    ORDER BY price ASC
");
$stmtVar->execute([$product['id']]);
$variants = $stmtVar->fetchAll(PDO::FETCH_ASSOC);

// Danh sách color & storage duy nhất
$colors   = [];
$storages = [];
foreach ($variants as $v) {
    if ($v['color'] && !in_array($v['color'], $colors, true)) {
        $colors[] = $v['color'];
    }
    if ($v['storage'] && !in_array($v['storage'], $storages, true)) {
        $storages[] = $v['storage'];
    }
}

// Giá & ảnh mặc định
$basePrice   = (int)$product['price'];
$baseImage   = $product['image'] ?: 'assets/img/placeholder-product.jpg';
$oldPrice    = !is_null($product['old_price']) ? (int)$product['old_price'] : 0;
$hasOld      = $oldPrice > 0;

$initialPrice   = $basePrice;
$initialImage   = $baseImage;
$defaultColor   = '';
$defaultStorage = '';

if (!empty($variants)) {
    $initialPrice   = (int)$variants[0]['price'];
    $initialImage   = $variants[0]['image'] ?: $baseImage;
    $defaultColor   = $variants[0]['color']   ?? '';
    $defaultStorage = $variants[0]['storage'] ?? '';
}

// Tính % giảm
$discountPercent = 0;
if ($hasOld && $oldPrice > $initialPrice) {
    $discountPercent = round(100 * ($oldPrice - $initialPrice) / $oldPrice);
}

// Gallery ảnh: mỗi biến thể chỉ 1 ảnh → ở đây chỉ hiển thị 1 ảnh hiện tại
$galleryImages = [
    $initialImage,
];

// Chuẩn bị data variant cho JS (có cả image)
$variantData = [];
foreach ($variants as $v) {
    $variantData[] = [
        'color'     => $v['color'],
        'storage'   => $v['storage'],
        'price'     => (int)$v['price'],
        'old_price' => $v['old_price'] !== null ? (int)$v['old_price'] : null,
        'stock'     => (int)$v['stock'],
        'image'     => $v['image'] ?: $baseImage,
    ];
}
?>

<section class="section">
    <div class="section-header">
        <h2>Chi tiết sản phẩm</h2>
        <a href="product-list.php">← Quay lại danh sách</a>
    </div>

    <div class="product-detail">
        <!-- Cột trái: ảnh & gallery -->
        <div class="product-detail__left">
            <div class="product-detail__image-main">
                <img id="mainProductImage" src="<?= htmlspecialchars($initialImage) ?>"
                    alt="<?= htmlspecialchars($product['name']) ?>">
                <span class="product-detail__discount-badge" id="discountBadge"
                    style="<?= $discountPercent > 0 ? '' : 'display:none;' ?>">
                    -<?= $discountPercent ?>%
                </span>
            </div>

            <!-- Gallery: mỗi màu chỉ 1 ảnh nên chỉ hiển thị đúng 1 thumbnail -->
            <div class="product-detail__gallery">
                <?php foreach ($galleryImages as $index => $img): ?>
                    <button type="button"
                        class="product-detail__thumb is-active"
                        data-image="<?= htmlspecialchars($img) ?>">
                        <img src="<?= htmlspecialchars($img) ?>" alt="Ảnh 1">
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Cột phải: thông tin chính -->
        <div class="product-detail__right">
            <h1 class="product-detail__title"><?= htmlspecialchars($product['name']) ?></h1>
            <div class="product-detail__meta">
                <span>Mã sản phẩm: <strong><?= htmlspecialchars($sku) ?></strong></span>
                <span>Danh mục: <strong><?= htmlspecialchars($product['category_name']) ?></strong></span>
            </div>

            <div class="product-detail__price-block">
                <div>
                    <div
                        class="product-detail__price"
                        id="productPrice"
                        data-base-price="<?= $basePrice ?>"
                        data-old-price="<?= $oldPrice ?>">
                        <?= format_price($initialPrice) ?>
                    </div>
                    <div
                        class="product-detail__old"
                        id="productOldPrice"
                        style="<?= $hasOld ? '' : 'display:none;' ?>">
                        <?= $hasOld ? format_price($oldPrice) : '' ?>
                    </div>
                </div>
                <div class="product-detail__badge-off" id="productDiscountText"
                    style="<?= $discountPercent > 0 ? '' : 'display:none;' ?>">
                    Giảm <?= $discountPercent ?>% so với giá niêm yết
                </div>
            </div>

            <!-- Ưu đãi -->
            <div class="product-detail__offers">
                <h3>Ưu đãi</h3>
                <ul>
                    <li><i class="fa-solid fa-circle-check"></i> Trả góp 0% qua thẻ tín dụng, xét duyệt nhanh.</li>
                    <li><i class="fa-solid fa-circle-check"></i> Bảo hành chính hãng 12 tháng.</li>
                    <li><i class="fa-solid fa-circle-check"></i> Thu cũ đổi mới, trợ giá lên đến 5.000.000₫.</li>
                </ul>
            </div>

            <!-- Tùy chọn phiên bản từ DB -->
            <?php if (!empty($variants)): ?>
                <div class="product-detail__options">
                    <?php if (!empty($colors)): ?>
                        <div class="option-group">
                            <div class="option-label">Màu sắc</div>
                            <div class="option-list">
                                <?php foreach ($colors as $color): ?>
                                    <button
                                        type="button"
                                        class="option-chip js-color-chip <?= $color === $defaultColor ? 'is-active' : '' ?>"

                                        data-color="<?= htmlspecialchars($color) ?>">
                                        <?= htmlspecialchars($color) ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($storages)): ?>
                        <div class="option-group">
                            <div class="option-label">Dung lượng</div>
                            <div class="option-list">
                                <?php foreach ($storages as $st): ?>
                                    <button
                                        type="button"
                                        class="option-chip js-storage-chip <?= $st === $defaultStorage ? 'is-active' : '' ?>"
                                        data-storage="<?= htmlspecialchars($st) ?>">
                                        <?= htmlspecialchars($st) ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <p style="font-size:0.85rem;color:#6b7280;margin-bottom:8px;">
                    Sản phẩm này không có biến thể màu/dung lượng, chỉ một phiên bản duy nhất.
                </p>
            <?php endif; ?>

            <!-- Nút hành động -->
            <div class="product-detail__actions">
                <!-- form MUA NGAY -> thêm vào giỏ & chuyển thẳng checkout -->
                <form method="post" class="product-detail__buy-now">
                    <input type="hidden" name="action" value="add_to_cart">
                    <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
                    <input type="hidden" name="quantity" value="1">
                    <input type="hidden" name="buy_now" value="1">

                    <input type="hidden" name="color" id="selectedColorInput"
                        value="<?= htmlspecialchars($defaultColor) ?>">
                    <input type="hidden" name="storage" id="selectedStorageInput"
                        value="<?= htmlspecialchars($defaultStorage) ?>">

                    <button type="submit" class="btn btn--primary btn--full" id="btnBuyNow">
                        Mua ngay
                    </button>
                </form>

                <div class="product-detail__cta-row">
                    <!-- form THÊM VÀO GIỎ (ở lại trang chi tiết) -->
                    <form method="post">
                        <input type="hidden" name="action" value="add_to_cart">
                        <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
                        <input type="hidden" name="quantity" value="1">

                        <input type="hidden" name="color" id="selectedColorInput2"
                            value="<?= htmlspecialchars($defaultColor) ?>">
                        <input type="hidden" name="storage" id="selectedStorageInput2"
                            value="<?= htmlspecialchars($defaultStorage) ?>">

                        <button type="submit" class="btn btn--ghost" id="btnAddCart">
                            <i class="fa-solid fa-cart-plus" style="margin-right:4px;"></i> Thêm vào giỏ
                        </button>
                    </form>
                </div>

                <p id="variantMessage"
                    style="margin-top:6px;font-size:0.8rem;color:#ef4444;display:none;">
                    Phiên bản này hiện chưa có trong hệ thống, vui lòng chọn tổ hợp màu/dung lượng khác.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- TAB: Thông số - Mô tả - Đánh giá -->
<section class="section">
    <div class="product-tabs">
        <button type="button" class="product-tab-btn is-active" data-tab="specs">Thông số kỹ thuật</button>
        <button type="button" class="product-tab-btn" data-tab="description">Mô tả sản phẩm</button>
        <button type="button" class="product-tab-btn" data-tab="reviews">Đánh giá & nhận xét</button>
    </div>

    <div class="product-tabs__content">
        <div class="product-tab-pane is-active" id="tab-specs">
            <?php if (!empty($product['specs'])): ?>
                <p><?= nl2br(htmlspecialchars($product['specs'])) ?></p>
            <?php else: ?>
                <p>Thông số kỹ thuật đang được cập nhật.</p>
            <?php endif; ?>
        </div>

        <div class="product-tab-pane" id="tab-description">
            <?php if (!empty($product['description'])): ?>
                <p><?= nl2br(htmlspecialchars($product['description'])) ?></p>
            <?php else: ?>
                <p>Mô tả sản phẩm đang được cập nhật.</p>
            <?php endif; ?>
        </div>

        <div class="product-tab-pane" id="tab-reviews">
            <p>Chức năng đánh giá & nhận xét sẽ được cập nhật sau.</p>
            <p>Hiện tại, vui lòng liên hệ hotline <strong>1800 0000</strong> để được tư vấn chi tiết.</p>
        </div>
    </div>
</section>

<script>
    // Data biến thể từ PHP (có cả image)
    const PRODUCT_VARIANTS = <?= json_encode($variantData, JSON_UNESCAPED_UNICODE) ?>;

    document.addEventListener('DOMContentLoaded', () => {
        // GALLERY ẢNH (hiện tại chỉ 1 thumbnail, nhưng vẫn giữ logic)
        const mainImg = document.getElementById('mainProductImage');
        const thumbs = document.querySelectorAll('.product-detail__thumb');

        thumbs.forEach(btn => {
            btn.addEventListener('click', () => {
                const img = btn.getAttribute('data-image');
                if (img && mainImg) {
                    mainImg.src = img;
                }
                thumbs.forEach(t => t.classList.remove('is-active'));
                btn.classList.add('is-active');
            });
        });

        // TABS
        const tabButtons = document.querySelectorAll('.product-tab-btn');
        const tabPanes = document.querySelectorAll('.product-tab-pane');

        tabButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                const tab = btn.getAttribute('data-tab');
                tabButtons.forEach(b => b.classList.remove('is-active'));
                btn.classList.add('is-active');

                tabPanes.forEach(pane => pane.classList.remove('is-active'));
                const activePane = document.getElementById('tab-' + tab);
                if (activePane) activePane.classList.add('is-active');
            });
        });

        // BIẾN THỂ: MÀU & DUNG LƯỢNG
        const colorChips   = document.querySelectorAll('.js-color-chip');
        const storageChips = document.querySelectorAll('.js-storage-chip');
        const priceEl      = document.getElementById('productPrice');
        const oldPriceEl   = document.getElementById('productOldPrice');
        const discountText = document.getElementById('productDiscountText');
        const discountBadge= document.getElementById('discountBadge');
        const variantMsg   = document.getElementById('variantMessage');

        const hiddenColor1   = document.getElementById('selectedColorInput');
        const hiddenStorage1 = document.getElementById('selectedStorageInput');
        const hiddenColor2   = document.getElementById('selectedColorInput2');
        const hiddenStorage2 = document.getElementById('selectedStorageInput2');

        const baseOldPrice   = parseInt(priceEl.dataset.oldPrice || '0', 10);
        const fallbackPrice  = parseInt(priceEl.dataset.basePrice || '0', 10);

        function getActiveValue(nodeList, attr) {
            let val = '';
            nodeList.forEach(chip => {
                if (chip.classList.contains('is-active')) {
                    val = chip.getAttribute(attr) || '';
                }
            });
            return val;
        }

        function formatCurrency(v) {
            try {
                return new Intl.NumberFormat('vi-VN', {
                    style: 'currency',
                    currency: 'VND',
                    maximumFractionDigits: 0
                }).format(v);
            } catch (e) {
                return v.toLocaleString('vi-VN') + '₫';
            }
        }

        // Lọc danh sách dung lượng theo màu đã chọn
        function filterStoragesByColor(selectedColor) {
            // Nếu không chọn màu hoặc không có biến thể → hiện tất cả
            if (!PRODUCT_VARIANTS || PRODUCT_VARIANTS.length === 0 || !selectedColor) {
                storageChips.forEach(chip => {
                    chip.style.display = '';
                });
                // Nếu không còn chip nào active thì set cái đầu tiên
                let hasActive = false;
                storageChips.forEach(chip => {
                    if (chip.classList.contains('is-active') && chip.style.display !== 'none') {
                        hasActive = true;
                    }
                });
                if (!hasActive && storageChips.length > 0) {
                    storageChips.forEach(c => c.classList.remove('is-active'));
                    storageChips[0].classList.add('is-active');
                }
                return;
            }

            // Lấy danh sách dung lượng hợp lệ cho màu đang chọn
            const allowedStorages = new Set(
                PRODUCT_VARIANTS
                    .filter(v => v.color === selectedColor && v.storage)
                    .map(v => v.storage)
            );

            // Ẩn/hiện từng chip dung lượng
            storageChips.forEach(chip => {
                const st = chip.getAttribute('data-storage') || '';
                if (!st || !allowedStorages.has(st)) {
                    chip.style.display = 'none';
                    chip.classList.remove('is-active');
                } else {
                    chip.style.display = '';
                }
            });

            // Nếu sau khi lọc không còn chip active → auto chọn dung lượng đầu tiên hợp lệ
            let activeChip = null;
            storageChips.forEach(chip => {
                if (chip.classList.contains('is-active') && chip.style.display !== 'none') {
                    activeChip = chip;
                }
            });
            if (!activeChip) {
                const firstVisible = Array.from(storageChips).find(c => c.style.display !== 'none');
                if (firstVisible) {
                    firstVisible.classList.add('is-active');
                }
            }
        }

        function updateVariantPrice() {
            const selectedColor   = getActiveValue(colorChips, 'data-color');
            const selectedStorage = getActiveValue(storageChips, 'data-storage');

            if (hiddenColor1)   hiddenColor1.value   = selectedColor;
            if (hiddenStorage1) hiddenStorage1.value = selectedStorage;
            if (hiddenColor2)   hiddenColor2.value   = selectedColor;
            if (hiddenStorage2) hiddenStorage2.value = selectedStorage;

            // Tìm đúng biến thể (ưu tiên trùng cả màu + dung lượng)
            let foundVariant = null;
            if (PRODUCT_VARIANTS && PRODUCT_VARIANTS.length > 0) {
                foundVariant = PRODUCT_VARIANTS.find(v =>
                    (!selectedColor   || v.color   === selectedColor) &&
                    (!selectedStorage || v.storage === selectedStorage)
                );
            }

            let priceToShow = fallbackPrice;
            let imageToShow = mainImg ? mainImg.src : null;

            if (foundVariant) {
                priceToShow = parseInt(foundVariant.price || fallbackPrice, 10);
                if (foundVariant.image) {
                    imageToShow = foundVariant.image;
                }
                if (variantMsg) variantMsg.style.display = 'none';
            } else {
                if (variantMsg) variantMsg.style.display = 'block';
            }

            // Cập nhật ảnh theo biến thể (mỗi màu chỉ 1 ảnh)
            if (mainImg && imageToShow) {
                mainImg.src = imageToShow;
            }
            const firstThumb = document.querySelector('.product-detail__thumb');
            if (firstThumb && imageToShow) {
                firstThumb.setAttribute('data-image', imageToShow);
                const imgTag = firstThumb.querySelector('img');
                if (imgTag) imgTag.src = imageToShow;
            }

            // Cập nhật giá
            if (priceEl) {
                priceEl.textContent = formatCurrency(priceToShow);
            }

            // Cập nhật % giảm nếu có oldPrice
            if (baseOldPrice > 0 && priceToShow < baseOldPrice) {
                const percent = Math.round(100 * (baseOldPrice - priceToShow) / baseOldPrice);
                if (discountText) {
                    discountText.style.display = '';
                    discountText.textContent = 'Giảm ' + percent + '% so với giá niêm yết';
                }
                if (discountBadge) {
                    discountBadge.style.display = '';
                    discountBadge.textContent = '-' + percent + '%';
                }
                if (oldPriceEl) oldPriceEl.style.display = '';
            } else {
                if (discountText) discountText.style.display = 'none';
                if (discountBadge) discountBadge.style.display = 'none';
                if (oldPriceEl && baseOldPrice <= 0) oldPriceEl.style.display = 'none';
            }
        }

        // Click màu
        colorChips.forEach(chip => {
            chip.addEventListener('click', () => {
                colorChips.forEach(c => c.classList.remove('is-active'));
                chip.classList.add('is-active');

                const selectedColor = chip.getAttribute('data-color') || '';
                filterStoragesByColor(selectedColor); // lọc dung lượng theo màu
                updateVariantPrice();
            });
        });

        // Click dung lượng
        storageChips.forEach(chip => {
            chip.addEventListener('click', () => {
                // Nếu chip đang bị ẩn thì bỏ qua
                if (chip.style.display === 'none') return;

                storageChips.forEach(c => c.classList.remove('is-active'));
                chip.classList.add('is-active');
                updateVariantPrice();
            });
        });

        // Khởi tạo lần đầu: đọc màu đang active (PHP set mặc định)
        const initialColor = getActiveValue(colorChips, 'data-color');
        filterStoragesByColor(initialColor);
        updateVariantPrice();
    });
</script>


<?php
require_once __DIR__ . '/includes/footer.php';
