<?php
/** @var PDO $pdo */

$table = hmn_table('ad_banners');
$placement = trim((string)($_GET['placement'] ?? ''));
$sql = "SELECT id, title, image_url, link_url, placement, sort_order FROM {$table} WHERE is_active = 1";
$params = [];
if ($placement !== '') {
    $sql .= " AND placement = :placement";
    $params[':placement'] = $placement;
}
$sql .= " ORDER BY sort_order ASC, id DESC";
$st = $pdo->prepare($sql);
$st->execute($params);

hmn_json_response([
    'success' => true,
    'ads' => $st->fetchAll(PDO::FETCH_ASSOC),
]);
