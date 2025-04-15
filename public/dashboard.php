<?php
require_once __DIR__ . '/../includes/auth.php';

if (!is_logged_in()) {
    header('Location: index.php');
    exit;
}

$user = current_user();
$username = $user['username'];
$userFolder = __DIR__ . '/../storage/' . $username;

// Ensure user folder exists
if (!is_dir($userFolder)) {
    if (!mkdir($userFolder, 0777, true)) {
        die("❌ Failed to create user folder: $userFolder");
    }
}

// Safely read user folder contents
$files = [];
if (is_readable($userFolder)) {
    $files = array_diff(scandir($userFolder), ['.', '..']);
} else {
    echo "<div class='alert alert-warning'>⚠️ Cannot access user folder: $userFolder</div>";
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - <?php echo htmlspecialchars($username); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #121212;
            color: #fff;
        }
        .card {
            background-color: #1e1e1e;
            border: 1px solid #333;
        }
        a, a:hover {
            color: #90caf9;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <h1 class="mb-4">Welcome, <?php echo htmlspecialchars($user['full_name']); ?></h1>

        <div class="mb-3">
            <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
        </div>

        <h4>Your Files</h4>
        <div class="row">
            <?php if (empty($files)): ?>
                <p class="text-muted">You have no files in your folder.</p>
            <?php else: ?>
                <?php foreach ($files as $file): ?>
                    <div class="col-md-4">
                        <div class="card mb-3">
                            <div class="card-body">
                                <strong><?php echo htmlspecialchars($file); ?></strong><br>
                                <a href="#">Download</a> | <a href="#">Delete</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>