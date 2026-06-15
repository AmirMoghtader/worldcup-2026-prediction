<?php
/** @var PDO $pdo */
$data = hmn_read_json();
$id = (int)($data['id'] ?? 0);
if ($id <= 0) {
    hmn_json_response(['success' => false, 'error' => 'عضو VIP نامعتبر است.']);
}

$table = hmn_table('vip_members');
$pdo->prepare("UPDATE {$table} SET is_active = 0 WHERE id = :id")->execute([':id' => $id]);
hmn_json_response(['success' => true]);
