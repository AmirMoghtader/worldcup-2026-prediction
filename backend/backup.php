<?php
declare(strict_types=1);

session_start();
require __DIR__ . '/db.php';
require __DIR__ . '/worldcup.php';

if (($_SESSION['wc_role'] ?? '') !== 'admin') {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'error' => 'دسترسی مجاز نیست.'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$pdo = hmn_get_db();
wc_ensure_tables($pdo);

$tables = [
    'users', 'admins', 'matches', 'default_bets', 'bets', 'predictions',
    'settings', 'login_lockouts', 'rewards', 'reward_redemptions', 'ad_banners',
];

$data = [
    'meta' => [
        'generated_at' => date('c'),
        'app' => 'worldcup',
        'version' => '2.0',
        'host' => $_SERVER['HTTP_HOST'] ?? 'cli',
        'table_prefix' => (require dirname(__DIR__) . '/config.php')['table_prefix'] ?? '',
    ],
    'tables' => [],
];

foreach ($tables as $base) {
    $name = hmn_table($base);
    $rows = $pdo->query("SELECT * FROM {$name}")->fetchAll(PDO::FETCH_ASSOC);
    $data['tables'][$base] = ['table' => $name, 'rows' => $rows];
}

$json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
$format = strtolower((string)($_GET['format'] ?? 'zip'));

if ($format === 'zip' && class_exists('ZipArchive')) {
    $tmp = tempnam(sys_get_temp_dir(), 'worldcup-backup-');
    if ($tmp !== false) {
        $zip = new ZipArchive();
        if ($zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            $zip->addFromString('backup.json', (string)$json);
            $zip->close();
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="worldcup-backup-' . date('Ymd-His') . '.zip"');
            readfile($tmp);
            @unlink($tmp);
            exit;
        }
        @unlink($tmp);
    }
}

header('Content-Type: application/json; charset=utf-8');
header('Content-Disposition: attachment; filename="worldcup-backup-' . date('Ymd-His') . '.json"');
echo $json;
