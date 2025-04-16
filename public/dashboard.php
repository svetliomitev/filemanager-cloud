<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/quota.php';

if (!is_logged_in()) {
    header("Location: index.php");
    exit;
}

$user = current_user();
$username = $user['username'];
$userFolder = realpath(__DIR__ . '/../storage/' . $username);
$quotaGB = $user['quota_gb'];
$usedBytes = get_user_storage_usage($userFolder);
$usedGB = round($usedBytes / (1024 * 1024 * 1024), 2);
$percentUsed = round(($usedBytes / ($quotaGB * 1024 * 1024 * 1024)) * 100, 1);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - File Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://releases.transloadit.com/uppy/v3.13.0/uppy.min.css" rel="stylesheet">
    <script src="https://releases.transloadit.com/uppy/v3.13.0/uppy.min.js"></script>
</head>
<body class="bg-dark text-white">

<nav class="navbar navbar-expand-lg navbar-dark bg-secondary mb-4">
  <div class="container-fluid">
    <a class="navbar-brand" href="#">FileManager</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
      </ul>
      <span class="navbar-text">
        Logged in as: <?= htmlspecialchars($username) ?>
      </span>
    </div>
  </div>
</nav>

<div class="container">
    <h3>Welcome, <?= htmlspecialchars($username) ?></h3>

    <div class="mb-4">
        <strong>Storage Used:</strong> <?= $usedGB ?> GB / <?= $quotaGB ?> GB (<?= $percentUsed ?>%)
        <div class="progress">
            <div class="progress-bar bg-info" role="progressbar" style="width: <?= $percentUsed ?>%;" aria-valuenow="<?= $percentUsed ?>" aria-valuemin="0" aria-valuemax="100"><?= $percentUsed ?>%</div>
        </div>
    </div>

    <div class="mb-4" id="drag-drop-area"></div>

    <script>
    const uppy = new Uppy.Core({
        restrictions: {
            maxFileSize: 20 * 1024 * 1024 * 1024, // 20GB max
        },
        autoProceed: true
    });

    uppy.use(Uppy.Dashboard, {
        inline: true,
        target: '#drag-drop-area',
        theme: 'dark',
        proudlyDisplayPoweredByUppy: false
    });

    uppy.use(Uppy.XHRUpload, {
        endpoint: '/api/upload_chunk.php',
        formData: true,
        bundle: false,
        limit: 1
    });

    uppy.on('complete', (result) => {
        const files = result.successful.map(f => ({
            name: f.name,
            uploadId: f.meta.uploadId || '',
            totalChunks: f.meta.totalChunks || '',
        }));

        // Trigger finalization
        files.forEach(file => {
            fetch('/api/assemble_chunks.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(file)
            }).then(r => r.text()).then(console.log);
        });

        alert('Upload complete!');
        location.reload();
    });
    </script>

    <hr>

    <h4>Your Files</h4>
    <ul>
        <?php
        if (is_dir($userFolder)) {
            $files = array_diff(scandir($userFolder), ['.', '..']);
            foreach ($files as $file) {
                echo "<li>" . htmlspecialchars($file) . "</li>";
            }
        } else {
            echo "<li>⚠️ No folder found for user.</li>";
        }
        ?>
    </ul>
</div>

</body>
</html>