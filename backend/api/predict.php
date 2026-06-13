<?php
/** @var PDO $pdo */

$data = hmn_read_json();
$uid = wc_current_user_id();
$tb  = hmn_table('bets');
$tm  = hmn_table('matches');
$tp  = hmn_table('predictions');
$settings = wc_get_settings($pdo);
$lockMin = (int)($settings['prediction_lock_minutes'] ?? 10);
$windowHours = (int)($settings['prediction_window_hours'] ?? 48);

$items = [];
if (isset($data['predictions']) && is_array($data['predictions'])) {
    foreach ($data['predictions'] as $row) {
        if (!is_array($row)) {
            continue;
        }
        $items[] = [
            'bet_id' => (int)($row['bet_id'] ?? 0),
            'selected_option' => trim((string)($row['selected_option'] ?? '')),
        ];
    }
} else {
    $items[] = [
        'bet_id' => (int)($data['bet_id'] ?? 0),
        'selected_option' => trim((string)($data['selected_option'] ?? '')),
    ];
}

if (!$items) {
    http_response_code(400);
    hmn_json_response(['success' => false, 'error' => 'هیچ پیش‌بینی‌ای برای ثبت ارسال نشده است.']);
}

$betIds = array_values(array_unique(array_filter(array_map(static fn(array $item): int => (int)$item['bet_id'], $items))));
if (!$betIds) {
    http_response_code(400);
    hmn_json_response(['success' => false, 'error' => 'شناسه شرط نامعتبر است.']);
}

$placeholders = implode(',', array_fill(0, count($betIds), '?'));
$st = $pdo->prepare(
    "SELECT b.*, m.match_datetime, m.is_open, m.status
     FROM {$tb} b
     JOIN {$tm} m ON m.id = b.match_id
     WHERE b.id IN ({$placeholders}) AND b.is_active = 1"
);
$st->execute($betIds);
$bets = [];
foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $bets[(int)$row['id']] = $row;
}

foreach ($items as $item) {
    $betId = (int)$item['bet_id'];
    $selected = $item['selected_option'];

    if (!$betId || $selected === '') {
        http_response_code(400);
        hmn_json_response(['success' => false, 'error' => 'اطلاعات یکی از پیش‌بینی‌ها ناقص است.']);
    }

    if (!isset($bets[$betId])) {
        hmn_json_response(['success' => false, 'error' => 'یکی از شرط‌ها پیدا نشد یا غیرفعال است.']);
    }

    $bet = $bets[$betId];
    $options = json_decode((string)($bet['options_json'] ?? '[]'), true) ?? [];
    $valid = array_map(static fn($o) => is_array($o) ? (string)($o['value'] ?? $o['label'] ?? '') : (string)$o, $options);

    $isExactScore = (string)($bet['bet_type'] ?? '') === 'exact_score';
    $isValidExact = $isExactScore && preg_match('/^\d{1,2}-\d{1,2}$/', $selected);
    if (!$isValidExact && !in_array($selected, $valid, true)) {
        hmn_json_response(['success' => false, 'error' => 'یکی از گزینه‌های انتخاب‌شده نامعتبر است.']);
    }

    $matchRow = [
        'match_datetime' => $bet['match_datetime'],
        'is_open' => $bet['is_open'],
        'status' => $bet['status'],
    ];
    if (!wc_is_prediction_open($matchRow, $lockMin, $windowHours)) {
        hmn_json_response(['success' => false, 'error' => 'زمان ثبت پیش‌بینی برای این بازی به پایان رسیده است.']);
    }
}

$save = $pdo->prepare(
    "INSERT INTO {$tp} (user_id, match_id, bet_id, selected_option, submitted_at)
     VALUES (:uid, :mid, :bid, :sel, NOW())
     ON DUPLICATE KEY UPDATE selected_option = :sel, submitted_at = NOW(), is_correct = NULL, points_earned = 0"
);

foreach ($items as $item) {
    $bet = $bets[(int)$item['bet_id']];
    $save->execute([
        ':uid' => $uid,
        ':mid' => (int)$bet['match_id'],
        ':bid' => (int)$item['bet_id'],
        ':sel' => $item['selected_option'],
    ]);
}

hmn_json_response([
    'success' => true,
    'saved_count' => count($items),
    'message' => 'پیش‌بینی‌ها با موفقیت ثبت شدند.',
]);
