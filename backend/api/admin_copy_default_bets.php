<?php
/** @var PDO $pdo */
$data = hmn_read_json();
$match_id = (int)($data['match_id'] ?? 0);
if (!empty($data['all_matches'])) {
    $result = wc_sync_default_bets_to_all_matches($pdo);
    hmn_json_response(['success' => true, 'all_matches' => true] + $result);
}
if (!$match_id) { hmn_json_response(['success' => false, 'error' => 'match_id required']); }

$result = wc_sync_default_bets_to_match($pdo, $match_id);
hmn_json_response(['success' => true, 'match_id' => $match_id] + $result);
