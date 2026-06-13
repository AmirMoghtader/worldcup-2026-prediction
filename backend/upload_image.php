<?php
declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=utf-8');

if (($_SESSION['wc_role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'دسترسی ندارید.'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (empty($_FILES['image']['tmp_name']) || !is_uploaded_file($_FILES['image']['tmp_name'])) {
    echo json_encode(['success' => false, 'error' => 'فایل تصویر دریافت نشد.'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$folder = preg_replace('/[^a-z0-9_\-]/i', '', (string)($_POST['folder'] ?? 'general')) ?: 'general';
$file = $_FILES['image'];
$ext = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
$allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
if (!in_array($ext, $allowed, true)) {
    echo json_encode(['success' => false, 'error' => 'فرمت فایل مجاز نیست.'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$uploadDir = dirname(__DIR__) . '/uploads/' . $folder;
if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
    echo json_encode(['success' => false, 'error' => 'ساخت پوشه آپلود انجام نشد.'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$base = $folder . '-' . date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
$target = $uploadDir . '/' . $base;
if (!move_uploaded_file($file['tmp_name'], $target)) {
    echo json_encode(['success' => false, 'error' => 'آپلود فایل انجام نشد.'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$url = '/uploads/' . $folder . '/' . $base;
echo json_encode(['success' => true, 'url' => $url], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
