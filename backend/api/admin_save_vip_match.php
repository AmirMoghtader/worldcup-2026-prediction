<?php
/** @var PDO $pdo */
$data = hmn_read_json();
$vipMatchTable = hmn_table('vip_matches');
$matchTable = hmn_table('matches');

$id = (int)($data['id'] ?? 0);
$sourceMatchId = (int)($data['source_match_id'] ?? 0);
$team1 = trim((string)($data['team1'] ?? ''));
$team2 = trim((string)($data['team2'] ?? ''));
$team1Flag = trim((string)($data['team1_flag'] ?? ''));
$team2Flag = trim((string)($data['team2_flag'] ?? ''));
$groupName = trim((string)($data['group_name'] ?? ''));
$stage = trim((string)($data['stage'] ?? 'vip')) ?: 'vip';
$matchDatetimeInput = trim((string)($data['match_datetime'] ?? ''));
$venue = trim((string)($data['venue'] ?? ''));
$status = trim((string)($data['status'] ?? 'upcoming'));
$isActive = !array_key_exists('is_active', $data) ? 1 : (!empty($data['is_active']) ? 1 : 0);
$score1 = (!isset($data['score_team1']) || $data['score_team1'] === '') ? null : max(0, (int)$data['score_team1']);
$score2 = (!isset($data['score_team2']) || $data['score_team2'] === '') ? null : max(0, (int)$data['score_team2']);
$resultOption = trim((string)($data['result_option'] ?? ''));

if ($sourceMatchId > 0) {
    $srcSt = $pdo->prepare("SELECT * FROM {$matchTable} WHERE id = :id LIMIT 1");
    $srcSt->execute([':id' => $sourceMatchId]);
    $source = $srcSt->fetch(PDO::FETCH_ASSOC);
    if ($source) {
        $team1 = $team1 !== '' ? $team1 : (string)$source['team1'];
        $team2 = $team2 !== '' ? $team2 : (string)$source['team2'];
        $team1Flag = $team1Flag !== '' ? $team1Flag : (string)($source['team1_flag'] ?? '');
        $team2Flag = $team2Flag !== '' ? $team2Flag : (string)($source['team2_flag'] ?? '');
        $groupName = $groupName !== '' ? $groupName : (string)($source['group_name'] ?? '');
        $stage = $stage !== 'vip' ? $stage : (string)($source['stage'] ?? 'group');
        $matchDatetimeInput = $matchDatetimeInput !== ''
            ? $matchDatetimeInput
            : str_replace(' ', 'T', wc_match_display_datetime((string)$source['match_datetime']));
        $venue = $venue !== '' ? $venue : (string)($source['venue'] ?? '');
    }
}

if ($team1 === '' || $team2 === '') {
    hmn_json_response(['success' => false, 'error' => 'هر دو تیم بازی VIP باید مشخص باشند.']);
}
if ($matchDatetimeInput === '') {
    hmn_json_response(['success' => false, 'error' => 'زمان بازی VIP را وارد کنید.']);
}
if (!in_array($status, ['upcoming', 'live', 'finished'], true)) {
    $status = 'upcoming';
}

$matchDatetime = wc_match_input_tehran_to_storage($matchDatetimeInput);
if ($resultOption === '') {
    $resultOption = wc_vip_match_result_option($score1, $score2);
}

$payload = [
    ':source_match_id' => $sourceMatchId > 0 ? $sourceMatchId : null,
    ':team1' => $team1,
    ':team2' => $team2,
    ':team1_flag' => $team1Flag,
    ':team2_flag' => $team2Flag,
    ':group_name' => $groupName,
    ':stage' => $stage,
    ':match_datetime' => $matchDatetime,
    ':venue' => $venue,
    ':is_active' => $isActive,
    ':status' => $status,
    ':score_team1' => $score1,
    ':score_team2' => $score2,
    ':result_option' => $resultOption,
];

if ($id > 0) {
    $payload[':id'] = $id;
    $pdo->prepare(
        "UPDATE {$vipMatchTable} SET
         source_match_id = :source_match_id,
         team1 = :team1,
         team2 = :team2,
         team1_flag = :team1_flag,
         team2_flag = :team2_flag,
         group_name = :group_name,
         stage = :stage,
         match_datetime = :match_datetime,
         venue = :venue,
         is_active = :is_active,
         status = :status,
         score_team1 = :score_team1,
         score_team2 = :score_team2,
         result_option = :result_option
         WHERE id = :id"
    )->execute($payload);
    hmn_json_response(['success' => true, 'id' => $id]);
}

$pdo->prepare(
    "INSERT INTO {$vipMatchTable}
     (source_match_id, team1, team2, team1_flag, team2_flag, group_name, stage, match_datetime, venue, is_active, status, score_team1, score_team2, result_option)
     VALUES
     (:source_match_id, :team1, :team2, :team1_flag, :team2_flag, :group_name, :stage, :match_datetime, :venue, :is_active, :status, :score_team1, :score_team2, :result_option)"
)->execute($payload);

hmn_json_response(['success' => true, 'id' => (int)$pdo->lastInsertId()]);
