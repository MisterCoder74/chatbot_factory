<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

if (!isset($_FILES['image'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing image file']);
    exit;
}

$file = $_FILES['image'];
if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
    echo json_encode(['status' => 'error', 'message' => 'Upload error']);
    exit;
}

$maxBytes = 5 * 1024 * 1024;
if (($file['size'] ?? 0) > $maxBytes) {
    echo json_encode(['status' => 'error', 'message' => 'File too large (max 5MB)']);
    exit;
}

$tmp = $file['tmp_name'] ?? null;
if (!$tmp || !is_file($tmp)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid upload']);
    exit;
}

$info = @getimagesize($tmp);
if ($info === false) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid image file']);
    exit;
}

$mime = $info['mime'] ?? '';
$allowed = ['image/png', 'image/jpeg', 'image/webp'];
if (!in_array($mime, $allowed, true)) {
    echo json_encode(['status' => 'error', 'message' => 'Unsupported image format']);
    exit;
}

$raw = @file_get_contents($tmp);
if ($raw === false) {
    echo json_encode(['status' => 'error', 'message' => 'Unable to read uploaded file']);
    exit;
}

$b64 = base64_encode($raw);

echo json_encode([
    'status' => 'success',
    'mime' => $mime,
    'data_url' => 'data:' . $mime . ';base64,' . $b64
]);
