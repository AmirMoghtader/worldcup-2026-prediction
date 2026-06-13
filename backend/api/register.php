<?php
/** @var PDO $pdo */
$data = hmn_read_json();
$phone = trim((string)($data['phone'] ?? ''));
$name  = trim((string)($data['name']  ?? ''));

if ($phone === '') {
    http_response_code(400);
    hmn_json_response(['success' => false, 'error' => 'شماره موبایل وارد نشده است.']);
}

$cleaned = hmn_clean_phone($phone);
if (!preg_match('/^\d{10,15}$/', $cleaned)) {
    hmn_json_response(['success' => false, 'error' => 'شماره موبایل نامعتبر است.']);
}

if (!hmn_rate_limit('reg|' . $cleaned, 10, 600)) {
    http_response_code(429);
    hmn_json_response(['success' => false, 'error' => 'تعداد درخواست زیاد است. چند دقیقه بعد تلاش کنید.']);
}

$t = hmn_table('users');

// Find existing user
$variants = hmn_phone_variants($phone);
$placeholders = implode(',', array_fill(0, count($variants), '?'));
$st = $pdo->prepare("SELECT id, phone, name, total_points FROM {$t} WHERE phone IN ({$placeholders}) LIMIT 1");
$st->execute($variants);
$user = $st->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    // New user — name required
    if ($name === '') {
        hmn_json_response(['success' => false, 'error' => 'نام را وارد کنید.', 'need_name' => true]);
    }
    $pdo->prepare("INSERT INTO {$t} (phone, name) VALUES (:p, :n)")
        ->execute([':p' => $cleaned, ':n' => $name]);
    $userId = (int)$pdo->lastInsertId();
    $userName = $name;
} else {
    $userId = (int)$user['id'];
    $userName = $user['name'];
}

session_regenerate_id(true);
$_SESSION['wc_user_id'] = $userId;
$_SESSION['wc_role']    = 'user';
$_SESSION['wc_phone']   = $cleaned;
$_SESSION['started_at'] = time();

hmn_json_response([
    'success' => true,
    'user'    => ['id' => $userId, 'name' => $userName, 'phone' => $cleaned],
]);
