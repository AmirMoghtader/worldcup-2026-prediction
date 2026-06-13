<?php
/** @var PDO $pdo */
$data = hmn_read_json();
$id      = (int)($data['id'] ?? 0);
$is_open = isset($data['is_open']) ? (int)(bool)$data['is_open'] : null;
if (!$id) { hmn_json_response(['success' => false, 'error' => 'id required']); }

$tm = hmn_table('matches');
if ($is_open === null) {
    // Toggle
    $pdo->prepare("UPDATE {$tm} SET is_open = 1 - is_open WHERE id = :id")->execute([':id' => $id]);
} else {
    $pdo->prepare("UPDATE {$tm} SET is_open = :v WHERE id = :id")->execute([':v' => $is_open, ':id' => $id]);
}
$st = $pdo->prepare("SELECT is_open FROM {$tm} WHERE id = :id LIMIT 1");
$st->execute([':id' => $id]);
$row = $st->fetch(PDO::FETCH_ASSOC);
hmn_json_response(['success' => true, 'is_open' => (int)($row['is_open'] ?? 0)]);
