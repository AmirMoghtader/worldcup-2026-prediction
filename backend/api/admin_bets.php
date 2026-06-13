<?php
/** @var PDO $pdo */
$match_id = (int)($_GET['match_id'] ?? 0);
if (!$match_id) { hmn_json_response(['success' => false, 'error' => 'match_id required']); }
$tb = hmn_table('bets');
$st = $pdo->prepare("SELECT * FROM {$tb} WHERE match_id = :mid ORDER BY display_order ASC, id ASC");
$st->execute([':mid' => $match_id]);
$bets = $st->fetchAll(PDO::FETCH_ASSOC);
foreach ($bets as &$b) { $b['options'] = json_decode($b['options_json'] ?? '[]', true) ?? []; }
unset($b);
hmn_json_response(['success' => true, 'bets' => $bets]);
