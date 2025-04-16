<?php
// Enable detailed error reporting
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../data/php_errors.log');
error_reporting(E_ALL);

error_log("🧪 Upload handler triggered");

// Log the raw $_FILES array for debugging
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("📨 POST request received.");
    if (!isset($_FILES['file'])) {
        error_log("🚫 \$_FILES['file'] is not set.");
    } else {
        error_log("📥 File upload received: " . print_r($_FILES['file'], true));
    }
}

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/quota.php';

if (!is_logged_in()) {
    http_response_code(403);
    die("❌ Not logged in.");
}

$user = current_user();
$username = $user['username'];
$userFolder = __DIR__ . '/../storage/' . $username;

// Ensure user folder exists
if (!is_dir($userFolder)) {
    if (!mkdir($userFolder, 0777, true)) {
        error_log("❌ Failed to create user folder: $userFolder");
        http_response_code(500);
        die("❌ Server error: Cannot create user folder.");
    }
}

// Enforce quota
$quota_bytes = $user['quota_gb'] * 1024 * 1024 * 1024;
$current_usage = get_user_storage_usage($userFolder);

if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
    $upload_size = $_FILES['file']['size'];

    // Log size info
    error_log("📊 Quota check: usage={$current_usage}B, uploading={$upload_size}B, quota={$quota_bytes}B");

    if (($current_usage + $upload_size) > $quota_bytes) {
        error_log("❌ Upload rejected due to quota limit.");
        http_response_code(413); // Payload Too Large
        die("❌ Upload exceeds your quota. Please delete files or request more space.");
    }

    $filename = basename($_FILES['file']['name']);
    $destination = $userFolder . '/' . $filename;

    // Prevent overwrite
    if (file_exists($destination)) {
        error_log("❌ File already exists: $destination");
        http_response_code(409); // Conflict
        die("❌ A file with that name already exists.");
    }

    // Try to move the uploaded file
    if (!move_uploaded_file($_FILES['file']['tmp_name'], $destination)) {
        error_log("❌ move_uploaded_file failed!");
        error_log("Temp path: " . $_FILES['file']['tmp_name']);
        error_log("Destination: $destination");
        error_log("Size: " . $_FILES['file']['size']);
        error_log("Disk Free Space: " . disk_free_space(dirname($destination)));

        http_response_code(500);
        echo "❌ Failed to move uploaded file.";
        exit;
    }

    error_log("✅ Upload succeeded: $filename");
    http_response_code(200);
    echo "✅ Upload successful.";
} else {
    $err = $_FILES['file']['error'] ?? 'N/A';
    error_log("❌ Upload failed. Upload error code: $err");
    http_response_code(400);
    echo "❌ No file uploaded or upload error.";
}