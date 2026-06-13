<?php
/** @var PDO $pdo */
$uid = wc_current_user_id();
$tp = hmn_table('predictions');
$tb = hmn_table('bets');
$tm = hmn_table('matches');

$matchId = (int)($_GET['match_id'] ?? 0);
$whereExtra = $matchId ? ' AND p.match_id = :mid' : '';
$st = $pdo->prepare(
    "SELECT p.*, b.label AS bet_label, b.points AS bet_points,
            m.team1, m.team2, m.team1_flag, m.team2_flag, m.match_datetime, m.status,
            m.score_team1, m.score_team2
     FROM {$tp} p
     JOIN {$tb} b ON b.id = p.bet_id
     JOIN {$tm} m ON m.id = p.match_id
     WHERE p.user_id = :uid{$whereExtra}
     ORDER BY p.submitted_at DESC"
);
$params = [':uid' => $uid];
if ($matchId) $params[':mid'] = $matchId;
$st->execute($params);
$rows = $st->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as &$row) {
    $row = wc_match_row_for_display($row);
}
unset($row);

$tu = hmn_table('users');
$u  = $pdo->prepare("SELECT total_points FROM {$tu} WHERE id = :id LIMIT 1");
$u->execute([':id' => $uid]);
$uRow = $u->fetch(PDO::FETCH_ASSOC);

hmn_json_response([
    'success'      => true,
    'predictions'  => $rows,
    'total_points' => (int)($uRow['total_points'] ?? 0),
]);
