<?php
session_start();

$username = $_SESSION['username'] ?? 'guest';
$path = $_GET['path'] ?? '';
$uploadId = $_POST['uploadId'] ?? uniqid();
$chunkIndex = $_POST['chunkIndex'] ?? 'unknown';

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo "❌ Upload failed or missing file.";
    exit;
}

$tmpDir = __DIR__ . '/../chunks_tmp/' . $username . '/' . $uploadId;

if (!is_dir($tmpDir)) {
    mkdir($tmpDir, 0777, true);
}

$target = $tmpDir . '/' . $chunkIndex;
move_uploaded_file($_FILES['file']['tmp_name'], $target);

http_response_code(200);
echo "✅ Chunk $chunkIndex uploaded.";