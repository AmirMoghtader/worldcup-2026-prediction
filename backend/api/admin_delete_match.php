<?php
/** @var PDO $pdo */
$data = hmn_read_json();
$id = (int)($data['id'] ?? 0);
if (!$id) { hmn_json_response(['success' => false, 'error' => 'id required']); }
$tm = hmn_table('matches');
$tb = hmn_table('bets');
$tp = hmn_table('predictions');
$pdo->prepare("DELETE FROM {$tp} WHERE match_id = :id")->execute([':id' => $id]);
$pdo->prepare("DELETE FROM {$tb} WHERE match_id = :id")->execute([':id' => $id]);
$pdo->prepare("DELETE FROM {$tm} WHERE id = :id")->execute([':id' => $id]);
hmn_json_response(['success' => true]);
