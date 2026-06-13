<?php
/** @var PDO $pdo */

$uid = wc_current_user_id();
$data = hmn_read_json();
$rewardId = (int)($data['reward_id'] ?? 0);
if ($rewardId <= 0) {
    hmn_json_response(['success' => false, 'error' => 'شناسه جایزه نامعتبر است.']);
}

$rewardTable = hmn_table('rewards');
$redemptionTable = hmn_table('reward_redemptions');
$userTable = hmn_table('users');

try {
    $pdo->beginTransaction();

    $userSt = $pdo->prepare("SELECT * FROM {$userTable} WHERE id = :id LIMIT 1 FOR UPDATE");
    $userSt->execute([':id' => $uid]);
    $user = $userSt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        $pdo->rollBack();
        hmn_json_response(['success' => false, 'error' => 'کاربر یافت نشد.']);
    }

    $rewardSt = $pdo->prepare("SELECT * FROM {$rewardTable} WHERE id = :id LIMIT 1 FOR UPDATE");
    $rewardSt->execute([':id' => $rewardId]);
    $reward = $rewardSt->fetch(PDO::FETCH_ASSOC);
    if (!$reward || (int)($reward['is_active'] ?? 0) !== 1) {
        $pdo->rollBack();
        hmn_json_response(['success' => false, 'error' => 'این جایزه در حال حاضر فعال نیست.']);
    }

    $existingSt = $pdo->prepare(
        "SELECT * FROM {$redemptionTable}
         WHERE reward_id = :reward_id AND user_id = :user_id
         ORDER BY id DESC
         LIMIT 1"
    );
    $existingSt->execute([
        ':reward_id' => $rewardId,
        ':user_id' => $uid,
    ]);
    $existing = $existingSt->fetch(PDO::FETCH_ASSOC);
    if ($existing) {
        $pdo->rollBack();
        hmn_json_response([
            'success' => true,
            'already_redeemed' => true,
            'message' => 'این جایزه قبلا برای شما ثبت شده است.',
            'code' => trim((string)($existing['delivered_code'] ?? '')),
            'product_url' => trim((string)($reward['product_url'] ?? '')),
            'redemption_id' => (int)$existing['id'],
        ]);
    }

    $available = wc_get_available_points($user);
    $cost = (int)($reward['points_cost'] ?? 0);
    if ($available < $cost) {
        $pdo->rollBack();
        hmn_json_response([
            'success' => false,
            'error' => 'امتیاز شما کم است.',
            'need_more_points' => $cost - $available,
            'available_points' => $available,
        ]);
    }

    if ($reward['stock'] !== null && (int)$reward['stock'] <= 0) {
        $pdo->rollBack();
        hmn_json_response(['success' => false, 'error' => 'موجودی این جایزه تمام شده است.']);
    }

    $snapshot = [
        'title' => $reward['title'],
        'description' => $reward['description'],
        'image_url' => $reward['image_url'],
        'product_url' => $reward['product_url'],
        'points_cost' => $cost,
    ];
    $code = trim((string)($reward['reward_code'] ?? ''));
    $pdo->prepare(
        "INSERT INTO {$redemptionTable}
        (reward_id, user_id, points_spent, reward_snapshot_json, delivered_code)
        VALUES
        (:reward_id, :user_id, :points_spent, :snapshot, :delivered_code)"
    )->execute([
        ':reward_id' => $rewardId,
        ':user_id' => $uid,
        ':points_spent' => $cost,
        ':snapshot' => json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ':delivered_code' => $code,
    ]);

    if ($reward['stock'] !== null) {
        $pdo->prepare("UPDATE {$rewardTable} SET stock = stock - 1 WHERE id = :id AND stock > 0")->execute([':id' => $rewardId]);
    }

    wc_recalculate_user_redeemed_points($pdo, [$uid]);
    $userSt->execute([':id' => $uid]);
    $updatedUser = $userSt->fetch(PDO::FETCH_ASSOC) ?: $user;
    $updatedUser['available_points'] = wc_get_available_points($updatedUser);

    $pdo->commit();
    hmn_json_response([
        'success' => true,
        'message' => 'جایزه برای شما ثبت شد.',
        'code' => $code,
        'product_url' => trim((string)($reward['product_url'] ?? '')),
        'user' => $updatedUser,
        'redemption_id' => (int)$pdo->lastInsertId(),
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    hmn_json_response(['success' => false, 'error' => 'خطا در ثبت جایزه.']);
}
