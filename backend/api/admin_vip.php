<?php
/** @var PDO $pdo */
$memberTable = hmn_table('vip_members');
$matchTable = hmn_table('vip_matches');
$betTable = hmn_table('vip_bets');
$userTable = hmn_table('users');
$settingsTable = hmn_table('settings');

$members = $pdo->query(
    "SELECT vm.*, u.name AS user_name, u.total_points, u.redeemed_points
     FROM {$memberTable} vm
     LEFT JOIN {$userTable} u ON u.id = vm.user_id
     ORDER BY vm.is_active DESC, vm.created_at DESC, vm.id DESC"
)->fetchAll(PDO::FETCH_ASSOC);

$matches = $pdo->query(
    "SELECT m.*,
            (SELECT COUNT(*) FROM {$betTable} b WHERE b.vip_match_id = m.id) AS bets_count,
            (SELECT COALESCE(SUM(amount), 0) FROM {$betTable} b WHERE b.vip_match_id = m.id) AS pool_total,
            (SELECT COALESCE(SUM(amount), 0) FROM {$betTable} b WHERE b.vip_match_id = m.id AND b.outcome = 'team1') AS pool_team1,
            (SELECT COALESCE(SUM(amount), 0) FROM {$betTable} b WHERE b.vip_match_id = m.id AND b.outcome = 'draw') AS pool_draw,
            (SELECT COALESCE(SUM(amount), 0) FROM {$betTable} b WHERE b.vip_match_id = m.id AND b.outcome = 'team2') AS pool_team2
     FROM {$matchTable} m
     ORDER BY m.match_datetime ASC, m.id DESC"
)->fetchAll(PDO::FETCH_ASSOC);

foreach ($matches as &$match) {
    $match = wc_vip_match_row_for_display($match);
}
unset($match);

$recentBets = $pdo->query(
    "SELECT b.*, vm.team1, vm.team2, vm.match_datetime, u.name AS user_name, u.phone AS user_phone
     FROM {$betTable} b
     JOIN {$matchTable} vm ON vm.id = b.vip_match_id
     LEFT JOIN {$userTable} u ON u.id = b.user_id
     ORDER BY b.created_at DESC, b.id DESC
     LIMIT 30"
)->fetchAll(PDO::FETCH_ASSOC);

hmn_json_response([
    'success' => true,
    'vip_bank_balance' => (int)($pdo->query("SELECT vip_bank_balance FROM {$settingsTable} WHERE id = 1 LIMIT 1")->fetchColumn() ?: 0),
    'members' => $members,
    'matches' => $matches,
    'recent_bets' => $recentBets,
]);
