<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/functions.php';

/**
 * Chỉ cho phép admin truy cập
 * - Nếu chưa đăng nhập / không phải admin → chuyển về login
 */
require_admin();
