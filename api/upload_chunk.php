<?php
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../includes/auth.php';

if (!is_logged_in()) {
    http_response_code(403);
    exit("Not authorized");
}

$user = current_user();
$username = $user['username'];
$tmpDir = __DIR__ . '/../tmp/uploads/' . $username;
@mkdir($tmpDir, 0777, true);

// Uppy sends `uppy-chunk-number` and `uppy-chunks-total` headers
$chunkIndex = $_SERVER['HTTP_UPPY_CHUNK_NUMBER'] ?? null;
$uploadId = $_SERVER['HTTP_UPPY_UPLOAD_UUID'] ?? null;

if (!$chunkIndex || !$uploadId || !isset($_FILES['file'])) {
    http_response_code(400);
    exit("Missing required fields");
}

$chunkName = "$uploadId.part$chunkIndex";
$destination = $tmpDir . '/' . $chunkName;

if (!move_uploaded_file($_FILES['file']['tmp_name'], $destination)) {
    http_response_code(500);
    exit("Failed to store chunk");
}

http_response_code(200);
echo "✅ Chunk $chunkIndex received.";