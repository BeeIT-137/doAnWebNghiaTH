<?php
// cart.php
require_once __DIR__ . '/includes/functions.php';

$pdo       = db();
$csrfToken = generate_csrf_token();


//
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        redirect('cart.php');
    }
//

    // Xóa toàn bộ giỏ
    if (isset($_POST['clear'])) {
        clear_cart();
        redirect('cart.php');
        exit;
    }

    // Xóa 1 item (nút Xóa bên phải)
    if (isset($_POST['remove_key'])) {
        $removeKey = $_POST['remove_key'];
        remove_from_cart($removeKey);
        redirect('cart.php');
        exit;
    }


// 
    if (!empty($_POST['items']) && is_array($_POST['items'])) {
        foreach ($_POST['items'] as $key => $qty) {
            $qty = (int)$qty;
            update_cart($key, $qty);
        }
        redirect('cart.php');
        exit;
    }
}
//


// LẤY GIỎ HÀNG TỪ SESSION
$cartItems = get_cart_items();

// Include header SAU khi đã xử lý POST
require_once __DIR__ . '/includes/header.php';

// Nếu giỏ hàng trống
if (empty($cartItems)) {
?>
    <h1 class="page-title">Giỏ hàng</h1>
    <div class="cart-empty">
        <p>Giỏ hàng của bạn đang trống.</p>
        <a href="product-list.php" class="btn btn--primary">Tiếp tục mua sắm</a>
    </div>
<?php
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

// Lấy danh sách product_id để query sản phẩm
$productsMap = [];
$productIds  = [];

foreach ($cartItems as $key => $item) {
    // Hỗ trợ cả kiểu cũ (key là product_id) lẫn kiểu mới (có product_id trong mảng)
    $pid = isset($item['product_id']) ? (int)$item['product_id'] : (int)$key;
    if ($pid > 0) {
        $productIds[] = $pid;
    }
}
$productIds = array_unique($productIds);

if (!empty($productIds)) {
    $placeholders = implode(',', array_fill(0, count($productIds), '?'));
    $stmtP = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
    $stmtP->execute($productIds);
    $rowsP = $stmtP->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rowsP as $row) {
        $productsMap[(int)$row['id']] = $row;
    }
}

// Chuẩn bị query lấy giá & ảnh theo biến thể (nếu có)
$stmtVariant = $pdo->prepare("
    SELECT price, image 
    FROM product_variants
    WHERE product_id = ? AND color = ? AND storage = ?
    LIMIT 1
");

// Chuẩn bị mảng $items để render + tính tổng
$items      = [];
$grandTotal = 0;

foreach ($cartItems as $key => $item) {
    $pid     = isset($item['product_id']) ? (int)$item['product_id'] : (int)$key;
    $qty     = isset($item['quantity']) ? (int)$item['quantity'] : 1;
    $color   = $item['color']   ?? '';
    $storage = $item['storage'] ?? '';

    if ($pid <= 0 || !isset($productsMap[$pid]) || $qty <= 0) {
        continue;
    }

    $p           = $productsMap[$pid];
    $unitPrice   = (int)$p['price'];
    $variantImg  = '';
    $productImg  = $p['image'] ?? '';

    // Nếu có màu/dung lượng -> thử lấy giá & ảnh từ product_variants
    if ($color !== '' || $storage !== '') {
        $stmtVariant->execute([$pid, $color, $storage]);
        $vRow = $stmtVariant->fetch(PDO::FETCH_ASSOC);
        if ($vRow) {
            $unitPrice  = (int)$vRow['price'];
            $variantImg = $vRow['image'] ?? '';
        }
    }

    // Ảnh ưu tiên: ảnh biến thể -> ảnh sản phẩm -> placeholder
    $imageToUse = $variantImg ?: $productImg;

    $lineTotal  = $unitPrice * $qty;
    $grandTotal += $lineTotal;

    $items[] = [
        'key'        => $key,
        'product_id' => $pid,
        'name'       => $p['name'],
        'image'      => $imageToUse,
        'slug'       => $p['slug'] ?? '',
        'price'      => $unitPrice,
        'quantity'   => $qty,
        'color'      => $color,
        'storage'    => $storage,
        'line_total' => $lineTotal,
    ];
}
?>

<h1 class="page-title">Giỏ hàng</h1>

<div class="cart-layout">
    <!-- BẢNG SẢN PHẨM -->
    <div class="cart-table-wrap">
        <form method="post" id="cart-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Sản phẩm</th>
                        <th>Giá</th>
                        <th>Số lượng</th>
                        <th>Thành tiền</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="cart-body">
                    <?php foreach ($items as $item): ?>
                        <tr class="cart-row">
                            <td>
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <div class="cart-thumb">
                                        <img src="<?= htmlspecialchars($item['image'] ?: 'assets/img/placeholder-product.jpg') ?>"
                                            alt="<?= htmlspecialchars($item['name']) ?>">
                                    </div>
                                    <div>
                                        <a href="product-detail.php?slug=<?= urlencode($item['slug']) ?>"
                                            style="font-weight:500;font-size:0.95rem;">
                                            <?= htmlspecialchars($item['name']) ?>
                                        </a>
                                        <?php if ($item['color'] || $item['storage']): ?>
                                            <div style="font-size:0.8rem;color:#6b7280;margin-top:2px;">
                                                <?php if ($item['color']): ?>
                                                    Màu: <strong><?= htmlspecialchars($item['color']) ?></strong>
                                                <?php endif; ?>
                                                <?php if ($item['storage']): ?>
                                                    &nbsp;| Dung lượng: <strong><?= htmlspecialchars($item['storage']) ?></strong>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>

                            <td><?= format_price($item['price']) ?></td>

                            <td>
                                <div class="qty-control">
                                    <button type="button" class="qty-btn js-qty-minus">−</button>
                                    <input
                                        type="number"
                                        min="1"
                                        name="items[<?= htmlspecialchars($item['key']) ?>]"
                                        class="qty-input js-cart-qty"
                                        value="<?= $item['quantity'] ?>"
                                        data-unit-price="<?= (int)$item['price'] ?>">
                                    <button type="button" class="qty-btn js-qty-plus">+</button>
                                </div>
                            </td>

                            <td class="cart-line-total">
                                <?= format_price($item['line_total']) ?>
                            </td>

                            <td>
                                <button
                                    type="submit"
                                    name="remove_key"
                                    value="<?= htmlspecialchars($item['key']) ?>"
                                    class="remove-btn"
                                    onclick="return confirm('Xóa sản phẩm này khỏi giỏ hàng?');">
                                    <i class="fa-solid fa-trash"></i> Xóa
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="cart-table__actions">
                <button type="submit" class="btn btn--ghost">
                    Cập nhật giỏ hàng
                </button>
            </div>
        </form>
    </div>

    <!-- TÓM TẮT ĐƠN HÀNG -->
    <aside class="cart-summary">
        <h3>Tóm tắt giỏ hàng</h3>

        <div class="cart-summary__row">
            <span>Tạm tính</span>
            <span id="cart-subtotal" class="cart-total-value"><?= format_price($grandTotal) ?></span>
        </div>
        <div class="cart-summary__row">
            <span>Phí vận chuyển</span>
            <span>Miễn phí</span>
        </div>
        <div class="cart-summary__row cart-summary__row--total">
            <span>Tổng thanh toán</span>
            <span id="cart-total" class="cart-total-value"><?= format_price($grandTotal) ?></span>
        </div>

        <div style="margin-top:10px;display:flex;flex-direction:column;gap:6px;">
            <a href="checkout.php" class="btn btn--primary btn--full">
                Tiến hành đặt hàng
            </a>

            <form method="post" onsubmit="return confirm('Bạn muốn xóa toàn bộ giỏ hàng?');">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="clear" value="1">
                <button type="submit" class="btn btn--ghost btn--full">
                    Xóa giỏ hàng
                </button>
            </form>

            <a href="product-list.php" class="btn btn--ghost btn--full">
                Tiếp tục mua sắm
            </a>
        </div>
    </aside>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>

<script>
    // JS: cộng/trừ số lượng + cập nhật tổng tiền hiển thị
    document.addEventListener('DOMContentLoaded', function() {
        const rows = document.querySelectorAll('.cart-row');
        const subtotalSpan = document.getElementById('cart-subtotal');
        const totalSpan = document.getElementById('cart-total');

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

        function recalcCart() {
            let total = 0;

            rows.forEach(row => {
                const qtyInput = row.querySelector('.js-cart-qty');
                const lineCell = row.querySelector('.cart-line-total');
                if (!qtyInput || !lineCell) return;

                const unitPrice = parseInt(qtyInput.dataset.unitPrice || '0', 10);
                let qty = parseInt(qtyInput.value || '0', 10);
                if (isNaN(qty) || qty < 1) {
                    qty = 1;
                    qtyInput.value = 1;
                }

                const lineTotal = unitPrice * qty;
                total += lineTotal;

                lineCell.textContent = formatCurrency(lineTotal);
            });

            if (subtotalSpan) subtotalSpan.textContent = formatCurrency(total);
            if (totalSpan) totalSpan.textContent = formatCurrency(total);
        }

        rows.forEach(row => {
            const minusBtn = row.querySelector('.js-qty-minus');
            const plusBtn = row.querySelector('.js-qty-plus');
            const qtyInput = row.querySelector('.js-cart-qty');

            if (!qtyInput) return;

            minusBtn && minusBtn.addEventListener('click', function() {
                let val = parseInt(qtyInput.value || '1', 10);
                if (isNaN(val) || val <= 1) val = 1;
                else val -= 1;
                qtyInput.value = val;
                recalcCart();
            });

            plusBtn && plusBtn.addEventListener('click', function() {
                let val = parseInt(qtyInput.value || '1', 10);
                if (isNaN(val) || val < 1) val = 1;
                else val += 1;
                qtyInput.value = val;
                recalcCart();
            });

            qtyInput.addEventListener('change', recalcCart);
            qtyInput.addEventListener('input', recalcCart);
        });

        // Tính lần đầu
        recalcCart();
    });
</script>
