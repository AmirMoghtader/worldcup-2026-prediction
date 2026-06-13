<?php
declare(strict_types=1);

session_start();
require __DIR__ . '/db.php';
require __DIR__ . '/worldcup.php';

if (($_SESSION['wc_role'] ?? '') !== 'admin') {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'دسترسی ندارید.';
    exit;
}

$pdo = hmn_get_db();
wc_ensure_tables($pdo);

$tu = hmn_table('users');
$tp = hmn_table('predictions');
$rows = $pdo->query(
    "SELECT u.name, u.phone, u.total_points, u.redeemed_points, u.created_at,
            (SELECT COUNT(*) FROM {$tp} WHERE user_id = u.id) AS predictions_count,
            (SELECT COUNT(*) FROM {$tp} WHERE user_id = u.id AND is_correct = 1) AS correct_count
     FROM {$tu} u
     ORDER BY u.total_points DESC, u.created_at ASC"
)->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=\"worldcup-users-' . date('Ymd-His') . '.csv\"');
echo "\xEF\xBB\xBF";
$out = fopen('php://output', 'wb');
fputcsv($out, ['نام', 'شماره تلفن', 'امتیاز کل', 'امتیاز خرج شده', 'امتیاز قابل خرج', 'تعداد پیش‌بینی', 'پیش‌بینی صحیح', 'تاریخ عضویت']);
foreach ($rows as $row) {
    fputcsv($out, [
        $row['name'],
        $row['phone'],
        (int)$row['total_points'],
        (int)$row['redeemed_points'],
        wc_get_available_points($row),
        (int)$row['predictions_count'],
        (int)$row['correct_count'],
        $row['created_at'],
    ]);
}
fclose($out);
exit;
