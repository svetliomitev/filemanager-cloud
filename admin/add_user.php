<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

if (!is_admin()) exit("Unauthorized");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $db->prepare("INSERT INTO users (username, email, password, full_name, quota_gb) VALUES (?, ?, ?, ?, ?)");
    $stmt->bindValue(1, $_POST['username']);
    $stmt->bindValue(2, $_POST['email']);
    $stmt->bindValue(3, password_hash($_POST['password'], PASSWORD_BCRYPT));
    $stmt->bindValue(4, $_POST['full_name']);
    $stmt->bindValue(5, (int)$_POST['quota_gb']);
    $stmt->execute();

    @mkdir(__DIR__ . '/../storage/' . $_POST['username'], 0777, true);
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8"><title>Add User</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>body { background-color: #121212; color: #fff; }</style>
</head>
<body>
<div class="container py-5">
    <h2>Add New User</h2>
    <form method="post" class="card card-body bg-dark text-white">
        <input type="text" name="full_name" class="form-control mb-2" placeholder="Full Name" required>
        <input type="email" name="email" class="form-control mb-2" placeholder="Email" required>
        <input type="text" name="username" class="form-control mb-2" placeholder="Username" required>
        <input type="password" name="password" class="form-control mb-2" placeholder="Password" required>
        <input type="number" name="quota_gb" class="form-control mb-3" placeholder="Quota GB" value="10" min="1">
        <button class="btn btn-primary">Create User</button>
    </form>
</div>
</body>
</html>