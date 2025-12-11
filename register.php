<?php
session_start();
require_once __DIR__ . "/includes/functions.php";
$pdo = db();

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Yêu cầu không hợp lệ. Vui lòng thử lại.";
    } else {
        $username  = trim($_POST['username'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $phone     = trim($_POST['phone'] ?? '');
        $password  = $_POST['password'] ?? '';
        $password2 = $_POST['password_confirm'] ?? '';

        if ($username === '' || $email === '' || $password === '' || $password2 === '') {
            $error = "Vui lòng nhập đầy đủ các trường bắt buộc.";
        } elseif (!validate_email($email)) {
            $error = "Email không hợp lệ.";
        } elseif (!validate_phone($phone)) {
            $error = "Số điện thoại không hợp lệ (bắt đầu 0, 10-11 chữ số).";
        } elseif ($password !== $password2) {
            $error = "Mật khẩu xác nhận không khớp.";
        } elseif (strlen($password) < 6) {
            $error = "Mật khẩu phải có ít nhất 6 ký tự.";
        } else {
            // Kiểm tra email đã tồn tại chưa
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = "Email này đã được sử dụng. Vui lòng chọn email khác.";
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("
                    INSERT INTO users (username, email, password, phone, role)
                    VALUES (:username, :email, :password, :phone, 2)
                ");
                try {
                    $stmt->execute([
                        ':username' => $username,
                        ':email'    => $email,
                        ':password' => $hash,
                        ':phone'    => $phone,
                    ]);

                    // tự đăng nhập luôn sau khi đăng ký
                    $userId = (int)$pdo->lastInsertId();
                    $_SESSION['user'] = [
                        'id'       => $userId,
                        'username' => $username,
                        'email'    => $email,
                        'phone'    => $phone,
                        'role'     => 2,
                    ];

                    // Có thể chuyển hướng thẳng về trang chủ
                    header("Location: index.php");
                    exit;
                } catch (PDOException $e) {
                    log_error('Register Insert Error', ['error' => $e->getMessage()]);
                    $error = "Có lỗi xảy ra. Vui lòng thử lại sau.";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Đăng ký – TechPhone</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Dùng lại CSS của trang login -->
    <link rel="stylesheet" href="assets/css/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>

    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-header">
                <h2>Tạo tài khoản</h2>
                <p>Đăng ký để mua sắm dễ dàng hơn tại <strong>TechPhone</strong></p>
            </div>

            <?php if ($error): ?>
                <div class="login-error">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="post" class="login-form">
                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">

                <div class="form-group">
                    <label>Họ và tên</label>
                    <div class="input-group">
                        <i class="fa-solid fa-user"></i>
                        <input
                            type="text"
                            name="username"
                            placeholder="Nhập họ tên..."
                            value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                            required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Email</label>
                    <div class="input-group">
                        <i class="fa-solid fa-envelope"></i>
                        <input
                            type="email"
                            name="email"
                            placeholder="Nhập email..."
                            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                            required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Số điện thoại (bắt buộc)</label>
                    <div class="input-group">
                        <i class="fa-solid fa-phone"></i>
                        <input
                            type="text"
                            name="phone"
                            placeholder="Nhập số điện thoại..."
                            value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>Mật khẩu</label>
                    <div class="input-group">
                        <i class="fa-solid fa-lock"></i>
                        <input
                            type="password"
                            name="password"
                            placeholder="Nhập mật khẩu..."
                            required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Nhập lại mật khẩu</label>
                    <div class="input-group">
                        <i class="fa-solid fa-lock"></i>
                        <input
                            type="password"
                            name="password_confirm"
                            placeholder="Nhập lại mật khẩu..."
                            required>
                    </div>
                </div>

                <button type="submit" class="btn-login">
                    <i class="fa-solid fa-user-plus"></i> Đăng ký
                </button>

                <div class="login-bottom">
                    <p>Đã có tài khoản?
                        <a href="login.php">Đăng nhập ngay</a>
                    </p>
                </div>

            </form>
        </div>
    </div>

</body>

</html>