<?php
require_once __DIR__ . '/../includes/auth.php';

if (!is_logged_in()) {
    header("Location: index.php");
    exit;
}

$user = current_user();
$username = $user['username'];
$baseDir = realpath(__DIR__ . '/../storage/' . $username);

$subPath = $_GET['path'] ?? '';
$item = $_GET['file'] ?? '';
$targetPath = $baseDir . '/' . $subPath . '/' . $item;
$realPath = realpath($targetPath);

if ($realPath === false || strpos($realPath, $baseDir) !== 0) {
    die("❌ Invalid path.");
}

$is_folder = is_dir($realPath) ? 1 : 0;
$token = bin2hex(random_bytes(16));
$db = new SQLite3(__DIR__ . '/../data/database.sqlite');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $expires = $_POST['expires'] ?? '';
    $password_hash = $password ? password_hash($password, PASSWORD_BCRYPT) : null;
    $fullPath = str_replace('\\', '/', $realPath); // for Windows compatibility

    // Запис в shared_links
    $stmt = $db->prepare("INSERT INTO shared_links (token, path, is_folder, owner, password_hash, expires_at) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bindValue(1, $token);
    $stmt->bindValue(2, $fullPath);
    $stmt->bindValue(3, $is_folder, SQLITE3_INTEGER);
    $stmt->bindValue(4, $username);
    $stmt->bindValue(5, $password_hash);
    $stmt->bindValue(6, $expires ?: null);

    if ($stmt->execute()) {
        // Запис в share_logs
        $log = $db->prepare("INSERT INTO share_logs (owner, path, is_folder, token) VALUES (?, ?, ?, ?)");
        $log->bindValue(1, $username);
        $log->bindValue(2, $fullPath);
        $log->bindValue(3, $is_folder, SQLITE3_INTEGER);
        $log->bindValue(4, $token);
        $log->execute();

        $publicLink = "public_share.php?token=" . $token;
        echo "<div class='alert alert-success'>✅ Shared successfully. <br>Link: <a href='$publicLink' target='_blank'>$publicLink</a></div>";
    } else {
        echo "<div class='alert alert-danger'>❌ Failed to share: " . $db->lastErrorMsg() . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Share <?= htmlspecialchars($item) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-dark text-white">
<div class="container py-5">
    <h3>Share <?= $is_folder ? "Folder" : "File" ?>: <code><?= htmlspecialchars($item) ?></code></h3>

    <form method="POST" class="mt-4">
        <div class="mb-3">
            <label for="password" class="form-label">Optional password:</label>
            <input type="text" name="password" class="form-control" placeholder="Leave empty for no password">
        </div>
        <div class="mb-3">
            <label for="expires" class="form-label">Expiry date (optional):</label>
            <input type="datetime-local" name="expires" class="form-control">
        </div>
        <button type="submit" class="btn btn-primary">Generate Share Link</button>
        <a href="dashboard.php?path=<?= urlencode($subPath) ?>" class="btn btn-secondary">Back</a>
    </form>
</div>
</body>
</html>