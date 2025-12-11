<?php
// order-process.php - tạo đơn hàng từ giỏ hàng (yêu cầu đăng nhập)

session_start();
require_once __DIR__ . '/includes/functions.php';
$pdo = db();

// Bắt buộc đăng nhập để có user_id (orders.user_id NOT NULL trong schema)
require_login();
$userId = current_user_id();

// Giỏ trống -> về cart
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header('Location: cart.php');
    exit;
}

function op_get_cart_items(PDO $pdo): array
{
    $items = $_SESSION['cart'] ?? [];
    if (empty($items)) return [];

    $result = [];
    $stmtProduct = $pdo->prepare("SELECT * FROM products WHERE id = ? LIMIT 1");
    $stmtVariant = $pdo->prepare("
        SELECT * FROM product_variants 
        WHERE product_id = ? AND color = ? AND storage = ? 
        LIMIT 1
    ");

    foreach ($items as $row) {
        $productId = (int)($row['product_id'] ?? 0);
        if ($productId <= 0) continue;

        $stmtProduct->execute([$productId]);
        $product = $stmtProduct->fetch(PDO::FETCH_ASSOC);
        if (!$product) continue;

        $color   = $row['color'] ?? '';
        $storage = $row['storage'] ?? '';
        $qty     = max(1, (int)($row['quantity'] ?? 1));

        $variant = null;
        if ($color !== '' || $storage !== '') {
            $stmtVariant->execute([$productId, $color, $storage]);
            $variant = $stmtVariant->fetch(PDO::FETCH_ASSOC) ?: null;
        }

        if ($variant) {
            $unitPrice        = (int)$variant['price'];
            $variantId        = (int)$variant['id'];
            $availableStock   = (int)$variant['stock'];
        } else {
            $unitPrice      = (int)$product['price'];
            $variantId      = null;
            $availableStock = (int)$product['stock'];
            // nếu yêu cầu màu/dung lượng nhưng không tìm thấy variant thì xem như hết hàng
            if ($color !== '' || $storage !== '') {
                $availableStock = 0;
            }
        }

        $lineTotal = $unitPrice * $qty;

        $result[] = [
            'product_id' => $productId,
            'name'       => $product['name'],
            'color'      => $color,
            'storage'    => $storage,
            'qty'        => $qty,
            'unit_price' => $unitPrice,
            'variant_id' => $variantId,
            'available_stock' => $availableStock,
            'line_total' => $lineTotal,
        ];
    }

    return $result;
}

$cartItems = op_get_cart_items($pdo);
if (empty($cartItems)) {
    header('Location: cart.php');
    exit;
}

// Kiểm tra tồn kho trước khi tạo đơn
foreach ($cartItems as $item) {
    $available = (int)($item['available_stock'] ?? 0);
    if ($available <= 0 || $item['qty'] > $available) {
        $_SESSION['checkout_error'] = 'Sản phẩm "' . ($item['name'] ?? '#') . '" không đủ tồn kho. Chỉ còn ' . $available . ' sản phẩm.';
        header('Location: checkout.php');
        exit;
    }
}

// Lấy dữ liệu form
$name    = trim($_POST['customer_name'] ?? '');
$phone   = trim($_POST['customer_phone'] ?? '');
    $address = trim($_POST['customer_address'] ?? '');
    $note    = trim($_POST['customer_note'] ?? '');

if ($name === '' || $phone === '' || $address === '') {
    $_SESSION['checkout_error'] = 'Vui lòng nhập đầy đủ họ tên, số điện thoại và địa chỉ.';
    header('Location: checkout.php');
    exit;
}

// Tính tổng
$subtotal = 0;
foreach ($cartItems as $item) {
    $subtotal += $item['line_total'];
}
$shippingFee = $subtotal > 0 ? 30000 : 0;
$total       = $subtotal + $shippingFee;

// Tạo đơn
try {
    $pdo->beginTransaction();

    $stmtOrder = $pdo->prepare("
        INSERT INTO orders (user_id, total, status, created_at,
                            customer_name, customer_phone, customer_address, customer_note)
        VALUES (:user_id, :total, :status, NOW(),
                :name, :phone, :address, :note)
    ");
    $stmtOrder->execute([
        ':user_id' => $userId,
        ':total'   => $total,
        ':status'  => 'pending',
        ':name'    => $name,
        ':phone'   => $phone,
        ':address' => $address,
        ':note'    => $note !== '' ? $note : null,
    ]);

    $orderId = (int)$pdo->lastInsertId();

    $stmtItem = $pdo->prepare("
        INSERT INTO order_items (order_id, product_id, quantity, price, color, storage)
        VALUES (:order_id, :product_id, :qty, :price, :color, :storage)
    ");

    foreach ($cartItems as $item) {
        $stmtItem->execute([
            ':order_id'   => $orderId,
            ':product_id' => $item['product_id'],
            ':qty'        => $item['qty'],
            ':price'      => $item['unit_price'],
            ':color'      => $item['color'] !== '' ? $item['color'] : null,
            ':storage'    => $item['storage'] !== '' ? $item['storage'] : null,
        ]);
    }

    $pdo->commit();

    $_SESSION['cart'] = [];

    header('Location: order-success.php?order_id=' . $orderId);
    exit;
} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['checkout_error'] = 'Có lỗi xảy ra khi tạo đơn hàng. Vui lòng thử lại sau.';
    header('Location: checkout.php');
    exit;
}
