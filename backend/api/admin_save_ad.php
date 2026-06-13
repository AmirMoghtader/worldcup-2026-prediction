<?php
/** @var PDO $pdo */

$data = hmn_read_json();
$id = (int)($data['id'] ?? 0);
$title = trim((string)($data['title'] ?? ''));
$imageUrl = trim((string)($data['image_url'] ?? ''));
$linkUrl = trim((string)($data['link_url'] ?? ''));
$placement = trim((string)($data['placement'] ?? 'leaderboard_below'));
$sortOrder = (int)($data['sort_order'] ?? 0);
$isActive = !empty($data['is_active']) ? 1 : 0;

if ($imageUrl === '') {
    hmn_json_response(['success' => false, 'error' => 'عکس بنر الزامی است.']);
}

$allowedPlacements = ['leaderboard_below'];
if (!in_array($placement, $allowedPlacements, true)) {
    $placement = 'leaderboard_below';
}

$table = hmn_table('ad_banners');

if ($id > 0) {
    $pdo->prepare(
        "UPDATE {$table}
         SET title = :title,
             image_url = :image_url,
             link_url = :link_url,
             placement = :placement,
             sort_order = :sort_order,
             is_active = :is_active
         WHERE id = :id"
    )->execute([
        ':title' => $title,
        ':image_url' => $imageUrl,
        ':link_url' => $linkUrl,
        ':placement' => $placement,
        ':sort_order' => $sortOrder,
        ':is_active' => $isActive,
        ':id' => $id,
    ]);
    hmn_json_response(['success' => true, 'id' => $id]);
}

$pdo->prepare(
    "INSERT INTO {$table}
    (title, image_url, link_url, placement, sort_order, is_active)
    VALUES
    (:title, :image_url, :link_url, :placement, :sort_order, :is_active)"
)->execute([
    ':title' => $title,
    ':image_url' => $imageUrl,
    ':link_url' => $linkUrl,
    ':placement' => $placement,
    ':sort_order' => $sortOrder,
    ':is_active' => $isActive,
]);

hmn_json_response(['success' => true, 'id' => (int)$pdo->lastInsertId()]);
