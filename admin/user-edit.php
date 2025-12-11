<?php
// admin/user-edit.php
require_once __DIR__ . '/../includes/admin_auth.php';
$pdo = db();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: users.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: users.php');
    exit;
}

$currentUserId = current_user_id();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $role     = (int)($_POST['role'] ?? 2);
    $password = $_POST['password'] ?? '';

    if ($username === '' || $email === '') {
        $errors[] = 'Tên và email không được để trống.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email không hợp lệ.';
    }

    // Không cho phép hạ quyền admin ID 1 xuống user
    if ($user['id'] == 1 && $role != 1) {
        $errors[] = 'Không thể thay đổi quyền của tài khoản admin gốc (ID 1).';
    }

    if (empty($errors)) {
        // Kiểm tra email trùng
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id <> ?");
        $stmt->execute([$email, $id]);
        if ($stmt->fetch()) {
            $errors[] = 'Email này đã được sử dụng bởi tài khoản khác.';
        } else {
            // Cập nhật
            if ($password !== '') {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $updateSql = "
                    UPDATE users
                    SET username = :username,
                        email    = :email,
                        phone    = :phone,
                        role     = :role,
                        password = :password
                    WHERE id = :id
                ";
                $params = [
                    ':username' => $username,
                    ':email'    => $email,
                    ':phone'    => $phone,
                    ':role'     => $role,
                    ':password' => $hash,
                    ':id'       => $id,
                ];
            } else {
                $updateSql = "
                    UPDATE users
                    SET username = :username,
                        email    = :email,
                        phone    = :phone,
                        role     = :role
                    WHERE id = :id
                ";
                $params = [
                    ':username' => $username,
                    ':email'    => $email,
                    ':phone'    => $phone,
                    ':role'     => $role,
                    ':id'       => $id,
                ];
            }

            $stmt = $pdo->prepare($updateSql);
            $stmt->execute($params);

            // Nếu sửa chính mình → cập nhật session
            if ($id === $currentUserId) {
                $_SESSION['user']['username'] = $username;
                $_SESSION['user']['email']    = $email;
                $_SESSION['user']['phone']    = $phone;
                $_SESSION['user']['role']     = $role;
            }

            header('Location: users.php');
            exit;
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<style>
    /* ========== CUSTOM UI CHO TRANG SỬA USER ========== */

    .user-edit-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        margin-bottom: 10px;
    }

    .user-edit-header__left h2 {
        font-size: 1.05rem;
        margin-bottom: 2px;
    }

    .user-edit-header__left p {
        font-size: 0.8rem;
        color: #9ca3af;
    }

    .user-edit-badges {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        justify-content: flex-end;
    }

    .badge {
        border-radius: 999px;
        padding: 3px 8px;
        font-size: 0.7rem;
        border: 1px solid rgba(148, 163, 184, 0.5);
        background: rgba(15, 23, 42, 0.8);
        color: #e5e7eb;
    }

    .badge--id {
        border-color: rgba(56, 189, 248, 0.6);
        color: #7dd3fc;
    }

    .badge--role-admin {
        border-color: rgba(248, 113, 113, 0.8);
        color: #fecaca;
        background: rgba(127, 29, 29, 0.9);
    }

    .badge--role-user {
        border-color: rgba(52, 211, 153, 0.7);
        color: #6ee7b7;
        background: rgba(6, 78, 59, 0.9);
    }

    /* Layout 2 cột (thông tin + quyền/mật khẩu) */

    .user-edit-layout {
        display: grid;
        grid-template-columns: minmax(0, 1.4fr) minmax(0, 1fr);
        gap: 12px;
    }

    .user-edit-card {
        background: rgba(15, 23, 42, 0.96);
        border-radius: 18px;
        padding: 10px 12px 12px;
        border: 1px solid #1f2937;
        box-shadow: 0 16px 40px rgba(15, 23, 42, 0.95);
    }

    .user-edit-card h3 {
        font-size: 0.9rem;
        margin-bottom: 8px;
    }

    /* FORM GROUP + LABEL HIỆN ĐẠI */

    .user-edit-card .form-group {
        display: flex;
        flex-direction: column;
        gap: 2px;
        margin-bottom: 10px;
    }

    .user-edit-card .form-group label {
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #9ca3af;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-bottom: 2px;
    }

    /* Chấm tròn đỏ nhỏ trước label (giống accent) */
    .user-edit-card .form-group label::before {
        content: "";
        width: 6px;
        height: 6px;
        border-radius: 999px;
        background: #e11b22;
        box-shadow: 0 0 0 2px rgba(248, 113, 113, 0.4);
    }

    /* Label optional màu nhạt hơn (nếu cần dùng sau) */
    .user-edit-card .form-group label span.optional {
        font-size: 0.7rem;
        color: #6b7280;
    }

    /* Input / select / textarea dùng style chung từ admin.css nhưng bo tròn hơn một chút */
    .user-edit-card .form-group input,
    .user-edit-card .form-group select,
    .user-edit-card .form-group textarea {
        border-radius: 10px;
        border-width: 1px;
        padding: 6px 10px;
        font-size: 0.85rem;
    }

    /* Action buttons */

    .user-edit-actions {
        margin-top: 10px;
        display: flex;
        justify-content: flex-end;
        gap: 8px;
    }

    /* Responsive */

    @media (max-width: 767.98px) {
        .user-edit-layout {
            grid-template-columns: minmax(0, 1fr);
        }

        .user-edit-header {
            flex-direction: column;
            align-items: flex-start;
        }

        .user-edit-badges {
            justify-content: flex-start;
        }
    }
</style>

<div class="admin-form">
    <div class="user-edit-header">
        <div class="user-edit-header__left">
            <h2>Sửa thông tin người dùng</h2>
            <p>Cập nhật tên, email, quyền và mật khẩu một cách an toàn.</p>
        </div>
        <div class="user-edit-badges">
            <span class="badge badge--id">ID: #<?= (int)$user['id'] ?></span>
            <?php if ((int)$user['role'] === 1): ?>
                <span class="badge badge--role-admin">Admin</span>
            <?php else: ?>
                <span class="badge badge--role-user">User</span>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
        <div style="background:#450a0a;border:1px solid #7f1d1d;color:#fecaca;padding:8px 10px;border-radius:10px;font-size:0.8rem;margin-bottom:10px;">
            <?php foreach ($errors as $err): ?>
                <div>- <?= htmlspecialchars($err) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post">
        <div class="user-edit-layout">
            <!-- CỘT TRÁI: Thông tin cơ bản -->
            <div class="user-edit-card">
                <h3>Thông tin cơ bản</h3>
                <div class="form-group">
                    <label>Họ và tên *</label>
                    <input
                        type="text"
                        name="username"
                        value="<?= htmlspecialchars($_POST['username'] ?? $user['username']) ?>"
                        placeholder="Nhập họ tên người dùng">
                </div>
                <div class="form-group">
                    <label>Email *</label>
                    <input
                        type="email"
                        name="email"
                        value="<?= htmlspecialchars($_POST['email'] ?? $user['email']) ?>"
                        placeholder="ví dụ: user@example.com">
                </div>
                <div class="form-group">
                    <label>Số điện thoại <span class="optional">(không bắt buộc)</span></label>
                    <input
                        type="text"
                        name="phone"
                        value="<?= htmlspecialchars($_POST['phone'] ?? $user['phone']) ?>"
                        placeholder="SĐT liên hệ của người dùng">
                </div>
            </div>

            <!-- CỘT PHẢI: Quyền & bảo mật -->
            <div class="user-edit-card">
                <h3>Quyền & bảo mật</h3>
                <div class="form-group">
                    <label>Quyền sử dụng</label>
                    <select name="role">
                        <option value="1" <?= (int)($user['role']) === 1 ? 'selected' : '' ?>>Admin</option>
                        <option value="2" <?= (int)($user['role']) === 2 ? 'selected' : '' ?>>User</option>
                    </select>
                    <small>Admin có toàn quyền quản trị hệ thống, User chỉ sử dụng frontend.</small>
                </div>
                <div class="form-group">
                    <label>Mật khẩu mới</label>
                    <input
                        type="password"
                        name="password"
                        placeholder="Để trống nếu không đổi mật khẩu">
                    <small>Mật khẩu nên có tối thiểu 8 ký tự, bao gồm chữ và số.</small>
                </div>
            </div>
        </div>

        <div class="user-edit-actions">
            <a href="users.php" class="btn btn--ghost">
                Hủy
            </a>
            <button type="submit" class="btn btn--primary">
                <i class="fa-solid fa-floppy-disk"></i> Lưu thay đổi
            </button>
        </div>
    </form>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
