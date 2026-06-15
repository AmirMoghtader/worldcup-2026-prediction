<?php
/** @var PDO $pdo */
$data = hmn_read_json();
$memberTable = hmn_table('vip_members');
$userTable = hmn_table('users');

$id = (int)($data['id'] ?? 0);
$phone = trim((string)($data['phone'] ?? ''));
$cleaned = hmn_clean_phone($phone);
if (!preg_match('/^\d{10,15}$/', $cleaned)) {
    hmn_json_response(['success' => false, 'error' => 'شماره موبایل VIP معتبر نیست.']);
}

$currentBalanceRaw = trim((string)($data['current_balance'] ?? ''));
$isActive = !array_key_exists('is_active', $data) ? 1 : (!empty($data['is_active']) ? 1 : 0);
$variants = hmn_phone_variants($cleaned);
$placeholders = implode(',', array_fill(0, count($variants), '?'));

$userSt = $pdo->prepare("SELECT id FROM {$userTable} WHERE phone IN ({$placeholders}) LIMIT 1");
$userSt->execute($variants);
$userId = (int)($userSt->fetchColumn() ?: 0);

$member = null;
if ($id > 0) {
    $st = $pdo->prepare("SELECT * FROM {$memberTable} WHERE id = :id LIMIT 1");
    $st->execute([':id' => $id]);
    $member = $st->fetch(PDO::FETCH_ASSOC) ?: null;
}
if (!$member) {
    $st = $pdo->prepare("SELECT * FROM {$memberTable} WHERE phone IN ({$placeholders}) LIMIT 1");
    $st->execute($variants);
    $member = $st->fetch(PDO::FETCH_ASSOC) ?: null;
}

if ($member) {
    $payload = [
        ':id' => $member['id'],
        ':phone' => $cleaned,
        ':user_id' => $userId > 0 ? $userId : null,
        ':is_active' => $isActive,
    ];
    $sql = "UPDATE {$memberTable} SET phone = :phone, user_id = :user_id, is_active = :is_active";
    if ($currentBalanceRaw !== '') {
        $payload[':current_balance'] = max(0, (int)$currentBalanceRaw);
        $sql .= ", current_balance = :current_balance";
    }
    $sql .= " WHERE id = :id";
    $pdo->prepare($sql)->execute($payload);
    hmn_json_response(['success' => true, 'id' => (int)$member['id']]);
}

$balance = $currentBalanceRaw !== '' ? max(0, (int)$currentBalanceRaw) : wc_vip_default_credit();
$pdo->prepare(
    "INSERT INTO {$memberTable} (phone, user_id, current_balance, initial_balance, is_active)
     VALUES (:phone, :user_id, :current_balance, :initial_balance, :is_active)"
)->execute([
    ':phone' => $cleaned,
    ':user_id' => $userId > 0 ? $userId : null,
    ':current_balance' => $balance,
    ':initial_balance' => $balance,
    ':is_active' => $isActive,
]);

hmn_json_response(['success' => true, 'id' => (int)$pdo->lastInsertId()]);
