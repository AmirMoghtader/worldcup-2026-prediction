<?php

declare(strict_types=1);

/**
 * Simple PDO wrapper for MySQL connection.
 *
 * Usage:
 *   $pdo = hmn_get_db();
 */
function hmn_get_db(): PDO
{
    static $pdo;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $configPath = dirname(__DIR__) . '/config.php';
    if (!file_exists($configPath)) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'error' => 'config.php not found. Please run install.php first.',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $config = require $configPath;

    $host = $config['db_host'] ?? 'localhost';
    $dbname = $config['db_name'] ?? '';
    $user = $config['db_user'] ?? '';
    $pass = $config['db_pass'] ?? '';
    $charset = $config['db_charset'] ?? 'utf8mb4';

    $dsn = "mysql:host={$host};dbname={$dbname};charset={$charset}";

    try {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } catch (Throwable $e) {
        // جزئیات خطا فقط در لاگ سرور ثبت می‌شود تا اطلاعات حساس به کاربر لو نرود.
        error_log('DB connection failed: ' . $e->getMessage());
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'error' => 'Database connection failed.',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    return $pdo;
}

/**
 * Returns configured table name with prefix.
 */
function hmn_table(string $base): string
{
    $configPath = dirname(__DIR__) . '/config.php';
    $prefix = '';
    if (file_exists($configPath)) {
        $config = require $configPath;
        $prefix = (string)($config['table_prefix'] ?? '');
    }
    return $prefix . $base;
}

/**
 * نرمال‌سازی کاننیکالِ شمارهٔ موبایل.
 *
 * هدف: هر چهار حالتِ ورودی (با/بدونِ صفرِ ابتدایی، ارقامِ فارسی/عربی/انگلیسی،
 * و پیش‌شماره‌های +98 / 0098 / 98) به یک فرمِ یکتا تبدیل شوند تا اکانت/کیف‌پولِ
 * تکراری ساخته نشود. خروجی برای موبایلِ ایران: «09xxxxxxxxx».
 * شماره‌هایی که موبایلِ ایران نیستند (مثل تلفنِ ثابتِ مطب) دست‌نخورده (فقط ارقام) برمی‌گردند.
 *
 * این تابع هم‌ارزِ سمتِ کلاینتِ canonicalizeMobilePhone() در index.html است.
 */
if (!function_exists('hmn_normalize_phone')) {
    function hmn_normalize_phone(string $raw): string
    {
        // ارقام فارسی/عربی → انگلیسی
        $map = [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ];
        $s = strtr($raw, $map);
        // حذفِ هر چیزِ غیرعددی (فاصله، خط تیره، پرانتز، +، ...)
        $digits = preg_replace('/\D+/', '', $s) ?? '';
        if ($digits === '') {
            return '';
        }
        // یکدست‌سازیِ پیش‌شماره‌ی بین‌المللی به سمتِ هستهٔ موبایل
        if (strncmp($digits, '0098', 4) === 0 && strlen($digits) >= 13) {
            $digits = substr($digits, 4);          // 00989xxxxxxxxx → 9xxxxxxxxx
        } elseif (strncmp($digits, '98', 2) === 0 && strlen($digits) === 12) {
            $digits = substr($digits, 2);          // 989xxxxxxxxx → 9xxxxxxxxx
        }
        // هستهٔ موبایلِ بدونِ صفر (9xxxxxxxxx) → افزودنِ صفرِ ابتدایی
        if (strlen($digits) === 10 && $digits[0] === '9') {
            $digits = '0' . $digits;
        }
        return $digits;
    }
}

/**
 * فرم‌های هم‌ارزِ یک شماره برای lookupِ متحمل (WHERE phone IN (...)).
 *
 * چون نرمال‌سازی «فقط از این به بعد» اعمال می‌شود، ردیف‌های قدیمی ممکن است در فرمتِ
 * دیگری (بدونِ صفر، با +98، با فاصله و ...) ذخیره شده باشند. این تابع همهٔ شکل‌های
 * محتملِ ذخیره‌شده را برمی‌گرداند تا کاربرِ قدیمی پیدا شود و اکانتِ تکراری ساخته نشود.
 */
if (!function_exists('hmn_phone_variants')) {
    function hmn_phone_variants(string $raw): array
    {
        $canon = hmn_normalize_phone($raw);
        $variants = [];
        $add = static function (string $v) use (&$variants): void {
            if ($v !== '' && !in_array($v, $variants, true)) {
                $variants[] = $v;
            }
        };
        $add($canon);
        // ارقامِ خامِ ورودی (پس از فارسی→انگلیسی و حذفِ غیرعدد) — مطابقت با ذخیره‌سازیِ خیلی قدیمی
        $map = [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ];
        $add(preg_replace('/\D+/', '', strtr($raw, $map)) ?? '');
        // فرمتِ خامِ ورودی بدونِ تغییر (legacyِ hmn_clean_phone فقط فاصله را پاک می‌کرد؛
        // پس مقادیری مثل «+989...» ممکن است عیناً ذخیره شده باشند)
        $add(preg_replace('/\s+/', '', $raw) ?? '');
        if (preg_match('/^09\d{9}$/', $canon)) {
            $core = substr($canon, 1);             // 9xxxxxxxxx
            $add($core);                            // بدونِ صفرِ ابتدایی
            $add('98' . $core);                     // 989xxxxxxxxx
            $add('0098' . $core);                   // 00989xxxxxxxxx
            $add('+98' . $core);                    // +989xxxxxxxxx (legacy با علامتِ +)
        }
        return $variants;
    }
}

