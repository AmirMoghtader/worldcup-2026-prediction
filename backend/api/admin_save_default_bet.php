<?php
/** @var PDO $pdo */
$data    = hmn_read_json();
$id      = (int)($data['id'] ?? 0);
$label   = trim((string)($data['label'] ?? ''));
$bt      = trim((string)($data['bet_type'] ?? 'custom'));
$opts    = (array)($data['options'] ?? []);
$points  = max(1, (int)($data['points'] ?? 10));
$active  = isset($data['is_active']) ? (int)(bool)$data['is_active'] : 1;
$order   = (int)($data['display_order'] ?? 0);

if ($label === '' || empty($opts)) {
    hmn_json_response(['success' => false, 'error' => 'label و options الزامی است.']);
}
$normalized = [];
foreach ($opts as $o) {
    $str = is_array($o) ? (string)($o['label'] ?? $o['value'] ?? '') : (string)$o;
    if ($str !== '') $normalized[] = $str;
}
$oj = json_encode($normalized, JSON_UNESCAPED_UNICODE);
$td = hmn_table('default_bets');
if ($id) {
    $pdo->prepare("UPDATE {$td} SET label=:l,bet_type=:bt,options_json=:oj,points=:p,is_active=:ia,display_order=:do WHERE id=:id")
        ->execute([':l'=>$label,':bt'=>$bt,':oj'=>$oj,':p'=>$points,':ia'=>$active,':do'=>$order,':id'=>$id]);
} else {
    $pdo->prepare("INSERT INTO {$td} (label,bet_type,options_json,points,is_active,display_order) VALUES (:l,:bt,:oj,:p,:ia,:do)")
        ->execute([':l'=>$label,':bt'=>$bt,':oj'=>$oj,':p'=>$points,':ia'=>$active,':do'=>$order]);
    $id = (int)$pdo->lastInsertId();
}
wc_sync_default_bets_to_all_matches($pdo);
hmn_json_response(['success' => true, 'id' => $id]);
