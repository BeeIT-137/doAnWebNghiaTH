<?php
// admin/order-detail.php
require_once __DIR__ . '/includes/header.php';

$pdo = db();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: orders.php');
    exit;
}

// Cập nhật trạng thái từ trang chi tiết
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_status') {
    $status  = $_POST['status'] ?? 'pending';
    $allowed = ['pending', 'processing', 'completed', 'cancelled'];

    if (in_array($status, $allowed, true)) {
        try {
            $pdo->beginTransaction();

            $stmtCheck = $pdo->prepare("SELECT status FROM orders WHERE id = ? FOR UPDATE");
            $stmtCheck->execute([$id]);
            $orderRow = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            if ($orderRow) {
                $oldStatus = $orderRow['status'];

                if ($oldStatus !== $status) {
                    if ($oldStatus !== 'completed' && $status === 'completed') {
                        adjust_order_stock($pdo, $id, 'deduct');
                    } elseif ($oldStatus === 'completed' && $status !== 'completed') {
                        adjust_order_stock($pdo, $id, 'return');
                    }

                    $stmtUpdate = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
                    $stmtUpdate->execute([$status, $id]);
                }
            }

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
        }
    }

    header("Location: order-detail.php?id=" . $id);
    exit;
}

// Lấy thông tin đơn
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header('Location: orders.php');
    exit;
}

// Lấy chi tiết sản phẩm trong đơn
$stmtItems = $pdo->prepare("
    SELECT 
        oi.*,
        p.name AS product_name,
        p.image AS product_image
    FROM order_items oi
    INNER JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$stmtItems->execute([$id]);
$items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="admin-main__inner">
    <div style="margin-bottom:10px;">
        <a href="orders.php" class="link-inline">&larr; Quay lại danh sách đơn</a>
    </div>

    <div class="admin-cards" style="margin-bottom:12px;">
        <div class="admin-card">
            <div class="admin-card__label">Mã đơn hàng</div>
            <div class="admin-card__value">#<?= (int)$order['id'] ?></div>
            <div class="admin-card__icon"><i class="fa-solid fa-receipt"></i></div>
        </div>
        <div class="admin-card">
            <div class="admin-card__label">Tổng thanh toán</div>
            <div class="admin-card__value">
                <?= number_format($order['total'], 0, ',', '.') ?>đ
            </div>
            <div class="admin-card__icon"><i class="fa-solid fa-coins"></i></div>
        </div>
        <div class="admin-card">
            <div class="admin-card__label">Ngày tạo</div>
            <div class="admin-card__value" style="font-size:0.9rem;">
                <?= htmlspecialchars($order['created_at']) ?>
            </div>
            <div class="admin-card__icon"><i class="fa-regular fa-calendar"></i></div>
        </div>
    </div>

    <!-- FORM ĐỔI TRẠNG THÁI -->
    <div class="admin-form">
        <form method="post" style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;">
            <input type="hidden" name="action" value="update_status">
            <div class="form-group" style="min-width:200px;">
                <label>Trạng thái đơn hàng</label>
                <select name="status">
                    <option value="pending" <?= $order['status'] === 'pending'    ? 'selected' : '' ?>>Chờ xử lý</option>
                    <option value="processing" <?= $order['status'] === 'processing' ? 'selected' : '' ?>>Đang xử lý</option>
                    <option value="completed" <?= $order['status'] === 'completed'  ? 'selected' : '' ?>>Hoàn tất</option>
                    <option value="cancelled" <?= $order['status'] === 'cancelled'  ? 'selected' : '' ?>>Đã hủy</option>
                </select>
            </div>
            <div>
                <button type="submit" class="btn btn--primary">
                    <i class="fa-solid fa-floppy-disk"></i> Cập nhật
                </button>
            </div>
        </form>
    </div>

    <!-- GRID: THÔNG TIN KHÁCH + ĐỊA CHỈ -->
    <div class="admin-charts" style="margin-bottom:12px;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));">
        <div class="admin-chart-card">
            <h3 style="font-size:0.95rem;margin-bottom:6px;">Thông tin khách hàng</h3>
            <p style="font-size:0.9rem;margin-bottom:4px;">
                <strong>Khách hàng:</strong>
                <?= htmlspecialchars($order['customer_name'] ?: 'Khách lẻ') ?>
            </p>
            <?php if (!empty($order['customer_phone'])): ?>
                <p style="font-size:0.9rem;margin-bottom:4px;">
                    <strong>Số điện thoại:</strong>
                    <?= htmlspecialchars($order['customer_phone']) ?>
                </p>
            <?php endif; ?>
            <?php if (!empty($order['customer_email'])): ?>
                <p style="font-size:0.9rem;margin-bottom:4px;">
                    <strong>Email:</strong>
                    <?= htmlspecialchars($order['customer_email']) ?>
                </p>
            <?php endif; ?>
        </div>

        <div class="admin-chart-card">
            <h3 style="font-size:0.95rem;margin-bottom:6px;">Địa chỉ & ghi chú</h3>
            <p style="font-size:0.9rem;margin-bottom:4px;">
                <strong>Địa chỉ giao hàng:</strong><br>
                <?= nl2br(htmlspecialchars($order['customer_address'] ?? '')) ?>
            </p>
            <?php if (!empty($order['customer_note'])): ?>
                <p style="font-size:0.9rem;margin-top:4px;">
                    <strong>Ghi chú:</strong><br>
                    <?= nl2br(htmlspecialchars($order['customer_note'])) ?>
                </p>
            <?php endif; ?>
        </div>
    </div>

    <!-- BẢNG SẢN PHẨM TRONG ĐƠN -->
    <div class="admin-table-wrap">
        <h2 style="font-size:0.95rem;margin-bottom:6px;">Sản phẩm trong đơn</h2>

        <table class="admin-table">
            <thead>
                <tr>
                    <th>Sản phẩm</th>
                    <th>Biến thể</th>
                    <th>Đơn giá</th>
                    <th>Số lượng</th>
                    <th>Thành tiền</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                    <tr>
                        <td colspan="5" style="text-align:center;font-size:0.85rem;color:#9ca3af;">
                            Đơn hàng chưa có sản phẩm.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($items as $it): ?>
                        <tr>
                            <td>
                                <div style="display:flex;gap:8px;align-items:center;">
                                    <div style="width:48px;height:48px;border-radius:10px;overflow:hidden;background:#020617;">
                                        <?php if (!empty($it['product_image'])): ?>
                                            <img src="../<?= htmlspecialchars($it['product_image']) ?>"
                                                 alt="<?= htmlspecialchars($it['product_name']) ?>"
                                                 style="width:100%;height:100%;object-fit:contain;">
                                        <?php else: ?>
                                            <img src="../assets/img/placeholder-product.jpg"
                                                 alt="No image"
                                                 style="width:100%;height:100%;object-fit:contain;">
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <div style="font-size:0.9rem;font-weight:500;">
                                            <?= htmlspecialchars($it['product_name']) ?>
                                        </div>
                                        <div style="font-size:0.75rem;color:#9ca3af;">
                                            ID sản phẩm: #<?= (int)$it['product_id'] ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td style="font-size:0.8rem;">
                                <?php if (!empty($it['color'])): ?>
                                    <div>Màu: <strong><?= htmlspecialchars($it['color']) ?></strong></div>
                                <?php endif; ?>
                                <?php if (!empty($it['storage'])): ?>
                                    <div>Dung lượng: <strong><?= htmlspecialchars($it['storage']) ?></strong></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= number_format($it['price'], 0, ',', '.') ?>đ
                            </td>
                            <td>
                                <?= (int)$it['quantity'] ?>
                            </td>
                            <td>
                                <strong>
                                    <?= number_format($it['price'] * $it['quantity'], 0, ',', '.') ?>đ
                                </strong>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
