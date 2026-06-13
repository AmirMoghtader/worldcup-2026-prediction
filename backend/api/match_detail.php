<?php
/** @var PDO $pdo */
$id = (int)($_GET['id'] ?? 0);
if (!$id) { http_response_code(400); hmn_json_response(['success' => false, 'error' => 'id required']); }

wc_maybe_sync_scores($pdo);
$tm = hmn_table('matches');
$tb = hmn_table('bets');
$tp = hmn_table('predictions');
$settings = wc_get_settings($pdo);
$lockMin = (int)($settings['prediction_lock_minutes'] ?? 10);
$windowHours = (int)($settings['prediction_window_hours'] ?? 48);

$st = $pdo->prepare("SELECT * FROM {$tm} WHERE id = :id LIMIT 1");
$st->execute([':id' => $id]);
$match = $st->fetch(PDO::FETCH_ASSOC);
if (!$match) { http_response_code(404); hmn_json_response(['success' => false, 'error' => 'match not found']); }

$match['is_prediction_open'] = wc_is_prediction_open($match, $lockMin, $windowHours) ? 1 : 0;
$match['lock_minutes'] = $lockMin;
$match['prediction_window_hours'] = $windowHours;
$match['result_data'] = json_decode((string)($match['result_data_json'] ?? '{}'), true) ?: [];
$match = wc_match_row_for_display($match);

// Bets
$bets = $pdo->prepare("SELECT * FROM {$tb} WHERE match_id = :id AND is_active = 1 ORDER BY display_order ASC, id ASC");
$bets->execute([':id' => $id]);
$bets = $bets->fetchAll(PDO::FETCH_ASSOC);
foreach ($bets as &$b) {
    $b['options'] = json_decode($b['options_json'] ?? '[]', true) ?? [];
}
unset($b);

// User predictions for this match
$userPreds = [];
$uid = wc_current_user_id();
if ($uid) {
    $st2 = $pdo->prepare("SELECT bet_id, selected_option, is_correct, points_earned FROM {$tp} WHERE user_id = :uid AND match_id = :mid");
    $st2->execute([':uid' => $uid, ':mid' => $id]);
    foreach ($st2->fetchAll(PDO::FETCH_ASSOC) as $p) {
        $userPreds[(int)$p['bet_id']] = $p;
    }
}

hmn_json_response([
    'success' => true,
    'match'   => $match,
    'bets'    => $bets,
    'user_predictions' => $userPreds,
    'settings' => $settings,
]);
