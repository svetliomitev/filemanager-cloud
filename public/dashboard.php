<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/quota.php';

if (!is_logged_in()) {
    header("Location: index.php");
    exit;
}

$user = current_user();
$user_dir = __DIR__ . "/../storage/" . $user['username'];

if (!file_exists($user_dir)) {
    mkdir($user_dir, 0777, true);
}

// Handle file uploads
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['upload'])) {
    $target = $user_dir . '/' . basename($_FILES['upload']['name']);
    $upload_size = $_FILES['upload']['size'];
    if (get_user_storage_usage($user['username']) + $upload_size <= $user['quota_gb'] * 1024 ** 3) {
        move_uploaded_file($_FILES['upload']['tmp_name'], $target);
    } else {
        $upload_error = "Quota exceeded. Upload not allowed.";
    }
}

// Handle file deletion
if (isset($_GET['delete'])) {
    $file = basename($_GET['delete']);
    $filepath = $user_dir . '/' . $file;
    if (file_exists($filepath)) unlink($filepath);
}

// Handle new folder creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['foldername'])) {
    $foldername = trim($_POST['foldername']);
    if ($foldername !== '' && preg_match('/^[\w\- ]+$/', $foldername)) {
        mkdir($user_dir . '/' . $foldername);
    }
}

$files = array_diff(scandir($user_dir), ['.', '..']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $user['full_name'] ?> - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #121212; color: #fff; }
        .file-box { padding: 1rem; border: 1px solid #333; border-radius: 10px; margin-bottom: 0.5rem; }
    </style>
</head>
<body>
<div class="container py-5">
    <h2 class="mb-4">Welcome, <?= htmlspecialchars($user['full_name']) ?></h2>
    
    <div class="mb-4">
        <strong>Used:</strong> <?= format_bytes(get_user_storage_usage($user['username'])) ?> /
        <?= $user['quota_gb'] ?> GB
    </div>

    <form method="post" enctype="multipart/form-data" class="mb-3">
        <div class="input-group">
            <input type="file" name="upload" class="form-control" required>
            <button class="btn btn-success">Upload</button>
        </div>
        <?php if (!empty($upload_error)): ?>
            <div class="text-danger mt-2"><?= $upload_error ?></div>
        <?php endif; ?>
    </form>

    <form method="post" class="mb-4">
        <div class="input-group">
            <input type="text" name="foldername" class="form-control" placeholder="New Folder Name" required>
            <button class="btn btn-secondary">Create Folder</button>
        </div>
    </form>

    <h4>Your Files</h4>
    <?php foreach ($files as $file): ?>
    <div class="file-box">
        <div class="d-flex justify-content-between align-items-center">
            <span><?= htmlspecialchars($file) ?></span>
            <a href="?delete=<?= urlencode($file) ?>" class="btn btn-danger btn-sm">Delete</a>
        </div>
        
        <!-- Share Form -->
        <form method="post" action="/api/share.php" class="mt-2" target="_blank">
            <input type="hidden" name="action" value="create">
            <input type="hidden" name="file" value="<?= htmlspecialchars($file) ?>">
            <div class="row g-1">
                <div class="col">
                    <input type="text" name="expires" class="form-control form-control-sm" placeholder="Expires (optional)">
                </div>
                <div class="col">
                    <input type="text" name="password" class="form-control form-control-sm" placeholder="Password (optional)">
                </div>
                <div class="col-auto">
                    <button class="btn btn-info btn-sm">Share</button>
                </div>
            </div>
        </form>

        <!-- Revoke Form -->
        <form method="post" action="/api/share.php" class="mt-1">
            <input type="hidden" name="action" value="revoke">
            <input type="hidden" name="file" value="<?= htmlspecialchars($file) ?>">
            <button class="btn btn-warning btn-sm">Revoke Share</button>
        </form>
    </div>
<?php endforeach; ?>
</div>
</body>
</html>