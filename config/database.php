<?php

/**
 * Cấu hình kết nối DB:
 * - Production (cPanel): dùng biến môi trường DB_HOST/DB_NAME/DB_USER/DB_PASS hoặc giá trị mặc định bên dưới.
 * - Local: đặt APP_ENV=local và tạo config/db.local.php để override (không upload file này lên host).
 */

$appEnv = getenv('APP_ENV') ?: 'production'; // đặt APP_ENV=local trên máy dev

$config = [
    'host'    => getenv('DB_HOST') ?: 'localhost',
    'name'    => getenv('DB_NAME') ?: 'viakingv_phone_shop',   // sửa theo DB trên cPanel
    'user'    => getenv('DB_USER') ?: 'viakingv_phone_shop',   // sửa theo user DB trên cPanel
    'pass'    => getenv('DB_PASS') ?: 'viakingv_phone_shop',   // sửa theo pass DB trên cPanel
    'charset' => 'utf8mb4',
];

// Chỉ override cấu hình local khi APP_ENV=local
$localFile = __DIR__ . '/db.local.php';
if ($appEnv === 'local' && is_file($localFile)) {
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
