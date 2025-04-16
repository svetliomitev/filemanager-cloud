<?php
$db = new SQLite3(__DIR__ . '/../data/database.sqlite');

$token = $_GET['token'] ?? '';
if (!$token) {
    http_response_code(400);
    die("❌ Missing token.");
}

$stmt = $db->prepare("SELECT * FROM shared_links WHERE token = ?");
$stmt->bindValue(1, $token);
$result = $stmt->execute();
$link = $result->fetchArray(SQLITE3_ASSOC);

if (!$link) {
    http_response_code(404);
    die("❌ Invalid or expired link.");
}

// Check expiry
if ($link['expires_at'] && strtotime($link['expires_at']) < time()) {
    die("⏳ This link has expired.");
}

$path = $link['path'];
$is_folder = $link['is_folder'];
$requires_password = $link['password_hash'] ? true : false;

// Check password (if needed)
if ($requires_password && ($_SERVER['REQUEST_METHOD'] === 'POST')) {
    $submitted = $_POST['password'] ?? '';
    if (!password_verify($submitted, $link['password_hash'])) {
        $error = "❌ Incorrect password.";
    } else {
        $_SESSION["share_access_$token"] = true;
    }
}

// If password is needed and not yet passed:
session_start();
if ($requires_password && !($_SESSION["share_access_$token"] ?? false)) {
    ?>
    <!DOCTYPE html>
    <html lang="en"><head>
        <meta charset="UTF-8">
        <title>Protected Link</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    </head><body class="bg-dark text-white">
    <div class="container py-5">
        <h3>🔐 Protected Share</h3>
        <?php if (isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
        <form method="POST">
            <input type="password" name="password" class="form-control mb-3" placeholder="Enter password" required>
            <button type="submit" class="btn btn-primary">Access</button>
        </form>
    </div>
    </body></html>
    <?php
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Shared <?= $is_folder ? 'Folder' : 'File' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-dark text-white">
<div class="container py-5">
    <h3>📤 Shared <?= $is_folder ? 'Folder' : 'File' ?></h3>

    <?php if (!$is_folder): ?>
        <p>
            <a href="../download.php?path=<?= urlencode(dirname(str_replace(__DIR__ . '/../storage/', '', $path))) ?>&file=<?= urlencode(basename($path)) ?>" class="btn btn-success">⬇️ Download File</a>
        </p>
    <?php else: ?>
        <p><strong>Contents of folder:</strong></p>
        <ul class="list-group">
            <?php
            $items = array_diff(scandir($path), ['.', '..']);
            foreach ($items as $entry) {
                $full = $path . '/' . $entry;
                echo "<li class='list-group-item bg-dark text-white d-flex justify-content-between align-items-center'>";
                echo is_dir($full) ? "📁 $entry" : "📄 $entry";
                if (is_file($full)) {
                    $relative = str_replace(__DIR__ . '/../storage/' . $link['owner'] . '/', '', $full);
                    echo "<a href=\"../download.php?path=" . urlencode(dirname($relative)) . "&file=" . urlencode(basename($entry)) . "\" class='btn btn-sm btn-outline-light'>Download</a>";
                }
                echo "</li>";
            }
            ?>
        </ul>
    <?php endif; ?>
</div>
</body>
</html>