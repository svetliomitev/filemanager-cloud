<?php

// 1. Abort if DB already exists
if (file_exists(__DIR__ . '/../data/database.sqlite')) {
    die("✅ Database already initialized.");
}

// 2. Create necessary directories with safe permissions
@mkdir(__DIR__ . '/../data', 0777, true);
@mkdir(__DIR__ . '/../storage', 0777, true);
@mkdir(__DIR__ . '/../shared', 0777, true);

// 3. Create SQLite database
$db = new SQLite3(__DIR__ . '/../data/database.sqlite');

// 4. Create tables
$db->exec("CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE,
    email TEXT UNIQUE,
    password TEXT,
    full_name TEXT,
    quota_gb INTEGER DEFAULT 10,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
)");

$db->exec("CREATE TABLE files (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    path TEXT,
    name TEXT,
    is_folder INTEGER DEFAULT 0,
    size_bytes INTEGER,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id)
)");

$db->exec("CREATE TABLE shares (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    file_id INTEGER,
    user_id INTEGER,
    token TEXT UNIQUE,
    password TEXT DEFAULT NULL,
    expires_at TEXT DEFAULT NULL,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(file_id) REFERENCES files(id),
    FOREIGN KEY(user_id) REFERENCES users(id)
)");

// 5. Load environment variables using Composer
require_once __DIR__ . '/../vendor/autoload.php';

try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
} catch (Exception $e) {
    die("❌ Failed to load .env: " . $e->getMessage());
}

// 6. Optional debug: Print loaded environment
// echo "<pre>"; print_r($_ENV); echo "</pre>";

// 7. Read admin config from .env
$admin_user  = $_ENV['ADMIN_USER']  ?? 'admin';
$admin_email = $_ENV['ADMIN_EMAIL'] ?? 'admin@bgdemo.top';
$admin_pass  = $_ENV['ADMIN_PASS']  ?? 'S1neQU@n0n#9';
$full_name   = $_ENV['ADMIN_NAME']  ?? 'Svetoslav Mitev';

// 8. Hash password
$hashed_pass = password_hash($admin_pass, PASSWORD_BCRYPT);

// 9. Insert admin user
$stmt = $db->prepare("INSERT INTO users (username, email, password, full_name, quota_gb) VALUES (?, ?, ?, ?, 999)");
$stmt->bindValue(1, $admin_user);
$stmt->bindValue(2, $admin_email);
$stmt->bindValue(3, $hashed_pass);
$stmt->bindValue(4, $full_name);

if ($stmt->execute()) {
    echo "✅ Installation complete.<br>Admin user <strong>$admin_user</strong> created.<br>";
    echo "This script will now delete itself.";
    unlink(__FILE__);
} else {
    echo "❌ Failed to insert admin user.<br>";
    echo "SQLite error: " . $db->lastErrorMsg();
}