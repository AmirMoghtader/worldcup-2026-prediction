<?php
/** @var PDO $pdo */

$rewardTable = hmn_table('rewards');
$redemptionTable = hmn_table('reward_redemptions');
$userTable = hmn_table('users');

$rewards = $pdo->query("SELECT * FROM {$rewardTable} ORDER BY sort_order ASC, id DESC")->fetchAll(PDO::FETCH_ASSOC);
$redemptions = $pdo->query(
    "SELECT rr.*, r.title AS reward_title, u.name AS user_name, u.phone AS user_phone
     FROM {$redemptionTable} rr
     JOIN {$rewardTable} r ON r.id = rr.reward_id
     JOIN {$userTable} u ON u.id = rr.user_id
     ORDER BY rr.created_at DESC, rr.id DESC
     LIMIT 100"
)->fetchAll(PDO::FETCH_ASSOC);

hmn_json_response([
    'success' => true,
    'rewards' => $rewards,
    'redemptions' => $redemptions,
]);
