<?php
declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/db.php';
require __DIR__ . '/worldcup.php';

if (($_SESSION['wc_role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'دسترسی مجاز نیست.'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$pdo = hmn_get_db();
wc_ensure_tables($pdo);

$json = null;
if (!empty($_FILES['backup']['tmp_name'])) {
    $tmp = $_FILES['backup']['tmp_name'];
    if (class_exists('ZipArchive')) {
        $zip = new ZipArchive();
        if ($zip->open($tmp) === true) {
            $json = $zip->getFromName('backup.json');
            $zip->close();
        }
    }
    if ($json === null || $json === false) {
        $json = file_get_contents($tmp);
    }
} else {
    $json = file_get_contents('php://input');
}

if (!$json) {
    echo json_encode(['success' => false, 'error' => 'فایل بکاپ دریافت نشد.'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$data = json_decode($json, true);
if (!is_array($data) || !isset($data['tables']) || !is_array($data['tables'])) {
    echo json_encode(['success' => false, 'error' => 'ساختار بکاپ نامعتبر است.'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$knownTables = [
    'users', 'admins', 'matches', 'default_bets', 'bets', 'predictions',
    'settings', 'login_lockouts', 'rewards', 'reward_redemptions', 'ad_banners',
];

try {
    $pdo->beginTransaction();
    foreach ($knownTables as $base) {
        if (!isset($data['tables'][$base]['rows']) || !is_array($data['tables'][$base]['rows'])) {
            continue;
        }
        $table = hmn_table($base);
        $cols = [];
        $colStmt = $pdo->query("SHOW COLUMNS FROM {$table}");
        while ($col = $colStmt->fetch(PDO::FETCH_ASSOC)) {
            if (!empty($col['Field'])) {
                $cols[] = $col['Field'];
            }
        }
        if (!$cols) {
            continue;
        }
        $pdo->exec("DELETE FROM {$table}");
        foreach ($data['tables'][$base]['rows'] as $row) {
            if (!is_array($row)) {
                continue;
            }
            $shared = array_values(array_intersect($cols, array_keys($row)));
            if (!$shared) {
                continue;
            }
            $fieldList = implode(', ', $shared);
            $placeholders = implode(', ', array_map(static fn(string $col): string => ':' . $col, $shared));
            $stmt = $pdo->prepare("INSERT INTO {$table} ({$fieldList}) VALUES ({$placeholders})");
            $payload = [];
            foreach ($shared as $field) {
                $payload[':' . $field] = $row[$field];
            }
            $stmt->execute($payload);
        }
    }
    $userIds = $pdo->query("SELECT id FROM " . hmn_table('users'))->fetchAll(PDO::FETCH_COLUMN);
    wc_recalculate_user_totals($pdo, array_map('intval', $userIds));
    wc_recalculate_user_redeemed_points($pdo, array_map('intval', $userIds));
    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'بکاپ با موفقیت بازگردانی شد.'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'error' => 'بازگردانی بکاپ انجام نشد.'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
