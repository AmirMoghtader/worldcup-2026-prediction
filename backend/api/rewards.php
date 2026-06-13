<?php
/** @var PDO $pdo */

$rewardTable = hmn_table('rewards');
$redemptionTable = hmn_table('reward_redemptions');
$userTable = hmn_table('users');
$uid = wc_current_user_id();

$rewards = $pdo->query("SELECT id, title, description, image_url, reward_code, product_url, discount_percent, points_cost, stock, is_active, sort_order FROM {$rewardTable} WHERE is_active = 1 ORDER BY sort_order ASC, id DESC")->fetchAll(PDO::FETCH_ASSOC);
$user = null;
$redemptions = [];

if ($uid) {
    $userSt = $pdo->prepare("SELECT id, name, total_points, redeemed_points FROM {$userTable} WHERE id = :id LIMIT 1");
    $userSt->execute([':id' => $uid]);
    $user = $userSt->fetch(PDO::FETCH_ASSOC) ?: null;
    if ($user) {
        $user['available_points'] = wc_get_available_points($user);
    }
    $redSt = $pdo->prepare(
        "SELECT rr.*, r.title AS reward_title, r.image_url, r.product_url, r.discount_percent
         FROM {$redemptionTable} rr
         JOIN {$rewardTable} r ON r.id = rr.reward_id
         WHERE rr.user_id = :user_id
         ORDER BY rr.created_at DESC, rr.id DESC"
    );
    $redSt->execute([':user_id' => $uid]);
    $redemptions = $redSt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($redemptions as &$redemption) {
        $snapshot = json_decode((string)($redemption['reward_snapshot_json'] ?? ''), true);
        if (is_array($snapshot)) {
            $redemption['reward_snapshot'] = $snapshot;
            if (empty($redemption['image_url']) && !empty($snapshot['image_url'])) {
                $redemption['image_url'] = $snapshot['image_url'];
            }
            if (empty($redemption['product_url']) && !empty($snapshot['product_url'])) {
                $redemption['product_url'] = $snapshot['product_url'];
            }
            if ((!isset($redemption['discount_percent']) || (int)$redemption['discount_percent'] === 0) && isset($snapshot['discount_percent'])) {
                $redemption['discount_percent'] = (int)$snapshot['discount_percent'];
            }
        }
    }
    unset($redemption);
}

hmn_json_response([
    'success' => true,
    'rewards' => $rewards,
    'user' => $user,
    'redemptions' => $redemptions,
]);
