<?php
/** @var PDO $pdo */

$data = hmn_read_json();
$phone = trim((string)($data['phone'] ?? ''));

if ($phone === '') {
    http_response_code(400);
    hmn_json_response(['success' => false, 'error' => 'شماره موبایل وارد نشده است.']);
}

$cleaned = hmn_clean_phone($phone);
if (!preg_match('/^\d{10,15}$/', $cleaned)) {
    hmn_json_response(['success' => false, 'error' => 'شماره موبایل نامعتبر است.']);
}

$variants = hmn_phone_variants($phone);
$placeholders = implode(',', array_fill(0, count($variants), '?'));

$adminTable = hmn_table('admins');
$adminSt = $pdo->prepare("SELECT id, name, phone FROM {$adminTable} WHERE phone IN ({$placeholders}) LIMIT 1");
$adminSt->execute($variants);
$admin = $adminSt->fetch(PDO::FETCH_ASSOC);

$userTable = hmn_table('users');
$userSt = $pdo->prepare("SELECT id, name, phone FROM {$userTable} WHERE phone IN ({$placeholders}) LIMIT 1");
$userSt->execute($variants);
$user = $userSt->fetch(PDO::FETCH_ASSOC);

hmn_json_response([
    'success' => true,
    'phone' => $cleaned,
    'is_admin' => $admin ? true : false,
    'admin_name' => $admin['name'] ?? '',
    'has_user' => $user ? true : false,
    'user_name' => $user['name'] ?? '',
]);
