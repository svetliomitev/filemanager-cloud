<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

if (!is_admin()) {
    header("Location: ../index.php");
    exit;
}

$users = $db->query("SELECT * FROM users WHERE quota_gb != 999 ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - User Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>body { background-color: #121212; color: #fff; }</style>
</head>
<body>
<div class="container py-5">
    <h2 class="mb-4">User Management</h2>
    <a href="add_user.php" class="btn btn-success mb-3">+ Add New User</a>

    <table class="table table-dark table-striped table-bordered">
        <thead>
            <tr>
                <th>Name</th><th>Email</th><th>Username</th><th>Quota</th><th>Created</th><th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($u = $users->fetchArray(SQLITE3_ASSOC)): ?>
            <tr>
                <td><?= htmlspecialchars($u['full_name']) ?></td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td><?= htmlspecialchars($u['username']) ?></td>
                <td><?= $u['quota_gb'] ?> GB</td>
                <td><?= $u['created_at'] ?></td>
                <td>
                    <a href="edit_user.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-info">Edit</a>
                    <a href="delete_user.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this user and all data?')">Delete</a>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>
</body>
</html>