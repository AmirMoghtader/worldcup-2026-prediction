<?php
/** @var PDO $pdo */
$ts = hmn_table('settings');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    hmn_require_admin();
    $data = hmn_read_json();
    $defaults = wc_default_settings_row();
    $allowedProviders = ['varzesh3_html', 'espn_scoreboard', 'generic_json'];
    $provider = trim((string)($data['live_scores_provider'] ?? $defaults['live_scores_provider']));
    if (!in_array($provider, $allowedProviders, true)) {
        $provider = $defaults['live_scores_provider'];
    }
    $payload = [
        'site_name' => trim((string)($data['site_name'] ?? $defaults['site_name'])) ?: $defaults['site_name'],
        'site_tagline' => trim((string)($data['site_tagline'] ?? $defaults['site_tagline'])),
        'prediction_lock_minutes' => max(0, (int)($data['prediction_lock_minutes'] ?? $defaults['prediction_lock_minutes'])),
        'prediction_window_hours' => max(1, min(168, (int)($data['prediction_window_hours'] ?? $defaults['prediction_window_hours']))),
        'logo_url' => trim((string)($data['logo_url'] ?? $defaults['logo_url'])),
        'nav_logo_url' => trim((string)($data['nav_logo_url'] ?? '')),
        'auth_logo_url' => trim((string)($data['auth_logo_url'] ?? '')),
        'footer_logo_url' => trim((string)($data['footer_logo_url'] ?? '')),
        'admin_logo_url' => trim((string)($data['admin_logo_url'] ?? '')),
        'hero_banner_url' => trim((string)($data['hero_banner_url'] ?? '')),
        'hero_banner_link_url' => trim((string)($data['hero_banner_link_url'] ?? '')),
        'hero_banner_pure_mode' => !empty($data['hero_banner_pure_mode']) ? 1 : 0,
        'hero_banner_mobile_url' => trim((string)($data['hero_banner_mobile_url'] ?? '')),
        'hero_banner_height_desktop' => max(120, min(520, (int)($data['hero_banner_height_desktop'] ?? $defaults['hero_banner_height_desktop']))),
        'hero_banner_height_mobile' => max(96, min(360, (int)($data['hero_banner_height_mobile'] ?? $defaults['hero_banner_height_mobile']))),
        'home_sidebar_banner_url' => trim((string)($data['home_sidebar_banner_url'] ?? '')),
        'home_sidebar_banner_link_url' => trim((string)($data['home_sidebar_banner_link_url'] ?? '')),
        'live_scores_enabled' => !empty($data['live_scores_enabled']) ? 1 : 0,
        'live_scores_provider' => $provider,
        'live_scores_feed_url' => trim((string)($data['live_scores_feed_url'] ?? '')),
        'live_scores_refresh_minutes' => max(1, min(60, (int)($data['live_scores_refresh_minutes'] ?? $defaults['live_scores_refresh_minutes']))),
        'footer_note' => trim((string)($data['footer_note'] ?? $defaults['footer_note'])),
        'footer_credit' => trim((string)($data['footer_credit'] ?? $defaults['footer_credit'])),
    ];
    $pdo->prepare(
        "UPDATE {$ts} SET
        site_name=:site_name,
        site_tagline=:site_tagline,
        prediction_lock_minutes=:prediction_lock_minutes,
        prediction_window_hours=:prediction_window_hours,
        logo_url=:logo_url,
        nav_logo_url=:nav_logo_url,
        auth_logo_url=:auth_logo_url,
        footer_logo_url=:footer_logo_url,
        admin_logo_url=:admin_logo_url,
        hero_banner_url=:hero_banner_url,
        hero_banner_link_url=:hero_banner_link_url,
        hero_banner_pure_mode=:hero_banner_pure_mode,
        hero_banner_mobile_url=:hero_banner_mobile_url,
        hero_banner_height_desktop=:hero_banner_height_desktop,
        hero_banner_height_mobile=:hero_banner_height_mobile,
        home_sidebar_banner_url=:home_sidebar_banner_url,
        home_sidebar_banner_link_url=:home_sidebar_banner_link_url,
        live_scores_enabled=:live_scores_enabled,
        live_scores_provider=:live_scores_provider,
        live_scores_feed_url=:live_scores_feed_url,
        live_scores_refresh_minutes=:live_scores_refresh_minutes,
        footer_note=:footer_note,
        footer_credit=:footer_credit
        WHERE id=1"
    )->execute($payload);
    hmn_json_response(['success' => true]);
}

$s = wc_get_settings($pdo);
if (wc_current_role() !== 'admin') {
    unset($s['live_scores_feed_url'], $s['live_scores_last_sync_at'], $s['live_scores_provider']);
}
hmn_json_response(['success' => true, 'settings' => $s]);
