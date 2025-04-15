<?php
require_once __DIR__ . '/../includes/db.php';

$token = $_GET['token'] ?? '';
$stmt = $db->prepare("SELECT s.*, f.name, u.username FROM shares s
    JOIN files f ON f.id = s.file_id
    JOIN users u ON u.id = s.user_id
    WHERE s.token = :token");
$stmt->bindValue(':token', $token);
$res = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

if (!$res) die("Invalid or expired link.");

if ($res['expires_at'] && strtotime($res['expires_at']) < time()) {
    die("This link has expired.");
}

$filepath = __DIR__ . "/../storage/" . $res['username'] . '/' . $res['name'];

if (!file_exists($filepath)) die("File not found.");

// Check password protection
if ($res['password'] && ($_POST['pw'] ?? null)) {
    if (!password_verify($_POST['pw'], $res['password'])) {
        die("Invalid password.");
    }
}

if ($res['password'] && empty($_POST['pw'])) {
    echo '<form method="post"><input type="password" name="pw" placeholder="Password"><button>View</button></form>';
    exit;
}

// Output file
header('Content-Disposition: inline; filename="' . basename($res['name']) . '"');
header('Content-Type: ' . mime_content_type($filepath));
readfile($filepath);