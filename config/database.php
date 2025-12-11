<?php

/**
 * Database config that works on both localhost and cPanel.
 *
 * Production (cPanel):
 * - Use DB_HOST / DB_NAME / DB_USER / DB_PASS environment variables, or keep
 *   the fallback values below in sync with your cPanel database.
 *
 * Local (WAMP/XAMPP/etc.):
 * - Create config/db.local.php with your local credentials (this file is
 *   intended to stay only on your machine).
 * - APP_ENV=local forces the local config.
 * - If APP_ENV is not set, requests from localhost/127.0.0.1 will
 *   automatically load db.local.php when it exists.
 */

$localFile = __DIR__ . '/db.local.php';

$appEnv = getenv('APP_ENV');
if ($appEnv === false || $appEnv === '') {
    $serverName = $_SERVER['SERVER_NAME'] ?? '';
    $appEnv = ($serverName === 'localhost' || $serverName === '127.0.0.1') ? 'local' : 'production';
}

$config = [
    'host'    => getenv('DB_HOST') ?: 'localhost',            // cPanel DB host
    'name'    => getenv('DB_NAME') ?: 'viakingv_phone_shop', // cPanel DB name
    'user'    => getenv('DB_USER') ?: 'viakingv_phone_shop', // cPanel DB user
    'pass'    => getenv('DB_PASS') ?: 'viakingv_phone_shop', // cPanel DB password
    'charset' => 'utf8mb4',
];

// Override with local credentials when in local environment
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
            die('Ket noi database that bai: ' . $e->getMessage());
        }
    }

    return $pdo;
}
