<?php
/** @var PDO $pdo */
$tu = hmn_table('users');
$tm = hmn_table('matches');
$tp = hmn_table('predictions');
$tr = hmn_table('rewards');
wc_maybe_sync_scores($pdo);

$total_users = (int)$pdo->query("SELECT COUNT(*) FROM {$tu}")->fetchColumn();
$total_matches = (int)$pdo->query("SELECT COUNT(*) FROM {$tm}")->fetchColumn();
$total_predictions = (int)$pdo->query("SELECT COUNT(*) FROM {$tp}")->fetchColumn();
$upcoming_matches = (int)$pdo->query("SELECT COUNT(*) FROM {$tm} WHERE status='upcoming'")->fetchColumn();
$live_matches = (int)$pdo->query("SELECT COUNT(*) FROM {$tm} WHERE status='live'")->fetchColumn();
$finished_matches = (int)$pdo->query("SELECT COUNT(*) FROM {$tm} WHERE status='finished'")->fetchColumn();
$active_rewards = (int)$pdo->query("SELECT COUNT(*) FROM {$tr} WHERE is_active=1")->fetchColumn();

hmn_json_response([
    'success' => true,
    'stats' => [
        'total_users'       => $total_users,
        'total_matches'     => $total_matches,
        'total_predictions' => $total_predictions,
        'upcoming_matches'  => $upcoming_matches,
        'live_matches'      => $live_matches,
        'finished_matches'  => $finished_matches,
        'active_rewards'    => $active_rewards,
    ],
]);
