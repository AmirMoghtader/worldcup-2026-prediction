<?php
/** @var PDO $pdo */
$userId = wc_current_user_id();
$tu = hmn_table('users');
$tm = hmn_table('matches');
$tb = hmn_table('bets');
$tp = hmn_table('predictions');

$user = $pdo->prepare("SELECT id,phone,name,total_points,redeemed_points,created_at FROM {$tu} WHERE id=:id");
$user->execute([':id' => $userId]);
$u = $user->fetch(PDO::FETCH_ASSOC);
if (!$u) { hmn_json_response(['success' => false, 'error' => 'کاربر یافت نشد']); }
$u['available_points'] = wc_get_available_points($u);
$u = wc_attach_vip_to_user($pdo, $u);

// Stats
$stats = $pdo->prepare(
    "SELECT COUNT(*) AS total_predictions,
            SUM(CASE WHEN is_correct=1 THEN 1 ELSE 0 END) AS correct_predictions,
            SUM(points_earned) AS total_points_earned
     FROM {$tp} WHERE user_id=:id"
);
$stats->execute([':id' => $userId]);
$st = $stats->fetch(PDO::FETCH_ASSOC);

// Predictions grouped by match
$preds = $pdo->prepare(
    "SELECT p.id AS pred_id, p.bet_id, p.selected_option, p.is_correct, p.points_earned,
            b.label AS bet_label, b.correct_option,
            m.id AS match_id, m.team1, m.team2, m.team1_flag, m.team2_flag,
            m.match_datetime, m.status, m.score_team1, m.score_team2
     FROM {$tp} p
     JOIN {$tb} b ON b.id = p.bet_id
     JOIN {$tm} m ON m.id = p.match_id
     WHERE p.user_id = :uid
     ORDER BY m.match_datetime DESC, b.id ASC"
);
$preds->execute([':uid' => $userId]);
$allPreds = $preds->fetchAll(PDO::FETCH_ASSOC);

// Group by match
$byMatch = [];
foreach ($allPreds as $row) {
    $mid = $row['match_id'];
    if (!isset($byMatch[$mid])) {
        $byMatch[$mid] = [
            'id' => $mid,
            'team1' => $row['team1'],
            'team2' => $row['team2'],
            'team1_flag' => $row['team1_flag'],
            'team2_flag' => $row['team2_flag'],
            'match_datetime' => $row['match_datetime'],
            'status' => $row['status'],
            'score_team1' => $row['score_team1'],
            'score_team2' => $row['score_team2'],
            'predictions' => [],
        ];
        $byMatch[$mid] = wc_match_row_for_display($byMatch[$mid]);
    }
    $byMatch[$mid]['predictions'][] = [
        'bet_id' => $row['bet_id'],
        'bet_label' => $row['bet_label'],
        'selected_option' => $row['selected_option'],
        'correct_option' => $row['correct_option'],
        'is_correct' => $row['is_correct'],
        'points_earned' => $row['points_earned'],
    ];
}

hmn_json_response([
    'success' => true,
    'user' => $u,
    'vip' => $u['vip'] ?? null,
    'stats' => $st,
    'matches' => array_values($byMatch),
]);
