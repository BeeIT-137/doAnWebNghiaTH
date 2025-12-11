<?php
// order-success.php – Trang cảm ơn
session_start();
require_once __DIR__ . '/includes/functions.php';
$pdo = db();

$orderId = (int)($_GET['order_id'] ?? 0);
if ($orderId <= 0) {
    header('Location: index.php');
    exit;
}

// Lấy thông tin đơn để hiển thị (optional)
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? LIMIT 1");
$stmt->execute([$orderId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

require_once __DIR__ . '/includes/header.php';
?>

<main class="site-main">
    <div class="container">
        <div class="cart-summary" style="max-width:520px;margin:0 auto;">
            <h1 style="font-size:1.2rem;margin-bottom:6px;">Đặt hàng thành công</h1>

            <p style="font-size:0.9rem;color:#6b7280;margin-bottom:6px;">
                Cảm ơn bạn đã mua sắm tại <strong>TechPhone</strong>.
            </p>

            <?php if ($order): ?>
                <p style="font-size:0.9rem;margin-bottom:4px;">
                    Mã đơn hàng của bạn: <strong>#<?= (int)$order['id'] ?></strong>
                </p>
                <?php if (!empty($order['customer_name'])): ?>
                    <p style="font-size:0.9rem;margin-bottom:4px;">
                        Tên khách hàng: <strong><?= htmlspecialchars($order['customer_name']) ?></strong>
                    </p>
                <?php endif; ?>
                <p style="font-size:0.9rem;margin-bottom:4px;">
                    Tổng thanh toán: <strong><?= number_format($order['total'], 0, ',', '.') ?>₫</strong>
                </p>
                <p style="font-size:0.8rem;color:#6b7280;margin-top:6px;">
                    Bộ phận chăm sóc khách hàng sẽ liên hệ với bạn qua số điện thoại
                    <?= htmlspecialchars($order['customer_phone'] ?? '') ?> để xác nhận đơn và giao hàng trong thời gian sớm nhất.
                </p>
            <?php else: ?>
                <p>Không tìm thấy thông tin đơn hàng. Vui lòng kiểm tra lại.</p>
            <?php endif; ?>

            <div style="margin-top:12px;display:flex;flex-direction:column;gap:6px;">
                <a href="index.php" class="btn btn--primary btn--full">
                    Về trang chủ
                </a>
                <a href="product-list.php" class="btn btn--ghost btn--full">
                    Tiếp tục mua sắm
                </a>
            </div>
        </div>
    </div>
</main>

<?php
require_once __DIR__ . '/includes/footer.php';
