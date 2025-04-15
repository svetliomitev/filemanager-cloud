<?php
require_once __DIR__ . '/../includes/auth.php';

if (is_logged_in()) {
    header("Location: dashboard.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (login($_POST['credential'], $_POST['password'])) {
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Invalid login credentials.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Cloud File Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #121212; color: #fff; }
        .form-control, .btn { border-radius: 0.5rem; }
        .login-box { max-width: 400px; margin: auto; padding-top: 10vh; }
    </style>
</head>
<body>
    <div class="login-box">
        <div class="card bg-dark text-white shadow-lg p-4">
            <div class="card-body">
                <h3 class="card-title mb-4">Sign In</h3>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <form method="post">
                    <div class="mb-3">
                        <label>Email or Username</label>
                        <input type="text" name="credential" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <button class="btn btn-primary w-100">Login</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>