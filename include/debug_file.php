<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$file = __DIR__ . '/territory_record_excel_download_php8.php';
echo "Checking file path: " . $file . "\n";
echo "File exists: " . (file_exists($file) ? "YES" : "NO") . "\n";
echo "Is readable: " . (is_readable($file) ? "YES" : "NO") . "\n";
echo "PHP Version: " . PHP_VERSION . "\n";
?>