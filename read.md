# Bao cao nhanh (Tieng Viet + code)

Doc nhanh 5 phut: gom doan code chinh + giai thich bien/chuc nang.

## 1) Cau hinh DB (`config/database.php`)
```php
$localFile = __DIR__ . '/db.local.php'; // chi luu o may dev

$appEnv = getenv('APP_ENV');
if ($appEnv === false || $appEnv === '') {
    $serverName = $_SERVER['SERVER_NAME'] ?? '';
    $appEnv = ($serverName === 'localhost' || $serverName === '127.0.0.1') ? 'local' : 'production';
}

$config = [
    'host'    => getenv('DB_HOST') ?: 'localhost',            // host tren cPanel
    'name'    => getenv('DB_NAME') ?: 'viakingv_phone_shop', // DB fallback
    'user'    => getenv('DB_USER') ?: 'viakingv_phone_shop', // user fallback
    'pass'    => getenv('DB_PASS') ?: 'viakingv_phone_shop', // pass fallback
    'charset' => 'utf8mb4',
];

if ($appEnv === 'local' && is_file($localFile)) {
    $local = include $localFile; // host/name/user/pass/charset
    if (is_array($local)) {
        $config = array_merge($config, array_intersect_key($local, $config)); // override key co trong $config
    }
}

function getPDO(): PDO {
    static $pdo = null; // singleton
    global $config;
    if ($pdo === null) {
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', $config['host'], $config['name'], $config['charset']);
        $pdo = new PDO($dsn, $config['user'], $config['pass'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}
```
- `$appEnv`: auto local neu hostname = localhost/127.0.0.1, nguoc lai production.
- `$config`: uu tien bien moi truong, fallback gia tri mau cPanel.
- `db.local.php`: override rieng local (khong upload).
- `getPDO()`: tao PDO che do exception, fetch assoc, khong emulate.

## 2) Helpers chung (`includes/functions.php`)

Kho ham ho tro: session, DB helper, gio hang, auth, format, validate, CSRF, upload, log, dong bo ton kho.

```php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';

// Cache-busting assets
if (!defined('ASSET_VERSION')) {
    $stylePath = __DIR__ . '/../assets/css/style.css';
    $ver = is_file($stylePath) ? (string)filemtime($stylePath) : '1.0';
    define('ASSET_VERSION', $ver);
}

function db() { return getPDO(); }
```

**Gio hang (SESSION, key `product|color|storage`)**
```php
function cart_make_key($product_id, $color = '', $storage = '') { /* tao key */ }
function add_to_cart($product_id, $qty = 1, $color = '', $storage = '') { /* them sp/variant */ }
function update_cart($idOrKey, $qty) { /* cap nhat qty, qty<=0 thi xoa */ }
function remove_from_cart($idOrKey) { /* xoa item */ }
function clear_cart() { unset($_SESSION['cart']); }
function get_cart_items() { return $_SESSION['cart'] ?? []; }
function get_cart_total() { /* tinh tong tu DB + variants */ }
```

**Auth**
```php
function is_logged_in() { return !empty($_SESSION['user']); }
function current_user_id() { return $_SESSION['user']['id'] ?? null; }
function current_user_role() { return $_SESSION['user']['role'] ?? null; }
function require_login() { if (!is_logged_in()) redirect('login.php'); }
function require_admin() { if (!is_logged_in() || current_user_role() !== 1) redirect('../login.php'); }
```

**CSRF / format / validate / slug / log**
```php
function generate_csrf_token() { /* tao token, luu session */ }
function verify_csrf_token($token) { /* so sanh token */ }
function format_price($price) { return number_format((float)$price, 0, ',', '.') . '₫'; }
function validate_email($email) { return filter_var($email, FILTER_VALIDATE_EMAIL) !== false; }
function validate_phone($phone) { return (bool)preg_match('/^0\d{9,10}$/', trim($phone)); }
function make_slug($str) { /* bo dau TV, thay bang '-' */ }
function log_error($message, $context=[]) { /* ghi logs/error.log */ }
```

**Upload anh**
```php
function upload_image(string $fieldName, ?string $oldPath = null): ?string {
    // check $_FILES, MIME cho phep, size <= 3MB
    // move_uploaded_file vao uploads/products, xoa file cu neu co
}
```

**Dong bo ton kho**
```php
function adjust_order_stock(PDO $pdo, int $orderId, string $direction): void {
    // $direction: 'deduct' (tru kho) hoac 'return' (cong kho)
    // update stock variant (color/storage) neu co, cap nhat tong stock vao products
}
```

## 3) Guard admin (`includes/admin_auth.php`)
```php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/functions.php';
require_admin(); // neu khong phai admin -> redirect login
```

## 4) Layout dung chung
- `includes/header.php`: HTML head, load CSS/JS, menu danh muc, mo layout.
- `includes/footer.php`: chan trang, nap JS (co ASSET_VERSION).

## 5) Trang khach hang (code chinh)

**index.php (trang chu)**
```php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/header.php';
$pdo = db();

$sqlPhones = "SELECT p.*, c.slug AS cat_slug FROM products p
    JOIN categories c ON p.category_id = c.id
    WHERE c.slug IN ('dien-thoai','iphone','samsung','xiaomi')
    ORDER BY p.created_at DESC LIMIT 8";
$phones = $pdo->query($sqlPhones)->fetchAll();

$sqlAccessories = "SELECT p.*, c.slug AS cat_slug FROM products p
    JOIN categories c ON p.category_id = c.id
    WHERE c.slug = 'phu-kien'
    ORDER BY p.created_at DESC LIMIT 8";
$accessories = $pdo->query($sqlAccessories)->fetchAll();

require_once __DIR__ . '/includes/footer.php';
```

**product-list.php (loc/sap xep/them gio)**
```php
require_once __DIR__ . '/includes/functions.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_to_cart') {
    add_to_cart((int)$_POST['product_id'], (int)($_POST['quantity'] ?? 1));
    $qs = $_GET ? ('?' . http_build_query($_GET)) : '';
    redirect('product-list.php' . $qs);
}
require_once __DIR__ . '/includes/header.php';
$pdo = db();

// Doc filter: q, category, brand, price, storage, screen, sort -> build $where, $params, $orderBy
$sql = "SELECT p.*, c.name AS category_name, COALESCE(s.sold_qty,0) AS sold_qty
        FROM products p
        JOIN product_categories pc ON p.id = pc.product_id
        JOIN categories c ON pc.category_id = c.id
        LEFT JOIN (SELECT product_id, SUM(quantity) sold_qty FROM order_items GROUP BY product_id) s
            ON s.product_id = p.id";
// Them WHERE ... GROUP BY p.id ORDER BY $orderBy
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();
require_once __DIR__ . '/includes/footer.php';
```

**product-detail.php (chi tiet, bien the, mua ngay)**
```php
require_once __DIR__ . '/includes/functions.php';
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_to_cart') {
    add_to_cart((int)$_POST['product_id'], (int)($_POST['quantity'] ?? 1),
        trim($_POST['color'] ?? ''), trim($_POST['storage'] ?? ''));
    if (isset($_POST['buy_now']) && $_POST['buy_now'] === '1') redirect('checkout.php');
    $slug = $_GET['slug'] ?? '';
    redirect('product-detail.php' . ($slug ? '?slug=' . urlencode($slug) : ''));
}

$slug = trim($_GET['slug'] ?? '');
$stmt = $pdo->prepare("SELECT p.*, c.name AS category_name FROM products p
    JOIN categories c ON p.category_id = c.id WHERE p.slug = ? LIMIT 1");
$stmt->execute([$slug]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

$stmtVar = $pdo->prepare("SELECT id, product_id, color, storage, image, price, old_price, stock
    FROM product_variants WHERE product_id = ? ORDER BY price ASC");
$stmtVar->execute([$product['id']]);
$variants = $stmtVar->fetchAll(PDO::FETCH_ASSOC);

// Chon gia/anh mac dinh, tinh % giam, render form chon mau/dung luong, add-to-cart/buy-now
require_once __DIR__ . '/includes/footer.php';
```

**cart.php (xem/sua gio)**
```php
// Doc $_SESSION['cart'], hien item, cho update/remove, tinh tong = get_cart_total()
```

**checkout.php (yeu cau login, kiem ton, CSRF)**
```php
require_once __DIR__ . '/includes/functions.php';
require_login();
$pdo = db();
$cartItems   = get_cart_items();
$csrfToken   = generate_csrf_token();
$shippingFee = 30000;

// Map cart -> product/variant, kiem ton kho, tinh $grandTotal
// Form POST sang order-process.php kem token
require_once __DIR__ . '/includes/footer.php';
```

**order-process.php (tao don, tru kho)**
```php
session_start();
require_once __DIR__ . '/includes/functions.php';
require_login();
$pdo = db();
$userId = current_user_id();

function op_get_cart_items(PDO $pdo): array { /* rebuild item + variant + gia + ton */ }
$items = op_get_cart_items($pdo);

// Insert orders, order_items
// adjust_order_stock($pdo, $orderId, 'deduct');
// clear cart, redirect order-success.php?code=...
```

**order-tracking.php**
- Form nhap ma don + so dien thoai, query orders + order_items, hien trang thai.

## 6) Khu vuc admin (`/admin`)

**admin/products.php (liet ke/tim/xoa)**
```php
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../includes/functions.php';
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$id]);
    }
    redirect('products.php');
}

$sql = "SELECT p.*, c.name AS category_name,
    COALESCE(v.variant_count,0) variant_count,
    COALESCE(v.min_price,NULL) variant_min_price,
    COALESCE(v.max_price,NULL) variant_max_price,
    COALESCE(v.total_stock,NULL) variant_total_stock
    FROM products p
    JOIN categories c ON p.category_id = c.id
    LEFT JOIN (
        SELECT product_id, COUNT(*) variant_count, MIN(price) min_price,
               MAX(price) max_price, SUM(stock) total_stock
        FROM product_variants GROUP BY product_id
    ) v ON v.product_id = p.id
    ORDER BY p.created_at DESC";
$products = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
// Render bang quan ly
```

**admin/product-edit.php (them/sua san pham + bien the)**
- POST: doc form san pham + list bien the; upload anh qua `upload_image`; insert/update `products` va `product_variants`.
- GET: load san pham + bien the hien co de hien form.

**admin/orders.php** / **admin/order-detail.php**
- orders.php: liet ke/tim don, cap nhat trang thai.
- order-detail.php: xem chi tiet, doi trang thai -> goi `adjust_order_stock` de tru/hoan ton kho.

**admin/users.php** / **admin/user-edit.php**
- CRUD user, role (1 = admin, 2 = user).

**admin/stats.php**
- Thong ke doanh thu, don hang, san pham ban chay.

## 7) Luong don hang (tong ket)
1) Xem sp (index / product-list / product-detail) -> `add_to_cart`.  
2) `cart.php` -> `checkout.php` (login, kiem ton, CSRF).  
3) `order-process.php` tao `orders` + `order_items`, `adjust_order_stock('deduct')`, clear cart, redirect `order-success.php`.  
4) Khach theo doi qua `order-tracking.php`; admin quan ly qua `admin/orders.php` va `admin/order-detail.php`.
