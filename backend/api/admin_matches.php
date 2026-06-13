<?php
/** @var PDO $pdo */
$tm = hmn_table('matches');
$tp = hmn_table('predictions');
wc_maybe_sync_scores($pdo);
$settings = wc_get_settings($pdo);
$lockMin = (int)($settings['prediction_lock_minutes'] ?? 10);
$windowHours = (int)($settings['prediction_window_hours'] ?? 48);

$rows = $pdo->query("SELECT m.*, (SELECT COUNT(DISTINCT user_id) FROM {$tp} WHERE match_id = m.id) AS pred_count FROM {$tm} m ORDER BY m.match_datetime ASC")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as &$r) {
    $r['is_prediction_open'] = wc_is_prediction_open($r, $lockMin, $windowHours) ? 1 : 0;
    $r = wc_match_row_for_display($r);
}
unset($r);
hmn_json_response(['success' => true, 'matches' => $rows]);
