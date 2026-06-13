<?php
$ta = hmn_table('admins');
$st = $pdo->prepare("SELECT id, name, phone FROM {$ta} WHERE id = :id LIMIT 1");
$st->execute([':id' => $_SESSION['wc_admin_id'] ?? 0]);
$admin = $st->fetch(PDO::FETCH_ASSOC);
hmn_json_response(['success' => true, 'admin' => $admin]);
