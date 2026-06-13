<?php
/** @var PDO $pdo */
$t = hmn_table('matches');
wc_maybe_sync_scores($pdo);
$settings = wc_get_settings($pdo);
$lockMin = (int)($settings['prediction_lock_minutes'] ?? 10);
$windowHours = (int)($settings['prediction_window_hours'] ?? 48);

$uid = wc_current_user_id();
$rows = $pdo->query("SELECT * FROM {$t} ORDER BY match_datetime ASC")->fetchAll(PDO::FETCH_ASSOC);

$matches = [];
foreach ($rows as $row) {
    $row['is_prediction_open'] = wc_is_prediction_open($row, $lockMin, $windowHours) ? 1 : 0;
    $matches[] = wc_match_row_for_display($row);
}

// If user logged in, add prediction count per match
if ($uid) {
    $tp = hmn_table('predictions');
    $st = $pdo->prepare("SELECT match_id, COUNT(*) as cnt FROM {$tp} WHERE user_id = :uid GROUP BY match_id");
    $st->execute([':uid' => $uid]);
    $preds = [];
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $preds[(int)$r['match_id']] = (int)$r['cnt'];
    }
    foreach ($matches as &$m) {
        $m['user_predictions'] = $preds[(int)$m['id']] ?? 0;
    }
    unset($m);
}

hmn_json_response(['success' => true, 'matches' => $matches, 'settings' => $settings]);
