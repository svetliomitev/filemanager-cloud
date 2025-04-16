<?php
require_once __DIR__ . '/../includes/auth.php';

if (!is_logged_in()) {
    http_response_code(403);
    exit("❌ Not logged in");
}

$user = current_user();
$username = $user['username'];

$uploadId = $_POST['uploadId'] ?? '';
$filename = basename($_POST['filename'] ?? 'file.bin');
$chunks = intval($_POST['totalChunks'] ?? 0);

$chunkDir = __DIR__ . "/../tmp/upload_chunks/{$username}/$uploadId";
$finalPath = __DIR__ . "/../storage/$username/$filename";

$out = fopen($finalPath, 'wb');
for ($i = 0; $i < $chunks; $i++) {
    $chunkFile = "$chunkDir/chunk_$i";
    if (!file_exists($chunkFile)) {
        http_response_code(500);
        exit("❌ Missing chunk $i");
    }
    fwrite($out, file_get_contents($chunkFile));
    unlink($chunkFile); // Clean up
}
fclose($out);

rmdir($chunkDir); // Clean session dir

http_response_code(200);
echo "✅ File assembled";