<?php

function get_user_storage_usage($folder) {
    $totalSize = 0;

    if (!is_dir($folder)) {
        return 0;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($folder, RecursiveDirectoryIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        $totalSize += $file->getSize();
    }

    return $totalSize; // in bytes
}