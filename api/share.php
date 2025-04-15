<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

if (!is_logged_in()) exit("Unauthorized");

$user = current_user();
$action = $_POST['action'] ?? '';
$filename = basename($_POST['file'] ?? '');

$filepath = realpath(__DIR__ . "/../storage/" . $user['username'] . '/' . $filename);
if (!$filepath || strpos($filepath, "/storage/{$user['username']}/") === false) {
    exit("Invalid file");
}

if ($action === 'create') {
    $token = bin2hex(random_bytes(12));
    $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_BCRYPT) : null;
    $expires = !empty($_POST['expires']) ? date('Y-m-d H:i:s', strtotime($_POST['expires'])) : null;

    // Insert into shares table
    $stmt = $db->prepare("INSERT INTO shares (file_id, user_id, token, password, expires_at) 
        VALUES (
            (SELECT id FROM files WHERE user_id = :uid AND name = :filename),
            :uid, :token, :password, :expires)");
    $stmt->bindValue(':uid', $user['id']);
    $stmt->bindValue(':filename', $filename);
    $stmt->bindValue(':token', $token);
    $stmt->bindValue(':password', $password);
    $stmt->bindValue(':expires', $expires);
    $stmt->execute();

    echo json_encode(['status' => 'ok', 'link' => "https://yourdomain.com/shared.php?token=$token"]);

} elseif ($action === 'revoke') {
    $stmt = $db->prepare("DELETE FROM shares WHERE user_id = :uid AND file_id = (
        SELECT id FROM files WHERE user_id = :uid AND name = :filename
    )");
    $stmt->bindValue(':uid', $user['id']);
    $stmt->bindValue(':filename', $filename);
    $stmt->execute();
    echo json_encode(['status' => 'revoked']);
}