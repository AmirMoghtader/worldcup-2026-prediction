<?php
/** @var PDO $pdo */

$data = hmn_read_json();
$id = (int)($data['id'] ?? 0);
if ($id <= 0) {
    hmn_json_response(['success' => false, 'error' => 'شناسه بنر نامعتبر است.']);
}

$table = hmn_table('ad_banners');
$pdo->prepare("DELETE FROM {$table} WHERE id = :id")->execute([':id' => $id]);
hmn_json_response(['success' => true]);
