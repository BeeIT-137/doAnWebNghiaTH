<?php
// admin/index.php
require_once __DIR__ . '/../includes/admin_auth.php'; // enforce admin login + helpers
require_once __DIR__ . '/includes/header.php'; // load shared admin layout


$pdo = db(); // database handle

// Tổng số user
$totalUsers = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(); // total accounts

// Tổng số sản phẩm
$totalProducts = (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn(); // total products

// Tổng số đơn hàng
$totalOrders = (int)$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn(); // total orders

// Doanh thu (sum total đơn completed)
$totalRevenue = (float)$pdo->query("
    SELECT COALESCE(SUM(total),0) FROM orders WHERE status = 'completed'
")->fetchColumn(); // revenue from completed orders

// Đơn hàng theo trạng thái
$statusCountsStmt = $pdo->query("
    SELECT status, COUNT(*) as cnt
    FROM orders
    GROUP BY status
"); // count orders grouped by status
$statusCounts = $statusCountsStmt->fetchAll();
?>

<h2 style="margin-bottom:10px;">Tổng quan</h2>

<div class="admin-cards">
    <div class="admin-card">
        <div class="admin-card__label">Người dùng</div>
        <div class="admin-card__value"><?= $totalUsers ?></div>
        <div style="font-size:0.75rem;color:#9ca3af;">Tổng số tài khoản đã đăng ký</div>
    </div>
    <div class="admin-card">
        <div class="admin-card__label">Sản phẩm</div>
        <div class="admin-card__value"><?= $totalProducts ?></div>
        <div style="font-size:0.75rem;color:#9ca3af;">Tổng số sản phẩm trong kho</div>
    </div>
    <div class="admin-card">
        <div class="admin-card__label">Đơn hàng</div>
        <div class="admin-card__value"><?= $totalOrders ?></div>
        <div style="font-size:0.75rem;color:#9ca3af;">Tính tất cả trạng thái</div>
    </div>
    <div class="admin-card">
        <div class="admin-card__label">Doanh thu (completed)</div>
        <div class="admin-card__value"><?= number_format($totalRevenue, 0, ',', '.') ?>₫</div>
        <div style="font-size:0.75rem;color:#9ca3af;">Chỉ tính đơn hàng đã hoàn thành</div>
    </div>
</div>

<div class="admin-table-wrap">
    <h3 style="margin-bottom:6px;">Đơn hàng theo trạng thái</h3>
    <table class="admin-table">
        <thead>
            <tr>
                <th>Trạng thái</th>
                <th>Số đơn</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($statusCounts)): ?>
                <tr>
                    <td colspan="2">Chưa có đơn hàng.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($statusCounts as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['status']) ?></td>
                        <td><?= (int)$row['cnt'] ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
