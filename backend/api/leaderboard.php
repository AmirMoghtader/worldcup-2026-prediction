<?php
/** @var PDO $pdo */
$tu = hmn_table('users');
$limit = max(1, min(200, (int)($_GET['limit'] ?? 50)));
$rows = $pdo->prepare(
    "SELECT id, name, total_points, phone FROM {$tu} ORDER BY total_points DESC, created_at ASC LIMIT :lim"
);
$rows->bindValue(':lim', $limit, PDO::PARAM_INT);
$rows->execute();
$leaderboard = $rows->fetchAll(PDO::FETCH_ASSOC);
// Mask phone for privacy: 09XXXXX123 → 09XXXXX***
foreach ($leaderboard as &$u) {
    $p = $u['phone'];
    $u['phone_masked'] = strlen($p) >= 6 ? substr($p, 0, 7) . '***' : '***';
    unset($u['phone']);
}
hmn_json_response(['success' => true, 'leaderboard' => $leaderboard]);
