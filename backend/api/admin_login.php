<?php
/** @var PDO $pdo */
require_once __DIR__ . '/../login_lockout.php';
$data = hmn_read_json();
$phone    = trim((string)($data['phone'] ?? ''));
$password = (string)($data['password'] ?? '');

if ($phone === '' || $password === '') {
    http_response_code(400);
    hmn_json_response(['success' => false, 'error' => 'شماره و رمز عبور وارد کنید.']);
}

$cleaned = hmn_clean_phone($phone);
if (!preg_match('/^\d{10,15}$/', $cleaned)) {
    hmn_json_response(['success' => false, 'error' => 'شماره موبایل نامعتبر است.']);
}

if (!hmn_rate_limit('admin_login|' . $cleaned, 5, 300)) {
    http_response_code(429);
    hmn_json_response(['success' => false, 'error' => 'تعداد تلاش زیاد است.']);
}

hmn_login_lock_guard($pdo, $cleaned);

$ta = hmn_table('admins');
$variants = hmn_phone_variants($phone);
$placeholders = implode(',', array_fill(0, count($variants), '?'));
$st = $pdo->prepare("SELECT id, name, password_hash, phone FROM {$ta} WHERE phone IN ({$placeholders}) LIMIT 1");
$st->execute($variants);
$admin = $st->fetch(PDO::FETCH_ASSOC);

if (!$admin || !password_verify($password, (string)$admin['password_hash'])) {
    $lock = hmn_login_register_failure($pdo, $cleaned);
    http_response_code(401);
    hmn_json_response(['success' => false, 'error' => 'شماره یا رمز عبور نادرست است.']);
}

hmn_login_register_success($pdo, $cleaned);
session_regenerate_id(true);
$_SESSION['wc_role']     = 'admin';
$_SESSION['wc_admin_id'] = (int)$admin['id'];
$_SESSION['wc_phone']    = $cleaned;
$_SESSION['started_at']  = time();

hmn_json_response(['success' => true, 'admin' => ['id' => $admin['id'], 'name' => $admin['name']]]);
