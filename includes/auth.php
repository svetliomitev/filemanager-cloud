<?php
session_start();
require_once __DIR__ . '/db.php';

function login($credential, $password) {
    global $db;
    $stmt = $db->prepare("SELECT * FROM users WHERE email = :cred OR username = :cred");
    $stmt->bindValue(':cred', $credential);
    $result = $stmt->execute();
    $user = $result->fetchArray(SQLITE3_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user'] = [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'full_name' => $user['full_name'],
            'quota_gb' => $user['quota_gb'],
            'is_admin' => ($user['quota_gb'] === 999)
        ];
        return true;
    }
    return false;
}

function is_logged_in() {
    return isset($_SESSION['user']);
}

function is_admin() {
    return is_logged_in() && $_SESSION['user']['is_admin'];
}

function current_user() {
    return $_SESSION['user'] ?? null;
}

function logout() {
    session_unset();
    session_destroy();
}
