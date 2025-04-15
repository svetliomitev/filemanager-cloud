<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/quota.php';

if (!is_logged_in()) {
    http_response_code(403);
    die("❌ Not logged in.");
}

$user = current_user();
$username = $user['username'];
$userFolder = __DIR__ . '/../storage/' . $username;

if (!is_dir($userFolder)) {
    mkdir($userFolder, 0777, true);
}

// Enforce quota
$quota_bytes = $user['quota_gb'] * 1024 * 1024 * 1024;
$current_usage = get_user_storage_usage($userFolder);

// Handle uploaded file
if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
    $upload_size = $_FILES['file']['size'];

    if (($current_usage + $upload_size) > $quota_bytes) {
        http_response_code(413); // Payload Too Large
        die("❌ Upload exceeds your quota. Please delete files or request more space.");
    }

    $filename = basename($_FILES['file']['name']);
    $destination = $userFolder . '/' . $filename;

    // Optional: prevent overwrite
    if (file_exists($destination)) {
        http_response_code(409); // Conflict
        die("❌ A file with that name already exists.");
    }

    // Move file
    if (move_uploaded_file($_FILES['file']['tmp_name'], $destination)) {
        http_response_code(200);
        echo "✅ Upload successful.";
    } else {
        http_response_code(500);
        echo "❌ Failed to move uploaded file.";
    }

} else {
    http_response_code(400);
    echo "❌ No file uploaded or upload error.";
}