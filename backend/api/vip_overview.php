<?php
/** @var PDO $pdo */
$userId = wc_current_user_id();
$userTable = hmn_table('users');
$memberTable = hmn_table('vip_members');
$matchTable = hmn_table('vip_matches');
$betTable = hmn_table('vip_bets');
$settingsTable = hmn_table('settings');

$userSt = $pdo->prepare("SELECT id, phone, name, total_points, redeemed_points, created_at FROM {$userTable} WHERE id = :id LIMIT 1");
$userSt->execute([':id' => $userId]);
$user = $userSt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    hmn_json_response(['success' => false, 'error' => 'کاربر پیدا نشد.']);
}
$user = wc_attach_vip_to_user($pdo, $user);
$vip = $user['vip'] ?? null;
if (!$vip) {
    http_response_code(403);
    hmn_json_response(['success' => false, 'error' => 'این بخش فقط برای کاربران VIP فعال است.']);
}

$matches = $pdo->query(
    "SELECT * FROM {$matchTable}
     WHERE is_active = 1
     ORDER BY match_datetime ASC, id DESC"
)->fetchAll(PDO::FETCH_ASSOC);

$poolRows = $pdo->query(
    "SELECT vip_match_id, outcome, COALESCE(SUM(amount), 0) AS total_amount, COUNT(*) AS bets_count
     FROM {$betTable}
     GROUP BY vip_match_id, outcome"
)->fetchAll(PDO::FETCH_ASSOC);
$poolsByMatch = [];
foreach ($poolRows as $row) {
    $mid = (int)$row['vip_match_id'];
    if (!isset($poolsByMatch[$mid])) {
        $poolsByMatch[$mid] = [
            'team1' => 0,
            'draw' => 0,
            'team2' => 0,
        ];
    }
    $poolsByMatch[$mid][$row['outcome']] = (int)$row['total_amount'];
}

$userBetSt = $pdo->prepare("SELECT * FROM {$betTable} WHERE user_id = :user_id ORDER BY created_at DESC");
$userBetSt->execute([':user_id' => $userId]);
$userBetRows = $userBetSt->fetchAll(PDO::FETCH_ASSOC);
$userBetsByMatch = [];
foreach ($userBetRows as $row) {
    $userBetsByMatch[(int)$row['vip_match_id']] = $row;
}

foreach ($matches as &$match) {
    $match = wc_vip_match_row_for_display($match);
    $pool = $poolsByMatch[(int)$match['id']] ?? ['team1' => 0, 'draw' => 0, 'team2' => 0];
    $totalPool = (int)$pool['team1'] + (int)$pool['draw'] + (int)$pool['team2'];
    $netPool = (int)floor($totalPool * 0.9);
    $match['pool_total'] = $totalPool;
    $match['net_pool_total'] = $netPool;
    $match['pools'] = $pool;
    $match['option_labels'] = [
        'team1' => 'برد ' . $match['team1'],
        'draw' => 'مساوی',
        'team2' => 'برد ' . $match['team2'],
    ];
    $match['estimates'] = [];
    foreach (['team1', 'draw', 'team2'] as $option) {
        $optionPool = (int)($pool[$option] ?? 0);
        $multiplier = $optionPool > 0 ? round(max(1, $netPool / $optionPool), 2) : 0;
        $match['estimates'][$option] = [
            'share_percent' => $totalPool > 0 ? round(($optionPool / $totalPool) * 100, 1) : 0,
            'multiplier' => $multiplier,
            'ratio_label' => $multiplier > 0 ? ($multiplier . ' به 1') : '—',
        ];
    }
    $match['is_betting_open'] = ($match['status'] ?? 'upcoming') === 'upcoming'
        && empty($match['settled_at'])
        && !empty($match['match_timestamp'])
        && (int)$match['match_timestamp'] > time();
    if (isset($userBetsByMatch[(int)$match['id']])) {
        $bet = $userBetsByMatch[(int)$match['id']];
        $match['user_bet'] = [
            'id' => (int)$bet['id'],
            'outcome' => $bet['outcome'],
            'amount' => (int)$bet['amount'],
            'payout_amount' => (int)$bet['payout_amount'],
            'jackpot_payout' => (int)($bet['jackpot_payout'] ?? 0),
            'exact_score_team1' => $bet['exact_score_team1'] !== null ? (int)$bet['exact_score_team1'] : null,
            'exact_score_team2' => $bet['exact_score_team2'] !== null ? (int)$bet['exact_score_team2'] : null,
            'exact_score_hit' => (int)($bet['exact_score_hit'] ?? 0),
            'result_status' => $bet['result_status'],
            'created_at' => $bet['created_at'],
        ];
    } else {
        $match['user_bet'] = null;
    }
}
unset($match);

hmn_json_response([
    'success' => true,
    'user' => $user,
    'vip' => $vip,
    'vip_bank_balance' => (int)($pdo->query("SELECT vip_bank_balance FROM {$settingsTable} WHERE id = 1 LIMIT 1")->fetchColumn() ?: 0),
    'matches' => $matches,
    'user_bets' => $userBetRows,
]);
