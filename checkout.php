<?php
// checkout.php - đặt hàng (bắt buộc đăng nhập)

require_once __DIR__ . '/includes/functions.php';
require_login();

$pdo        = db();
$cartItems  = get_cart_items();
$csrfToken  = generate_csrf_token();
$shippingFee = 30000;

// Giỏ trống -> thông báo
if (empty($cartItems)) {
    require_once __DIR__ . '/includes/header.php';
    ?>
    <h1 class="page-title">Thanh toán</h1>
    <div class="cart-empty">
        <p>Giỏ hàng của bạn đang trống, không thể tiến hành thanh toán.</p>
        <a href="product-list.php" class="btn btn--primary">Tiếp tục mua sắm</a>
    </div>
    <?php
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$errors  = [];
$success = '';
$orderId = null;

// Lấy thông tin sản phẩm để hiển thị và tính tổng
$productsMap = [];
$productIds  = [];

foreach ($cartItems as $key => $item) {
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
    foreach ($stmtP->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $productsMap[(int)$row['id']] = $row;
    }
}

$stmtVariant = $pdo->prepare("
    SELECT price, stock
    FROM product_variants
    WHERE product_id = ? AND color = ? AND storage = ?
    LIMIT 1
");

$grandTotal = 0;
foreach ($cartItems as $key => $item) {
    $pid     = isset($item['product_id']) ? (int)$item['product_id'] : (int)$key;
    $qty     = isset($item['quantity']) ? (int)$item['quantity'] : 1;
    $color   = $item['color']   ?? '';
    $storage = $item['storage'] ?? '';

    if ($pid <= 0 || !isset($productsMap[$pid]) || $qty <= 0) {
        continue;
    }

    $p     = $productsMap[$pid];
    $price = (int)$p['price'];

    if ($color !== '' || $storage !== '') {
        $stmtVariant->execute([$pid, $color, $storage]);
        $vRow = $stmtVariant->fetch(PDO::FETCH_ASSOC);
        if ($vRow) {
            $price = (int)$vRow['price'];
        }
    }

    $grandTotal += $price * $qty;
}

// Submit đơn hàng
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Phiếu không hợp lệ, vui lòng thử lại.';
    }

    $fullname = trim($_POST['fullname'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $address  = trim($_POST['address'] ?? '');
    $note     = trim($_POST['note'] ?? '');

    if ($fullname === '') {
        $errors[] = 'Vui lòng nhập họ tên người nhận.';
    }
    if ($phone === '') {
        $errors[] = 'Vui lòng nhập số điện thoại.';
    }
    if ($address === '') {
        $errors[] = 'Vui lòng nhập địa chỉ nhận hàng.';
    }

    // Kiểm tra tồn kho cho từng item
    if (empty($errors)) {
        foreach ($cartItems as $key => $item) {
            $pid     = isset($item['product_id']) ? (int)$item['product_id'] : (int)$key;
            $qty     = isset($item['quantity']) ? (int)$item['quantity'] : 1;
            $color   = $item['color']   ?? '';
            $storage = $item['storage'] ?? '';

            if ($pid <= 0 || !isset($productsMap[$pid]) || $qty <= 0) {
                $errors[] = 'Sản phẩm không hợp lệ trong giỏ hàng.';
                continue;
            }

            $p         = $productsMap[$pid];
            $available = (int)$p['stock'];

            if ($color !== '' || $storage !== '') {
                $stmtVariant->execute([$pid, $color, $storage]);
                $vRow = $stmtVariant->fetch(PDO::FETCH_ASSOC);
                if ($vRow) {
                    $available = (int)$vRow['stock'];
                } else {
                    $available = 0;
                }
            }

            if ($available < $qty) {
                $errors[] = 'Sản phẩm "' . $p['name'] . '" không đủ tồn kho (còn ' . $available . ', cần ' . $qty . ').';
            }
        }
    }

    if (empty($errors)) {
        $userId = current_user_id();
        if (!$userId) {
            redirect('login.php');
        }

        try {
            $pdo->beginTransaction();

            $stmtOrder = $pdo->prepare("
                INSERT INTO orders (user_id, total, status, created_at,
                                    customer_name, customer_phone, customer_address, customer_note)
                VALUES (:user_id, :total, :status, NOW(),
                        :customer_name, :customer_phone, :customer_address, :customer_note)
            ");
            $stmtOrder->execute([
                ':user_id'          => $userId,
                ':total'            => $grandTotal + $shippingFee,
                ':status'           => 'pending',
                ':customer_name'    => $fullname,
                ':customer_phone'   => $phone,
                ':customer_address' => $address,
                ':customer_note'    => $note !== '' ? $note : null,
            ]);

            $orderId = (int)$pdo->lastInsertId();

            $stmtItem = $pdo->prepare("
                INSERT INTO order_items 
                    (order_id, product_id, quantity, price, color, storage)
                VALUES 
                    (:order_id, :product_id, :quantity, :price, :color, :storage)
            ");

            foreach ($cartItems as $key => $item) {
                $pid     = isset($item['product_id']) ? (int)$item['product_id'] : (int)$key;
                $qty     = isset($item['quantity']) ? (int)$item['quantity'] : 1;
                $color   = $item['color']   ?? '';
                $storage = $item['storage'] ?? '';

                if ($pid <= 0 || !isset($productsMap[$pid]) || $qty <= 0) {
                    continue;
                }

                $p     = $productsMap[$pid];
                $price = (int)$p['price'];

                if ($color !== '' || $storage !== '') {
                    $stmtVariant->execute([$pid, $color, $storage]);
                    $vRow = $stmtVariant->fetch(PDO::FETCH_ASSOC);
                    if ($vRow) {
                        $price = (int)$vRow['price'];
                    }
                }

                $stmtItem->execute([
                    ':order_id'   => $orderId,
                    ':product_id' => $pid,
                    ':quantity'   => $qty,
                    ':price'      => $price,
                    ':color'      => $color,
                    ':storage'    => $storage,
                ]);
            }

            $pdo->commit();
            clear_cart();
            $success = 'Đặt hàng thành công! Mã đơn của bạn là #' . $orderId;
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Có lỗi xảy ra khi tạo đơn hàng. Vui lòng thử lại.';
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<h1 class="page-title">Thanh toán</h1>

<div class="checkout-layout">
    <div class="checkout-form">
        <?php if (!empty($errors)): ?>
            <div class="alert alert--error">
                <?php foreach ($errors as $err): ?>
                    <div><?= htmlspecialchars($err) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert--success">
                <?= htmlspecialchars($success) ?><br>
                <a href="order-tracking.php" class="link-inline">Tra cứu đơn hàng</a>
                hoặc
                <a href="product-list.php" class="link-inline">tiếp tục mua sắm</a>.
            </div>
        <?php else: ?>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <div class="form-group">
                    <label>Họ tên người nhận</label>
                    <input type="text" name="fullname" required placeholder="Nhập họ tên đầy đủ">
                </div>
                <div class="form-group">
                    <label>Số điện thoại</label>
                    <input type="text" name="phone" required placeholder="VD: 098xxxxxxx">
                </div>
                <div class="form-group">
                    <label>Email (không bắt buộc)</label>
                    <input type="email" name="email" placeholder="VD: user@example.com">
                </div>
                <div class="form-group">
                    <label>Địa chỉ nhận hàng</label>
                    <textarea name="address" rows="3" required placeholder="Số nhà, đường, phường/xã, quận/huyện, tỉnh/thành"></textarea>
                </div>
                <div class="form-group">
                    <label>Ghi chú (không bắt buộc)</label>
                    <textarea name="note" rows="2" placeholder="VD: Giao giờ hành chính, gọi trước khi giao..."></textarea>
                </div>

                <button type="submit" class="btn btn--primary btn--full">
                    <i class="fa-solid fa-check"></i> Xác nhận đặt hàng
                </button>
            </form>
        <?php endif; ?>
    </div>

    <aside class="checkout-summary">
        <h3>Tạm tính đơn hàng</h3>
        <div class="checkout-summary__body">
            <?php foreach ($cartItems as $key => $item): ?>
                <?php
                $pid     = isset($item['product_id']) ? (int)$item['product_id'] : (int)$key;
                $qty     = isset($item['quantity']) ? (int)$item['quantity'] : 1;
                $color   = $item['color']   ?? '';
                $storage = $item['storage'] ?? '';

                if ($pid <= 0 || !isset($productsMap[$pid]) || $qty <= 0) {
                    continue;
                }

                $p     = $productsMap[$pid];
                $price = (int)$p['price'];

                if ($color !== '' || $storage !== '') {
                    $stmtVariant->execute([$pid, $color, $storage]);
                    $vRow = $stmtVariant->fetch(PDO::FETCH_ASSOC);
                    if ($vRow) {
                        $price = (int)$vRow['price'];
                    }
                }

                $lineTotal = $price * $qty;
                ?>
                <div class="checkout-item">
                    <div class="checkout-item__left">
                        <div class="checkout-item__name"><?= htmlspecialchars($p['name']) ?></div>
                        <div class="checkout-item__meta">
                            <?php if ($color): ?>
                                <span>Màu: <strong><?= htmlspecialchars($color) ?></strong></span>
                            <?php endif; ?>
                            <?php if ($storage): ?>
                                <span style="margin-left:6px;">Dung lượng: <strong><?= htmlspecialchars($storage) ?></strong></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="checkout-item__right">
                        <div class="checkout-item__price"><?= format_price($price) ?></div>
                        <div class="checkout-item__qty">x <?= $qty ?></div>
                        <div class="checkout-item__total"><?= format_price($lineTotal) ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="checkout-summary__footer">
            <div class="checkout-summary__row">
                <span>Tạm tính</span>
                <span><?= format_price($grandTotal) ?></span>
            </div>
            <div class="checkout-summary__row">
                <span>Phí vận chuyển</span>
                <span><?= $shippingFee > 0 ? format_price($shippingFee) : 'Miễn phí' ?></span>
            </div>
            <div class="checkout-summary__row checkout-summary__row--total">
                <span>Tổng thanh toán</span>
                <span><?= format_price($grandTotal + $shippingFee) ?></span>
            </div>
        </div>
    </aside>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
