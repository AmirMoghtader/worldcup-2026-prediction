<?php
/** @var PDO $pdo */
$td = hmn_table('default_bets');

// Seed defaults on first run
$cnt = (int)$pdo->query("SELECT COUNT(*) FROM {$td}")->fetchColumn();
if ($cnt === 0) {
    $defaults = [
        [
            'label'   => 'نتیجه بازی',
            'bet_type'=> 'winner',
            'options' => ['تیم اول برنده', 'مساوی', 'تیم دوم برنده'],
            'points'  => 15,
            'order'   => 1,
        ],
        [
            'label'   => 'تعداد گل بازی',
            'bet_type'=> 'total_goals',
            'options' => ['۰ تا ۱ گل', '۲ تا ۳ گل', '۴ گل و بیشتر'],
            'points'  => 12,
            'order'   => 2,
        ],
        [
            'label'   => 'هر دو تیم گل می‌زنند؟',
            'bet_type'=> 'btts',
            'options' => ['بله', 'خیر'],
            'points'  => 10,
            'order'   => 3,
        ],
        [
            'label'   => 'اولین تیم گل‌زن',
            'bet_type'=> 'first_goal_team',
            'options' => ['تیم اول', 'تیم دوم', 'بدون گل'],
            'points'  => 12,
            'order'   => 4,
        ],
        [
            'label'   => 'گل در نیمه اول؟',
            'bet_type'=> 'first_half_goal',
            'options' => ['بله', 'خیر'],
            'points'  => 8,
            'order'   => 5,
        ],
        [
            'label'   => 'ضربه پنالتی در بازی؟',
            'bet_type'=> 'penalty',
            'options' => ['بله', 'خیر'],
            'points'  => 12,
            'order'   => 6,
        ],
        [
            'label'   => 'نتیجه دقیق',
            'bet_type'=> 'exact_score',
            'options' => ['۱-۰', '۲-۰', '۲-۱', '۳-۰', '۳-۱', '۳-۲', '۰-۰', '۱-۱', '۲-۲', '۰-۱', '۰-۲', '۱-۲', '۰-۳', '۱-۳', '۲-۳', 'سایر'],
            'points'  => 30,
            'order'   => 7,
        ],
    ];

    $st = $pdo->prepare(
        "INSERT INTO {$td} (label,bet_type,options_json,points,is_active,display_order) VALUES (:l,:bt,:oj,:p,1,:do)"
    );
    foreach ($defaults as $d) {
        $st->execute([
            ':l'  => $d['label'],
            ':bt' => $d['bet_type'],
            ':oj' => json_encode($d['options'], JSON_UNESCAPED_UNICODE),
            ':p'  => $d['points'],
            ':do' => $d['order'],
        ]);
    }
}

$rows = $pdo->query("SELECT * FROM {$td} ORDER BY display_order ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as &$r) {
    $r['options'] = json_decode($r['options_json'] ?? '[]', true) ?? [];
}
unset($r);
hmn_json_response(['success' => true, 'default_bets' => $rows]);
