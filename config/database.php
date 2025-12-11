<?php

/**
 * Cấu hình kết nối DB cho production (cPanel) và hỗ trợ override bằng db.local.php khi chạy localhost.
 *
 * - Production: set các biến môi trường DB_HOST/DB_NAME/DB_USER/DB_PASS trên host cPanel,
 *   hoặc sửa các giá trị mặc định bên dưới cho đúng prefix DB của bạn.
 * - Local: tạo file config/db.local.php trả về mảng cấu hình (xem db.local.php mẫu).
 */

$config = [
    'host'    => getenv('DB_HOST') ?: 'localhost',
    'name'    => getenv('DB_NAME') ?: 'viakingv_phone_shop',   // sửa theo DB trên cPanel
    'user'    => getenv('DB_USER') ?: 'viakingv_phone_shop',   // sửa theo user DB trên cPanel
    'pass'    => getenv('DB_PASS') ?: 'viakingv_phone_shop',   // sửa theo pass DB trên cPanel
    'charset' => 'utf8mb4',
];

// Override bằng cấu hình local nếu có
$localFile = __DIR__ . '/db.local.php';
if (is_file($localFile)) {
    $local = include $localFile;
    if (is_array($local)) {
        $config = array_merge($config, array_intersect_key($local, $config));
    }
}

function getPDO(): PDO
{
    static $pdo = null;
    global $config;

    if ($pdo === null) {
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', $config['host'], $config['name'], $config['charset']);
        try {
            $pdo = new PDO($dsn, $config['user'], $config['pass'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            die('Kết nối database thất bại: ' . $e->getMessage());
        }
    }

    return $pdo;
}
