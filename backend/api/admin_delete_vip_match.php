<?php
/** @var PDO $pdo */
$data = hmn_read_json();
$id = (int)($data['id'] ?? 0);
if ($id <= 0) {
    hmn_json_response(['success' => false, 'error' => 'بازی VIP نامعتبر است.']);
}

$matchTable = hmn_table('vip_matches');
$betTable = hmn_table('vip_bets');
$st = $pdo->prepare("SELECT COUNT(*) FROM {$betTable} WHERE vip_match_id = :id");
$st->execute([':id' => $id]);
if ((int)$st->fetchColumn() > 0) {
    hmn_json_response(['success' => false, 'error' => 'برای این بازی شرط ثبت شده است؛ به‌جای حذف آن را غیرفعال کنید.']);
}

$pdo->prepare("DELETE FROM {$matchTable} WHERE id = :id")->execute([':id' => $id]);
hmn_json_response(['success' => true]);
