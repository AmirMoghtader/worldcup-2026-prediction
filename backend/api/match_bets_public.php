<?php
/** @var PDO $pdo */
$match_id = (int)($_GET['match_id'] ?? $_GET['id'] ?? 0);
if (!$match_id) {
    hmn_json_response(['success' => false, 'error' => 'match_id required']);
}
$tb = hmn_table('bets');
$bets = $pdo->prepare("SELECT * FROM {$tb} WHERE match_id = :mid AND is_active = 1 ORDER BY display_order ASC, id ASC");
$bets->execute([':mid' => $match_id]);
$rows = $bets->fetchAll(PDO::FETCH_ASSOC);
hmn_json_response(['success' => true, 'bets' => $rows]);
