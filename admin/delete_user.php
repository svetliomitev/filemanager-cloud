<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

if (!is_admin()) exit("Unauthorized");

$id = (int)$_GET['id'];
$user = $db->querySingle("SELECT * FROM users WHERE id = $id", true);
if (!$user) exit("User not found");

$db->exec("DELETE FROM shares WHERE user_id = $id");
$db->exec("DELETE FROM files WHERE user_id = $id");
$db->exec("DELETE FROM users WHERE id = $id");

$dir = __DIR__ . '/../storage/' . $user['username'];
if (is_dir($dir)) {
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($files as $file) {
        $file->isDir() ? rmdir($file) : unlink($file);
    }
    rmdir($dir);
}

header("Location: index.php");