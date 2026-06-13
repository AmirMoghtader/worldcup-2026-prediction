<?php

declare(strict_types=1);

/**
 * قفلِ پیش‌رونده‌ی ورود با رمز (Brute-force protection) — بر اساسِ شمارهٔ موبایل (نه سشن/کوکی).
 *
 * سیاست:
 *  - هر شماره اگر به threshold (پیش‌فرض ۱۰) تلاشِ ناموفقِ رمز برسد، قفل می‌شود.
 *  - مدتِ قفل پیش‌رونده است: ۱۰ دقیقه، سپس ۲۰، ۴۰، ۸۰، … (دو برابر در هر بار)، با سقفِ ۲۴ ساعت.
 *  - ورودِ موفق همهٔ شمارنده‌ها را صفر می‌کند.
 *  - اگر مدتی طولانی (decay) هیچ تلاشی نباشد، شمارنده/سطحِ قفل ریست می‌شود.
 *
 * ذخیره‌سازی در جدولِ {prefix}login_lockouts است تا با دور انداختنِ کوکی قابل دور زدن نباشد.
 */

if (!function_exists('hmn_login_lock_config')) {
    function hmn_login_lock_config(): array
    {
        return [
            'threshold'     => 10,    // تعداد تلاشِ ناموفق تا قفل
            'base_minutes'  => 10,    // مدتِ اولین قفل
            'max_minutes'   => 1440,  // سقفِ مدتِ قفل (۲۴ ساعت)
            'decay_seconds' => 86400, // اگر این مدت هیچ تلاشی نبود، همه‌چیز ریست می‌شود
        ];
    }
}

if (!function_exists('hmn_ensure_login_lockout_table')) {
    function hmn_ensure_login_lockout_table(PDO $pdo): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $t = hmn_table('login_lockouts');
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS {$t} (
                phone VARCHAR(20) NOT NULL PRIMARY KEY,
                fail_count INT NOT NULL DEFAULT 0,
                strikes INT NOT NULL DEFAULT 0,
                locked_until DATETIME NULL DEFAULT NULL,
                last_fail_at DATETIME NULL DEFAULT NULL,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (Throwable $e) {
            // ignore (جدول ممکن است از قبل باشد یا دیتابیس قدیمی باشد)
        }
        $done = true;
    }
}

if (!function_exists('hmn_login_norm_phone')) {
    function hmn_login_norm_phone(string $phone): string
    {
        return function_exists('hmn_clean_phone') ? hmn_clean_phone($phone) : preg_replace('/\s+/', '', $phone);
    }
}

if (!function_exists('hmn_login_lock_status')) {
    /**
     * وضعیتِ فعلیِ قفل. خروجی: ['locked'=>bool,'remaining'=>int(seconds),'fail_count'=>int,'strikes'=>int,'threshold'=>int]
     */
    function hmn_login_lock_status(PDO $pdo, string $phone): array
    {
        hmn_ensure_login_lockout_table($pdo);
        $cfg = hmn_login_lock_config();
        $t = hmn_table('login_lockouts');
        $phone = hmn_login_norm_phone($phone);
        $base = ['locked' => false, 'remaining' => 0, 'fail_count' => 0, 'strikes' => 0, 'threshold' => $cfg['threshold']];
        try {
            $st = $pdo->prepare("SELECT fail_count, strikes, UNIX_TIMESTAMP(locked_until) AS lu, UNIX_TIMESTAMP(NOW()) AS now_ts FROM {$t} WHERE phone = :p LIMIT 1");
            $st->execute([':p' => $phone]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return $base;
        }
        if (!$row) {
            return $base;
        }
        $now = (int)$row['now_ts'];
        $lu = !empty($row['lu']) ? (int)$row['lu'] : 0;
        $remaining = $lu > $now ? ($lu - $now) : 0;
        return [
            'locked'     => $remaining > 0,
            'remaining'  => $remaining,
            'fail_count' => (int)$row['fail_count'],
            'strikes'    => (int)$row['strikes'],
            'threshold'  => $cfg['threshold'],
        ];
    }
}

if (!function_exists('hmn_login_register_failure')) {
    /**
     * ثبتِ یک تلاشِ ناموفقِ رمز. در صورت رسیدن به threshold، قفلِ پیش‌رونده اعمال می‌شود.
     * خروجی مانند hmn_login_lock_status (شاملِ locked/remaining/fail_count/strikes/threshold).
     */
    function hmn_login_register_failure(PDO $pdo, string $phone): array
    {
        hmn_ensure_login_lockout_table($pdo);
        $cfg = hmn_login_lock_config();
        $t = hmn_table('login_lockouts');
        $phone = hmn_login_norm_phone($phone);
        try {
            $st = $pdo->prepare("SELECT fail_count, strikes, UNIX_TIMESTAMP(locked_until) AS lu, UNIX_TIMESTAMP(last_fail_at) AS lf, UNIX_TIMESTAMP(NOW()) AS now_ts FROM {$t} WHERE phone = :p LIMIT 1");
            $st->execute([':p' => $phone]);
            $row = $st->fetch(PDO::FETCH_ASSOC);

            $now = $row ? (int)$row['now_ts'] : time();
            $failCount = $row ? (int)$row['fail_count'] : 0;
            $strikes = $row ? (int)$row['strikes'] : 0;
            $lockedUntil = ($row && $row['lu']) ? (int)$row['lu'] : 0;
            $lastFail = ($row && $row['lf']) ? (int)$row['lf'] : 0;

            // اگر همین حالا قفل است، فقط زمانِ باقی‌مانده را گزارش کن (شمارنده دستکاری نشود)
            if ($lockedUntil > $now) {
                return ['locked' => true, 'remaining' => $lockedUntil - $now, 'fail_count' => $failCount, 'strikes' => $strikes, 'threshold' => $cfg['threshold']];
            }
            // decay: اگر مدتِ زیادی هیچ تلاشی نبوده، ریست کن
            if ($lastFail > 0 && ($now - $lastFail) > $cfg['decay_seconds']) {
                $failCount = 0;
                $strikes = 0;
            }

            $failCount++;

            if ($failCount >= $cfg['threshold']) {
                $strikes++;
                $minutes = (int)min($cfg['max_minutes'], $cfg['base_minutes'] * (2 ** ($strikes - 1)));
                if ($minutes < 1) {
                    $minutes = 1;
                }
                $failCount = 0; // برای چرخهٔ بعدی ریست می‌شود
                // مدتِ قفل به‌صورتِ عددِ صحیحِ محاسبه‌شده در SQL درج می‌شود (امن از تزریق).
                $sql = "INSERT INTO {$t} (phone, fail_count, strikes, locked_until, last_fail_at, updated_at)
                        VALUES (:p, :fc, :sk, (NOW() + INTERVAL {$minutes} MINUTE), NOW(), NOW())
                        ON DUPLICATE KEY UPDATE fail_count = :fc, strikes = :sk, locked_until = (NOW() + INTERVAL {$minutes} MINUTE), last_fail_at = NOW(), updated_at = NOW()";
                $pdo->prepare($sql)->execute([':p' => $phone, ':fc' => $failCount, ':sk' => $strikes]);
                return ['locked' => true, 'remaining' => $minutes * 60, 'fail_count' => $failCount, 'strikes' => $strikes, 'threshold' => $cfg['threshold']];
            }

            $sql = "INSERT INTO {$t} (phone, fail_count, strikes, locked_until, last_fail_at, updated_at)
                    VALUES (:p, :fc, :sk, NULL, NOW(), NOW())
                    ON DUPLICATE KEY UPDATE fail_count = :fc, strikes = :sk, last_fail_at = NOW(), updated_at = NOW()";
            $pdo->prepare($sql)->execute([':p' => $phone, ':fc' => $failCount, ':sk' => $strikes]);
            return ['locked' => false, 'remaining' => 0, 'fail_count' => $failCount, 'strikes' => $strikes, 'threshold' => $cfg['threshold']];
        } catch (Throwable $e) {
            return ['locked' => false, 'remaining' => 0, 'fail_count' => 0, 'strikes' => 0, 'threshold' => $cfg['threshold']];
        }
    }
}

if (!function_exists('hmn_login_register_success')) {
    /** ورودِ موفق → پاک‌کردنِ رکوردِ قفل برای این شماره. */
    function hmn_login_register_success(PDO $pdo, string $phone): void
    {
        hmn_ensure_login_lockout_table($pdo);
        $t = hmn_table('login_lockouts');
        $phone = hmn_login_norm_phone($phone);
        try {
            $pdo->prepare("DELETE FROM {$t} WHERE phone = :p")->execute([':p' => $phone]);
        } catch (Throwable $e) {
            // ignore
        }
    }
}

if (!function_exists('hmn_login_lock_guard')) {
    /** اگر شماره قفل است، با پیام مناسب پاسخ می‌دهد و درخواست را تمام می‌کند. */
    function hmn_login_lock_guard(PDO $pdo, string $phone): void
    {
        $st = hmn_login_lock_status($pdo, $phone);
        if (!empty($st['locked'])) {
            $mins = max(1, (int)ceil($st['remaining'] / 60));
            http_response_code(429);
            hmn_json_response([
                'success'    => false,
                'locked'     => true,
                'error'      => 'ورود با رمز به‌دلیلِ تلاش‌های ناموفقِ زیاد موقتاً قفل شده است. حدود ' . $mins . ' دقیقهٔ دیگر دوباره تلاش کنید.',
                'retryAfter' => (int)$st['remaining'],
            ]);
        }
    }
}

if (!function_exists('hmn_login_fail_response')) {
    /** پاسخِ «رمزِ نادرست» با آگاهی از قفل/تعداد تلاشِ باقی‌مانده. */
    function hmn_login_fail_response(array $lock, string $who, string $role): void
    {
        $base = 'رمز عبور ' . $who . ' نادرست است.';
        if (!empty($lock['locked'])) {
            $mins = max(1, (int)ceil($lock['remaining'] / 60));
            http_response_code(429);
            hmn_json_response([
                'success'         => false,
                'locked'          => true,
                'error'           => $base . ' به‌دلیلِ تلاش‌های ناموفقِ زیاد، ورود با رمز برای حدود ' . $mins . ' دقیقه قفل شد.',
                'retryAfter'      => (int)$lock['remaining'],
                'requiresPassword' => true,
                'role'            => $role,
            ]);
        }
        $left = max(0, (int)$lock['threshold'] - (int)$lock['fail_count']);
        $hint = ($left > 0 && $left <= 3) ? (' (' . $left . ' تلاش تا قفلِ موقت)') : '';
        hmn_json_response([
            'success'         => false,
            'error'           => $base . $hint,
            'requiresPassword' => true,
            'role'            => $role,
        ]);
    }
}
