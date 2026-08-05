<?php
// api/profile-debug.php - Debug Profile Update API
header('Content-Type: application/json');
session_start();

require_once '../config.php';

// Log the request
$log_file = __DIR__ . '/../Temp/profile_api_debug.log';

$debug_data = [
    'timestamp' => date('Y-m-d H:i:s'),
    'method' => $_SERVER['REQUEST_METHOD'],
    'post_data' => $_POST,
    'files' => array_keys($_FILES),
];

file_put_contents($log_file, json_encode($debug_data, JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);

echo json_encode([
    'success' => true,
    'message' => 'Debug logged',
    'received_post' => $_POST,
    'received_files' => array_keys($_FILES)
]);
?>
