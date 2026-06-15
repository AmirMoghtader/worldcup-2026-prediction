<?php

declare(strict_types=1);

function wc_default_settings_row(): array
{
    return [
        'site_name' => 'پیشبینی جام جهانی ۲۰۲۶',
        'brand_name' => '',
        'site_tagline' => 'پیش‌بینی زنده بازی‌ها، شرط‌های اختصاصی هر مسابقه و جدول امتیازات کاربران',
        'prediction_lock_minutes' => 10,
        'prediction_window_hours' => 48,
        'logo_url' => '/assets/worldcup.jpeg',
        'browser_icon_url' => '',
        'nav_logo_url' => '',
        'auth_logo_url' => '',
        'footer_logo_url' => '',
        'admin_logo_url' => '',
        'hero_banner_url' => '',
        'hero_banner_link_url' => '',
        'hero_banner_pure_mode' => 0,
        'hero_banner_mobile_url' => '',
        'hero_banner_height_desktop' => 220,
        'hero_banner_height_mobile' => 168,
        'rewards_hero_banner_url' => '',
        'rewards_hero_banner_link_url' => '',
        'rewards_hero_banner_pure_mode' => 0,
        'rewards_hero_banner_mobile_url' => '',
        'rewards_hero_banner_height_desktop' => 220,
        'rewards_hero_banner_height_mobile' => 168,
        'top_strip_banner_url' => '',
        'top_strip_banner_link_url' => '',
        'top_strip_banner_mobile_url' => '',
        'home_sidebar_banner_url' => '',
        'home_sidebar_banner_link_url' => '',
        'home_reward_slider_limit' => 3,
        'welcome_popup_image_url' => '',
        'welcome_popup_button_label' => 'شروع پیشبینی',
        'welcome_popup_button_url' => '/',
        'vip_bank_balance' => 0,
        'live_scores_enabled' => 0,
        'live_scores_provider' => 'varzesh3_html',
        'live_scores_feed_url' => '',
        'live_scores_refresh_minutes' => 5,
        'live_scores_last_sync_at' => null,
        'footer_note' => 'همه زمان‌ها به وقت ایران نمایش داده می‌شود.',
        'footer_credit' => 'طراحی و توسعه توسط ویرا وب آریا',
        'schedule_seeded' => 0,
    ];
}

function wc_default_bet_seed(): array
{
    return [
        [
            'label' => 'نتیجه بازی',
            'bet_type' => 'winner',
            'options' => ['تیم اول برنده', 'مساوی', 'تیم دوم برنده'],
            'points' => 15,
            'display_order' => 1,
        ],
        [
            'label' => 'تعداد گل بازی',
            'bet_type' => 'total_goals',
            'options' => ['۰ تا ۱ گل', '۲ تا ۳ گل', '۴ گل و بیشتر'],
            'points' => 12,
            'display_order' => 2,
        ],
        [
            'label' => 'هر دو تیم گل می‌زنند؟',
            'bet_type' => 'btts',
            'options' => ['بله', 'خیر'],
            'points' => 10,
            'display_order' => 3,
        ],
        [
            'label' => 'اولین تیم گل‌زن',
            'bet_type' => 'first_goal_team',
            'options' => ['تیم اول', 'تیم دوم', 'بدون گل'],
            'points' => 12,
            'display_order' => 4,
        ],
        [
            'label' => 'گل در نیمه اول؟',
            'bet_type' => 'first_half_goal',
            'options' => ['بله', 'خیر'],
            'points' => 8,
            'display_order' => 5,
        ],
        [
            'label' => 'ضربه پنالتی در بازی؟',
            'bet_type' => 'penalty',
            'options' => ['بله', 'خیر'],
            'points' => 10,
            'display_order' => 6,
        ],
        [
            'label' => 'نتیجه دقیق',
            'bet_type' => 'exact_score',
            'options' => ['۱-۰', '۲-۰', '۲-۱', '۳-۰', '۳-۱', '۳-۲', '۰-۰', '۱-۱', '۲-۲', '۰-۱', '۰-۲', '۱-۲', '۰-۳', '۱-۳', '۲-۳', 'سایر'],
            'points' => 30,
            'display_order' => 7,
        ],
    ];
}

function wc_ensure_column(PDO $pdo, string $table, string $column, string $definition): void
{
    $st = $pdo->prepare("SHOW COLUMNS FROM {$table} LIKE :column");
    $st->execute([':column' => $column]);
    if ($st->fetch(PDO::FETCH_ASSOC)) {
        return;
    }
    $pdo->exec("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
}

function wc_ensure_tables(PDO $pdo): void
{
    static $done = false;
    if ($done) {
        return;
    }

    $px = hmn_table('');
    $sql = "
    CREATE TABLE IF NOT EXISTS {$px}users (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        phone VARCHAR(20) NOT NULL UNIQUE,
        name VARCHAR(100) NOT NULL DEFAULT '',
        total_points INT NOT NULL DEFAULT 0,
        redeemed_points INT NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS {$px}admins (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        phone VARCHAR(20) NOT NULL UNIQUE,
        name VARCHAR(100) DEFAULT '',
        password_hash VARCHAR(255) NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS {$px}matches (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        team1 VARCHAR(100) NOT NULL DEFAULT 'تیم اول',
        team2 VARCHAR(100) NOT NULL DEFAULT 'تیم دوم',
        team1_flag VARCHAR(10) DEFAULT '',
        team2_flag VARCHAR(10) DEFAULT '',
        group_name VARCHAR(20) DEFAULT '',
        stage VARCHAR(20) DEFAULT 'group',
        match_datetime DATETIME NOT NULL,
        venue VARCHAR(200) DEFAULT '',
        is_open TINYINT(1) NOT NULL DEFAULT 1,
        status VARCHAR(20) NOT NULL DEFAULT 'upcoming',
        score_team1 TINYINT UNSIGNED NULL DEFAULT NULL,
        score_team2 TINYINT UNSIGNED NULL DEFAULT NULL,
        live_minute SMALLINT UNSIGNED NULL DEFAULT NULL,
        live_status_text VARCHAR(80) DEFAULT '',
        external_ref VARCHAR(190) DEFAULT '',
        result_data_json TEXT NULL DEFAULT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_datetime (match_datetime)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS {$px}default_bets (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        label VARCHAR(200) NOT NULL,
        bet_type VARCHAR(50) NOT NULL DEFAULT 'custom',
        options_json TEXT NOT NULL,
        points INT NOT NULL DEFAULT 10,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        display_order INT NOT NULL DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS {$px}bets (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        match_id INT UNSIGNED NOT NULL,
        default_bet_id INT UNSIGNED NULL DEFAULT NULL,
        label VARCHAR(200) NOT NULL,
        bet_type VARCHAR(50) DEFAULT 'custom',
        options_json TEXT NOT NULL,
        correct_option VARCHAR(200) NULL DEFAULT NULL,
        points INT NOT NULL DEFAULT 10,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        sync_with_default TINYINT(1) NOT NULL DEFAULT 0,
        display_order INT NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_match (match_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS {$px}predictions (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        match_id INT UNSIGNED NOT NULL,
        bet_id INT UNSIGNED NOT NULL,
        selected_option VARCHAR(200) NOT NULL,
        is_correct TINYINT(1) NULL DEFAULT NULL,
        points_earned INT NOT NULL DEFAULT 0,
        submitted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_user_bet (user_id, bet_id),
        INDEX idx_user (user_id),
        INDEX idx_match (match_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS {$px}settings (
        id INT UNSIGNED NOT NULL DEFAULT 1 PRIMARY KEY,
        site_name VARCHAR(200) DEFAULT 'پیشبینی جام جهانی ۲۰۲۶',
        brand_name VARCHAR(120) DEFAULT '',
        site_tagline VARCHAR(255) DEFAULT '',
        prediction_lock_minutes INT NOT NULL DEFAULT 10,
        prediction_window_hours INT NOT NULL DEFAULT 48,
        logo_url VARCHAR(500) DEFAULT '',
        browser_icon_url VARCHAR(500) DEFAULT '',
        nav_logo_url VARCHAR(500) DEFAULT '',
        auth_logo_url VARCHAR(500) DEFAULT '',
        footer_logo_url VARCHAR(500) DEFAULT '',
        admin_logo_url VARCHAR(500) DEFAULT '',
        hero_banner_url VARCHAR(500) DEFAULT '',
        hero_banner_link_url VARCHAR(500) DEFAULT '',
        hero_banner_pure_mode TINYINT(1) NOT NULL DEFAULT 0,
        hero_banner_mobile_url VARCHAR(500) DEFAULT '',
        hero_banner_height_desktop INT NOT NULL DEFAULT 220,
        hero_banner_height_mobile INT NOT NULL DEFAULT 168,
        rewards_hero_banner_url VARCHAR(500) DEFAULT '',
        rewards_hero_banner_link_url VARCHAR(500) DEFAULT '',
        rewards_hero_banner_pure_mode TINYINT(1) NOT NULL DEFAULT 0,
        rewards_hero_banner_mobile_url VARCHAR(500) DEFAULT '',
        rewards_hero_banner_height_desktop INT NOT NULL DEFAULT 220,
        rewards_hero_banner_height_mobile INT NOT NULL DEFAULT 168,
        top_strip_banner_url VARCHAR(500) DEFAULT '',
        top_strip_banner_link_url VARCHAR(500) DEFAULT '',
        top_strip_banner_mobile_url VARCHAR(500) DEFAULT '',
        home_sidebar_banner_url VARCHAR(500) DEFAULT '',
        home_sidebar_banner_link_url VARCHAR(500) DEFAULT '',
        home_reward_slider_limit INT NOT NULL DEFAULT 3,
        welcome_popup_image_url VARCHAR(500) DEFAULT '',
        welcome_popup_button_label VARCHAR(120) DEFAULT 'شروع پیشبینی',
        welcome_popup_button_url VARCHAR(500) DEFAULT '/',
        live_scores_enabled TINYINT(1) NOT NULL DEFAULT 0,
        live_scores_provider VARCHAR(40) NOT NULL DEFAULT 'varzesh3_html',
        live_scores_feed_url VARCHAR(500) DEFAULT '',
        live_scores_refresh_minutes INT NOT NULL DEFAULT 5,
        live_scores_last_sync_at DATETIME NULL DEFAULT NULL,
        footer_note VARCHAR(255) DEFAULT '',
        footer_credit VARCHAR(255) DEFAULT '',
        schedule_seeded TINYINT(1) NOT NULL DEFAULT 0,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS {$px}rewards (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(200) NOT NULL,
        description TEXT NULL DEFAULT NULL,
        image_url VARCHAR(500) DEFAULT '',
        reward_code VARCHAR(255) DEFAULT '',
        product_url VARCHAR(500) DEFAULT '',
        discount_percent SMALLINT UNSIGNED NOT NULL DEFAULT 0,
        points_cost INT NOT NULL DEFAULT 10,
        stock INT NULL DEFAULT NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        sort_order INT NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS {$px}ad_banners (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(200) NOT NULL DEFAULT '',
        image_url VARCHAR(500) DEFAULT '',
        link_url VARCHAR(500) DEFAULT '',
        placement VARCHAR(50) NOT NULL DEFAULT 'leaderboard_below',
        sort_order INT NOT NULL DEFAULT 0,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS {$px}reward_redemptions (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        reward_id INT UNSIGNED NOT NULL,
        user_id INT UNSIGNED NOT NULL,
        points_spent INT NOT NULL DEFAULT 0,
        reward_snapshot_json TEXT NULL DEFAULT NULL,
        delivered_code VARCHAR(255) DEFAULT '',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_reward (reward_id),
        INDEX idx_user (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS {$px}login_lockouts (
        phone VARCHAR(20) NOT NULL PRIMARY KEY,
        fail_count INT NOT NULL DEFAULT 0,
        strikes INT NOT NULL DEFAULT 0,
        locked_until DATETIME NULL DEFAULT NULL,
        last_fail_at DATETIME NULL DEFAULT NULL,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS {$px}vip_members (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        phone VARCHAR(20) NOT NULL UNIQUE,
        user_id INT UNSIGNED NULL DEFAULT NULL,
        current_balance BIGINT NOT NULL DEFAULT 500000,
        initial_balance BIGINT NOT NULL DEFAULT 500000,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_user (user_id),
        INDEX idx_active (is_active)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS {$px}vip_matches (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        source_match_id INT UNSIGNED NULL DEFAULT NULL,
        team1 VARCHAR(100) NOT NULL DEFAULT 'تیم اول',
        team2 VARCHAR(100) NOT NULL DEFAULT 'تیم دوم',
        team1_flag VARCHAR(10) DEFAULT '',
        team2_flag VARCHAR(10) DEFAULT '',
        group_name VARCHAR(20) DEFAULT '',
        stage VARCHAR(20) DEFAULT 'vip',
        match_datetime DATETIME NOT NULL,
        venue VARCHAR(200) DEFAULT '',
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        status VARCHAR(20) NOT NULL DEFAULT 'upcoming',
        score_team1 SMALLINT UNSIGNED NULL DEFAULT NULL,
        score_team2 SMALLINT UNSIGNED NULL DEFAULT NULL,
        result_option VARCHAR(20) DEFAULT '',
        settled_at DATETIME NULL DEFAULT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_datetime (match_datetime),
        INDEX idx_source_match (source_match_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

    CREATE TABLE IF NOT EXISTS {$px}vip_bets (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        vip_member_id INT UNSIGNED NOT NULL,
        user_id INT UNSIGNED NOT NULL,
        vip_match_id INT UNSIGNED NOT NULL,
        outcome VARCHAR(20) NOT NULL,
        amount BIGINT NOT NULL DEFAULT 0,
        payout_amount BIGINT NOT NULL DEFAULT 0,
        jackpot_payout BIGINT NOT NULL DEFAULT 0,
        exact_score_team1 SMALLINT UNSIGNED NULL DEFAULT NULL,
        exact_score_team2 SMALLINT UNSIGNED NULL DEFAULT NULL,
        exact_score_hit TINYINT(1) NOT NULL DEFAULT 0,
        result_status VARCHAR(20) NOT NULL DEFAULT 'open',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        settled_at DATETIME NULL DEFAULT NULL,
        UNIQUE KEY uq_user_match (user_id, vip_match_id),
        INDEX idx_vip_match (vip_match_id),
        INDEX idx_vip_member (vip_member_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";

    foreach (array_filter(array_map('trim', explode(';', $sql))) as $query) {
        $pdo->exec($query);
    }

    $matchesTable = hmn_table('matches');
    $settingsTable = hmn_table('settings');
    $betsTable = hmn_table('bets');
    $usersTable = hmn_table('users');

    wc_ensure_column($pdo, $matchesTable, 'result_data_json', "TEXT NULL DEFAULT NULL");
    wc_ensure_column($pdo, $matchesTable, 'live_minute', 'SMALLINT UNSIGNED NULL DEFAULT NULL');
    wc_ensure_column($pdo, $matchesTable, 'live_status_text', "VARCHAR(80) DEFAULT ''");
    wc_ensure_column($pdo, $matchesTable, 'external_ref', "VARCHAR(190) DEFAULT ''");
    wc_ensure_column($pdo, $settingsTable, 'site_tagline', "VARCHAR(255) DEFAULT ''");
    wc_ensure_column($pdo, $settingsTable, 'brand_name', "VARCHAR(120) DEFAULT ''");
    wc_ensure_column($pdo, $settingsTable, 'prediction_window_hours', 'INT NOT NULL DEFAULT 48');
    wc_ensure_column($pdo, $settingsTable, 'logo_url', "VARCHAR(500) DEFAULT ''");
    wc_ensure_column($pdo, $settingsTable, 'browser_icon_url', "VARCHAR(500) DEFAULT ''");
    wc_ensure_column($pdo, $settingsTable, 'nav_logo_url', "VARCHAR(500) DEFAULT ''");
    wc_ensure_column($pdo, $settingsTable, 'auth_logo_url', "VARCHAR(500) DEFAULT ''");
    wc_ensure_column($pdo, $settingsTable, 'footer_logo_url', "VARCHAR(500) DEFAULT ''");
    wc_ensure_column($pdo, $settingsTable, 'admin_logo_url', "VARCHAR(500) DEFAULT ''");
    wc_ensure_column($pdo, $settingsTable, 'hero_banner_url', "VARCHAR(500) DEFAULT ''");
    wc_ensure_column($pdo, $settingsTable, 'hero_banner_link_url', "VARCHAR(500) DEFAULT ''");
    wc_ensure_column($pdo, $settingsTable, 'hero_banner_pure_mode', 'TINYINT(1) NOT NULL DEFAULT 0');
    wc_ensure_column($pdo, $settingsTable, 'hero_banner_mobile_url', "VARCHAR(500) DEFAULT ''");
    wc_ensure_column($pdo, $settingsTable, 'hero_banner_height_desktop', 'INT NOT NULL DEFAULT 220');
    wc_ensure_column($pdo, $settingsTable, 'hero_banner_height_mobile', 'INT NOT NULL DEFAULT 168');
    wc_ensure_column($pdo, $settingsTable, 'rewards_hero_banner_url', "VARCHAR(500) DEFAULT ''");
    wc_ensure_column($pdo, $settingsTable, 'rewards_hero_banner_link_url', "VARCHAR(500) DEFAULT ''");
    wc_ensure_column($pdo, $settingsTable, 'rewards_hero_banner_pure_mode', 'TINYINT(1) NOT NULL DEFAULT 0');
    wc_ensure_column($pdo, $settingsTable, 'rewards_hero_banner_mobile_url', "VARCHAR(500) DEFAULT ''");
    wc_ensure_column($pdo, $settingsTable, 'rewards_hero_banner_height_desktop', 'INT NOT NULL DEFAULT 220');
    wc_ensure_column($pdo, $settingsTable, 'rewards_hero_banner_height_mobile', 'INT NOT NULL DEFAULT 168');
    wc_ensure_column($pdo, $settingsTable, 'top_strip_banner_url', "VARCHAR(500) DEFAULT ''");
    wc_ensure_column($pdo, $settingsTable, 'top_strip_banner_link_url', "VARCHAR(500) DEFAULT ''");
    wc_ensure_column($pdo, $settingsTable, 'top_strip_banner_mobile_url', "VARCHAR(500) DEFAULT ''");
    wc_ensure_column($pdo, $settingsTable, 'home_sidebar_banner_url', "VARCHAR(500) DEFAULT ''");
    wc_ensure_column($pdo, $settingsTable, 'home_sidebar_banner_link_url', "VARCHAR(500) DEFAULT ''");
    wc_ensure_column($pdo, $settingsTable, 'home_reward_slider_limit', 'INT NOT NULL DEFAULT 3');
    wc_ensure_column($pdo, $settingsTable, 'welcome_popup_image_url', "VARCHAR(500) DEFAULT ''");
    wc_ensure_column($pdo, $settingsTable, 'welcome_popup_button_label', "VARCHAR(120) DEFAULT 'شروع پیشبینی'");
    wc_ensure_column($pdo, $settingsTable, 'welcome_popup_button_url', "VARCHAR(500) DEFAULT '/'");
    wc_ensure_column($pdo, $settingsTable, 'vip_bank_balance', 'BIGINT NOT NULL DEFAULT 0');
    wc_ensure_column($pdo, $settingsTable, 'live_scores_enabled', 'TINYINT(1) NOT NULL DEFAULT 0');
    wc_ensure_column($pdo, $settingsTable, 'live_scores_provider', "VARCHAR(40) NOT NULL DEFAULT 'varzesh3_html'");
    wc_ensure_column($pdo, $settingsTable, 'live_scores_feed_url', "VARCHAR(500) DEFAULT ''");
    wc_ensure_column($pdo, $settingsTable, 'live_scores_refresh_minutes', 'INT NOT NULL DEFAULT 5');
    wc_ensure_column($pdo, $settingsTable, 'live_scores_last_sync_at', 'DATETIME NULL DEFAULT NULL');
    wc_ensure_column($pdo, $settingsTable, 'footer_note', "VARCHAR(255) DEFAULT ''");
    wc_ensure_column($pdo, $settingsTable, 'footer_credit', "VARCHAR(255) DEFAULT ''");
    wc_ensure_column($pdo, $settingsTable, 'schedule_seeded', 'TINYINT(1) NOT NULL DEFAULT 0');
    wc_ensure_column($pdo, $betsTable, 'default_bet_id', 'INT UNSIGNED NULL DEFAULT NULL');
    wc_ensure_column($pdo, $betsTable, 'sync_with_default', 'TINYINT(1) NOT NULL DEFAULT 0');
    wc_ensure_column($pdo, $betsTable, 'display_order', 'INT NOT NULL DEFAULT 0');
    wc_ensure_column($pdo, $usersTable, 'redeemed_points', 'INT NOT NULL DEFAULT 0');
    wc_ensure_column($pdo, hmn_table('rewards'), 'product_url', "VARCHAR(500) DEFAULT ''");
    wc_ensure_column($pdo, hmn_table('rewards'), 'discount_percent', 'SMALLINT UNSIGNED NOT NULL DEFAULT 0');
    wc_ensure_column($pdo, hmn_table('vip_members'), 'user_id', 'INT UNSIGNED NULL DEFAULT NULL');
    wc_ensure_column($pdo, hmn_table('vip_members'), 'current_balance', 'BIGINT NOT NULL DEFAULT 500000');
    wc_ensure_column($pdo, hmn_table('vip_members'), 'initial_balance', 'BIGINT NOT NULL DEFAULT 500000');
    wc_ensure_column($pdo, hmn_table('vip_members'), 'is_active', 'TINYINT(1) NOT NULL DEFAULT 1');
    wc_ensure_column($pdo, hmn_table('vip_matches'), 'source_match_id', 'INT UNSIGNED NULL DEFAULT NULL');
    wc_ensure_column($pdo, hmn_table('vip_matches'), 'score_team1', 'SMALLINT UNSIGNED NULL DEFAULT NULL');
    wc_ensure_column($pdo, hmn_table('vip_matches'), 'score_team2', 'SMALLINT UNSIGNED NULL DEFAULT NULL');
    wc_ensure_column($pdo, hmn_table('vip_matches'), 'result_option', "VARCHAR(20) DEFAULT ''");
    wc_ensure_column($pdo, hmn_table('vip_matches'), 'settled_at', 'DATETIME NULL DEFAULT NULL');
    wc_ensure_column($pdo, hmn_table('vip_bets'), 'payout_amount', 'BIGINT NOT NULL DEFAULT 0');
    wc_ensure_column($pdo, hmn_table('vip_bets'), 'jackpot_payout', 'BIGINT NOT NULL DEFAULT 0');
    wc_ensure_column($pdo, hmn_table('vip_bets'), 'exact_score_team1', 'SMALLINT UNSIGNED NULL DEFAULT NULL');
    wc_ensure_column($pdo, hmn_table('vip_bets'), 'exact_score_team2', 'SMALLINT UNSIGNED NULL DEFAULT NULL');
    wc_ensure_column($pdo, hmn_table('vip_bets'), 'exact_score_hit', 'TINYINT(1) NOT NULL DEFAULT 0');
    wc_ensure_column($pdo, hmn_table('vip_bets'), 'result_status', "VARCHAR(20) NOT NULL DEFAULT 'open'");
    wc_ensure_column($pdo, hmn_table('vip_bets'), 'settled_at', 'DATETIME NULL DEFAULT NULL');

    $defaults = wc_default_settings_row();
    $existing = $pdo->query("SELECT * FROM {$settingsTable} WHERE id = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if (!$existing) {
        $pdo->prepare(
            "INSERT INTO {$settingsTable}
            (id, site_name, brand_name, site_tagline, prediction_lock_minutes, prediction_window_hours, logo_url, browser_icon_url, nav_logo_url, auth_logo_url, footer_logo_url, admin_logo_url, hero_banner_url, hero_banner_link_url, hero_banner_pure_mode, hero_banner_mobile_url, hero_banner_height_desktop, hero_banner_height_mobile, rewards_hero_banner_url, rewards_hero_banner_link_url, rewards_hero_banner_pure_mode, rewards_hero_banner_mobile_url, rewards_hero_banner_height_desktop, rewards_hero_banner_height_mobile, top_strip_banner_url, top_strip_banner_link_url, top_strip_banner_mobile_url, home_sidebar_banner_url, home_sidebar_banner_link_url, home_reward_slider_limit, welcome_popup_image_url, welcome_popup_button_label, welcome_popup_button_url, vip_bank_balance, live_scores_enabled, live_scores_provider, live_scores_feed_url, live_scores_refresh_minutes, live_scores_last_sync_at, footer_note, footer_credit, schedule_seeded)
            VALUES (1, :site_name, :brand_name, :site_tagline, :prediction_lock_minutes, :prediction_window_hours, :logo_url, :browser_icon_url, :nav_logo_url, :auth_logo_url, :footer_logo_url, :admin_logo_url, :hero_banner_url, :hero_banner_link_url, :hero_banner_pure_mode, :hero_banner_mobile_url, :hero_banner_height_desktop, :hero_banner_height_mobile, :rewards_hero_banner_url, :rewards_hero_banner_link_url, :rewards_hero_banner_pure_mode, :rewards_hero_banner_mobile_url, :rewards_hero_banner_height_desktop, :rewards_hero_banner_height_mobile, :top_strip_banner_url, :top_strip_banner_link_url, :top_strip_banner_mobile_url, :home_sidebar_banner_url, :home_sidebar_banner_link_url, :home_reward_slider_limit, :welcome_popup_image_url, :welcome_popup_button_label, :welcome_popup_button_url, :vip_bank_balance, :live_scores_enabled, :live_scores_provider, :live_scores_feed_url, :live_scores_refresh_minutes, :live_scores_last_sync_at, :footer_note, :footer_credit, :schedule_seeded)"
        )->execute($defaults);
    } else {
        $merged = array_merge($defaults, $existing);
        $updatePayload = [
            'site_name' => $merged['site_name'],
            'brand_name' => $merged['brand_name'],
            'site_tagline' => $merged['site_tagline'],
            'prediction_lock_minutes' => (int)$merged['prediction_lock_minutes'],
            'prediction_window_hours' => (int)$merged['prediction_window_hours'],
            'logo_url' => $merged['logo_url'],
            'browser_icon_url' => $merged['browser_icon_url'],
            'nav_logo_url' => $merged['nav_logo_url'],
            'auth_logo_url' => $merged['auth_logo_url'],
            'footer_logo_url' => $merged['footer_logo_url'],
            'admin_logo_url' => $merged['admin_logo_url'],
            'hero_banner_url' => $merged['hero_banner_url'],
            'hero_banner_link_url' => $merged['hero_banner_link_url'],
            'hero_banner_pure_mode' => (int)$merged['hero_banner_pure_mode'],
            'hero_banner_mobile_url' => $merged['hero_banner_mobile_url'],
            'hero_banner_height_desktop' => (int)$merged['hero_banner_height_desktop'],
            'hero_banner_height_mobile' => (int)$merged['hero_banner_height_mobile'],
            'rewards_hero_banner_url' => $merged['rewards_hero_banner_url'],
            'rewards_hero_banner_link_url' => $merged['rewards_hero_banner_link_url'],
            'rewards_hero_banner_pure_mode' => (int)$merged['rewards_hero_banner_pure_mode'],
            'rewards_hero_banner_mobile_url' => $merged['rewards_hero_banner_mobile_url'],
            'rewards_hero_banner_height_desktop' => (int)$merged['rewards_hero_banner_height_desktop'],
            'rewards_hero_banner_height_mobile' => (int)$merged['rewards_hero_banner_height_mobile'],
            'top_strip_banner_url' => $merged['top_strip_banner_url'],
            'top_strip_banner_link_url' => $merged['top_strip_banner_link_url'],
            'top_strip_banner_mobile_url' => $merged['top_strip_banner_mobile_url'],
            'home_sidebar_banner_url' => $merged['home_sidebar_banner_url'],
            'home_sidebar_banner_link_url' => $merged['home_sidebar_banner_link_url'],
            'home_reward_slider_limit' => (int)$merged['home_reward_slider_limit'],
            'welcome_popup_image_url' => $merged['welcome_popup_image_url'],
            'welcome_popup_button_label' => $merged['welcome_popup_button_label'],
            'welcome_popup_button_url' => $merged['welcome_popup_button_url'],
            'vip_bank_balance' => (int)$merged['vip_bank_balance'],
            'live_scores_enabled' => (int)$merged['live_scores_enabled'],
            'live_scores_provider' => $merged['live_scores_provider'],
            'live_scores_feed_url' => $merged['live_scores_feed_url'],
            'live_scores_refresh_minutes' => (int)$merged['live_scores_refresh_minutes'],
            'live_scores_last_sync_at' => $merged['live_scores_last_sync_at'],
            'footer_note' => $merged['footer_note'],
            'footer_credit' => $merged['footer_credit'],
            'schedule_seeded' => (int)$merged['schedule_seeded'],
        ];
        $pdo->prepare(
            "UPDATE {$settingsTable} SET
            site_name = :site_name,
            brand_name = :brand_name,
            site_tagline = :site_tagline,
            prediction_lock_minutes = :prediction_lock_minutes,
            prediction_window_hours = :prediction_window_hours,
            logo_url = :logo_url,
            browser_icon_url = :browser_icon_url,
            nav_logo_url = :nav_logo_url,
            auth_logo_url = :auth_logo_url,
            footer_logo_url = :footer_logo_url,
            admin_logo_url = :admin_logo_url,
            hero_banner_url = :hero_banner_url,
            hero_banner_link_url = :hero_banner_link_url,
            hero_banner_pure_mode = :hero_banner_pure_mode,
            hero_banner_mobile_url = :hero_banner_mobile_url,
            hero_banner_height_desktop = :hero_banner_height_desktop,
            hero_banner_height_mobile = :hero_banner_height_mobile,
            rewards_hero_banner_url = :rewards_hero_banner_url,
            rewards_hero_banner_link_url = :rewards_hero_banner_link_url,
            rewards_hero_banner_pure_mode = :rewards_hero_banner_pure_mode,
            rewards_hero_banner_mobile_url = :rewards_hero_banner_mobile_url,
            rewards_hero_banner_height_desktop = :rewards_hero_banner_height_desktop,
            rewards_hero_banner_height_mobile = :rewards_hero_banner_height_mobile,
            top_strip_banner_url = :top_strip_banner_url,
            top_strip_banner_link_url = :top_strip_banner_link_url,
            top_strip_banner_mobile_url = :top_strip_banner_mobile_url,
            home_sidebar_banner_url = :home_sidebar_banner_url,
            home_sidebar_banner_link_url = :home_sidebar_banner_link_url,
            home_reward_slider_limit = :home_reward_slider_limit,
            welcome_popup_image_url = :welcome_popup_image_url,
            welcome_popup_button_label = :welcome_popup_button_label,
            welcome_popup_button_url = :welcome_popup_button_url,
            vip_bank_balance = :vip_bank_balance,
            live_scores_enabled = :live_scores_enabled,
            live_scores_provider = :live_scores_provider,
            live_scores_feed_url = :live_scores_feed_url,
            live_scores_refresh_minutes = :live_scores_refresh_minutes,
            live_scores_last_sync_at = :live_scores_last_sync_at,
            footer_note = :footer_note,
            footer_credit = :footer_credit,
            schedule_seeded = :schedule_seeded
            WHERE id = 1"
        )->execute($updatePayload);
    }

    $matchCount = (int)$pdo->query("SELECT COUNT(*) FROM {$matchesTable}")->fetchColumn();
    if ($matchCount > 0) {
        $pdo->exec("UPDATE {$settingsTable} SET schedule_seeded = 1 WHERE id = 1");
    }

    wc_seed_default_bets($pdo);
    $done = true;
}

function wc_vip_default_credit(): int
{
    return 500000;
}

function wc_vip_match_result_option(?int $score1, ?int $score2): string
{
    if ($score1 === null || $score2 === null) {
        return '';
    }
    if ($score1 > $score2) {
        return 'team1';
    }
    if ($score2 > $score1) {
        return 'team2';
    }
    return 'draw';
}

function wc_get_user_vip(PDO $pdo, int $userId, ?string $phone = null): ?array
{
    if ($userId <= 0) {
        return null;
    }

    $vipTable = hmn_table('vip_members');
    $vip = null;

    $byUser = $pdo->prepare("SELECT * FROM {$vipTable} WHERE user_id = :user_id LIMIT 1");
    $byUser->execute([':user_id' => $userId]);
    $vip = $byUser->fetch(PDO::FETCH_ASSOC) ?: null;

    if (!$vip && $phone) {
        $variants = hmn_phone_variants($phone);
        $placeholders = implode(',', array_fill(0, count($variants), '?'));
        $byPhone = $pdo->prepare("SELECT * FROM {$vipTable} WHERE phone IN ({$placeholders}) LIMIT 1");
        $byPhone->execute($variants);
        $vip = $byPhone->fetch(PDO::FETCH_ASSOC) ?: null;
        if ($vip && (int)($vip['user_id'] ?? 0) !== $userId) {
            $cleaned = hmn_normalize_phone($phone);
            $pdo->prepare("UPDATE {$vipTable} SET user_id = :user_id, phone = :phone WHERE id = :id")
                ->execute([
                    ':user_id' => $userId,
                    ':phone' => $cleaned,
                    ':id' => $vip['id'],
                ]);
            $vip['user_id'] = $userId;
            $vip['phone'] = $cleaned;
        }
    }

    if (!$vip || (int)($vip['is_active'] ?? 0) !== 1) {
        return null;
    }

    $vip['current_balance'] = (int)($vip['current_balance'] ?? wc_vip_default_credit());
    $vip['initial_balance'] = (int)($vip['initial_balance'] ?? wc_vip_default_credit());
    return $vip;
}

function wc_attach_vip_to_user(PDO $pdo, array $user): array
{
    if (empty($user['id'])) {
        $user['is_vip'] = 0;
        return $user;
    }

    $vip = wc_get_user_vip($pdo, (int)$user['id'], (string)($user['phone'] ?? ''));
    $user['is_vip'] = $vip ? 1 : 0;
    if ($vip) {
        $user['vip'] = [
            'id' => (int)$vip['id'],
            'phone' => $vip['phone'],
            'current_balance' => (int)$vip['current_balance'],
            'initial_balance' => (int)$vip['initial_balance'],
            'is_active' => (int)$vip['is_active'],
        ];
    }
    return $user;
}

function wc_vip_match_row_for_display(array $row): array
{
    if (!empty($row['match_datetime'])) {
        $row['match_datetime_raw'] = $row['match_datetime'];
        $row['match_timestamp'] = wc_match_storage_timestamp((string)$row['match_datetime']);
        $row['match_datetime'] = wc_match_display_datetime((string)$row['match_datetime']);
    }
    if (empty($row['result_option'])) {
        $row['result_option'] = wc_vip_match_result_option(
            isset($row['score_team1']) ? (int)$row['score_team1'] : null,
            isset($row['score_team2']) ? (int)$row['score_team2'] : null
        );
    }
    return $row;
}

function wc_settle_vip_match(PDO $pdo, int $vipMatchId): array
{
    $matchTable = hmn_table('vip_matches');
    $betTable = hmn_table('vip_bets');
    $memberTable = hmn_table('vip_members');
    $settingsTable = hmn_table('settings');

    $matchSt = $pdo->prepare("SELECT * FROM {$matchTable} WHERE id = :id LIMIT 1 FOR UPDATE");
    $matchSt->execute([':id' => $vipMatchId]);
    $match = $matchSt->fetch(PDO::FETCH_ASSOC);
    if (!$match) {
        throw new RuntimeException('بازی VIP پیدا نشد.');
    }
    if (!empty($match['settled_at'])) {
        return ['already_settled' => true, 'winners' => 0, 'total_pool' => 0];
    }

    $resultOption = trim((string)($match['result_option'] ?? ''));
    if ($resultOption === '') {
        $resultOption = wc_vip_match_result_option(
            isset($match['score_team1']) ? (int)$match['score_team1'] : null,
            isset($match['score_team2']) ? (int)$match['score_team2'] : null
        );
    }
    if (!in_array($resultOption, ['team1', 'draw', 'team2'], true)) {
        throw new RuntimeException('برای این بازی نتیجه معتبر ثبت نشده است.');
    }

    $betsSt = $pdo->prepare("SELECT * FROM {$betTable} WHERE vip_match_id = :match_id ORDER BY id ASC FOR UPDATE");
    $betsSt->execute([':match_id' => $vipMatchId]);
    $bets = $betsSt->fetchAll(PDO::FETCH_ASSOC);

    $totalPool = 0;
    $outcomePool = 0;
    $winnerPool = 0;
    $bankContribution = 0;
    foreach ($bets as $bet) {
        $amount = (int)($bet['amount'] ?? 0);
        $totalPool += $amount;
        $cut = (int)floor($amount * 0.10);
        $bankContribution += $cut;
        $netAmount = $amount - $cut;
        $outcomePool += $netAmount;
        if (($bet['outcome'] ?? '') === $resultOption) {
            $winnerPool += $amount;
        }
    }

    $bankSt = $pdo->query("SELECT vip_bank_balance FROM {$settingsTable} WHERE id = 1 LIMIT 1 FOR UPDATE");
    $bankBalance = (int)($bankSt->fetchColumn() ?: 0);
    $bankBalance += $bankContribution;

    $updateBet = $pdo->prepare(
        "UPDATE {$betTable}
         SET payout_amount = :payout_amount,
             jackpot_payout = :jackpot_payout,
             exact_score_hit = :exact_score_hit,
             result_status = :result_status,
             settled_at = NOW()
         WHERE id = :id"
    );
    $creditMember = $pdo->prepare("UPDATE {$memberTable} SET current_balance = current_balance + :amount WHERE id = :id");

    $exactWinners = [];
    foreach ($bets as $bet) {
        if (
            $bet['exact_score_team1'] !== null &&
            $bet['exact_score_team2'] !== null &&
            (int)$bet['exact_score_team1'] === (int)$match['score_team1'] &&
            (int)$bet['exact_score_team2'] === (int)$match['score_team2']
        ) {
            $exactWinners[] = (int)$bet['id'];
        }
    }

    $exactWinnerCount = count($exactWinners);
    $jackpotBaseShare = $exactWinnerCount > 0 ? (int)floor($bankBalance / $exactWinnerCount) : 0;
    $jackpotRemainder = $exactWinnerCount > 0 ? ($bankBalance % $exactWinnerCount) : 0;
    $jackpotByBetId = [];
    if ($exactWinnerCount > 0) {
        foreach ($exactWinners as $index => $betId) {
            $jackpotByBetId[$betId] = $jackpotBaseShare + ($index < $jackpotRemainder ? 1 : 0);
        }
    }

    $winnerCount = 0;
    foreach ($bets as $bet) {
        $amount = (int)($bet['amount'] ?? 0);
        $isWinner = ($bet['outcome'] ?? '') === $resultOption;
        $payout = ($isWinner && $winnerPool > 0)
            ? (int)floor(($outcomePool * $amount) / $winnerPool)
            : 0;
        $betId = (int)$bet['id'];
        $exactHit = isset($jackpotByBetId[$betId]);
        $jackpotPayout = $exactHit ? (int)$jackpotByBetId[$betId] : 0;
        $status = $isWinner ? 'won' : 'lost';
        $updateBet->execute([
            ':payout_amount' => $payout,
            ':jackpot_payout' => $jackpotPayout,
            ':exact_score_hit' => $exactHit ? 1 : 0,
            ':result_status' => $status,
            ':id' => $bet['id'],
        ]);
        if ($payout > 0) {
            $creditMember->execute([
                ':amount' => $payout,
                ':id' => $bet['vip_member_id'],
            ]);
        }
        if ($jackpotPayout > 0) {
            $creditMember->execute([
                ':amount' => $jackpotPayout,
                ':id' => $bet['vip_member_id'],
            ]);
        }
        if ($isWinner) {
            $winnerCount++;
        }
    }

    $bankBalance = count($exactWinners) > 0 ? 0 : $bankBalance;
    $pdo->prepare("UPDATE {$settingsTable} SET vip_bank_balance = :balance WHERE id = 1")
        ->execute([':balance' => $bankBalance]);

    $pdo->prepare(
        "UPDATE {$matchTable}
         SET result_option = :result_option, status = 'finished', settled_at = NOW()
         WHERE id = :id"
    )->execute([
        ':result_option' => $resultOption,
        ':id' => $vipMatchId,
    ]);

    return [
        'already_settled' => false,
        'winners' => $winnerCount,
        'winner_pool' => $winnerPool,
        'total_pool' => $totalPool,
        'outcome_pool' => $outcomePool,
        'bank_contribution' => $bankContribution,
        'exact_winners' => $exactWinnerCount,
        'jackpot_paid_total' => $exactWinnerCount > 0 ? array_sum($jackpotByBetId) : 0,
        'jackpot_paid_per_winner' => $jackpotBaseShare,
        'vip_bank_balance' => $bankBalance,
        'result_option' => $resultOption,
    ];
}

function wc_seed_default_bets(PDO $pdo): void
{
    $table = hmn_table('default_bets');
    $count = (int)$pdo->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
    if ($count > 0) {
        return;
    }

    $st = $pdo->prepare(
        "INSERT INTO {$table} (label, bet_type, options_json, points, is_active, display_order)
        VALUES (:label, :bet_type, :options_json, :points, 1, :display_order)"
    );

    foreach (wc_default_bet_seed() as $row) {
        $st->execute([
            ':label' => $row['label'],
            ':bet_type' => $row['bet_type'],
            ':options_json' => json_encode($row['options'], JSON_UNESCAPED_UNICODE),
            ':points' => $row['points'],
            ':display_order' => $row['display_order'],
        ]);
    }
}

function wc_seed_matches(PDO $pdo, bool $force = false): int
{
    $table = hmn_table('matches');
    $count = (int)$pdo->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
    if ($count > 0 && !$force) {
        return 0;
    }

    if ($force && $count > 0) {
        $pdo->exec("DELETE FROM {$table}");
    }

    $rows = require __DIR__ . '/seed_matches.php';
    $st = $pdo->prepare(
        "INSERT INTO {$table}
        (team1, team2, team1_flag, team2_flag, group_name, stage, match_datetime, venue, is_open, status)
        VALUES
        (:team1, :team2, :team1_flag, :team2_flag, :group_name, :stage, :match_datetime, :venue, :is_open, :status)"
    );

    foreach ($rows as $row) {
        $st->execute([
            ':team1' => $row['team1'],
            ':team2' => $row['team2'],
            ':team1_flag' => $row['team1_flag'],
            ':team2_flag' => $row['team2_flag'],
            ':group_name' => $row['group_name'],
            ':stage' => $row['stage'],
            ':match_datetime' => $row['match_datetime'],
            ':venue' => $row['venue'],
            ':is_open' => $row['is_open'],
            ':status' => $row['status'],
        ]);
    }

    $settingsTable = hmn_table('settings');
    $pdo->exec("UPDATE {$settingsTable} SET schedule_seeded = 1 WHERE id = 1");

    return count($rows);
}

function wc_official_schedule_2026(): array
{
    static $rows = null;
    if ($rows === null) {
        $rows = require __DIR__ . '/official_schedule_2026.php';
    }
    return $rows;
}

function wc_sync_official_schedule_2026(PDO $pdo): int
{
    $table = hmn_table('matches');
    $rows = $pdo->query("SELECT id, team1, team2, stage, match_datetime FROM {$table}")->fetchAll(PDO::FETCH_ASSOC);
    if (!$rows) {
        return 0;
    }

    $officialByPair = [];
    foreach (wc_official_schedule_2026() as $item) {
        $key = wc_team_key((string)$item['team1']) . '|' . wc_team_key((string)$item['team2']);
        $officialByPair[$key] = (string)$item['match_datetime'];
        $officialByPair[wc_team_key((string)$item['team2']) . '|' . wc_team_key((string)$item['team1'])] = (string)$item['match_datetime'];
    }

    $updated = 0;
    $updateSt = $pdo->prepare("UPDATE {$table} SET match_datetime = :match_datetime WHERE id = :id");
    foreach ($rows as $row) {
        if (($row['stage'] ?? 'group') !== 'group') {
            continue;
        }
        $key = wc_team_key((string)$row['team1']) . '|' . wc_team_key((string)$row['team2']);
        $official = $officialByPair[$key] ?? null;
        if (!$official || $official === (string)$row['match_datetime']) {
            continue;
        }
        $updateSt->execute([
            ':match_datetime' => $official,
            ':id' => (int)$row['id'],
        ]);
        $updated++;
    }

    return $updated;
}

function wc_sync_default_bets_to_match(PDO $pdo, int $matchId): array
{
    $defaultTable = hmn_table('default_bets');
    $betTable = hmn_table('bets');

    $defaults = $pdo->query("SELECT * FROM {$defaultTable} ORDER BY display_order ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
    $existing = $pdo->prepare("SELECT * FROM {$betTable} WHERE match_id = :match_id AND default_bet_id IS NOT NULL");
    $existing->execute([':match_id' => $matchId]);

    $byDefaultId = [];
    foreach ($existing->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $byDefaultId[(int)$row['default_bet_id']] = $row;
    }

    $inserted = 0;
    $updated = 0;
    $insertSt = $pdo->prepare(
        "INSERT INTO {$betTable}
        (match_id, default_bet_id, label, bet_type, options_json, points, is_active, sync_with_default, display_order)
        VALUES
        (:match_id, :default_bet_id, :label, :bet_type, :options_json, :points, :is_active, 1, :display_order)"
    );
    $updateSt = $pdo->prepare(
        "UPDATE {$betTable} SET
        label = :label,
        bet_type = :bet_type,
        options_json = :options_json,
        points = :points,
        is_active = :is_active,
        display_order = :display_order
        WHERE id = :id"
    );

    foreach ($defaults as $defaultBet) {
        $defaultId = (int)$defaultBet['id'];
        if (isset($byDefaultId[$defaultId])) {
            if ((int)$byDefaultId[$defaultId]['sync_with_default'] === 1) {
                $updateSt->execute([
                    ':label' => $defaultBet['label'],
                    ':bet_type' => $defaultBet['bet_type'],
                    ':options_json' => $defaultBet['options_json'],
                    ':points' => (int)$defaultBet['points'],
                    ':is_active' => (int)$defaultBet['is_active'],
                    ':display_order' => (int)$defaultBet['display_order'],
                    ':id' => (int)$byDefaultId[$defaultId]['id'],
                ]);
                $updated++;
            }
            continue;
        }

        $insertSt->execute([
            ':match_id' => $matchId,
            ':default_bet_id' => $defaultId,
            ':label' => $defaultBet['label'],
            ':bet_type' => $defaultBet['bet_type'],
            ':options_json' => $defaultBet['options_json'],
            ':points' => (int)$defaultBet['points'],
            ':is_active' => (int)$defaultBet['is_active'],
            ':display_order' => (int)$defaultBet['display_order'],
        ]);
        $inserted++;
    }

    return ['inserted' => $inserted, 'updated' => $updated];
}

function wc_sync_default_bets_to_all_matches(PDO $pdo): array
{
    $matchTable = hmn_table('matches');
    $matchIds = $pdo->query("SELECT id FROM {$matchTable}")->fetchAll(PDO::FETCH_COLUMN);
    $inserted = 0;
    $updated = 0;

    foreach ($matchIds as $matchId) {
        $result = wc_sync_default_bets_to_match($pdo, (int)$matchId);
        $inserted += $result['inserted'];
        $updated += $result['updated'];
    }

    return ['matches' => count($matchIds), 'inserted' => $inserted, 'updated' => $updated];
}

function wc_get_settings(PDO $pdo): array
{
    $table = hmn_table('settings');
    $defaults = wc_default_settings_row();
    try {
        $row = $pdo->query("SELECT * FROM {$table} WHERE id = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        return array_merge($defaults, $row ?: []);
    } catch (Throwable $e) {
        return $defaults;
    }
}

function wc_match_storage_timezone(): DateTimeZone
{
    return new DateTimeZone('UTC');
}

function wc_display_timezone(): DateTimeZone
{
    return new DateTimeZone('Asia/Tehran');
}

function wc_match_datetime_from_storage(string $value): ?DateTimeImmutable
{
    $value = trim($value);
    if ($value === '') {
        return null;
    }
    $dt = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $value, wc_match_storage_timezone());
    if ($dt instanceof DateTimeImmutable) {
        return $dt;
    }
    try {
        return new DateTimeImmutable($value, wc_match_storage_timezone());
    } catch (Throwable $e) {
        return null;
    }
}

function wc_match_storage_timestamp(string $value): ?int
{
    $dt = wc_match_datetime_from_storage($value);
    return $dt ? $dt->getTimestamp() : null;
}

function wc_match_display_datetime(string $value): string
{
    $dt = wc_match_datetime_from_storage($value);
    return $dt ? $dt->setTimezone(wc_display_timezone())->format('Y-m-d H:i:s') : $value;
}

function wc_match_input_tehran_to_storage(string $value): string
{
    $normalized = trim(str_replace('T', ' ', $value));
    if ($normalized === '') {
        return '';
    }
    if (strlen($normalized) === 16) {
        $normalized .= ':00';
    }
    $dt = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $normalized, wc_display_timezone());
    if (!$dt) {
        return $normalized;
    }
    return $dt->setTimezone(wc_match_storage_timezone())->format('Y-m-d H:i:s');
}

function wc_match_row_for_display(array $row): array
{
    if (!empty($row['match_datetime'])) {
        $row['match_datetime_raw'] = $row['match_datetime'];
        $row['match_timestamp'] = wc_match_storage_timestamp((string)$row['match_datetime']);
        $row['match_datetime'] = wc_match_display_datetime((string)$row['match_datetime']);
    }
    return $row;
}

function wc_is_prediction_open(array $match, int $lockMinutes, int $windowHours = 48): bool
{
    if (!(int)($match['is_open'] ?? 0)) {
        return false;
    }
    if (($match['status'] ?? 'upcoming') !== 'upcoming') {
        return false;
    }
    $matchTime = wc_match_storage_timestamp((string)($match['match_datetime_raw'] ?? $match['match_datetime'] ?? ''));
    if (!$matchTime) {
        return false;
    }
    $now = time();
    if ($now >= ($matchTime - ($lockMinutes * 60))) {
        return false;
    }
    if ($matchTime > ($now + ($windowHours * 3600))) {
        return false;
    }
    return true;
}

function wc_normalize_digits(string $value): string
{
    return strtr($value, [
        '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
        '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
        '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
    ]);
}

function wc_match_option_alias(array $options, array $aliases): ?string
{
    $normalizedAliases = array_map(
        static function (string $value): string {
            $value = trim(wc_normalize_digits($value));
            return function_exists('mb_strtolower') ? mb_strtolower($value) : strtolower($value);
        },
        array_filter($aliases, static fn($value): bool => $value !== null && $value !== '')
    );

    foreach ($options as $option) {
        $normalizedOption = trim(wc_normalize_digits((string)$option));
        $normalizedOption = function_exists('mb_strtolower') ? mb_strtolower($normalizedOption) : strtolower($normalizedOption);
        if (in_array($normalizedOption, $normalizedAliases, true)) {
            return (string)$option;
        }
    }

    foreach ($options as $option) {
        $normalizedOption = trim(wc_normalize_digits((string)$option));
        $normalizedOption = function_exists('mb_strtolower') ? mb_strtolower($normalizedOption) : strtolower($normalizedOption);
        foreach ($normalizedAliases as $alias) {
            if ($alias !== '' && ((strpos($normalizedOption, $alias) !== false) || (strpos($alias, $normalizedOption) !== false))) {
                return (string)$option;
            }
        }
    }

    return null;
}

function wc_match_total_goals_option(array $options, int $totalGoals): ?string
{
    foreach ($options as $option) {
        $normalized = wc_normalize_digits(trim((string)$option));
        if (preg_match('/(\d+)\s*(?:تا|-|–|—)\s*(\d+)/u', $normalized, $m)) {
            if ($totalGoals >= (int)$m[1] && $totalGoals <= (int)$m[2]) {
                return (string)$option;
            }
        }
        if (preg_match('/(\d+)\s*(?:گل)?\s*(?:و|یا)?\s*(?:بیشتر|\+)/u', $normalized, $m)) {
            if ($totalGoals >= (int)$m[1]) {
                return (string)$option;
            }
        }
        if (preg_match('/کمتر از\s*(\d+)/u', $normalized, $m)) {
            if ($totalGoals < (int)$m[1]) {
                return (string)$option;
            }
        }
        if (preg_match('/(?:بیشتر از|بالای)\s*(\d+)/u', $normalized, $m)) {
            if ($totalGoals > (int)$m[1]) {
                return (string)$option;
            }
        }
        if ((string)$totalGoals === trim($normalized)) {
            return (string)$option;
        }
    }

    return wc_match_option_alias($options, ['سایر', 'other']);
}

function wc_prepare_result_data(array $raw, ?int $score1 = null, ?int $score2 = null): array
{
    $result = [];
    foreach (['first_goal_team', 'manual_winner', 'match_status'] as $field) {
        if (isset($raw[$field])) {
            $result[$field] = trim((string)$raw[$field]);
        }
    }
    foreach (['first_half_goal', 'penalty', 'red_card', 'clean_sheet'] as $field) {
        if (array_key_exists($field, $raw)) {
            $value = $raw[$field];
            if ($value === '' || $value === null) {
                continue;
            }
            $result[$field] = (int)(bool)$value;
        }
    }

    if ($score1 !== null && $score2 !== null) {
        $result['total_goals'] = $score1 + $score2;
        if (!isset($result['clean_sheet'])) {
            $result['clean_sheet'] = (($score1 === 0 || $score2 === 0) && !($score1 === 0 && $score2 === 0)) ? 1 : 0;
        }
        if (!isset($result['first_goal_team']) && ($score1 + $score2) === 0) {
            $result['first_goal_team'] = 'none';
        }
        if (!isset($result['first_half_goal']) && ($score1 + $score2) === 0) {
            $result['first_half_goal'] = 0;
        }
    }

    return $result;
}

function wc_resolve_correct_option(array $bet, array $match, array $resultData, ?string $manualOption = null): ?string
{
    $options = json_decode((string)($bet['options_json'] ?? '[]'), true) ?? [];
    $options = array_values(array_map('strval', $options));
    if ($manualOption !== null && $manualOption !== '') {
        return wc_match_option_alias($options, [$manualOption]) ?? $manualOption;
    }

    $score1 = isset($match['score_team1']) ? (int)$match['score_team1'] : null;
    $score2 = isset($match['score_team2']) ? (int)$match['score_team2'] : null;
    $type = (string)($bet['bet_type'] ?? 'custom');

    switch ($type) {
        case 'winner':
            if ($score1 === null || $score2 === null) {
                return null;
            }
            if ($score1 === $score2) {
                return wc_match_option_alias($options, ['مساوی', 'draw', 'x']) ?? ($options[1] ?? null);
            }
            $isTeam1 = $score1 > $score2;
            if ($isTeam1) {
                return wc_match_option_alias($options, ['تیم اول', 'تیم ۱', 'میزبان', $match['team1'] ?? '', ($match['team1'] ?? '') . ' برنده', 'team1'])
                    ?? ($options[0] ?? null);
            }
            return wc_match_option_alias($options, ['تیم دوم', 'تیم ۲', 'مهمان', $match['team2'] ?? '', ($match['team2'] ?? '') . ' برنده', 'team2'])
                ?? ($options[count($options) - 1] ?? null);

        case 'total_goals':
            if ($score1 === null || $score2 === null) {
                return null;
            }
            return wc_match_total_goals_option($options, $score1 + $score2);

        case 'btts':
            if ($score1 === null || $score2 === null) {
                return null;
            }
            return wc_match_option_alias($options, ($score1 > 0 && $score2 > 0) ? ['بله', 'yes'] : ['خیر', 'no']);

        case 'exact_score':
            if ($score1 === null || $score2 === null) {
                return null;
            }
            $score = "{$score1}-{$score2}";
            return wc_match_option_alias($options, [$score, str_replace('-', ' - ', $score)]) ?? $score;

        case 'first_goal_team':
        case 'first_scorer':
            $firstGoal = (string)($resultData['first_goal_team'] ?? '');
            if ($firstGoal === '' && $score1 !== null && $score2 !== null && ($score1 + $score2) === 0) {
                $firstGoal = 'none';
            }
            if ($firstGoal === 'team1') {
                return wc_match_option_alias($options, ['تیم اول', $match['team1'] ?? '', 'team1']);
            }
            if ($firstGoal === 'team2') {
                return wc_match_option_alias($options, ['تیم دوم', $match['team2'] ?? '', 'team2']);
            }
            if ($firstGoal === 'none') {
                return wc_match_option_alias($options, ['بدون گل', 'هیچ‌کدام', 'بدون', 'none']);
            }
            return null;

        case 'first_half_goal':
            if (!array_key_exists('first_half_goal', $resultData)) {
                return null;
            }
            return wc_match_option_alias($options, ((int)$resultData['first_half_goal'] === 1) ? ['بله', 'yes'] : ['خیر', 'no']);

        case 'penalty':
            if (!array_key_exists('penalty', $resultData)) {
                return null;
            }
            return wc_match_option_alias($options, ((int)$resultData['penalty'] === 1) ? ['بله', 'yes'] : ['خیر', 'no']);

        case 'clean_sheet':
            if ($score1 === null || $score2 === null) {
                return null;
            }
            return wc_match_option_alias($options, (($score1 === 0 || $score2 === 0) && !($score1 === 0 && $score2 === 0)) ? ['بله', 'yes'] : ['خیر', 'no']);

        default:
            return null;
    }
}

function wc_recalculate_user_totals(PDO $pdo, array $userIds): void
{
    if (!$userIds) {
        return;
    }
    $userIds = array_values(array_unique(array_map('intval', $userIds)));
    $predictionTable = hmn_table('predictions');
    $userTable = hmn_table('users');

    $sumSt = $pdo->prepare("SELECT COALESCE(SUM(points_earned), 0) FROM {$predictionTable} WHERE user_id = :user_id");
    $updateSt = $pdo->prepare("UPDATE {$userTable} SET total_points = :total WHERE id = :id");
    foreach ($userIds as $userId) {
        $sumSt->execute([':user_id' => $userId]);
        $total = (int)$sumSt->fetchColumn();
        $updateSt->execute([':total' => $total, ':id' => $userId]);
    }
}

function wc_recalculate_user_redeemed_points(PDO $pdo, array $userIds): void
{
    if (!$userIds) {
        return;
    }
    $userIds = array_values(array_unique(array_map('intval', $userIds)));
    $redemptionTable = hmn_table('reward_redemptions');
    $userTable = hmn_table('users');

    $sumSt = $pdo->prepare("SELECT COALESCE(SUM(points_spent), 0) FROM {$redemptionTable} WHERE user_id = :user_id");
    $updateSt = $pdo->prepare("UPDATE {$userTable} SET redeemed_points = :total WHERE id = :id");
    foreach ($userIds as $userId) {
        $sumSt->execute([':user_id' => $userId]);
        $total = (int)$sumSt->fetchColumn();
        $updateSt->execute([':total' => $total, ':id' => $userId]);
    }
}

function wc_get_available_points(array $userRow): int
{
    return max(0, (int)($userRow['total_points'] ?? 0) - (int)($userRow['redeemed_points'] ?? 0));
}

function wc_normalize_first_goal_team_value(string $value, array $match): string
{
    $value = trim($value);
    if ($value === '') {
        return '';
    }
    $normalized = wc_team_key($value);
    $team1 = wc_team_key((string)($match['team1'] ?? ''));
    $team2 = wc_team_key((string)($match['team2'] ?? ''));

    if (in_array($normalized, ['none', 'بدونگل', 'بدون', 'هیچکدام'], true)) {
        return 'none';
    }
    if (in_array($normalized, ['team1', 'تیماول', 'میزبان'], true) || ($team1 !== '' && $normalized === $team1)) {
        return 'team1';
    }
    if (in_array($normalized, ['team2', 'تیمدوم', 'مهمان'], true) || ($team2 !== '' && $normalized === $team2)) {
        return 'team2';
    }
    return $value;
}

function wc_apply_match_result(PDO $pdo, int $matchId, int $score1, int $score2, array $resultData, array $manualBetResults = []): array
{
    $matchTable = hmn_table('matches');
    $betTable = hmn_table('bets');
    $predictionTable = hmn_table('predictions');

    $matchSt = $pdo->prepare("SELECT * FROM {$matchTable} WHERE id = :id LIMIT 1");
    $matchSt->execute([':id' => $matchId]);
    $match = $matchSt->fetch(PDO::FETCH_ASSOC);
    if (!$match) {
        return ['success' => false, 'error' => 'بازی یافت نشد.'];
    }

    $match['score_team1'] = $score1;
    $match['score_team2'] = $score2;
    if (isset($resultData['first_goal_team'])) {
        $resultData['first_goal_team'] = wc_normalize_first_goal_team_value((string)$resultData['first_goal_team'], $match);
    }

    $pdo->prepare(
        "UPDATE {$matchTable}
        SET status = 'finished',
            is_open = 0,
            score_team1 = :score1,
            score_team2 = :score2,
            live_minute = NULL,
            live_status_text = :live_status_text,
            result_data_json = :result_data
        WHERE id = :id"
    )->execute([
        ':score1' => $score1,
        ':score2' => $score2,
        ':live_status_text' => trim((string)($resultData['match_status'] ?? 'پایان یافته')),
        ':result_data' => json_encode($resultData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ':id' => $matchId,
    ]);

    $betSt = $pdo->prepare("SELECT * FROM {$betTable} WHERE match_id = :match_id ORDER BY display_order ASC, id ASC");
    $betSt->execute([':match_id' => $matchId]);
    $bets = $betSt->fetchAll(PDO::FETCH_ASSOC);

    $manualNeeded = [];
    foreach ($bets as $bet) {
        $betId = (int)$bet['id'];
        $manualOption = isset($manualBetResults[$betId]) ? trim((string)$manualBetResults[$betId]) : null;
        $correctOption = wc_resolve_correct_option($bet, $match, $resultData, $manualOption);

        $pdo->prepare("UPDATE {$betTable} SET correct_option = :correct_option WHERE id = :id")
            ->execute([':correct_option' => $correctOption, ':id' => $betId]);

        if ($correctOption === null || $correctOption === '') {
            $manualNeeded[] = ['bet_id' => $betId, 'label' => $bet['label']];
            $pdo->prepare(
                "UPDATE {$predictionTable}
                SET is_correct = NULL, points_earned = 0
                WHERE match_id = :match_id AND bet_id = :bet_id"
            )->execute([':match_id' => $matchId, ':bet_id' => $betId]);
            continue;
        }

        $pdo->prepare(
            "UPDATE {$predictionTable}
            SET is_correct = (selected_option = :correct_option),
                points_earned = IF(selected_option = :correct_option, :points, 0)
            WHERE match_id = :match_id AND bet_id = :bet_id"
        )->execute([
            ':correct_option' => $correctOption,
            ':points' => (int)$bet['points'],
            ':match_id' => $matchId,
            ':bet_id' => $betId,
        ]);
    }

    $userSt = $pdo->prepare("SELECT DISTINCT user_id FROM {$predictionTable} WHERE match_id = :match_id");
    $userSt->execute([':match_id' => $matchId]);
    $userIds = array_map('intval', $userSt->fetchAll(PDO::FETCH_COLUMN));
    wc_recalculate_user_totals($pdo, $userIds);

    return [
        'success' => true,
        'affected_users' => count($userIds),
        'manual_required' => $manualNeeded,
    ];
}

function wc_update_live_match(PDO $pdo, int $matchId, int $score1, int $score2, ?int $minute, string $statusText = ''): void
{
    $matchTable = hmn_table('matches');
    $pdo->prepare(
        "UPDATE {$matchTable}
        SET status = 'live',
            is_open = 0,
            score_team1 = :score1,
            score_team2 = :score2,
            live_minute = :live_minute,
            live_status_text = :live_status_text
        WHERE id = :id"
    )->execute([
        ':score1' => $score1,
        ':score2' => $score2,
        ':live_minute' => $minute,
        ':live_status_text' => $statusText,
        ':id' => $matchId,
    ]);
}

function wc_team_key(string $value): string
{
    $value = trim(wc_normalize_digits($value));
    $value = preg_replace('/[\x{200c}\s\-_]+/u', '', $value) ?? $value;
    $value = preg_replace('/[^\p{L}\p{N}]+/u', '', $value) ?? $value;
    return function_exists('mb_strtolower') ? mb_strtolower($value) : strtolower($value);
}

function wc_parse_feed_payload(string $raw): array
{
    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        return [];
    }
    if (isset($decoded['matches']) && is_array($decoded['matches'])) {
        return $decoded['matches'];
    }
    if (isset($decoded['events']) && is_array($decoded['events'])) {
        return $decoded['events'];
    }
    if (isset($decoded['fixtures']) && is_array($decoded['fixtures'])) {
        return $decoded['fixtures'];
    }
    if (array_keys($decoded) === range(0, count($decoded) - 1)) {
        return $decoded;
    }
    return [];
}

function wc_pick_first_value(array $row, array $keys): mixed
{
    foreach ($keys as $key) {
        if (array_key_exists($key, $row) && $row[$key] !== null && $row[$key] !== '') {
            return $row[$key];
        }
    }
    return null;
}

function wc_remote_fetch(string $url, string $acceptHeader = '*/*'): string|false
{
    $headers = [
        'Accept: ' . $acceptHeader,
        'User-Agent: WorldCupSync/1.0',
    ];

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        $body = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($body !== false && $status >= 200 && $status < 400) {
            return $body;
        }
    }

    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'ignore_errors' => true,
            'header' => implode("\r\n", $headers) . "\r\n",
        ],
    ]);

    $raw = @file_get_contents($url, false, $context);
    return ($raw === false || trim((string)$raw) === '') ? false : $raw;
}

function wc_detect_feed_provider(string $provider, string $feedUrl): string
{
    $provider = trim($provider);
    if ($provider !== '') {
        return $provider;
    }
    if (stripos($feedUrl, 'varzesh3.com') !== false) {
        return 'varzesh3_html';
    }
    if (stripos($feedUrl, 'site.api.espn.com') !== false || stripos($feedUrl, 'espn.com') !== false) {
        return 'espn_scoreboard';
    }
    return 'generic_json';
}

function wc_extract_minute_from_text(string $text): ?int
{
    $text = wc_normalize_digits($text);
    if (preg_match('/(\d{1,3})\s*(?:\'|دقیقه)/u', $text, $m)) {
        return (int)$m[1];
    }
    if (preg_match('/^(\d{1,3})$/u', trim($text), $m)) {
        return (int)$m[1];
    }
    return null;
}

function wc_parse_espn_scoreboard_payload(string $raw): array
{
    $decoded = json_decode($raw, true);
    $events = is_array($decoded['events'] ?? null) ? $decoded['events'] : [];
    $items = [];

    foreach ($events as $event) {
        if (!is_array($event)) {
            continue;
        }
        $competition = is_array($event['competitions'][0] ?? null) ? $event['competitions'][0] : [];
        $competitors = is_array($competition['competitors'] ?? null) ? $competition['competitors'] : [];
        if (count($competitors) < 2) {
            continue;
        }

        $home = null;
        $away = null;
        foreach ($competitors as $competitor) {
            if (!is_array($competitor)) {
                continue;
            }
            if (($competitor['homeAway'] ?? '') === 'home') {
                $home = $competitor;
            } elseif (($competitor['homeAway'] ?? '') === 'away') {
                $away = $competitor;
            }
        }
        $home = $home ?: ($competitors[0] ?? null);
        $away = $away ?: ($competitors[1] ?? null);
        if (!is_array($home) || !is_array($away)) {
            continue;
        }

        $statusRoot = is_array($competition['status'] ?? null) ? $competition['status'] : (is_array($event['status'] ?? null) ? $event['status'] : []);
        $statusType = is_array($statusRoot['type'] ?? null) ? $statusRoot['type'] : [];
        $statusText = trim((string)($statusType['shortDetail'] ?? $statusType['detail'] ?? $statusRoot['displayClock'] ?? $statusType['description'] ?? ''));
        $statusState = function_exists('mb_strtolower') ? mb_strtolower((string)($statusType['state'] ?? '')) : strtolower((string)($statusType['state'] ?? ''));
        $completed = !empty($statusType['completed']);

        $items[] = [
            'id' => (string)($event['id'] ?? ''),
            'external_ref' => (string)($event['id'] ?? ''),
            'team1' => (string)($home['team']['displayName'] ?? $home['team']['shortDisplayName'] ?? ''),
            'team2' => (string)($away['team']['displayName'] ?? $away['team']['shortDisplayName'] ?? ''),
            'score1' => (int)($home['score'] ?? 0),
            'score2' => (int)($away['score'] ?? 0),
            'minute' => wc_extract_minute_from_text($statusText),
            'status_text' => $statusText,
            'status' => $completed ? 'finished' : ($statusState === 'in' ? 'live' : 'upcoming'),
        ];
    }

    return $items;
}

function wc_html_to_lines(string $raw): array
{
    $raw = preg_replace('/<script\b[^>]*>.*?<\/script>/is', "\n", $raw) ?? $raw;
    $raw = preg_replace('/<style\b[^>]*>.*?<\/style>/is', "\n", $raw) ?? $raw;
    $raw = preg_replace('/<(?:br|\/p|\/div|\/li|\/section|\/article|\/tr|\/td|\/h\d)[^>]*>/i', "\n", $raw) ?? $raw;
    $text = html_entity_decode(strip_tags($raw), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = str_replace(["\r", "\t", "\xc2\xa0"], ["", ' ', ' '], $text);
    $lines = preg_split('/\n+/u', $text) ?: [];

    $result = [];
    foreach ($lines as $line) {
        $line = trim(preg_replace('/\s+/u', ' ', $line) ?? $line);
        if ($line !== '') {
            $result[] = $line;
        }
    }
    return $result;
}

function wc_parse_varzesh3_match_line(string $line): ?array
{
    $line = trim(wc_normalize_digits($line));
    if ($line === '') {
        return null;
    }
    if (preg_match('/^(.*?)\s+(\d{1,2})\s*[-–—]\s*(\d{1,2})\s+(.*?)$/u', $line, $m)) {
        return [
            'team1' => trim($m[1]),
            'score1' => (int)$m[2],
            'score2' => (int)$m[3],
            'team2' => trim($m[4]),
        ];
    }
    return null;
}

function wc_parse_varzesh3_payload(string $raw): array
{
    $lines = wc_html_to_lines($raw);
    $items = [];
    $currentCompetition = '';
    $worldCupContext = false;
    $pendingTime = '';
    $pendingStatus = '';

    foreach ($lines as $line) {
        $normalized = wc_normalize_digits($line);

        if (preg_match('/^\d{4}\/\d{2}\/\d{2}$/u', $normalized)) {
            continue;
        }

        if (strpos($line, 'جام جهانی') !== false) {
            $currentCompetition = $line;
            $worldCupContext = true;
            continue;
        }

        if (
            !$pendingTime
            && preg_match('/(?:لیگ|سوپر|بسکتبال|والیبال|تنیس|هندبال|کشتی|فوتسال|فرمول|بوندسلیگا|لالیگا|سری آ|NBA|Euro)/ui', $line)
            && strpos($line, 'جام جهانی') === false
        ) {
            $currentCompetition = $line;
            $worldCupContext = false;
            continue;
        }

        if (preg_match('/^(\d{1,2}:\d{2})(?:\s+(.*))?$/u', $normalized, $m)) {
            $pendingTime = $m[1];
            $pendingStatus = trim((string)($m[2] ?? ''));
            continue;
        }

        if (!$worldCupContext || $pendingTime === '') {
            continue;
        }

        $match = wc_parse_varzesh3_match_line($line);
        if (!$match) {
            $pendingTime = '';
            $pendingStatus = '';
            continue;
        }

        $statusText = trim($pendingStatus);
        $statusKey = function_exists('mb_strtolower') ? mb_strtolower($statusText) : strtolower($statusText);
        $status = 'upcoming';
        if ($statusText !== '' && (
            strpos($statusText, 'نتیجه نهایی') !== false
            || strpos($statusText, 'پایان') !== false
            || strpos($statusText, 'تمام') !== false
        )) {
            $status = 'finished';
        } elseif (
            $statusText !== ''
            && (
                strpos($statusText, 'زنده') !== false
                || strpos($statusText, 'نیمه') !== false
                || strpos($statusKey, 'live') !== false
                || wc_extract_minute_from_text($statusText) !== null
            )
        ) {
            $status = 'live';
        }

        if ($status !== 'upcoming') {
            $items[] = [
                'team1' => $match['team1'],
                'team2' => $match['team2'],
                'score1' => $match['score1'],
                'score2' => $match['score2'],
                'minute' => wc_extract_minute_from_text($statusText),
                'status' => $status,
                'status_text' => $statusText !== '' ? $statusText : ($status === 'finished' ? 'پایان یافته' : 'زنده'),
                'competition' => $currentCompetition,
            ];
        }

        $pendingTime = '';
        $pendingStatus = '';
    }

    return $items;
}

function wc_sync_known_verified_results(PDO $pdo): int
{
    $matchTable = hmn_table('matches');
    $st = $pdo->prepare(
        "SELECT * FROM {$matchTable}
        WHERE team1 = 'مکزیک' AND team2 = 'آفریقای جنوبی' AND DATE(match_datetime) = '2026-06-11'
        ORDER BY id ASC LIMIT 1"
    );
    $st->execute();
    $match = $st->fetch(PDO::FETCH_ASSOC);
    if (!$match) {
        return 0;
    }
    if (($match['status'] ?? '') === 'finished' && (int)($match['score_team1'] ?? -1) === 2 && (int)($match['score_team2'] ?? -1) === 0) {
        return 0;
    }
    if ((wc_match_storage_timestamp((string)$match['match_datetime']) ?? PHP_INT_MAX) > time()) {
        return 0;
    }
    $result = wc_apply_match_result($pdo, (int)$match['id'], 2, 0, ['match_status' => 'پایان یافته']);
    return !empty($result['success']) ? 1 : 0;
}

function wc_maybe_sync_scores(PDO $pdo, bool $force = false): array
{
    $settings = wc_get_settings($pdo);
    $synced = 0;
    $verified = wc_sync_known_verified_results($pdo);
    if ($verified > 0) {
        $synced += $verified;
    }

    $enabled = (int)($settings['live_scores_enabled'] ?? 0) === 1;
    $provider = wc_detect_feed_provider((string)($settings['live_scores_provider'] ?? ''), (string)($settings['live_scores_feed_url'] ?? ''));
    $feedUrl = trim((string)($settings['live_scores_feed_url'] ?? ''));
    if ($provider === 'varzesh3_html' && $feedUrl === '') {
        $feedUrl = 'https://www.varzesh3.com/livescore';
    }
    if (!$enabled || $feedUrl === '') {
        return ['success' => true, 'synced' => $synced, 'skipped' => true];
    }

    $refreshMinutes = max(1, (int)($settings['live_scores_refresh_minutes'] ?? 5));
    $lastSync = trim((string)($settings['live_scores_last_sync_at'] ?? ''));
    if (!$force && $lastSync !== '') {
        $lastTs = strtotime($lastSync);
        if ($lastTs !== false && (time() - $lastTs) < ($refreshMinutes * 60)) {
            return ['success' => true, 'synced' => $synced, 'skipped' => true];
        }
    }

    $acceptHeader = $provider === 'varzesh3_html' ? 'text/html,application/xhtml+xml' : 'application/json,text/plain,*/*';
    $raw = wc_remote_fetch($feedUrl, $acceptHeader);
    if ($raw === false || trim((string)$raw) === '') {
        return ['success' => false, 'synced' => $synced, 'error' => 'feed_unreachable'];
    }

    if ($provider === 'varzesh3_html') {
        $items = wc_parse_varzesh3_payload($raw);
    } elseif ($provider === 'espn_scoreboard') {
        $items = wc_parse_espn_scoreboard_payload($raw);
    } else {
        $items = wc_parse_feed_payload($raw);
    }
    if (!$items) {
        return ['success' => false, 'synced' => $synced, 'error' => 'feed_invalid'];
    }

    $matchTable = hmn_table('matches');
    $matches = $pdo->query("SELECT * FROM {$matchTable} ORDER BY match_datetime ASC")->fetchAll(PDO::FETCH_ASSOC);
    $byRef = [];
    $byTeams = [];
    foreach ($matches as $match) {
        $matchId = (int)$match['id'];
        $ref = trim((string)($match['external_ref'] ?? ''));
        if ($ref !== '') {
            $byRef[$ref] = $match;
        }
        $teamKey = wc_team_key((string)$match['team1']) . '|' . wc_team_key((string)$match['team2']);
        $byTeams[$teamKey] = $match;
    }

    foreach ($items as $item) {
        if (!is_array($item)) {
            continue;
        }
        $externalRef = trim((string)(wc_pick_first_value($item, ['id', 'match_id', 'matchId', 'external_ref', 'ref']) ?? ''));
        $match = $externalRef !== '' && isset($byRef[$externalRef]) ? $byRef[$externalRef] : null;
        if (!$match) {
            $team1 = trim((string)(wc_pick_first_value($item, ['team1', 'home_team', 'homeTeam', 'home']) ?? ''));
            $team2 = trim((string)(wc_pick_first_value($item, ['team2', 'away_team', 'awayTeam', 'away']) ?? ''));
            if ($team1 === '' || $team2 === '') {
                continue;
            }
            $lookup = wc_team_key($team1) . '|' . wc_team_key($team2);
            $match = $byTeams[$lookup] ?? null;
            if (!$match) {
                $reverse = wc_team_key($team2) . '|' . wc_team_key($team1);
                $match = $byTeams[$reverse] ?? null;
            }
        }
        if (!$match) {
            continue;
        }

        $score1 = (int)(wc_pick_first_value($item, ['score1', 'home_score', 'homeScore']) ?? $match['score_team1'] ?? 0);
        $score2 = (int)(wc_pick_first_value($item, ['score2', 'away_score', 'awayScore']) ?? $match['score_team2'] ?? 0);
        $minuteRaw = wc_pick_first_value($item, ['minute', 'live_minute', 'elapsed']);
        $minute = ($minuteRaw === null || $minuteRaw === '') ? null : (int)$minuteRaw;
        $status = trim((string)(wc_pick_first_value($item, ['status', 'state']) ?? ''));
        $statusKey = function_exists('mb_strtolower') ? mb_strtolower($status) : strtolower($status);
        $statusText = trim((string)(wc_pick_first_value($item, ['status_text', 'statusText']) ?? ''));

        if (in_array($statusKey, ['finished', 'ft', 'ended', 'complete', 'full-time'], true)) {
            $resultData = wc_prepare_result_data([
                'match_status' => $statusText !== '' ? $statusText : 'پایان یافته',
                'first_goal_team' => trim((string)(wc_pick_first_value($item, ['first_goal_team']) ?? '')),
                'first_half_goal' => wc_pick_first_value($item, ['first_half_goal']),
                'penalty' => wc_pick_first_value($item, ['penalty']),
            ], $score1, $score2);
            $result = wc_apply_match_result($pdo, (int)$match['id'], $score1, $score2, $resultData);
            if (!empty($result['success'])) {
                $synced++;
            }
            continue;
        }

        if (in_array($statusKey, ['live', 'inplay', 'in_play', '1h', '2h', 'ht'], true)) {
            wc_update_live_match($pdo, (int)$match['id'], $score1, $score2, $minute, $statusText !== '' ? $statusText : ($minute ? $minute . ' دقیقه' : 'زنده'));
            $synced++;
        }
    }

    $settingsTable = hmn_table('settings');
    $pdo->prepare("UPDATE {$settingsTable} SET live_scores_last_sync_at = NOW() WHERE id = 1")->execute();

    return ['success' => true, 'synced' => $synced];
}
