<?php
/** @var PDO $pdo */
$role = wc_current_role();

if ($role === 'admin') {
    $adminId = (int)($_SESSION['wc_admin_id'] ?? 0);
    $ta = hmn_table('admins');
    $st = $pdo->prepare("SELECT id, phone, name FROM {$ta} WHERE id = :id LIMIT 1");
    $st->execute([':id' => $adminId]);
    $a = $st->fetch(PDO::FETCH_ASSOC);
    hmn_json_response(['success' => true, 'user' => $a ? array_merge($a, ['role' => 'admin']) : null, 'role' => 'admin']);
}

$uid = wc_current_user_id();
if (!$uid) {
    hmn_json_response(['success' => true, 'user' => null, 'role' => null]);
}
$t = hmn_table('users');
$st = $pdo->prepare("SELECT id, phone, name, total_points, redeemed_points FROM {$t} WHERE id = :id LIMIT 1");
$st->execute([':id' => $uid]);
$u = $st->fetch(PDO::FETCH_ASSOC);
if ($u) {
    $u['available_points'] = wc_get_available_points($u);
    $u = wc_attach_vip_to_user($pdo, $u);
}
hmn_json_response(['success' => true, 'user' => $u ? array_merge($u, ['role' => 'user']) : null, 'role' => 'user']);
