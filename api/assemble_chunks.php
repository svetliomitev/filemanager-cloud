<?php
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/quota.php';

if (!is_logged_in()) {
    http_response_code(403);
    exit("Not authorized");
}

$user = current_user();
$username = $user['username'];
$storageDir = __DIR__ . '/../storage/' . $username;
$tmpDir = __DIR__ . '/../tmp/uploads/' . $username;

@mkdir($storageDir, 0777, true);

$uploadId = $_POST['uploadId'] ?? null;
$fileName = basename($_POST['fileName'] ?? '');
$totalChunks = (int) ($_POST['chunks'] ?? 0);

if (!$uploadId || !$fileName || !$totalChunks) {
    http_response_code(400);
    exit("Missing fields");
}

// Enforce quota
$quota_bytes = $user['quota_gb'] * 1024 * 1024 * 1024;
$current_usage = get_user_storage_usage($storageDir);

// Calculate total size
$totalSize = 0;
for ($i = 0; $i < $totalChunks; $i++) {
    $chunkPath = "$tmpDir/$uploadId.part$i";
    if (!file_exists($chunkPath)) {
        http_response_code(400);
        exit("Missing chunk $i");
    }
    $totalSize += filesize($chunkPath);
}

if (($current_usage + $totalSize) > $quota_bytes) {
    http_response_code(413);
    exit("Upload exceeds quota");
}

// Reassemble
$destination = $storageDir . '/' . $fileName;
$fp = fopen($destination, 'wb');

for ($i = 0; $i < $totalChunks; $i++) {
    $chunkPath = "$tmpDir/$uploadId.part$i";
    $chunk = fopen($chunkPath, 'rb');
    stream_copy_to_stream($chunk, $fp);
    fclose($chunk);
    unlink($chunkPath);
}
fclose($fp);

http_response_code(200);
echo "✅ Upload complete.";