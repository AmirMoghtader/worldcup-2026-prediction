<?php
/** @var PDO $pdo */

$data = hmn_read_json();
$matchId = (int)($data['match_id'] ?? 0);
$score1 = isset($data['score_team1']) ? (int)$data['score_team1'] : null;
$score2 = isset($data['score_team2']) ? (int)$data['score_team2'] : null;
$manualBetResults = (array)($data['bet_results'] ?? []);
$resultData = wc_prepare_result_data((array)($data['result_data'] ?? []), $score1, $score2);

if (!$matchId) {
    hmn_json_response(['success' => false, 'error' => 'match_id required']);
}
if ($score1 === null || $score2 === null) {
    hmn_json_response(['success' => false, 'error' => 'نتیجه بازی را کامل وارد کنید.']);
}

$matchTable = hmn_table('matches');

$matchSt = $pdo->prepare("SELECT * FROM {$matchTable} WHERE id = :id LIMIT 1");
$matchSt->execute([':id' => $matchId]);
$match = $matchSt->fetch(PDO::FETCH_ASSOC);
if (!$match) {
    hmn_json_response(['success' => false, 'error' => 'بازی یافت نشد.']);
}
$result = wc_apply_match_result($pdo, $matchId, $score1, $score2, $resultData, $manualBetResults);
hmn_json_response($result);
