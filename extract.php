<?php

$zip = new ZipArchive();

$zipFile = 'doable.zip';
$extractPath = __DIR__;

if ($zip->open($zipFile) === TRUE) {
    $zip->extractTo($extractPath);
    $zip->close();

    echo "ZIP extracted successfully!";
} else {
    echo "Failed to extract ZIP file.";
}
