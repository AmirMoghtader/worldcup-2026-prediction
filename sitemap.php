<?php
declare(strict_types=1);

require __DIR__ . '/backend/db.php';
require __DIR__ . '/backend/worldcup.php';

header('Content-Type: application/xml; charset=utf-8');

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$base = $scheme . '://' . $host;

$urls = [
    ['loc' => $base . '/', 'priority' => '1.0', 'changefreq' => 'hourly'],
    ['loc' => $base . '/knockout', 'priority' => '0.8', 'changefreq' => 'daily'],
    ['loc' => $base . '/profile', 'priority' => '0.6', 'changefreq' => 'daily'],
];

try {
    $pdo = hmn_get_db();
    wc_ensure_tables($pdo);
    $table = hmn_table('matches');
    $rows = $pdo->query("SELECT id, created_at FROM {$table} ORDER BY match_datetime ASC")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $row) {
        $lastmod = $row['updated_at'] ?? $row['created_at'] ?? null;
        $urls[] = [
            'loc' => $base . '/match?id=' . (int)$row['id'],
            'priority' => '0.7',
            'changefreq' => 'hourly',
            'lastmod' => $lastmod ? date('c', strtotime((string)$lastmod)) : null,
        ];
    }
} catch (Throwable $e) {
}

echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
echo "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";
foreach ($urls as $item) {
    echo "  <url>\n";
    echo '    <loc>' . htmlspecialchars($item['loc'], ENT_XML1 | ENT_COMPAT, 'UTF-8') . "</loc>\n";
    if (!empty($item['lastmod'])) {
        echo '    <lastmod>' . htmlspecialchars((string)$item['lastmod'], ENT_XML1 | ENT_COMPAT, 'UTF-8') . "</lastmod>\n";
    }
    echo '    <changefreq>' . $item['changefreq'] . "</changefreq>\n";
    echo '    <priority>' . $item['priority'] . "</priority>\n";
    echo "  </url>\n";
}
echo "</urlset>\n";
