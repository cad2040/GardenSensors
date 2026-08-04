<?php
/**
 * First-run web setup: create admin user + user_settings, then lock.
 * Safe to expose only until setup.lock exists.
 */
declare(strict_types=1);

$root = dirname(__DIR__, 2);
$lockFile = $root . '/storage/setup.lock';
if (!is_dir($root . '/storage')) {
    @mkdir($root . '/storage', 0755, true);
}

require_once __DIR__ . '/../config.php';
require_once $root . '/vendor/autoload.php';

header('Content-Type: text/html; charset=UTF-8');

function setup_is_locked(string $lockFile): bool
{
    return is_file($lockFile);
}

function setup_pdo(): PDO
{
    return new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
}

$error = '';
$done = false;

if (setup_is_locked($lockFile)) {
    $error = 'Setup is already complete. Delete storage/setup.lock only if you intentionally want to re-run first-run.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error === '') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    try {
        if ($username === '' || strlen($username) < 3) {
            throw new InvalidArgumentException('Username must be at least 3 characters');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Valid email required');
        }
        if (strlen($password) < 8) {
            throw new InvalidArgumentException('Password must be at least 8 characters');
        }

        $pdo = setup_pdo();
        $hash = password_hash($password, PASSWORD_DEFAULT);

        // Replace seed admin or insert
        $exists = $pdo->query("SELECT id FROM users WHERE username = 'admin' OR id = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if ($exists) {
            $stmt = $pdo->prepare('UPDATE users SET username = ?, email = ?, password_hash = ?, role = COALESCE(role, \'admin\'), status = \'active\', updated_at = NOW() WHERE id = ?');
            $stmt->execute([$username, $email, $hash, $exists['id']]);
            $userId = (int)$exists['id'];
        } else {
            $stmt = $pdo->prepare('INSERT INTO users (username, email, password_hash, role, status, created_at, updated_at) VALUES (?, ?, ?, \'admin\', \'active\', NOW(), NOW())');
            $stmt->execute([$username, $email, $hash]);
            $userId = (int)$pdo->lastInsertId();
        }

        $settings = json_encode([
            'email_notifications' => true,
            'moisture_alert' => true,
            'temperature_alert' => true,
            'ph_alert' => true,
            'ec_alert' => true,
            'baseline_water_ec' => 0.3,
            'notification_email' => $email,
            'update_interval' => 300,
            'theme' => 'light',
            'language' => 'en',
            'timezone' => 'UTC',
        ]);

        $chk = $pdo->prepare('SELECT id FROM user_settings WHERE user_id = ?');
        $chk->execute([$userId]);
        if ($chk->fetch()) {
            $pdo->prepare('UPDATE user_settings SET settings = ?, updated_at = NOW() WHERE user_id = ?')->execute([$settings, $userId]);
        } else {
            $pdo->prepare('INSERT INTO user_settings (user_id, settings, created_at, updated_at) VALUES (?, ?, NOW(), NOW())')->execute([$userId, $settings]);
        }

        file_put_contents($lockFile, date('c') . " user_id={$userId}\n");
        $done = true;
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Garden Sensors — First-run setup</title>
  <style>
    body { font-family: Georgia, serif; background: #0f2418; color: #e8f0ea; margin: 0; min-height: 100vh; display: grid; place-items: center; }
    .card { background: #163526; padding: 2rem; border-radius: 8px; width: min(420px, 92vw); box-shadow: 0 12px 40px rgba(0,0,0,.35); }
    h1 { font-size: 1.4rem; margin: 0 0 .5rem; color: #9fd4a8; }
    p { opacity: .85; line-height: 1.45; }
    label { display: block; margin: .9rem 0 .35rem; font-size: .9rem; }
    input { width: 100%; padding: .65rem .7rem; border: 1px solid #2f5a40; border-radius: 4px; background: #0f2418; color: #e8f0ea; box-sizing: border-box; }
    button { margin-top: 1.2rem; width: 100%; padding: .75rem; border: 0; border-radius: 4px; background: #3d8f55; color: #fff; font-weight: 600; cursor: pointer; }
    .err { background: #5a1f1f; padding: .7rem; border-radius: 4px; margin-bottom: 1rem; }
    .ok { background: #1f5a32; padding: .7rem; border-radius: 4px; }
    a { color: #9fd4a8; }
  </style>
</head>
<body>
  <div class="card">
    <h1>Garden Sensors setup</h1>
    <?php if ($done): ?>
      <div class="ok">Admin account created. <a href="/login.php">Sign in</a></div>
    <?php elseif ($error && setup_is_locked($lockFile)): ?>
      <div class="err"><?php echo htmlspecialchars($error); ?></div>
      <p><a href="/login.php">Go to login</a></p>
    <?php else: ?>
      <p>Create your admin account. This runs once.</p>
      <?php if ($error): ?><div class="err"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
      <form method="post">
        <label>Username</label>
        <input name="username" required minlength="3" value="<?php echo htmlspecialchars($_POST['username'] ?? 'admin'); ?>">
        <label>Email</label>
        <input type="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
        <label>Password</label>
        <input type="password" name="password" required minlength="8">
        <button type="submit">Finish setup</button>
      </form>
    <?php endif; ?>
  </div>
</body>
</html>
