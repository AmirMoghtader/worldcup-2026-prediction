<?php
/** @var PDO $pdo */
$data     = hmn_read_json();
$id       = (int)($data['id'] ?? 0);
$match_id = (int)($data['match_id'] ?? 0);
$label    = trim((string)($data['label'] ?? ''));
$bet_type = trim((string)($data['bet_type'] ?? 'custom'));
$options  = (array)($data['options'] ?? []);
$points   = max(1, (int)($data['points'] ?? 10));
$is_active = isset($data['is_active']) ? (int)(bool)$data['is_active'] : 1;
$displayOrder = (int)($data['display_order'] ?? 0);
$syncWithDefault = isset($data['sync_with_default']) ? (int)(bool)$data['sync_with_default'] : 0;

if (!$match_id || $label === '' || empty($options)) {
    http_response_code(400);
    hmn_json_response(['success' => false, 'error' => 'match_id، label و options الزامی است.']);
}
$opts = [];
foreach ($options as $o) {
    $str = is_array($o) ? (string)($o['label'] ?? $o['value'] ?? '') : (string)$o;
    if ($str !== '') $opts[] = $str;
}
$opts_json = json_encode($opts, JSON_UNESCAPED_UNICODE);

$tb = hmn_table('bets');
if ($id) {
    $existing = $pdo->prepare("SELECT default_bet_id FROM {$tb} WHERE id = :id AND match_id = :mid LIMIT 1");
    $existing->execute([':id' => $id, ':mid' => $match_id]);
    $current = $existing->fetch(PDO::FETCH_ASSOC) ?: [];
    if (($current['default_bet_id'] ?? null) && !isset($data['sync_with_default'])) {
        $syncWithDefault = 0;
    }
    $pdo->prepare("UPDATE {$tb} SET label=:l,bet_type=:bt,options_json=:oj,points=:p,is_active=:ia,display_order=:do,sync_with_default=:sync WHERE id=:id AND match_id=:mid")
        ->execute([':l'=>$label,':bt'=>$bet_type,':oj'=>$opts_json,':p'=>$points,':ia'=>$is_active,':do' => $displayOrder, ':sync' => $syncWithDefault, ':id'=>$id,':mid'=>$match_id]);
    hmn_json_response(['success' => true, 'id' => $id]);
} else {
    $pdo->prepare("INSERT INTO {$tb} (match_id,label,bet_type,options_json,points,is_active,sync_with_default,display_order) VALUES (:mid,:l,:bt,:oj,:p,:ia,:sync,:do)")
        ->execute([':mid'=>$match_id,':l'=>$label,':bt'=>$bet_type,':oj'=>$opts_json,':p'=>$points,':ia'=>$is_active, ':sync' => $syncWithDefault, ':do' => $displayOrder]);
    hmn_json_response(['success' => true, 'id' => (int)$pdo->lastInsertId()]);
}
