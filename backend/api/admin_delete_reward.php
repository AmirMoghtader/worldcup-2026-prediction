<?php
/** @var PDO $pdo */

$data = hmn_read_json();
$id = (int)($data['id'] ?? 0);
if ($id <= 0) {
    hmn_json_response(['success' => false, 'error' => 'شناسه جایزه نامعتبر است.']);
}

$table = hmn_table('rewards');
$redemptionTable = hmn_table('reward_redemptions');
$stmt = $pdo->prepare("SELECT COUNT(*) FROM {$redemptionTable} WHERE reward_id = :id");
$stmt->execute([':id' => $id]);
$usedCount = (int)$stmt->fetchColumn();
if ($usedCount > 0) {
    $pdo->prepare("UPDATE {$table} SET is_active = 0 WHERE id = :id")->execute([':id' => $id]);
    hmn_json_response(['success' => true, 'message' => 'این جایزه قبلاً دریافت شده بود و فقط غیرفعال شد.']);
}
$pdo->prepare("DELETE FROM {$table} WHERE id = :id")->execute([':id' => $id]);
hmn_json_response(['success' => true]);
