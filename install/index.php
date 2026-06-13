<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>نصب سیستم پیشبینی جام جهانی ۲۰۲۶</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:Tahoma,sans-serif;background:#f0f4f8;display:flex;justify-content:center;align-items:flex-start;min-height:100vh;padding:2rem 1rem}
.card{background:#fff;border-radius:16px;box-shadow:0 4px 32px rgba(0,0,0,.1);padding:2.5rem;max-width:560px;width:100%}
h1{color:#0b1f3f;font-size:1.4rem;margin-bottom:.4rem}
p.sub{color:#64748b;font-size:.85rem;margin-bottom:2rem}
.section{margin-bottom:1.75rem;padding-bottom:1.75rem;border-bottom:1px solid #e4e8f0}
.section:last-of-type{border-bottom:none}
h2{font-size:1rem;color:#0b1f3f;margin-bottom:1rem;font-weight:700}
label{display:block;font-size:.82rem;color:#374151;font-weight:600;margin-bottom:.35rem}
input[type=text],input[type=password],input[type=number]{width:100%;padding:.6rem .85rem;border:1.5px solid #d1d5db;border-radius:8px;font-size:.9rem;font-family:inherit;color:#111;transition:border .2s}
input:focus{outline:none;border-color:#0b1f3f}
.row{display:grid;grid-template-columns:1fr 1fr;gap:1rem}
.hint{font-size:.75rem;color:#94a3b8;margin-top:.25rem}
.btn{display:block;width:100%;padding:.85rem;background:#0b1f3f;color:#fff;border:none;border-radius:10px;font-size:1rem;font-weight:700;cursor:pointer;font-family:inherit;margin-top:1.5rem;transition:background .2s}
.btn:hover{background:#1a3a72}
.msg{padding:.85rem 1rem;border-radius:8px;margin-bottom:1.25rem;font-size:.88rem;font-weight:600}
.msg.ok{background:#d1fae5;color:#065f46;border:1px solid #6ee7b7}
.msg.err{background:#fee2e2;color:#991b1b;border:1px solid #fca5a5}
.steps{display:flex;gap:.5rem;margin-bottom:2rem}
.step{flex:1;height:4px;border-radius:2px;background:#e2e8f0}
.step.done{background:#0b1f3f}
.step.active{background:#c8890a}
</style>
</head>
<body>
<div class="card">
  <h1>⚙️ نصب سیستم پیشبینی</h1>
  <p class="sub">جام جهانی فیفا ۲۰۲۶</p>

<?php
$configPath = dirname(__DIR__) . '/config.php';
$msg = '';
$msgType = '';

// Already installed?
$installed = file_exists($configPath);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['step'])) {
    $step = (int)$_POST['step'];

    if ($step === 1) {
        // Save config
        $dbHost = trim($_POST['db_host'] ?? 'localhost');
        $dbName = trim($_POST['db_name'] ?? '');
        $dbUser = trim($_POST['db_user'] ?? '');
        $dbPass = $_POST['db_pass'] ?? '';
        $prefix = preg_replace('/[^a-z0-9_]/i', '', trim($_POST['table_prefix'] ?? 'wc_'));
        if (!$prefix) $prefix = 'wc_';

        if (!$dbName || !$dbUser) {
            $msg = 'نام دیتابیس و نام کاربری را وارد کنید.';
            $msgType = 'err';
        } else {
            // Test connection
            try {
                $pdo = new PDO("mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                ]);
                // Write config
                $conf = "<?php\nreturn [\n    'db_host' => " . var_export($dbHost, true) . ",\n    'db_name' => " . var_export($dbName, true) . ",\n    'db_user' => " . var_export($dbUser, true) . ",\n    'db_pass' => " . var_export($dbPass, true) . ",\n    'db_charset' => 'utf8mb4',\n    'table_prefix' => " . var_export($prefix . '_', true) . ",\n];\n";
                // Note: prefix already has _ in var if entered as 'wc', we add _ after
                // Let me fix: store prefix as-is, user enters 'wc_' or 'wc'
                $prefix2 = rtrim($prefix, '_') . '_';
                $conf = "<?php\nreturn [\n    'db_host'      => " . var_export($dbHost, true) . ",\n    'db_name'      => " . var_export($dbName, true) . ",\n    'db_user'      => " . var_export($dbUser, true) . ",\n    'db_pass'      => " . var_export($dbPass, true) . ",\n    'db_charset'   => 'utf8mb4',\n    'table_prefix' => " . var_export($prefix2, true) . ",\n];\n";
                file_put_contents($configPath, $conf);
                $msg = 'اتصال به دیتابیس موفق بود. فایل config.php ذخیره شد.';
                $msgType = 'ok';
                $installed = true;
                $_POST['step'] = 2; // Move to step 2
            } catch (Exception $e) {
                $msg = 'خطا در اتصال به دیتابیس: ' . htmlspecialchars($e->getMessage());
                $msgType = 'err';
            }
        }
    }

    if ((int)$_POST['step'] === 2 && $installed) {
        // Create tables + admin
        try {
            require dirname(__DIR__) . '/backend/db.php';
            require dirname(__DIR__) . '/backend/worldcup.php';
            $pdo = hmn_get_db();
            wc_ensure_tables($pdo);

            // Create admin
            $adminPhone = preg_replace('/\D/', '', trim($_POST['admin_phone'] ?? ''));
            $adminPass  = $_POST['admin_pass'] ?? '';
            $adminName  = trim($_POST['admin_name'] ?? 'ادمین');
            if ($adminPhone && $adminPass) {
                $px = hmn_table('admins');
                $hash = password_hash($adminPass, PASSWORD_BCRYPT);
                $pdo->prepare("INSERT INTO {$px} (phone,name,password_hash) VALUES (:p,:n,:h) ON DUPLICATE KEY UPDATE password_hash=:h, name=:n")
                    ->execute([':p'=>$adminPhone,':n'=>$adminName,':h'=>$hash]);
            }

            $seeded = wc_seed_matches($pdo);
            $sync = wc_sync_default_bets_to_all_matches($pdo);

            $msg = '✅ جداول ساخته شدند، برنامه اولیه مسابقات بارگذاری شد و پنل ادمین آماده است.';
            if ($seeded > 0) {
                $msg .= ' (' . $seeded . ' بازی اولیه)';
            }
            $msgType = 'ok';
        } catch (Exception $e) {
            $msg = 'خطا: ' . htmlspecialchars($e->getMessage());
            $msgType = 'err';
        }
    }
}
?>

  <?php if ($msg): ?>
  <div class="msg <?= $msgType ?>"><?= $msg ?></div>
  <?php endif; ?>

  <?php if ($installed && $msgType === 'ok' && (int)($_POST['step'] ?? 0) === 2): ?>
  <div style="text-align:center;padding:1rem">
    <p style="font-size:1.1rem;color:#065f46;font-weight:700;margin-bottom:1rem">🎉 نصب کامل شد!</p>
    <a href="/" style="display:inline-block;padding:.7rem 2rem;background:#0b1f3f;color:#fff;border-radius:10px;text-decoration:none;font-weight:700">رفتن به سایت</a>
    &nbsp;
    <a href="/admin" style="display:inline-block;padding:.7rem 2rem;background:#c8890a;color:#fff;border-radius:10px;text-decoration:none;font-weight:700">پنل ادمین</a>
  </div>
  <?php else: ?>

  <!-- Step 1: DB Config -->
  <div class="section">
    <h2>۱. اتصال به دیتابیس</h2>
    <form method="POST">
      <input type="hidden" name="step" value="1">
      <div class="row" style="margin-bottom:1rem">
        <div>
          <label>هاست دیتابیس</label>
          <input type="text" name="db_host" value="<?= htmlspecialchars($_POST['db_host'] ?? 'localhost') ?>">
        </div>
        <div>
          <label>نام دیتابیس</label>
          <input type="text" name="db_name" value="<?= htmlspecialchars($_POST['db_name'] ?? '') ?>" placeholder="worldcup">
        </div>
      </div>
      <div class="row" style="margin-bottom:1rem">
        <div>
          <label>نام کاربری</label>
          <input type="text" name="db_user" value="<?= htmlspecialchars($_POST['db_user'] ?? 'root') ?>">
        </div>
        <div>
          <label>رمز عبور دیتابیس</label>
          <input type="password" name="db_pass" value="<?= htmlspecialchars($_POST['db_pass'] ?? '') ?>">
        </div>
      </div>
      <div>
        <label>پیشوند جداول</label>
        <input type="text" name="table_prefix" value="<?= htmlspecialchars($_POST['table_prefix'] ?? 'wc_') ?>" style="max-width:140px">
        <p class="hint">مثلاً: wc_ &nbsp;→&nbsp; جداول: wc_users, wc_matches, ...</p>
      </div>
      <button type="submit" class="btn">تست اتصال و ذخیره config.php</button>
    </form>
  </div>

  <!-- Step 2: Create tables + admin -->
  <?php if ($installed): ?>
  <div class="section">
    <h2>۲. ساخت جداول + ادمین اولیه</h2>
    <form method="POST">
      <input type="hidden" name="step" value="2">
      <div style="margin-bottom:1rem">
        <label>نام ادمین</label>
        <input type="text" name="admin_name" value="<?= htmlspecialchars($_POST['admin_name'] ?? 'ادمین') ?>">
      </div>
      <div class="row">
        <div>
          <label>شماره موبایل ادمین</label>
          <input type="text" name="admin_phone" value="<?= htmlspecialchars($_POST['admin_phone'] ?? '') ?>" placeholder="09xxxxxxxxx">
        </div>
        <div>
          <label>رمز عبور ادمین</label>
          <input type="password" name="admin_pass" placeholder="حداقل ۶ کاراکتر">
        </div>
      </div>
      <p class="hint" style="margin-top:.75rem">پس از نصب، لوگوی ارسالی شما به‌صورت پیش‌فرض در سایت استفاده می‌شود و از پنل ادمین قابل تغییر است.</p>
      <button type="submit" class="btn" style="background:#065f46">ساخت جداول و ادمین</button>
    </form>
  </div>
  <?php else: ?>
  <p style="color:#94a3b8;font-size:.82rem;margin-top:.5rem">ابتدا مرحله ۱ را کامل کنید.</p>
  <?php endif; ?>

  <?php endif; ?>
</div>
</body>
</html>
