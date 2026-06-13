<?php
/** @var PDO $pdo */

$table = hmn_table('ad_banners');
$ads = $pdo->query("SELECT * FROM {$table} ORDER BY placement ASC, sort_order ASC, id DESC")->fetchAll(PDO::FETCH_ASSOC);
hmn_json_response([
    'success' => true,
    'ads' => $ads,
]);
