<?php
declare(strict_types=1);

// ─── Session Config ───────────────────────────────────────────────────────────
$hmnSessionLifetime = 604800; // 7 days
if (PHP_SAPI !== 'cli') {
    ini_set('session.gc_maxlifetime', (string)$hmnSessionLifetime);
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    if (PHP_VERSION_ID < 80400) {
        ini_set('session.sid_length', '48');
        ini_set('session.sid_bits_per_character', '6');
    }
    $secureCookie = (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string)$_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');
    if ($secureCookie) ini_set('session.cookie_secure', '1');
    ini_set('session.cookie_samesite', 'Lax');
    session_set_cookie_params([
        'lifetime' => $hmnSessionLifetime, 'path' => '/',
        'secure' => $secureCookie, 'httponly' => true, 'samesite' => 'Lax',
    ]);
}
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require __DIR__ . '/db.php';
require __DIR__ . '/login_lockout.php';
require __DIR__ . '/worldcup.php';

$pdo = hmn_get_db();
$action = $_GET['action'] ?? '';

// ─── Session expiry ───────────────────────────────────────────────────────────
$nowTs = time();
if (!isset($_SESSION['started_at'])) {
    $_SESSION['started_at'] = $nowTs;
} elseif (($nowTs - (int)$_SESSION['started_at']) > $hmnSessionLifetime) {
    session_unset(); session_destroy();
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'session_expired'], JSON_UNESCAPED_UNICODE); exit;
}
if (PHP_SAPI !== 'cli') {
    if (!isset($_SESSION['last_regen'])) {
        $_SESSION['last_regen'] = $nowTs;
    } elseif (($nowTs - (int)$_SESSION['last_regen']) > 1800) {
        session_regenerate_id(true);
        $_SESSION['last_regen'] = $nowTs;
    }
}

// ─── Helpers ──────────────────────────────────────────────────────────────────
function hmn_read_json(): array {
    $raw = file_get_contents('php://input');
    if (!$raw) return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function hmn_json_response(array $data): void {
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); exit;
}

function hmn_rate_limit(string $key, int $limit, int $windowSeconds): bool {
    $now = time();
    if (!isset($_SESSION['_rl'])) $_SESSION['_rl'] = [];
    $bucket = $_SESSION['_rl'][$key] ?? ['count' => 0, 'ts' => $now];
    if (($now - (int)$bucket['ts']) > $windowSeconds) $bucket = ['count' => 0, 'ts' => $now];
    if ($bucket['count'] >= $limit) { $_SESSION['_rl'][$key] = $bucket; return false; }
    $bucket['count']++; $bucket['ts'] = $now;
    $_SESSION['_rl'][$key] = $bucket;
    return true;
}

function hmn_client_ip(): string {
    foreach (['HTTP_CF_CONNECTING_IP','HTTP_X_FORWARDED_FOR','REMOTE_ADDR'] as $k) {
        if (!empty($_SERVER[$k])) {
            $v = trim(explode(',', (string)$_SERVER[$k])[0]);
            if (filter_var($v, FILTER_VALIDATE_IP)) return $v;
        }
    }
    return '0.0.0.0';
}

function hmn_clean_phone(string $raw): string {
    return hmn_normalize_phone($raw);
}

function wc_current_user_id(): ?int {
    return isset($_SESSION['wc_user_id']) ? (int)$_SESSION['wc_user_id'] : null;
}

function wc_current_role(): ?string {
    return $_SESSION['wc_role'] ?? null;
}

function hmn_require_user(): void {
    if (!isset($_SESSION['wc_user_id'])) {
        http_response_code(401);
        hmn_json_response(['success' => false, 'error' => 'لطفاً وارد شوید.', 'auth_required' => true]);
    }
}

function hmn_require_admin(): void {
    if (($_SESSION['wc_role'] ?? '') !== 'admin') {
        http_response_code(403);
        hmn_json_response(['success' => false, 'error' => 'دسترسی ندارید.']);
    }
}

// ─── Ensure DB tables exist ───────────────────────────────────────────────────
wc_ensure_tables($pdo);
$bootSettings = wc_get_settings($pdo);
if ((int)($bootSettings['schedule_seeded'] ?? 0) === 0) {
    $seededMatches = wc_seed_matches($pdo);
    if ($seededMatches > 0) {
        wc_sync_default_bets_to_all_matches($pdo);
    }
}
wc_sync_official_schedule_2026($pdo);

// ─── Route ────────────────────────────────────────────────────────────────────
switch ($action) {
    // User auth
    case 'register':
    case 'login':
        require __DIR__ . '/api/register.php'; break;
    case 'logout':
        require __DIR__ . '/api/logout.php'; break;
    case 'me':
        require __DIR__ . '/api/me.php'; break;
    case 'auth_lookup':
        require __DIR__ . '/api/auth_lookup.php'; break;

    // Public match data
    case 'matches':
        require __DIR__ . '/api/matches.php'; break;
    case 'match_detail':
        require __DIR__ . '/api/match_detail.php'; break;
    case 'match_bets':
        require __DIR__ . '/api/match_bets_public.php'; break;
    case 'leaderboard':
        require __DIR__ . '/api/leaderboard.php'; break;
    case 'rewards':
        require __DIR__ . '/api/rewards.php'; break;
    case 'ads':
        require __DIR__ . '/api/ads.php'; break;

    // User predictions & profile
    case 'predict':
    case 'predict_batch':
        hmn_require_user();
        require __DIR__ . '/api/predict.php'; break;
    case 'my_predictions':
    case 'user_predictions':
        hmn_require_user();
        require __DIR__ . '/api/my_predictions.php'; break;
    case 'user_profile':
        hmn_require_user();
        require __DIR__ . '/api/user_profile.php'; break;
    case 'redeem_reward':
        hmn_require_user();
        require __DIR__ . '/api/redeem_reward.php'; break;
    case 'vip_overview':
        hmn_require_user();
        require __DIR__ . '/api/vip_overview.php'; break;
    case 'vip_place_bet':
        hmn_require_user();
        require __DIR__ . '/api/vip_place_bet.php'; break;

    // Admin auth
    case 'admin_login':
        require __DIR__ . '/api/admin_login.php'; break;
    case 'admin_logout':
        session_unset(); session_destroy();
        hmn_json_response(['success' => true]); break;
    case 'admin_me':
        hmn_require_admin();
        require __DIR__ . '/api/admin_me.php'; break;

    // Admin matches
    case 'admin_matches':
        hmn_require_admin();
        require __DIR__ . '/api/admin_matches.php'; break;
    case 'admin_save_match':
    case 'admin_add_match':
    case 'admin_update_match':
        hmn_require_admin();
        require __DIR__ . '/api/admin_save_match.php'; break;
    case 'admin_delete_match':
        hmn_require_admin();
        require __DIR__ . '/api/admin_delete_match.php'; break;
    case 'admin_toggle_match':
        hmn_require_admin();
        require __DIR__ . '/api/admin_toggle_match.php'; break;
    case 'admin_set_result':
        hmn_require_admin();
        require __DIR__ . '/api/admin_set_result.php'; break;
    case 'admin_stats':
        hmn_require_admin();
        require __DIR__ . '/api/admin_stats.php'; break;
    case 'admin_sync_scores':
        hmn_require_admin();
        require __DIR__ . '/api/admin_sync_scores.php'; break;

    // Admin bets
    case 'admin_bets':
        hmn_require_admin();
        require __DIR__ . '/api/admin_bets.php'; break;
    case 'admin_save_bet':
    case 'admin_add_bet':
    case 'admin_update_bet':
        hmn_require_admin();
        require __DIR__ . '/api/admin_save_bet.php'; break;
    case 'admin_delete_bet':
        hmn_require_admin();
        require __DIR__ . '/api/admin_delete_bet.php'; break;
    case 'admin_copy_default_bets':
        hmn_require_admin();
        require __DIR__ . '/api/admin_copy_default_bets.php'; break;

    // Admin default bets
    case 'admin_default_bets':
        hmn_require_admin();
        require __DIR__ . '/api/admin_default_bets.php'; break;
    case 'admin_save_default_bet':
    case 'admin_add_default_bet':
    case 'admin_update_default_bet':
        hmn_require_admin();
        require __DIR__ . '/api/admin_save_default_bet.php'; break;
    case 'admin_delete_default_bet':
        hmn_require_admin();
        require __DIR__ . '/api/admin_delete_default_bet.php'; break;

    // Admin users & settings
    case 'admin_users':
        hmn_require_admin();
        require __DIR__ . '/api/admin_users.php'; break;
    case 'admin_rewards':
        hmn_require_admin();
        require __DIR__ . '/api/admin_rewards.php'; break;
    case 'admin_vip':
        hmn_require_admin();
        require __DIR__ . '/api/admin_vip.php'; break;
    case 'admin_ads':
        hmn_require_admin();
        require __DIR__ . '/api/admin_ads.php'; break;
    case 'admin_save_reward':
    case 'admin_add_reward':
    case 'admin_update_reward':
        hmn_require_admin();
        require __DIR__ . '/api/admin_save_reward.php'; break;
    case 'admin_save_ad':
    case 'admin_add_ad':
    case 'admin_update_ad':
        hmn_require_admin();
        require __DIR__ . '/api/admin_save_ad.php'; break;
    case 'admin_delete_reward':
        hmn_require_admin();
        require __DIR__ . '/api/admin_delete_reward.php'; break;
    case 'admin_save_vip_member':
    case 'admin_add_vip_member':
    case 'admin_update_vip_member':
        hmn_require_admin();
        require __DIR__ . '/api/admin_save_vip_member.php'; break;
    case 'admin_delete_vip_member':
        hmn_require_admin();
        require __DIR__ . '/api/admin_delete_vip_member.php'; break;
    case 'admin_save_vip_match':
    case 'admin_add_vip_match':
    case 'admin_update_vip_match':
        hmn_require_admin();
        require __DIR__ . '/api/admin_save_vip_match.php'; break;
    case 'admin_delete_vip_match':
        hmn_require_admin();
        require __DIR__ . '/api/admin_delete_vip_match.php'; break;
    case 'admin_settle_vip_match':
        hmn_require_admin();
        require __DIR__ . '/api/admin_settle_vip_match.php'; break;
    case 'admin_delete_ad':
        hmn_require_admin();
        require __DIR__ . '/api/admin_delete_ad.php'; break;
    case 'admin_settings':
        require __DIR__ . '/api/admin_settings.php'; break;

    default:
        http_response_code(404);
        hmn_json_response(['success' => false, 'error' => 'endpoint not found']);
}
