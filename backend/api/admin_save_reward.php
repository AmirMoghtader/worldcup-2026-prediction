<?php
/** @var PDO $pdo */

$data = hmn_read_json();
$id = (int)($data['id'] ?? 0);
$title = trim((string)($data['title'] ?? ''));
$description = trim((string)($data['description'] ?? ''));
$imageUrl = trim((string)($data['image_url'] ?? ''));
$rewardCode = trim((string)($data['reward_code'] ?? ''));
$productUrl = trim((string)($data['product_url'] ?? ''));
$discountPercent = max(0, min(100, (int)($data['discount_percent'] ?? 0)));
$pointsCost = max(1, (int)($data['points_cost'] ?? 10));
$sortOrder = (int)($data['sort_order'] ?? 0);
$isActive = !empty($data['is_active']) ? 1 : 0;
$stockRaw = $data['stock'] ?? null;
$stock = ($stockRaw === '' || $stockRaw === null) ? null : max(0, (int)$stockRaw);

if ($title === '') {
    hmn_json_response(['success' => false, 'error' => 'عنوان جایزه الزامی است.']);
}

$table = hmn_table('rewards');

if ($id > 0) {
    $pdo->prepare(
        "UPDATE {$table}
         SET title = :title,
             description = :description,
             image_url = :image_url,
             reward_code = :reward_code,
             product_url = :product_url,
             discount_percent = :discount_percent,
             points_cost = :points_cost,
             stock = :stock,
             is_active = :is_active,
             sort_order = :sort_order
         WHERE id = :id"
    )->execute([
        ':title' => $title,
        ':description' => $description,
        ':image_url' => $imageUrl,
        ':reward_code' => $rewardCode,
        ':product_url' => $productUrl,
        ':discount_percent' => $discountPercent,
        ':points_cost' => $pointsCost,
        ':stock' => $stock,
        ':is_active' => $isActive,
        ':sort_order' => $sortOrder,
        ':id' => $id,
    ]);
    hmn_json_response(['success' => true, 'id' => $id]);
}

$pdo->prepare(
    "INSERT INTO {$table}
    (title, description, image_url, reward_code, product_url, discount_percent, points_cost, stock, is_active, sort_order)
    VALUES
    (:title, :description, :image_url, :reward_code, :product_url, :discount_percent, :points_cost, :stock, :is_active, :sort_order)"
)->execute([
    ':title' => $title,
    ':description' => $description,
    ':image_url' => $imageUrl,
    ':reward_code' => $rewardCode,
    ':product_url' => $productUrl,
    ':discount_percent' => $discountPercent,
    ':points_cost' => $pointsCost,
    ':stock' => $stock,
    ':is_active' => $isActive,
    ':sort_order' => $sortOrder,
]);

hmn_json_response(['success' => true, 'id' => (int)$pdo->lastInsertId()]);
