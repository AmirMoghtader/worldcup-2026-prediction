<?php
/** @var PDO $pdo */
$data = hmn_read_json();
$userId = wc_current_user_id();
$matchId = (int)($data['match_id'] ?? 0);
$outcome = trim((string)($data['outcome'] ?? ''));
$amount = max(0, (int)($data['amount'] ?? 0));
$exactScoreTeam1 = (!isset($data['exact_score_team1']) || $data['exact_score_team1'] === '') ? null : max(0, (int)$data['exact_score_team1']);
$exactScoreTeam2 = (!isset($data['exact_score_team2']) || $data['exact_score_team2'] === '') ? null : max(0, (int)$data['exact_score_team2']);

if ($matchId <= 0) {
    hmn_json_response(['success' => false, 'error' => 'بازی VIP نامعتبر است.']);
}
if (!in_array($outcome, ['team1', 'draw', 'team2'], true)) {
    hmn_json_response(['success' => false, 'error' => 'گزینه شرط معتبر نیست.']);
}
if ($amount <= 0) {
    hmn_json_response(['success' => false, 'error' => 'مبلغ شرط باید بیشتر از صفر باشد.']);
}
if ($exactScoreTeam1 === null || $exactScoreTeam2 === null) {
    hmn_json_response(['success' => false, 'error' => 'نتیجه دقیق را هم برای این شرط وارد کنید.']);
}

$userTable = hmn_table('users');
$memberTable = hmn_table('vip_members');
$matchTable = hmn_table('vip_matches');
$betTable = hmn_table('vip_bets');

$userSt = $pdo->prepare("SELECT id, phone, name FROM {$userTable} WHERE id = :id LIMIT 1");
$userSt->execute([':id' => $userId]);
$user = $userSt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    hmn_json_response(['success' => false, 'error' => 'کاربر پیدا نشد.']);
}
$vip = wc_get_user_vip($pdo, $userId, (string)$user['phone']);
if (!$vip) {
    http_response_code(403);
    hmn_json_response(['success' => false, 'error' => 'این بخش فقط برای کاربران VIP فعال است.']);
}

try {
    $pdo->beginTransaction();

    $memberSt = $pdo->prepare("SELECT * FROM {$memberTable} WHERE id = :id AND is_active = 1 LIMIT 1 FOR UPDATE");
    $memberSt->execute([':id' => $vip['id']]);
    $member = $memberSt->fetch(PDO::FETCH_ASSOC);
    if (!$member) {
        throw new RuntimeException('عضویت VIP شما فعال نیست.');
    }

    $matchSt = $pdo->prepare("SELECT * FROM {$matchTable} WHERE id = :id LIMIT 1 FOR UPDATE");
    $matchSt->execute([':id' => $matchId]);
    $match = $matchSt->fetch(PDO::FETCH_ASSOC);
    if (!$match || (int)($match['is_active'] ?? 0) !== 1) {
        throw new RuntimeException('این بازی برای میز شرط‌بندی فعال نیست.');
    }
    $match = wc_vip_match_row_for_display($match);
    if (($match['status'] ?? 'upcoming') !== 'upcoming' || !empty($match['settled_at'])) {
        throw new RuntimeException('مهلت شرط‌بندی این بازی تمام شده است.');
    }
    if ((int)($match['match_timestamp'] ?? 0) <= time()) {
        throw new RuntimeException('شروع بازی رسیده و ثبت شرط بسته شده است.');
    }

    $existingSt = $pdo->prepare("SELECT * FROM {$betTable} WHERE user_id = :user_id AND vip_match_id = :match_id LIMIT 1 FOR UPDATE");
    $existingSt->execute([':user_id' => $userId, ':match_id' => $matchId]);
    $existingBet = $existingSt->fetch(PDO::FETCH_ASSOC) ?: null;

    $balance = (int)($member['current_balance'] ?? 0);
    $previousAmount = $existingBet ? (int)($existingBet['amount'] ?? 0) : 0;
    $balanceDelta = $amount - $previousAmount;
    if ($balanceDelta > $balance) {
        throw new RuntimeException('اعتبار شما برای این شرط کافی نیست.');
    }

    if ($existingBet) {
        $pdo->prepare(
            "UPDATE {$betTable}
             SET outcome = :outcome,
                 amount = :amount,
                 exact_score_team1 = :exact_score_team1,
                 exact_score_team2 = :exact_score_team2,
                 payout_amount = 0,
                 jackpot_payout = 0,
                 exact_score_hit = 0,
                 result_status = 'open',
                 settled_at = NULL
             WHERE id = :id"
        )->execute([
            ':outcome' => $outcome,
            ':amount' => $amount,
            ':exact_score_team1' => $exactScoreTeam1,
            ':exact_score_team2' => $exactScoreTeam2,
            ':id' => $existingBet['id'],
        ]);
    } else {
        $pdo->prepare(
            "INSERT INTO {$betTable} (vip_member_id, user_id, vip_match_id, outcome, amount, exact_score_team1, exact_score_team2)
             VALUES (:vip_member_id, :user_id, :vip_match_id, :outcome, :amount, :exact_score_team1, :exact_score_team2)"
        )->execute([
            ':vip_member_id' => $member['id'],
            ':user_id' => $userId,
            ':vip_match_id' => $matchId,
            ':outcome' => $outcome,
            ':amount' => $amount,
            ':exact_score_team1' => $exactScoreTeam1,
            ':exact_score_team2' => $exactScoreTeam2,
        ]);
    }

    $pdo->prepare("UPDATE {$memberTable} SET current_balance = current_balance - :delta WHERE id = :id")
        ->execute([
            ':delta' => $balanceDelta,
            ':id' => $member['id'],
        ]);

    $pdo->commit();
    hmn_json_response([
        'success' => true,
        'mode' => $existingBet ? 'updated' : 'created',
        'balance' => $balance - $balanceDelta,
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    hmn_json_response(['success' => false, 'error' => $e->getMessage()]);
}
