<?php
session_start();

$username = $_SESSION['username'] ?? 'guest';
$data = json_decode(file_get_contents('php://input'), true);

$uploadId = $data['uploadId'] ?? '';
$totalChunks = (int)($data['totalChunks'] ?? 0);
$filename = $data['name'] ?? 'unnamed';
$path = $data['path'] ?? '';

$tmpDir = __DIR__ . '/../chunks_tmp/' . $username . '/' . $uploadId;
$destDir = realpath(__DIR__ . '/../storage/' . $username . '/' . $path);

// Ensure destination folder exists
if (!is_dir($destDir)) {
    mkdir($destDir, 0777, true);
}

$output = $destDir . '/' . $filename;
$outFile = fopen($output, 'wb');

// Assemble the chunks
for ($i = 0; $i < $totalChunks; $i++) {
    $chunkPath = $tmpDir . '/' . $i;
    if (file_exists($chunkPath)) {
        fwrite($outFile, file_get_contents($chunkPath));
    } else {
        fclose($outFile);
        http_response_code(400);
        echo "❌ Missing chunk $i";
        exit;
    }
}
fclose($outFile);

// Clean up chunk files
array_map('unlink', glob($tmpDir . '/*'));
rmdir($tmpDir);

echo "✅ File assembled as $filename";
