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
$file = $_GET['file'] ?? '';

$requested = $baseDir . '/' . $subPath . '/' . $file;
$realFilePath = realpath($requested);

// Защита: файлът трябва да съществува и да е в директорията на потребителя
if ($realFilePath === false || strpos($realFilePath, $baseDir) !== 0 || !is_file($realFilePath)) {
    http_response_code(404);
    echo "File not found or access denied.";
    exit;
}

// Настройки за изтегляне
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($realFilePath) . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($realFilePath));
readfile($realFilePath);
exit;