<?php
/** @var PDO $pdo */
$data = hmn_read_json();
$id       = (int)($data['id'] ?? 0);
$team1    = trim((string)($data['team1'] ?? ''));
$team2    = trim((string)($data['team2'] ?? ''));
$team1f   = trim((string)($data['team1_flag'] ?? ''));
$team2f   = trim((string)($data['team2_flag'] ?? ''));
$group    = trim((string)($data['group_name'] ?? ''));
$stage    = trim((string)($data['stage'] ?? 'group'));
$datetime = trim((string)($data['match_datetime'] ?? ''));
$venue    = trim((string)($data['venue'] ?? ''));
$externalRef = trim((string)($data['external_ref'] ?? ''));
$is_open  = isset($data['is_open']) ? (int)(bool)$data['is_open'] : 1;
$status   = trim((string)($data['status'] ?? 'upcoming'));

if ($team1 === '' || $team2 === '' || $datetime === '') {
    http_response_code(400);
    hmn_json_response(['success' => false, 'error' => 'نام تیم‌ها و تاریخ/ساعت بازی الزامی است.']);
}

// Validate datetime format
if (!preg_match('/^\d{4}-\d{2}-\d{2}[T ]\d{2}:\d{2}/', $datetime)) {
    hmn_json_response(['success' => false, 'error' => 'فرمت تاریخ/ساعت نامعتبر است (YYYY-MM-DDTHH:MM)']);
}
$dt = str_replace('T', ' ', $datetime);
if (strlen($dt) === 16) $dt .= ':00';
$dt = wc_match_input_tehran_to_storage($dt);

$tm = hmn_table('matches');
$allowed_stages = ['group','r32','r16','qf','sf','final','3rd'];
if (!in_array($stage, $allowed_stages, true)) $stage = 'group';
if (!in_array($status, ['upcoming', 'live', 'finished'], true)) $status = 'upcoming';

if ($id) {
    $pdo->prepare(
        "UPDATE {$tm} SET team1=:t1,team2=:t2,team1_flag=:f1,team2_flag=:f2,group_name=:g,stage=:st,match_datetime=:dt,venue=:v,external_ref=:external_ref,is_open=:io,status=:status WHERE id=:id"
    )->execute([':t1'=>$team1,':t2'=>$team2,':f1'=>$team1f,':f2'=>$team2f,':g'=>$group,':st'=>$stage,':dt'=>$dt,':v'=>$venue, ':external_ref' => $externalRef, ':io'=>$is_open, ':status' => $status, ':id'=>$id]);
    hmn_json_response(['success' => true, 'id' => $id]);
} else {
    $pdo->prepare(
        "INSERT INTO {$tm} (team1,team2,team1_flag,team2_flag,group_name,stage,match_datetime,venue,external_ref,is_open,status) VALUES (:t1,:t2,:f1,:f2,:g,:st,:dt,:v,:external_ref,:io,:status)"
    )->execute([':t1'=>$team1,':t2'=>$team2,':f1'=>$team1f,':f2'=>$team2f,':g'=>$group,':st'=>$stage,':dt'=>$dt,':v'=>$venue, ':external_ref' => $externalRef, ':io'=>$is_open, ':status' => $status]);
    $newId = (int)$pdo->lastInsertId();
    wc_sync_default_bets_to_match($pdo, $newId);
    hmn_json_response(['success' => true, 'id' => $newId]);
}
