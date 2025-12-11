<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check quyền admin + load hàm chung
require_once __DIR__ . '/../../includes/admin_auth.php';
require_once __DIR__ . '/../../includes/functions.php';

$pdo         = db();
$currentUser = $_SESSION['user'] ?? null;

// Xác định file hiện tại để active menu
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>TechPhone Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Dùng lại style chính + style admin -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="admin-body">
<div class="admin-layout">
    <!-- SIDEBAR -->
    <aside class="admin-sidebar">
        <div class="admin-sidebar__logo">
            <a href="index.php">
                Tech<span>Phone</span> <small>Admin</small>
            </a>
        </div>
        <nav class="admin-sidebar__nav">
            <a href="index.php"
               class="admin-nav__item <?= $currentPage === 'index.php' ? 'is-active' : '' ?>">
                <i class="fa-solid fa-gauge-high"></i> Dashboard
            </a>
            <a href="products.php"
               class="admin-nav__item <?= $currentPage === 'products.php' ? 'is-active' : '' ?>">
                <i class="fa-solid fa-box"></i> Sản phẩm
            </a>
            <a href="users.php"
               class="admin-nav__item <?= $currentPage === 'users.php' ? 'is-active' : '' ?>">
                <i class="fa-solid fa-users"></i> Người dùng
            </a>
            <a href="orders.php"
               class="admin-nav__item <?= $currentPage === 'orders.php' ? 'is-active' : '' ?>">
                <i class="fa-solid fa-receipt"></i> Đơn hàng
            </a>
            <a href="stats.php"
               class="admin-nav__item <?= $currentPage === 'stats.php' ? 'is-active' : '' ?>">
                <i class="fa-solid fa-chart-simple"></i> Thống kê
            </a>
        </nav>
    </aside>

    <!-- BACKDROP cho sidebar khi mở trên mobile -->
    <div class="admin-sidebar-backdrop" id="adminSidebarBackdrop"></div>

    <!-- MAIN PANEL -->
    <div class="admin-main">
        <header class="admin-topbar">
            <div class="admin-topbar__left" style="display:flex;align-items:center;gap:8px;">
                <!-- Nút menu 3 gạch (hiển thị trên mobile qua CSS) -->
                <button type="button" class="admin-topbar__menu-btn" id="adminMenuToggle">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <h1 class="admin-topbar__title">Bảng điều khiển</h1>
            </div>
            <div class="admin-topbar__right">
                <?php if ($currentUser): ?>
                    <div class="admin-user">
                        <i class="fa-solid fa-circle-user"></i>
                        <div>
                            <div class="admin-user__name">
                                <?= htmlspecialchars($currentUser['username']) ?>
                            </div>
                            <div class="admin-user__role">Quyền: Admin</div>
                        </div>
                    </div>
                <?php endif; ?>
                <a href="../login.php?action=logout" class="btn btn--ghost btn-sm">
                    <i class="fa-solid fa-right-from-bracket"></i> Đăng xuất
                </a>
            </div>
        </header>

        <main class="admin-main__inner">
