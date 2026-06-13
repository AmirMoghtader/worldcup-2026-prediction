<?php
declare(strict_types=1);

$default = '/assets/worldcup.jpeg';
$configPath = __DIR__ . '/config.php';

if (!file_exists($configPath)) {
    header('Cache-Control: public, max-age=900');
    header('Location: ' . $default, true, 302);
    exit;
}

require __DIR__ . '/backend/db.php';
require __DIR__ . '/backend/worldcup.php';

try {
    $pdo = hmn_get_db();
    wc_ensure_tables($pdo);
    $settings = wc_get_settings($pdo);
    $target = trim((string)($settings['browser_icon_url'] ?? ''));
    if ($target === '') {
        $target = trim((string)($settings['logo_url'] ?? ''));
    }
    if ($target === '') {
        $target = $default;
    }
} catch (Throwable $e) {
    $target = $default;
}

header('Cache-Control: public, max-age=900');
header('Location: ' . $target, true, 302);
exit;
