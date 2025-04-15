<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/quota.php';

if (!is_logged_in()) {
    header('Location: index.php');
    exit;
}

$user = current_user();
$username = $user['username'];
$full_name = $user['full_name'];
$quota_gb = $user['quota_gb'];

$userFolder = __DIR__ . '/../storage/' . $username;

if (!is_dir($userFolder)) {
    if (!mkdir($userFolder, 0777, true)) {
        die("❌ Failed to create user folder: $userFolder");
    }
}

$usage_bytes = get_user_storage_usage($userFolder);
$usage_gb = round($usage_bytes / (1024 ** 3), 2);
$quota_exceeded = $usage_gb >= $quota_gb;

$files = [];
if (is_readable($userFolder)) {
    $files = array_diff(scandir($userFolder), ['.', '..']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - <?php echo htmlspecialchars($full_name); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #121212;
            color: #fff;
        }
        .card {
            background-color: #1f1f1f;
            border: 1px solid #333;
        }
        .navbar, .dropdown-menu {
            background-color: #1a1a1a;
        }
        a, a:hover {
            color: #90caf9;
        }
        .progress {
            height: 20px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark px-3 mb-4 border-bottom border-secondary">
        <a class="navbar-brand" href="#">FileManager</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="#">Sharing</a></li>
                <?php if ((int)$user['quota_gb'] === 999): ?>
                    <li class="nav-item"><a class="nav-link" href="/admin/index.php">Admin Panel</a></li>
                <?php endif; ?>
            </ul>
            <span class="navbar-text me-3"><?php echo htmlspecialchars($full_name); ?></span>
            <a class="btn btn-outline-light btn-sm" href="logout.php">Logout</a>
        </div>
    </nav>

    <div class="container">

        <div class="mb-4">
            <h4>📁 Your Files</h4>
            <p>Used: <strong><?php echo $usage_gb; ?> GB</strong> of <strong><?php echo $quota_gb; ?> GB</strong></p>

            <?php if ($quota_exceeded): ?>
                <div class="alert alert-danger">🚫 You’ve exceeded your quota. Uploading is disabled.</div>
            <?php endif; ?>

            <form id="uploadForm" enctype="multipart/form-data" class="mb-4" <?php if ($quota_exceeded) echo 'style="pointer-events: none; opacity: 0.6;"'; ?>>
                <div class="input-group mb-2">
                    <input type="file" name="file" id="fileInput" class="form-control" required>
                    <button type="submit" class="btn btn-primary">Upload</button>
                </div>
                <div class="progress" style="display: none;">
                    <div class="progress-bar" role="progressbar" style="width: 0%">0%</div>
                </div>
                <div id="uploadStatus" class="mt-2"></div>
            </form>
        </div>

        <div class="row">
            <?php if (empty($files)): ?>
                <p class="text-muted">No files uploaded yet.</p>
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

    <script>
    document.getElementById('uploadForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const form = e.target;
        const fileInput = document.getElementById('fileInput');
        const file = fileInput.files[0];
        const formData = new FormData();
        formData.append('file', file);

        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'upload.php', true);

        const progressContainer = document.querySelector('.progress');
        const progressBar = document.querySelector('.progress-bar');
        const status = document.getElementById('uploadStatus');

        progressContainer.style.display = 'block';
        progressBar.style.width = '0%';
        progressBar.classList.remove('bg-success', 'bg-danger');
        progressBar.textContent = '0%';
        status.innerHTML = '';

        xhr.upload.onprogress = function(e) {
            if (e.lengthComputable) {
                const percent = Math.round((e.loaded / e.total) * 100);
                progressBar.style.width = percent + '%';
                progressBar.textContent = percent + '%';
            }
        };

        xhr.onload = function() {
            if (xhr.status === 200) {
                status.innerHTML = '<span class="text-success">✅ Upload complete!</span>';
                progressBar.classList.add('bg-success');
                setTimeout(() => location.reload(), 1500);
            } else {
                status.innerHTML = '<span class="text-danger">❌ Upload failed.</span>';
                progressBar.classList.add('bg-danger');
            }
        };

        xhr.onerror = function() {
            status.innerHTML = '<span class="text-danger">❌ Upload failed (connection error).</span>';
            progressBar.classList.add('bg-danger');
        };

        xhr.send(formData);
    });
    </script>
</body>
</html>