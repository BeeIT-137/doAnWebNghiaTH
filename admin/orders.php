<?php
// admin/orders.php

// Header admin (trong đó đã check admin_auth + load functions + CSS admin)
require_once __DIR__ . '/includes/header.php'; // boot admin layout + DB/helpers

$pdo = db(); // database handle

// Cập nhật trạng thái đơn hàng
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_status') {
    $id     = (int)($_POST['id'] ?? 0);      // order id to update
    $status = $_POST['status'] ?? 'pending'; // requested status

    $allowed = ['pending', 'processing', 'completed', 'cancelled'];

    if ($id > 0 && in_array($status, $allowed, true)) {
        try {
            $pdo->beginTransaction();

            // Khóa bản ghi đơn hàng trước khi đổi trạng thái để tránh race condition
            $stmtCheck = $pdo->prepare("SELECT status FROM orders WHERE id = ? FOR UPDATE"); // lock the order row
            $stmtCheck->execute([$id]);
            $orderRow = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            if ($orderRow) {
                $oldStatus = $orderRow['status'];

                
//
                if ($oldStatus !== $status) {
                    if ($oldStatus !== 'completed' && $status === 'completed') {
                        adjust_order_stock($pdo, $id, 'deduct'); // move to completed -> subtract stock
                    } elseif ($oldStatus === 'completed' && $status !== 'completed') {
                        adjust_order_stock($pdo, $id, 'return'); // leave completed -> return stock
                    }

                    $stmtUpdate = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
                    $stmtUpdate->execute([$status, $id]);
                }
            }
//


            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
        }
    }

    header('Location: orders.php');
    exit;
}

// Tìm kiếm đơn hàng
$search = trim($_GET['q'] ?? ''); // quick search keyword

$whereSql = '';
$params   = [];

if ($search !== '') {
    $whereSql = "
        WHERE id = :idExact
           OR customer_phone LIKE :kwPhone
           OR customer_name  LIKE :kwName
    ";

    $like = '%' . $search . '%'; // wildcard for phone/name search
    $params = [
        ':idExact' => (int)$search,
        ':kwPhone' => $like,
        ':kwName'  => $like,
    ];
}

// Đếm tổng và đếm theo trạng thái (không bị giới hạn 100 dòng)
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM orders {$whereSql}");
$countStmt->execute($params);
$totalOrdersCount = (int)$countStmt->fetchColumn();

$statusCounts = [
    'pending'    => 0,
    'processing' => 0,
    'completed'  => 0,
    'cancelled'  => 0,
]; // default zero counts for each status

$statusStmt = $pdo->prepare("
    SELECT status, COUNT(*) AS cnt
    FROM orders
    {$whereSql}
    GROUP BY status
"); // count orders by status with current filter
$statusStmt->execute($params);
foreach ($statusStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $statusCounts[$row['status']] = (int)$row['cnt'];
}

// Danh sách hiển thị (tối đa 100 bản ghi mới nhất)
$listStmt = $pdo->prepare("
    SELECT * FROM orders
    {$whereSql}
    ORDER BY created_at DESC
    LIMIT 100
"); // fetch latest 100 orders by current filter
$listStmt->execute($params);
$orders = $listStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="admin-main__inner">

    <div class="admin-cards">
        <div class="admin-card">
            <div class="admin-card__label">Tổng số đơn</div>
            <div class="admin-card__value"><?= $totalOrdersCount ?></div>
            <div class="admin-card__icon"><i class="fa-solid fa-receipt"></i></div>
            <div style="font-size:0.75rem;color:#9ca3af;">Dựa trên bộ lọc hiện tại (không giới hạn 100)</div>
        </div>
        <div class="admin-card">
            <div class="admin-card__label">Đơn chờ xử lý</div>
            <div class="admin-card__value">
                <?= $statusCounts['pending'] ?? 0 ?>
            </div>
            <div class="admin-card__icon"><i class="fa-regular fa-clock"></i></div>
        </div>
        <div class="admin-card">
            <div class="admin-card__label">Đơn đã hoàn tất</div>
            <div class="admin-card__value">
                <?= $statusCounts['completed'] ?? 0 ?>
            </div>
            <div class="admin-card__icon"><i class="fa-solid fa-circle-check"></i></div>
        </div>
    </div>

    <!-- TÌM KIẾM -->
    <div class="admin-form" style="margin-bottom:10px;">
        <form method="get" style="display:flex;flex-wrap:wrap;gap:8px;align-items:flex-end;">
            <div class="form-group" style="flex:1;min-width:220px;">
                <label>Tìm kiếm đơn hàng</label>
                <input
                    type="text"
                    name="q"
                    value="<?= htmlspecialchars($search) ?>"
                    placeholder="Nhập mã đơn / tên KH / SĐT...">
                <small>Ví dụ: 15, "Nguyễn", "090" ...</small>
            </div>
            <div>
                <button type="submit" class="btn btn--primary">
                    <i class="fa-solid fa-magnifying-glass"></i> Tìm kiếm
                </button>
            </div>
        </form>
    </div>

    <!-- DANH SÁCH ĐƠN -->
    <div class="admin-table-wrap">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
            <h2 style="font-size:0.95rem;">Danh sách đơn hàng mới nhất</h2>
            <span style="font-size:0.8rem;color:#9ca3af;">
                Hiển thị tối đa 100 đơn hàng theo bộ lọc
            </span>
        </div>

        <table class="admin-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Khách hàng</th>
                    <th>Liên hệ</th>
                    <th>Tổng tiền</th>
                    <th>Trạng thái</th>
                    <th>Ngày tạo</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="7" style="text-align:center;font-size:0.85rem;color:#9ca3af;">
                            Không tìm thấy đơn hàng nào.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>
                                <span class="badge badge--id">#<?= (int)$order['id'] ?></span>
                            </td>
                            <td>
                                <div style="font-size:0.9rem;font-weight:500;">
                                    <?= htmlspecialchars($order['customer_name'] ?: 'Khách lẻ') ?>
                                </div>
                                <?php if (!empty($order['customer_note'])): ?>
                                    <div style="font-size:0.75rem;color:#9ca3af;margin-top:2px;max-width:260px;">
                                        Ghi chú: <?= htmlspecialchars($order['customer_note']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td style="font-size:0.8rem;">
                                <div><i class="fa-solid fa-phone"></i> <?= htmlspecialchars($order['customer_phone'] ?? '') ?></div>
                                <?php if (!empty($order['customer_email'])): ?>
                                    <div><i class="fa-regular fa-envelope"></i> <?= htmlspecialchars($order['customer_email']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?= number_format($order['total'], 0, ',', '.') ?>đ</strong>
                            </td>
                            <td>
                                <form method="post" style="display:flex;align-items:center;gap:6px;">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="id" value="<?= (int)$order['id'] ?>">
                                    <select name="status">
                                        <option value="pending" <?= $order['status'] === 'pending'    ? 'selected' : '' ?>>Chờ xử lý</option>
                                        <option value="processing" <?= $order['status'] === 'processing' ? 'selected' : '' ?>>Đang xử lý</option>
                                        <option value="completed" <?= $order['status'] === 'completed'  ? 'selected' : '' ?>>Hoàn tất</option>
                                        <option value="cancelled" <?= $order['status'] === 'cancelled'  ? 'selected' : '' ?>>Đã hủy</option>
                                    </select>
                                    <button type="submit" class="btn btn--ghost btn-sm">
                                        Lưu
                                    </button>
                                </form>
                            </td>
                            <td style="font-size:0.8rem;color:#9ca3af;">
                                <?= htmlspecialchars($order['created_at']) ?>
                            </td>
                            <td style="text-align:right;">
                                <a href="order-detail.php?id=<?= (int)$order['id'] ?>" class="btn btn--ghost btn-sm">
                                    <i class="fa-regular fa-eye"></i> Chi tiết
                                </a>
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
