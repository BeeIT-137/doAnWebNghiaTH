<?php
// order-tracking.php
require_once __DIR__ . '/includes/functions.php';

$pdo = db();
$order = null;
$orderItems = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId = (int)($_POST['order_id'] ?? 0);

    if ($orderId > 0) {
        $stmt = $pdo->prepare("
            SELECT o.*, u.username, u.email, u.phone
            FROM orders o
            JOIN users u ON o.user_id = u.id
            WHERE o.id = ?
            LIMIT 1
        ");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();

        if ($order) {
            $stmtItems = $pdo->prepare("
                SELECT oi.*, p.name, p.image, p.slug
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                WHERE oi.order_id = ?
            ");
            $stmtItems->execute([$orderId]);
            $orderItems = $stmtItems->fetchAll();
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<section class="section">
    <div class="section-header">
        <h2>Tra cứu đơn hàng</h2>
    </div>

    <div class="form-card" style="max-width:480px;margin:0 auto 16px;">
        <form method="post" class="js-validate">
            <div class="form-group">
                <label>Mã đơn hàng *</label>
                <input type="number" name="order_id" data-required="true"
                    value="<?= htmlspecialchars($_POST['order_id'] ?? '') ?>"
                    placeholder="Nhập mã đơn (ví dụ: 1)">
            </div>
            <button type="submit" class="btn btn--primary btn--full">Tra cứu</button>
        </form>
    </div>

    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
        <?php if (!$order): ?>
            <p>Không tìm thấy đơn hàng với mã bạn đã nhập.</p>
        <?php else: ?>
            <div class="form-card">
                <h3>Thông tin đơn hàng #<?= (int)$order['id'] ?></h3>
                <p><strong>Trạng thái:</strong>
                    <?php
                    $statusLabel = [
                        'pending'    => 'Chờ xác nhận',
                        'processing' => 'Đang xử lý',
                        'completed'  => 'Hoàn thành',
                        'cancelled'  => 'Đã hủy',
                    ][$order['status']] ?? $order['status'];
                    echo htmlspecialchars($statusLabel);
                    ?>
                </p>
                <p><strong>Ngày tạo:</strong> <?= htmlspecialchars($order['created_at']) ?></p>
                <p><strong>Người đặt:</strong> <?= htmlspecialchars($order['username']) ?> (<?= htmlspecialchars($order['phone']) ?>)</p>
                <p><strong>Email:</strong> <?= htmlspecialchars($order['email']) ?></p>
                <p><strong>Tổng tiền:</strong> <?= format_price($order['total']) ?></p>

                <?php if (!empty($orderItems)): ?>
                    <h4 style="margin-top:10px;">Sản phẩm trong đơn</h4>
                    <ul style="font-size:0.9rem;margin-left:16px;">
                        <?php foreach ($orderItems as $item): ?>
                            <li>
                                <a href="product-detail.php?slug=<?= urlencode($item['slug']) ?>">
                                    <?= htmlspecialchars($item['name']) ?>
                                </a>
                                x <?= (int)$item['quantity'] ?> – <?= format_price($item['price']) ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</section>

<?php
require_once __DIR__ . '/includes/footer.php';
