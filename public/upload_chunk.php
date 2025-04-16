<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/quota.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!is_logged_in()) {
    http_response_code(403);
    exit("❌ Not logged in");
}

$user = current_user();
$username = $user['username'];

$chunkDir = __DIR__ . "/../tmp/upload_chunks/{$username}";
@mkdir($chunkDir, 0777, true);

$file = $_FILES['chunk'] ?? null;
if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    exit("❌ Chunk error");
}

$uploadId = $_POST['uploadId'] ?? '';
$chunkIndex = $_POST['chunkIndex'] ?? '';
$totalChunks = $_POST['totalChunks'] ?? '';

$targetDir = "$chunkDir/$uploadId";
@mkdir($targetDir, 0777, true);

$chunkPath = "$targetDir/chunk_$chunkIndex";
if (!move_uploaded_file($file['tmp_name'], $chunkPath)) {
    http_response_code(500);
    exit("❌ Failed to save chunk");
}

http_response_code(200);
echo "✅ Chunk $chunkIndex received";