<?php
/** @var PDO $pdo */
$data = hmn_read_json();
$id = (int)($data['id'] ?? 0);
if (!$id) { hmn_json_response(['success' => false, 'error' => 'id required']); }
$tb = hmn_table('bets');
$tp = hmn_table('predictions');
$pdo->prepare("DELETE FROM {$tp} WHERE bet_id = :id")->execute([':id' => $id]);
$pdo->prepare("DELETE FROM {$tb} WHERE id = :id")->execute([':id' => $id]);
hmn_json_response(['success' => true]);
