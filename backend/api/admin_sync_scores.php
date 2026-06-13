<?php
/** @var PDO $pdo */

$data = hmn_read_json();
$force = !empty($data['force']);
$result = wc_maybe_sync_scores($pdo, $force);
hmn_json_response($result);
