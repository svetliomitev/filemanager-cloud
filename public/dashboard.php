<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/quota.php';

if (!is_logged_in()) {
    header("Location: index.php");
    exit;
}

$user = current_user();
$username = $user['username'];
$is_admin = ($username === 'admin');
$quotaGB = $user['quota_gb'];

$baseFolder = __DIR__ . '/../storage/' . $username;

if (!is_dir($baseFolder)) {
    mkdir($baseFolder, 0777, true);
}

$subPath = $_GET['path'] ?? '';
$subPath = trim($subPath, '/');
$currentFolder = realpath($baseFolder . '/' . $subPath);

if ($currentFolder === false || strpos($currentFolder, realpath($baseFolder)) !== 0) {
    $currentFolder = $baseFolder;
    $subPath = '';
}

$usedBytes = get_user_storage_usage($baseFolder);
$usedGB = round($usedBytes / (1024 * 1024 * 1024), 2);
$percentUsed = round(($usedBytes / ($quotaGB * 1024 * 1024 * 1024)) * 100, 1);

$db = new SQLite3(__DIR__ . '/../data/database.sqlite');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_folder'])) {
    $newFolderName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $_POST['new_folder']);
    if ($newFolderName) {
        $newFolderPath = $currentFolder . '/' . $newFolderName;
        if (!file_exists($newFolderPath)) {
            mkdir($newFolderPath, 0777, true);
        }
    }
    header("Location: dashboard.php?path=" . urlencode($subPath));
    exit;
}
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
    <a class="navbar-brand" href="dashboard.php">FileManager</a>
    <div class="collapse navbar-collapse">
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
      </ul>
      <span class="navbar-text">Logged in as: <?= htmlspecialchars($username) ?></span>
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

  <form method="POST" class="mb-4 d-flex gap-2">
    <input type="text" name="new_folder" class="form-control" placeholder="New folder name" required>
    <button type="submit" class="btn btn-primary">Create Folder</button>
  </form>

  <div class="mb-4" id="drag-drop-area"></div>

  <script>
    const uppy = new Uppy.Core({
        restrictions: { maxFileSize: 20 * 1024 * 1024 * 1024 },
        autoProceed: true
    });

    uppy.use(Uppy.Dashboard, {
        inline: true,
        target: '#drag-drop-area',
        theme: 'dark',
        proudlyDisplayPoweredByUppy: false
    });

    uppy.use(Uppy.XHRUpload, {
        endpoint: '/api/upload_chunk.php?path=<?= urlencode($subPath) ?>',
        formData: true,
        bundle: false,
        limit: 1
    });

    uppy.on('complete', (result) => {
        const files = result.successful.map(f => ({
            name: f.name,
            uploadId: f.meta.uploadId || '',
            totalChunks: f.meta.totalChunks || '',
            path: "<?= $subPath ?>"
        }));

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
  <h4>Your Files <?= $subPath ? 'in /' . htmlspecialchars($subPath) : '' ?></h4>

  <?php
  $entries = array_diff(scandir($currentFolder), ['.', '..']);
  if ($subPath) {
      $parentPath = dirname($subPath);
      echo "<p><a class='btn btn-outline-light btn-sm' href='dashboard.php?path=" . urlencode($parentPath) . "'>&larr; Back</a></p>";
  }

  if (empty($entries)) {
      echo "<div class='alert alert-warning'>⚠️ No files or folders here.</div>";
  } else {
      echo "<ul class='list-group'>";
      foreach ($entries as $entry) {
          $entryPath = $currentFolder . '/' . $entry;
          $relativePath = ltrim($subPath . '/' . $entry, '/');
          $encodedShare = "path=" . urlencode($subPath) . "&file=" . urlencode($entry);
          if (is_dir($entryPath)) {
              echo "<li class='list-group-item bg-dark text-white d-flex justify-content-between align-items-center'>
                      <span>📁 <a href='dashboard.php?path=" . urlencode($relativePath) . "' class='text-white'>" . htmlspecialchars($entry) . "</a></span>
                      <span>
                          <a href='delete.php?$encodedShare' class='btn btn-sm btn-danger' onclick=\"return confirm('Delete entire folder?')\">Delete</a>
                          <a href='share.php?$encodedShare' class='btn btn-sm btn-warning'>Share</a>
                      </span>
                    </li>";
          } else {
              echo "<li class='list-group-item bg-dark text-white d-flex justify-content-between align-items-center'>
                      <span>📄 " . htmlspecialchars($entry) . "</span>
                      <span>
                          <a href='download.php?$encodedShare' class='btn btn-sm btn-success'>Download</a>
                          <a href='delete.php?$encodedShare' class='btn btn-sm btn-danger' onclick=\"return confirm('Delete this file?')\">Delete</a>
                          <a href='share.php?$encodedShare' class='btn btn-sm btn-warning'>Share</a>
                      </span>
                    </li>";
          }
      }
      echo "</ul>";
  }
  ?>

<hr>
  <h4>🔗 Active Shares</h4>
  <ul class="list-group">
  <?php
  $now = time();
  $res = $db->query("SELECT * FROM shared_links WHERE expires_at IS NULL OR expires_at > datetime('now') ORDER BY created_at DESC");
  while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
      $tokenUrl = "https://filemanager.bgdemo.top/public_share.php?token=" . $row['token'];
      echo "<li class='list-group-item bg-dark text-white d-flex justify-content-between align-items-center'>
              <span>" . ($row['is_folder'] ? '📁' : '📄') . " <code>" . basename($row['path']) . "</code><br>
              <small>Expires: " . ($row['expires_at'] ?? 'Never') . "</small>" . ($is_admin ? "<br><small>Owner: <strong>" . htmlspecialchars($row['owner']) . "</strong></small>" : '') . "</span>
              <span>
                  <a href='$tokenUrl' class='btn btn-sm btn-outline-info' target='_blank'>Open</a>
                  <a href='revoke.php?token=" . $row['token'] . "' class='btn btn-sm btn-outline-danger' onclick=\"return confirm('Revoke this link?')\">Revoke</a>
                  <button class='btn btn-sm btn-outline-light' onclick=\"showQRCode('$tokenUrl')\">QR</button>
              </span>
            </li>";
  }
  ?>
  </ul>

  <h4 class="mt-4">⛔ Expired Shares</h4>
  <ul class="list-group">
  <?php
  $res = $db->query("SELECT * FROM shared_links WHERE expires_at IS NOT NULL AND expires_at <= datetime('now') ORDER BY created_at DESC");
  while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
      echo "<li class='list-group-item bg-secondary text-white d-flex justify-content-between align-items-center'>
              <span>" . ($row['is_folder'] ? '📁' : '📄') . " <code>" . basename($row['path']) . "</code><br>
              <small>Expired: " . $row['expires_at'] . "</small>" . ($is_admin ? "<br><small>Owner: <strong>" . htmlspecialchars($row['owner']) . "</strong></small>" : '') . "</span>
              <span><span class='text-muted'>Expired</span></span>
            </li>";
  }
  ?>
  </ul>

  <?php if ($is_admin): ?>
    <hr>
    <h4>📜 Share Logs</h4>
    <form method="get" class="d-flex mb-2">
      <input type="text" name="log_filter" class="form-control me-2" placeholder="Filter by user or path..." value="<?= htmlspecialchars($_GET['log_filter'] ?? '') ?>">
      <button type="submit" class="btn btn-outline-light">Search</button>
      <a href="?clear_logs=1" class="btn btn-outline-danger ms-2" onclick="return confirm('Clear all logs?')">Clear</a>
      <a href="?export=csv" class="btn btn-outline-secondary ms-2">Export CSV</a>
      <a href="?export=json" class="btn btn-outline-secondary ms-1">Export JSON</a>
    </form>

    <div class="table-responsive">
      <table class="table table-dark table-sm">
        <thead>
          <tr>
            <th>Time</th>
            <th>User</th>
            <th>Type</th>
            <th>Path</th>
            <th>Token</th>
          </tr>
        </thead>
        <tbody>
        <?php
        $filter = isset($_GET['log_filter']) ? trim($_GET['log_filter']) : '';
        $filter_sql = $filter ? "WHERE owner LIKE '%$filter%' OR path LIKE '%$filter%'" : '';
        $logs = $db->query("SELECT * FROM share_logs $filter_sql ORDER BY created_at DESC LIMIT 100");
        while ($log = $logs->fetchArray(SQLITE3_ASSOC)) {
            echo "<tr>
                    <td>" . htmlspecialchars($log['created_at']) . "</td>
                    <td>" . htmlspecialchars($log['owner']) . "</td>
                    <td>" . ($log['is_folder'] ? 'Folder' : 'File') . "</td>
                    <td><code>" . htmlspecialchars($log['path']) . "</code></td>
                    <td><code>" . htmlspecialchars($log['token']) . "</code></td>
                  </tr>";
        }
        ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<!-- QR Modal -->
<div class="modal fade" id="qrModal" tabindex="-1" aria-labelledby="qrModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content bg-dark text-white">
      <div class="modal-header">
        <h5 class="modal-title" id="qrModalLabel">🔗 Share QR Code</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center">
        <img id="qrImage" src="" alt="QR Code" class="img-fluid mb-3">
        <div class="input-group">
          <input type="text" id="shareLink" class="form-control" readonly>
          <button class="btn btn-outline-light" onclick="copyLink()">Copy</button>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
function showQRCode(url) {
    const modal = new bootstrap.Modal(document.getElementById('qrModal'));
    document.getElementById('qrImage').src = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' + encodeURIComponent(url);
    document.getElementById('shareLink').value = url;
    modal.show();
}

function copyLink() {
    const input = document.getElementById('shareLink');
    input.select();
    document.execCommand('copy');
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>