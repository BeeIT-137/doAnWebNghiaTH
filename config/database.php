<?php

/**
 * Kết nối database MySQL bằng PDO
 * -------------------------------
 * - Sử dụng thông tin host / user / pass bên dưới
 * - Nếu bạn đổi tên database trong database.sql thì sửa DB_NAME cho khớp
 */

define('DB_HOST', 'localhost');      // host, thường là localhost
define('DB_NAME', 'phone_shop');     // tên database
define('DB_USER', 'root');           // user MySQL (XAMPP mặc định là root)
define('DB_PASS', '');               // mật khẩu MySQL (XAMPP mặc định rỗng)

/**
 * Hàm trả về instance PDO dùng chung toàn project
 * Dùng: $pdo = getPDO();
 */
function getPDO()
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // bật chế độ báo lỗi
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // trả về dạng mảng kết hợp
                PDO::ATTR_EMULATE_PREPARES   => false,                  // dùng prepared statement thật
            ]);
        } catch (PDOException $e) {
            // Bạn có thể log lỗi ra file, ở đây mình tạm die cho dễ debug
            die('Kết nối database thất bại: ' . $e->getMessage());
        }
    }

    return $pdo;
}
