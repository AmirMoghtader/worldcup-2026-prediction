<?php
/** @var PDO $pdo */
$data = hmn_read_json();
$id = (int)($data['match_id'] ?? $data['id'] ?? 0);
if ($id <= 0) {
    hmn_json_response(['success' => false, 'error' => 'بازی VIP نامعتبر است.']);
}

$vipMatchTable = hmn_table('vip_matches');
$matchTable = hmn_table('matches');
$matchSt = $pdo->prepare("SELECT * FROM {$vipMatchTable} WHERE id = :id LIMIT 1");
$matchSt->execute([':id' => $id]);
$vipMatch = $matchSt->fetch(PDO::FETCH_ASSOC);
if (!$vipMatch) {
    hmn_json_response(['success' => false, 'error' => 'بازی VIP پیدا نشد.']);
}

$score1 = (!isset($data['score_team1']) || $data['score_team1'] === '') ? null : max(0, (int)$data['score_team1']);
$score2 = (!isset($data['score_team2']) || $data['score_team2'] === '') ? null : max(0, (int)$data['score_team2']);
$resultOption = trim((string)($data['result_option'] ?? ''));

if (($score1 === null || $score2 === null || $resultOption === '') && !empty($vipMatch['source_match_id'])) {
    $srcSt = $pdo->prepare("SELECT score_team1, score_team2, status FROM {$matchTable} WHERE id = :id LIMIT 1");
    $srcSt->execute([':id' => $vipMatch['source_match_id']]);
    $source = $srcSt->fetch(PDO::FETCH_ASSOC) ?: null;
    if ($source) {
        if ($score1 === null) {
            $score1 = $source['score_team1'] !== null ? (int)$source['score_team1'] : null;
        }
        if ($score2 === null) {
            $score2 = $source['score_team2'] !== null ? (int)$source['score_team2'] : null;
        }
        if ($resultOption === '') {
            $resultOption = wc_vip_match_result_option($score1, $score2);
        }
    }
}

if ($resultOption === '') {
    $resultOption = wc_vip_match_result_option($score1, $score2);
}
if (!in_array($resultOption, ['team1', 'draw', 'team2'], true)) {
    hmn_json_response(['success' => false, 'error' => 'نتیجه نهایی بازی VIP را مشخص کنید.']);
}

try {
    $pdo->beginTransaction();
    $pdo->prepare(
        "UPDATE {$vipMatchTable}
         SET score_team1 = :score_team1,
             score_team2 = :score_team2,
             result_option = :result_option,
             status = 'finished'
         WHERE id = :id"
    )->execute([
        ':score_team1' => $score1,
        ':score_team2' => $score2,
        ':result_option' => $resultOption,
        ':id' => $id,
    ]);
    $result = wc_settle_vip_match($pdo, $id);
    $pdo->commit();
    hmn_json_response(['success' => true] + $result);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    hmn_json_response(['success' => false, 'error' => $e->getMessage()]);
}
