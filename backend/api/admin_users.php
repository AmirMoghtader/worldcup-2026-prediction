<?php
/** @var PDO $pdo */
$tu = hmn_table('users');
$tp = hmn_table('predictions');
$rows = $pdo->query(
    "SELECT u.id, u.phone, u.name, u.total_points, u.created_at,
            u.redeemed_points,
            (SELECT COUNT(*) FROM {$tp} WHERE user_id=u.id) AS pred_count,
            (SELECT COUNT(*) FROM {$tp} WHERE user_id=u.id AND is_correct=1) AS correct_count
     FROM {$tu} u ORDER BY u.total_points DESC, u.created_at ASC"
)->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as &$row) {
    $row['available_points'] = wc_get_available_points($row);
    $row = wc_attach_vip_to_user($pdo, $row);
}
unset($row);
hmn_json_response(['success' => true, 'users' => $rows]);
