<?php
require_once __DIR__ . '/../includes/auth.php';

if (!is_logged_in()) {
    header("Location: index.php");
    exit;
}

$user = current_user();
$username = $user['username'];
$baseDir = realpath(__DIR__ . '/../storage/' . $username);

$subPath = $_GET['path'] ?? '';
$item = $_GET['file'] ?? '';

$targetPath = $baseDir . '/' . $subPath . '/' . $item;
$realTargetPath = realpath($targetPath);

// Защита: валиден път в рамките на потребителската папка
if ($realTargetPath === false || strpos($realTargetPath, $baseDir) !== 0) {
    http_response_code(403);
    echo "Access denied.";
    exit;
}

// 🔁 Рекурсивно изтриване на директория
function delete_folder_recursive($dir) {
    $items = array_diff(scandir($dir), ['.', '..']);
    foreach ($items as $item) {
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        is_dir($path) ? delete_folder_recursive($path) : unlink($path);
    }
    return rmdir($dir);
}

// Изтриване
if (is_file($realTargetPath)) {
    unlink($realTargetPath);
} elseif (is_dir($realTargetPath)) {
    delete_folder_recursive($realTargetPath);
}

header("Location: dashboard.php?path=" . urlencode($subPath));
exit;