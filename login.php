<?php
session_start();
require_once __DIR__ . "/includes/functions.php";
$pdo = db();

// Xử lý đăng nhập
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Yêu cầu không hợp lệ. Vui lòng thử lại.";
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if ($email === '' || $password === '') {
            $error = "Vui lòng nhập đầy đủ Email và Mật khẩu.";
        } elseif (!validate_email($email)) {
            $error = "Email không hợp lệ.";
        } else {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user'] = $user;

                // Nếu là admin → chuyển vào admin panel
                if ($user['role'] == 1) {
                    header("Location: admin/index.php");
                } else {
                    header("Location: index.php");
                }
                exit;
            } else {
                $error = "Email hoặc mật khẩu không đúng.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Đăng nhập – TechPhone</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>

    <div class="login-wrapper">

        <div class="login-card">
            <div class="login-header">
                <h2>Đăng nhập</h2>
                <p>Chào mừng bạn quay lại với <strong>TechPhone</strong></p>
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
                    <label>Email</label>
                    <div class="input-group">
                        <i class="fa-solid fa-envelope"></i>
                        <input type="email" name="email" placeholder="Nhập email..." required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Mật khẩu</label>
                    <div class="input-group">
                        <i class="fa-solid fa-lock"></i>
                        <input type="password" name="password" placeholder="Nhập mật khẩu..." required>
                    </div>
                </div>

                <button type="submit" class="btn-login">
                    <i class="fa-solid fa-right-to-bracket"></i> Đăng nhập
                </button>

                <div class="login-demo-box">
                    <div class="login-demo-box__title">
                        <i class="fa-solid fa-wand-magic-sparkles"></i> Tài khoản demo
                    </div>
                    <div class="login-demo-box__item">
                        <div>Admin</div>
                        <div class="login-demo-box__creds">
                            <code>admin@gmail.com</code>
                            <code>123456</code>
                        </div>
                    </div>
                    <div class="login-demo-box__item">
                        <div>Khách hàng</div>
                        <div class="login-demo-box__creds">
                            <code>user@example.com</code>
                            <code>123456</code>
                        </div>
                    </div>
                </div>

                <div class="login-bottom">
                    <p>Chưa có tài khoản?
                        <a href="register.php">Đăng ký ngay</a>
                    </p>
                </div>

            </form>
        </div>

    </div>

</body>

</html>
