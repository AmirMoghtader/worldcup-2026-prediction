<?php
/** @var PDO $pdo */
$data = hmn_read_json();
$id = (int)($data['id'] ?? 0);
if (!$id) { hmn_json_response(['success' => false, 'error' => 'id required']); }
$betTable = hmn_table('bets');
$pdo->prepare("UPDATE {$betTable} SET is_active = 0, sync_with_default = 0 WHERE default_bet_id = :id")->execute([':id' => $id]);
$pdo->prepare("DELETE FROM " . hmn_table('default_bets') . " WHERE id = :id")->execute([':id' => $id]);
hmn_json_response(['success' => true]);
