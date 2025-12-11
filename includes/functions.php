<?php

/**
 * Hàm & tiện ích dùng chung cho toàn website
 * ------------------------------------------
 * - Khởi tạo session
 * - Kết nối database (thông qua getPDO())
 * - Hàm giỏ hàng (SESSION) hỗ trợ biến thể (màu + dung lượng)
 * - Hàm format giá, auth, phân quyền, redirect
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

// Version cache-busting cho assets (CSS/JS)
if (!defined('ASSET_VERSION')) {
    $stylePath = __DIR__ . '/../assets/css/style.css';
    $ver = is_file($stylePath) ? (string)filemtime($stylePath) : '1.0';
    define('ASSET_VERSION', $ver);
}

/**
 * Lấy PDO (wrapper cho getPDO) để sau gọi ngắn hơn: $pdo = db();
 */
function db()
{
    return getPDO();
}

/**
 * Định dạng giá tiền: 1.000.000₫
 */
function format_price($price)
{
    return number_format((float)$price, 0, ',', '.') . '₫';
}

/* =======================================================
 *  GIỎ HÀNG (DÙNG SESSION)
 *
 *  Cấu trúc mới khuyến nghị:
 *  $_SESSION['cart'] = [
 *      '1|Xanh|128GB' => [
 *          'product_id' => 1,
 *          'color'      => 'Xanh',
 *          'storage'    => '128GB',
 *          'quantity'   => 2,
 *      ],
 *      ...
 *  ];
 *
 *  Vẫn xử lý được dữ liệu cũ dạng:
 *  $_SESSION['cart'][product_id] = [
 *      'product_id' => 1,
 *      'quantity'   => 1
 *  ];
 * ======================================================= */

/**
 * Tạo key cho giỏ hàng từ product + màu + dung lượng
 * (Không dùng ở cart.php để tránh trùng tên hàm cart_key)
 */
function cart_make_key($product_id, $color = '', $storage = '')
{
    $product_id = (int)$product_id;
    $c = ($color !== '')   ? $color   : '-';
    $s = ($storage !== '') ? $storage : '-';
    return $product_id . '|' . $c . '|' . $s;
}

/**
 * Lấy toàn bộ items trong giỏ
 */
function get_cart_items()
{
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        return [];
    }
    return $_SESSION['cart'];
}

/**
 * Lấy tổng số lượng sản phẩm trong giỏ (để hiện badge trên icon)
 */
function get_cart_count()
{
    $cart  = get_cart_items();
    $total = 0;

    foreach ($cart as $item) {
        // Fallback: old session format may not have quantity, count it as 1
        if (is_array($item) && array_key_exists('quantity', $item)) {
            $qty = (int)$item['quantity'];
        } elseif (is_numeric($item)) {
            $qty = (int)$item;
        } else {
            $qty = 1;
        }

        if ($qty > 0) {
            $total += $qty;
        }
    }

    return $total;
}

/**
 * Thêm sản phẩm vào giỏ
 * - Hỗ trợ màu + dung lượng
 * - Nếu không truyền màu/dung lượng → sẽ tạo key product_id|-|-
 */
function add_to_cart($product_id, $qty = 1, $color = '', $storage = '')
{
    $product_id = (int)$product_id;
    $qty        = (int)$qty;

    if ($product_id <= 0) return;
    if ($qty < 1) $qty = 1;

    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    // Key mới theo biến thể
    $key = cart_make_key($product_id, $color, $storage);

    if (isset($_SESSION['cart'][$key])) {
        $_SESSION['cart'][$key]['quantity'] += $qty;
    } else {
        $_SESSION['cart'][$key] = [
            'product_id' => $product_id,
            'color'      => $color,
            'storage'    => $storage,
            'quantity'   => $qty,
        ];
    }
}

/**
 * Cập nhật số lượng sản phẩm trong giỏ
 * - $idOrKey có thể là:
 *   + key mới: "1|Xanh|128GB"
 *   + hoặc ID cũ: 1 (giỏ cũ)
 * - Nếu qty <= 0 thì xóa sản phẩm đó
 */
function update_cart($idOrKey, $qty)
{
    $qty = (int)$qty;

    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        return;
    }

    // Ưu tiên key mới
    if (isset($_SESSION['cart'][$idOrKey])) {
        if ($qty <= 0) {
            unset($_SESSION['cart'][$idOrKey]);
        } else {
            $_SESSION['cart'][$idOrKey]['quantity'] = $qty;
        }
        return;
    }

    // Fallback: kiểu cũ dùng id số nguyên làm key
    $product_id = (int)$idOrKey;
    if ($product_id > 0 && isset($_SESSION['cart'][$product_id])) {
        if ($qty <= 0) {
            unset($_SESSION['cart'][$product_id]);
        } else {
            $_SESSION['cart'][$product_id]['quantity'] = $qty;
        }
    }
}

/**
 * Xóa 1 sản phẩm khỏi giỏ
 * - $idOrKey có thể là key mới ("1|Xanh|128GB") hoặc ID cũ (1)
 */
function remove_from_cart($idOrKey)
{
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        return;
    }

    if (isset($_SESSION['cart'][$idOrKey])) {
        unset($_SESSION['cart'][$idOrKey]);
        return;
    }

    $product_id = (int)$idOrKey;
    if ($product_id > 0 && isset($_SESSION['cart'][$product_id])) {
        unset($_SESSION['cart'][$product_id]);
    }
}

/**
 * Xóa toàn bộ giỏ hàng
 */
function clear_cart()
{
    unset($_SESSION['cart']);
}

/**
 * Tính tổng tiền giỏ hàng dựa trên dữ liệu trong DB
 * - Ưu tiên giá trong product_variants nếu có color/storage
 * - Nếu không có biến thể thì dùng products.price
 * - Dùng cho mini-cart hoặc chỗ cần tổng nhanh
 */
function get_cart_total()
{
    $cart = get_cart_items();
    if (empty($cart)) {
        return 0;
    }

    $pdo = db();

    $stmtProduct = $pdo->prepare("SELECT id, price FROM products WHERE id = ? LIMIT 1");
    $stmtVariant = $pdo->prepare("
        SELECT price 
        FROM product_variants 
        WHERE product_id = ? AND color = ? AND storage = ?
        LIMIT 1
    ");

    $total = 0;

    foreach ($cart as $key => $item) {
        // Hỗ trợ cả cấu trúc mới và cũ
        if (is_array($item) && isset($item['product_id'])) {
            $product_id = (int)$item['product_id'];
            $quantity   = isset($item['quantity']) ? (int)$item['quantity'] : 1;
            $color      = isset($item['color']) ? $item['color'] : '';
            $storage    = isset($item['storage']) ? $item['storage'] : '';
        } else {
            // Dạng cũ: key = product_id
            $product_id = (int)$key;
            $quantity   = isset($item['quantity']) ? (int)$item['quantity'] : 1;
            $color      = '';
            $storage    = '';
        }

        if ($product_id <= 0 || $quantity <= 0) {
            continue;
        }

        // Giá cơ bản từ bảng products
        $stmtProduct->execute([$product_id]);
        $pRow = $stmtProduct->fetch(PDO::FETCH_ASSOC);
        if (!$pRow) continue;

        $price = (int)$pRow['price'];

        // Nếu có color/storage → thử lấy giá biến thể
        if ($color !== '' || $storage !== '') {
            $stmtVariant->execute([$product_id, $color, $storage]);
            $vRow = $stmtVariant->fetch(PDO::FETCH_ASSOC);
            if ($vRow) {
                $price = (int)$vRow['price'];
            }
        }

        $total += $price * $quantity;
    }

    return $total;
}

/* =======================================================
 *  AUTH / PHÂN QUYỀN
 * ======================================================= */

/**
 * Kiểm tra đã đăng nhập hay chưa
 */
function is_logged_in()
{
    return !empty($_SESSION['user']);
}

/**
 * Lấy ID user hiện tại (nếu đã đăng nhập)
 */
function current_user_id()
{
    return isset($_SESSION['user']['id']) ? (int)$_SESSION['user']['id'] : null;
}

/**
 * Lấy role user hiện tại
 * 1 = admin, 2 = user thường
 */
function current_user_role()
{
    return isset($_SESSION['user']['role']) ? (int)$_SESSION['user']['role'] : null;
}

/**
 * Kiểm tra có phải admin hay không
 */
function is_admin()
{
    return current_user_role() === 1;
}

/**
 * Hàm redirect tiện dụng
 */
function redirect($url)
{
    header('Location: ' . $url);
    exit;
}

/**
 * Yêu cầu đăng nhập (dùng ở các trang cần login)
 */
function require_login()
{
    if (!is_logged_in()) {
        redirect('login.php');
    }
}

/**
 * Yêu cầu phải là admin (dùng cho trang admin)
 */
function require_admin()
{
    if (!is_logged_in() || !is_admin()) {
        redirect('../login.php');
    }
}
// =======================================================
// HÀM DÙNG CHUNG
// =======================================================

if (!function_exists('make_slug')) {
    /**
     * Tạo slug từ tiếng Việt: "iPhone 15 Pro Max" -> "iphone-15-pro-max"
     */
    function make_slug($str)
    {
        $str = trim(mb_strtolower($str, 'UTF-8'));

        $from = [
            'à',
            'á',
            'ạ',
            'ả',
            'ã',
            'â',
            'ầ',
            'ấ',
            'ậ',
            'ẩ',
            'ẫ',
            'ă',
            'ằ',
            'ắ',
            'ặ',
            'ẳ',
            'ẵ',
            'è',
            'é',
            'ẹ',
            'ẻ',
            'ẽ',
            'ê',
            'ề',
            'ế',
            'ệ',
            'ể',
            'ễ',
            'ì',
            'í',
            'ị',
            'ỉ',
            'ĩ',
            'ò',
            'ó',
            'ọ',
            'ỏ',
            'õ',
            'ô',
            'ồ',
            'ố',
            'ộ',
            'ổ',
            'ỗ',
            'ơ',
            'ờ',
            'ớ',
            'ợ',
            'ở',
            'ỡ',
            'ù',
            'ú',
            'ụ',
            'ủ',
            'ũ',
            'ư',
            'ừ',
            'ứ',
            'ự',
            'ử',
            'ữ',
            'ỳ',
            'ý',
            'ỵ',
            'ỷ',
            'ỹ',
            'đ',
        ];
        $to = [
            'a',
            'a',
            'a',
            'a',
            'a',
            'a',
            'a',
            'a',
            'a',
            'a',
            'a',
            'a',
            'a',
            'a',
            'a',
            'a',
            'a',
            'e',
            'e',
            'e',
            'e',
            'e',
            'e',
            'e',
            'e',
            'e',
            'e',
            'e',
            'i',
            'i',
            'i',
            'i',
            'i',
            'o',
            'o',
            'o',
            'o',
            'o',
            'o',
            'o',
            'o',
            'o',
            'o',
            'o',
            'o',
            'o',
            'o',
            'o',
            'o',
            'o',
            'u',
            'u',
            'u',
            'u',
            'u',
            'u',
            'u',
            'u',
            'u',
            'u',
            'u',
            'y',
            'y',
            'y',
            'y',
            'y',
            'd',
        ];
        $str = str_replace($from, $to, $str);
        $str = preg_replace('/[^a-z0-9]+/i', '-', $str);
        $str = trim($str, '-');
        return $str;
    }
}

/* =======================================================
 *  HELPER FUNCTIONS (TỐI ƯU & CONSOLIDATE)
 * ======================================================= */

/**
 * Lấy giá sản phẩm (ưu tiên biến thể nếu có color/storage)
 * @param int $product_id ID sản phẩm
 * @param string $color Màu (optional)
 * @param string $storage Dung lượng (optional)
 * @return int|null Giá sản phẩm hoặc null nếu không tìm thấy
 */
function get_product_price($product_id, $color = '', $storage = '')
{
    $product_id = (int)$product_id;
    if ($product_id <= 0) return 0;

    $pdo = db();

    // Nếu có color và storage, lấy giá từ variant
    if ($color !== '' && $storage !== '') {
        $stmt = $pdo->prepare("
            SELECT price FROM product_variants 
            WHERE product_id = ? AND color = ? AND storage = ? 
            LIMIT 1
        ");
        $stmt->execute([$product_id, $color, $storage]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            return (int)$row['price'];
        }
    }

    // Fallback: lấy giá cơ bản từ products
    $stmt = $pdo->prepare("SELECT price FROM products WHERE id = ? LIMIT 1");
    $stmt->execute([$product_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ? (int)$row['price'] : 0;
}

/**
 * Tính thông tin giảm giá (amount & percentage)
 * Dùng chung cho toàn ứng dụng
 * @param float $price Giá hiện tại
 * @param float $old_price Giá gốc
 * @return array [amount, percent] hoặc [0, 0] nếu không giảm
 */
function calc_discount_info($price, $old_price)
{
    $price = (float)$price;
    $old = (float)$old_price;

    if ($old <= 0 || $old <= $price) {
        return [0, 0];
    }

    $amount = $old - $price;
    $percent = round($amount / $old * 100);

    return [$amount, $percent];
}

/**
 * Validate email
 * @param string $email Email cần kiểm tra
 * @return bool True nếu hợp lệ
 */
function validate_email($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate số điện thoại Việt (bắt đầu 0, 10-11 chữ số)
 * @param string $phone Số điện thoại
 * @return bool True nếu hợp lệ
 */
function validate_phone($phone)
{
    return (bool)preg_match('/^0\d{9,10}$/', trim($phone));
}

/**
 * Tạo CSRF token
 * @return string Token
 */
function generate_csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Kiểm tra CSRF token
 * @param string $token Token cần kiểm tra
 * @return bool True nếu hợp lệ
 */
function verify_csrf_token($token)
{
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

/**
 * Log error ra file (logs/error.log)
 * @param string $message Tin nhắn
 * @param array $context Dữ liệu bổ sung
 * @return void
 */
function log_error($message, $context = [])
{
    $logsDir = __DIR__ . '/../logs';
    if (!is_dir($logsDir)) {
        @mkdir($logsDir, 0755, true);
    }

    $file = $logsDir . '/error.log';
    $msg = date('Y-m-d H:i:s') . ' - ' . $message;
    if (!empty($context)) {
        $msg .= ' - ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    $msg .= "\n";

    error_log($msg, 3, $file);
}
/**
 * Upload ảnh (dùng cho sản phẩm & biến thể)
 *
 * @param string      $fieldName Tên input file (vd: 'image', 'variant_image_0' ...)
 * @param string|null $oldPath   Đường dẫn cũ (nếu có) để giữ lại hoặc xóa sau khi upload file mới
 *
 * @return string|null Đường dẫn tương đối lưu trong DB (vd: "uploads/products/abc.jpg") hoặc null nếu fail
 */
function upload_image(string $fieldName, ?string $oldPath = null): ?string
{
    // Không có field này trong form → trả về ảnh cũ
    if (!isset($_FILES[$fieldName])) {
        return $oldPath;
    }

    $file = $_FILES[$fieldName];

    // Không chọn file mới → giữ ảnh cũ
    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return $oldPath;
    }

    // Có lỗi khác khi upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return $oldPath;
    }

    // Giới hạn loại file cho an toàn
    $allowedMime = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
    ];

    $tmpPath = $file['tmp_name'];
    $mime    = @mime_content_type($tmpPath);

    if (!isset($allowedMime[$mime])) {
        // Sai loại file → không lưu
        return $oldPath;
    }

    // Giới hạn dung lượng (3MB)
    $maxSize = 3 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        return $oldPath;
    }

    $ext       = $allowedMime[$mime];
    $uploadDir = __DIR__ . '/../uploads/products';

    // Tạo thư mục nếu chưa có
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0755, true);
    }

    // Tạo tên file random
    $newName  = uniqid('p_', true) . '.' . $ext;
    $destAbs  = $uploadDir . '/' . $newName;
    $destRel  = 'uploads/products/' . $newName; // Lưu vào DB dạng này

    // Di chuyển file tạm sang thư mục upload
    if (!move_uploaded_file($tmpPath, $destAbs)) {
        return $oldPath;
    }

    // Xóa file cũ nếu tồn tại và cùng nằm trong thư mục dự án
    if ($oldPath) {
        $oldAbs = __DIR__ . '/../' . ltrim($oldPath, '/');
        if (is_file($oldAbs)) {
            @unlink($oldAbs);
        }
    }

    return $destRel;
}

/**
 * Cn bA`ng t ¯"n kho theo Ž`’­n hAÿng khi Ž` ¯i tr §ÿng thA­i
 * $direction = 'deduct' (gi ¯>m kho) ho §úc 'return' (hoÀn kho).
 */
function adjust_order_stock(PDO $pdo, int $orderId, string $direction): void
{
    if (!in_array($direction, ['deduct', 'return'], true)) {
        return;
    }

    $stmtItems = $pdo->prepare("
        SELECT product_id, quantity, color, storage
        FROM order_items
        WHERE order_id = ?
    ");
    $stmtItems->execute([$orderId]);
    $items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

    if (!$items) {
        return;
    }

    $deltaSign = $direction === 'deduct' ? -1 : 1;

    $stmtUpdateVariant = $pdo->prepare("
        UPDATE product_variants
        SET stock = GREATEST(stock + :delta, 0)
        WHERE product_id = :pid AND color = :color AND storage = :storage
    ");

    $stmtUpdateProduct = $pdo->prepare("
        UPDATE products
        SET stock = GREATEST(stock + :delta, 0)
        WHERE id = :pid
    ");

    $affectedProductsWithVariants = [];

    foreach ($items as $item) {
        $pid     = (int)$item['product_id'];
        $qty     = max(0, (int)$item['quantity']);
        $color   = trim($item['color']   ?? '');
        $storage = trim($item['storage'] ?? '');

        if ($pid <= 0 || $qty <= 0) {
            continue;
        }

        $delta = $deltaSign * $qty;
        $updatedVariant = false;

        if ($color !== '' || $storage !== '') {
            $stmtUpdateVariant->execute([
                ':delta'   => $delta,
                ':pid'     => $pid,
                ':color'   => $color,
                ':storage' => $storage,
            ]);

            if ($stmtUpdateVariant->rowCount() > 0) {
                $updatedVariant = true;
                $affectedProductsWithVariants[$pid] = true;
            }
        }

        if (!$updatedVariant) {
            $stmtUpdateProduct->execute([
                ':delta' => $delta,
                ':pid'   => $pid,
            ]);
        }
    }

    if (!empty($affectedProductsWithVariants)) {
        $stmtSumVariant = $pdo->prepare("
            SELECT SUM(stock) AS total_stock
            FROM product_variants
            WHERE product_id = ?
        ");
        $stmtUpdateProductStock = $pdo->prepare("
            UPDATE products
            SET stock = :stock
            WHERE id = :pid
        ");

        foreach (array_keys($affectedProductsWithVariants) as $pid) {
            $stmtSumVariant->execute([$pid]);
            $totalStock = (int)($stmtSumVariant->fetchColumn() ?? 0);

            $stmtUpdateProductStock->execute([
                ':stock' => $totalStock,
                ':pid'   => $pid,
            ]);
        }
    }
}

