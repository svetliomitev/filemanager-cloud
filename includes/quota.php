<?php
function get_user_storage_usage($username) {
    $folder = __DIR__ . '/../storage/' . $username;
    $size = 0;
    if (!file_exists($folder)) return 0;

    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folder));
    foreach ($rii as $file) {
        if ($file->isFile()) $size += $file->getSize();
    }
    return $size;
}

function format_bytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    return round($bytes / (1024 ** $pow), $precision) . ' ' . $units[$pow];
}