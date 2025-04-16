<?php
require_once __DIR__ . '/../includes/auth.php';

if (!is_logged_in()) {
    header("Location: index.php");
    exit;
}

$user = current_user();
$username = $user['username'];
$token = $_GET['token'] ?? '';

if (!$token) {
    die("❌ Missing token.");
}

$db = new SQLite3(__DIR__ . '/../data/database.sqlite');

$stmt = $db->prepare("DELETE FROM shared_links WHERE token = ? AND owner = ?");
$stmt->bindValue(1, $token);
$stmt->bindValue(2, $username);
$stmt->execute();

header("Location: dashboard.php");
exit;