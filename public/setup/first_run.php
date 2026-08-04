<?php
/**
 * Programmatic first-run (used by scripts/docker-install.sh).
 * POST: username, email, password
 */
declare(strict_types=1);

$root = dirname(__DIR__, 2);
$lockFile = $root . '/storage/setup.lock';
header('Content-Type: application/json');

if (is_file($lockFile)) {
    http_response_code(409);
    echo json_encode(['success' => false, 'error' => 'Setup already complete']);
    exit;
}

require_once __DIR__ . '/../config.php';

$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = (string)($_POST['password'] ?? '');

try {
    if (strlen($username) < 3 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
        throw new InvalidArgumentException('Invalid username, email, or password');
    }
    if (!is_dir($root . '/storage')) {
        mkdir($root . '/storage', 0755, true);
    }
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $exists = $pdo->query("SELECT id FROM users WHERE username = 'admin' OR id = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if ($exists) {
        $pdo->prepare('UPDATE users SET username = ?, email = ?, password_hash = ?, updated_at = NOW() WHERE id = ?')
            ->execute([$username, $email, $hash, $exists['id']]);
        $userId = (int)$exists['id'];
    } else {
        $pdo->prepare('INSERT INTO users (username, email, password_hash, role, status, created_at, updated_at) VALUES (?, ?, ?, \'admin\', \'active\', NOW(), NOW())')
            ->execute([$username, $email, $hash]);
        $userId = (int)$pdo->lastInsertId();
    }
    $settings = json_encode([
        'email_notifications' => true,
        'moisture_alert' => true,
        'temperature_alert' => true,
        'ec_alert' => true,
        'ph_alert' => true,
        'baseline_water_ec' => 0.3,
        'notification_email' => $email,
    ]);
    $chk = $pdo->prepare('SELECT id FROM user_settings WHERE user_id = ?');
    $chk->execute([$userId]);
    if ($chk->fetch()) {
        $pdo->prepare('UPDATE user_settings SET settings = ?, updated_at = NOW() WHERE user_id = ?')->execute([$settings, $userId]);
    } else {
        $pdo->prepare('INSERT INTO user_settings (user_id, settings, created_at, updated_at) VALUES (?, ?, NOW(), NOW())')->execute([$userId, $settings]);
    }
    file_put_contents($lockFile, date('c') . " user_id={$userId}\n");
    echo json_encode(['success' => true, 'user_id' => $userId]);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
