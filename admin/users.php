<?php
// admin/users.php
require_once __DIR__ . '/../includes/admin_auth.php'; // enforce admin access
$pdo = db(); // db connection

$currentUserId = current_user_id(); // track current admin to prevent self-delete

// Xử lý xóa user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $id = (int)($_POST['id'] ?? 0); // user id to remove

//
    if ($id > 0 && $id !== $currentUserId && $id !== 1) { // tránh xóa chính mình và user ID 1
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?"); // delete only if not current/primary admin
        $stmt->execute([$id]);
    }
//

    header('Location: users.php');
    exit;
}

require_once __DIR__ . '/includes/header.php';

// Lấy danh sách user
$stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC"); // fetch all users newest first
$users = $stmt->fetchAll();
?>

<h2 style="margin-bottom:10px;">Quản lý người dùng</h2>

<div class="admin-table-wrap">
    <table class="admin-table admin-table--users">
        <thead>
            <tr>
                <th>ID</th>
                <th>Tên</th>
                <th>Email</th>
                <th>Điện thoại</th>
                <th>Quyền</th>
                <th>Ngày tạo</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($users)): ?>
                <tr>
                    <td colspan="7">Chưa có người dùng.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?= (int)$u['id'] ?></td>
                        <td><?= htmlspecialchars($u['username']) ?></td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td><?= htmlspecialchars($u['phone']) ?></td>
                        <td><?= (int)$u['role'] === 1 ? 'Admin' : 'User' ?></td>
                        <td><?= htmlspecialchars($u['created_at']) ?></td>
                        <td>
                            <a href="user-edit.php?id=<?= (int)$u['id'] ?>" class="link-inline">Sửa</a>
                            <?php if ((int)$u['id'] !== $currentUserId && (int)$u['id'] !== 1): ?>
                                |
                                <form method="post" style="display:inline;" onsubmit="return confirm('Xóa người dùng này?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                    <button type="submit" class="link-inline" style="color:#f87171;border:none;background:none;padding:0;">
                                        Xóa
                                    </button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
