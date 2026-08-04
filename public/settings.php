<?php
require_once 'config.php';
require_once 'includes/functions.php';
require_once dirname(__DIR__) . '/vendor/autoload.php';

use GardenSensors\Services\MailService;

if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];
$message = '';
$messageType = '';
$error = null;

$defaultSettings = [
    'email_notifications' => true,
    'low_battery_alert' => true,
    'moisture_alert' => true,
    'temperature_alert' => true,
    'ph_alert' => true,
    'ec_alert' => true,
    'baseline_water_ec' => 0.3,
    'notification_email' => '',
    'update_interval' => 300,
    'theme' => 'light',
    'language' => 'en',
    'timezone' => 'UTC',
];

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . (defined('DB_CHARSET') ? DB_CHARSET : 'utf8mb4'),
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    $error = 'Database connection failed: ' . $e->getMessage();
    $pdo = null;
}

function load_user_settings(PDO $pdo, int $userId, array $defaults): array
{
    $stmt = $pdo->prepare('SELECT settings FROM user_settings WHERE user_id = ?');
    $stmt->execute([$userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row || empty($row['settings'])) {
        return $defaults;
    }
    $decoded = json_decode($row['settings'], true);
    if (!is_array($decoded)) {
        return $defaults;
    }
    return array_merge($defaults, $decoded);
}

function save_user_settings(PDO $pdo, int $userId, array $settings): void
{
    $json = json_encode($settings);
    $stmt = $pdo->prepare('SELECT id FROM user_settings WHERE user_id = ?');
    $stmt->execute([$userId]);
    if ($stmt->fetch()) {
        $upd = $pdo->prepare('UPDATE user_settings SET settings = ?, updated_at = NOW() WHERE user_id = ?');
        $upd->execute([$json, $userId]);
    } else {
        $ins = $pdo->prepare('INSERT INTO user_settings (user_id, settings, created_at, updated_at) VALUES (?, ?, NOW(), NOW())');
        $ins->execute([$userId, $json]);
    }
}

$settings = $defaultSettings;
if ($pdo) {
    try {
        $settings = load_user_settings($pdo, $userId, $defaultSettings);
        $u = $pdo->prepare('SELECT email FROM users WHERE id = ?');
        $u->execute([$userId]);
        $userEmail = $u->fetchColumn() ?: '';
        if (empty($settings['notification_email']) && $userEmail) {
            $settings['notification_email'] = $userEmail;
        }
    } catch (PDOException $e) {
        $error = 'Failed to load settings: ' . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo && !$error) {
    try {
        if (isset($_POST['update_notifications'])) {
            $settings['email_notifications'] = isset($_POST['email_notifications']);
            $settings['moisture_alert'] = isset($_POST['moisture_alert']);
            $settings['temperature_alert'] = isset($_POST['temperature_alert']);
            $settings['ph_alert'] = isset($_POST['ph_alert']);
            $settings['ec_alert'] = isset($_POST['ec_alert']);
            $settings['low_battery_alert'] = isset($_POST['low_battery_alert']);
            $settings['baseline_water_ec'] = max(0, min(5, (float)($_POST['baseline_water_ec'] ?? 0.3)));
            $email = trim($_POST['notification_email'] ?? '');
            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException('Invalid notification email');
            }
            $settings['notification_email'] = $email;
            save_user_settings($pdo, $userId, $settings);
            $message = 'Notification settings saved.';
            $messageType = 'success';
        }

        if (isset($_POST['send_test_email'])) {
            $settings = load_user_settings($pdo, $userId, $defaultSettings);
            $to = $settings['notification_email'] ?? '';
            if ($to === '') {
                $u = $pdo->prepare('SELECT email FROM users WHERE id = ?');
                $u->execute([$userId]);
                $to = (string)$u->fetchColumn();
            }
            if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException('Set a valid notification email first');
            }
            $mailer = new MailService();
            $result = $mailer->send(
                $to,
                'Garden Sensors test alert',
                "This is a test email from Garden Sensors.\nConfigure MAIL_* in .env for SMTP delivery.\n"
            );
            if (!empty($result['ok'])) {
                $message = 'Test email sent via ' . $result['method'] . ' to ' . $to;
                $messageType = 'success';
            } else {
                throw new RuntimeException($result['error'] ?? 'Failed to send test email');
            }
        }
    } catch (Throwable $e) {
        $message = $e->getMessage();
        $messageType = 'danger';
    }
}

// Fix test-email body: rebuild result message without self-ref in send() string
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo && isset($_POST['send_test_email']) && $messageType === 'success' && str_contains($message, 'Test email')) {
    // already ok if we fix the send call — rewrite send block properly by re-running logic once
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - <?php echo htmlspecialchars(APP_NAME); ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="/assets/css/style.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="app-container">
        <nav class="sidebar">
            <div class="sidebar-header">
                <a href="/" class="app-logo">
                    <i class="fas fa-leaf"></i>
                    <span><?php echo htmlspecialchars(APP_NAME); ?></span>
                </a>
            </div>
            <div class="nav-menu">
                <a href="/" class="nav-link"><i class="fas fa-home"></i><span>Dashboard</span></a>
                <a href="/sensors.php" class="nav-link"><i class="fas fa-microchip"></i><span>Sensors</span></a>
                <a href="/plants.php" class="nav-link"><i class="fas fa-seedling"></i><span>Plants</span></a>
                <a href="/readings.php" class="nav-link"><i class="fas fa-chart-line"></i><span>Readings</span></a>
                <a href="/settings.php" class="nav-link active"><i class="fas fa-cog"></i><span>Settings</span></a>
                <a href="/logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
            </div>
        </nav>

        <main class="main-content">
            <?php if ($error): ?>
                <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if ($message): ?>
                <div class="alert alert-<?php echo htmlspecialchars($messageType); ?>">
                    <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <div class="dashboard-header">
                <h1 class="dashboard-title">Settings</h1>
                <p class="dashboard-subtitle">Alerts, tap-water EC baseline, and email delivery</p>
            </div>

            <div class="settings-grid">
                <section class="settings-section">
                    <div class="settings-header">
                        <h2 class="settings-title"><i class="fas fa-bell"></i> Notification Settings</h2>
                    </div>
                    <form method="POST" class="settings-form">
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="email_notifications" <?php echo !empty($settings['email_notifications']) ? 'checked' : ''; ?>>
                                Enable email notifications
                            </label>
                        </div>
                        <div class="form-group">
                            <label for="notification_email">Notification email</label>
                            <input type="email" id="notification_email" name="notification_email" class="form-control"
                                   value="<?php echo htmlspecialchars($settings['notification_email'] ?? ''); ?>"
                                   placeholder="you@example.com">
                        </div>
                        <div class="form-group">
                            <label class="checkbox-label"><input type="checkbox" name="moisture_alert" <?php echo !empty($settings['moisture_alert']) ? 'checked' : ''; ?>> Moisture (needs water)</label>
                        </div>
                        <div class="form-group">
                            <label class="checkbox-label"><input type="checkbox" name="temperature_alert" <?php echo !empty($settings['temperature_alert']) ? 'checked' : ''; ?>> Temperature (too cold / hot)</label>
                        </div>
                        <div class="form-group">
                            <label class="checkbox-label"><input type="checkbox" name="ec_alert" <?php echo !empty($settings['ec_alert']) ? 'checked' : ''; ?>> Nutrient EC (needs food / flush)</label>
                        </div>
                        <div class="form-group">
                            <label class="checkbox-label"><input type="checkbox" name="ph_alert" <?php echo !empty($settings['ph_alert']) ? 'checked' : ''; ?>> Soil pH</label>
                        </div>
                        <div class="form-group">
                            <label class="checkbox-label"><input type="checkbox" name="low_battery_alert" <?php echo !empty($settings['low_battery_alert']) ? 'checked' : ''; ?>> Low battery</label>
                        </div>
                        <div class="form-group">
                            <label for="baseline_water_ec">Tap-water EC baseline (mS/cm)</label>
                            <input type="number" step="0.01" min="0" max="5" id="baseline_water_ec" name="baseline_water_ec" class="form-control"
                                   value="<?php echo htmlspecialchars((string)($settings['baseline_water_ec'] ?? 0.3)); ?>">
                            <small class="form-text">Measure a cup of your tap water and enter that EC. Available nutrient EC = soil EC − baseline.</small>
                        </div>
                        <button type="submit" name="update_notifications" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
                        <button type="submit" name="send_test_email" class="btn btn-secondary" style="margin-left:0.5rem;"><i class="fas fa-paper-plane"></i> Send test email</button>
                    </form>
                    <p class="form-text" style="margin-top:1rem;">SMTP: set <code>MAIL_HOST</code>, <code>MAIL_USERNAME</code>, <code>MAIL_PASSWORD</code> in <code>.env</code> for reliable delivery. Cron: <code>*/5 * * * * php …/public/cron/check_alerts.php</code></p>
                </section>

                <section class="settings-section">
                    <div class="settings-header">
                        <h2 class="settings-title"><i class="fas fa-info-circle"></i> System Information</h2>
                    </div>
                    <div class="info-grid">
                        <div class="info-item"><span class="info-label">PHP</span><span class="info-value"><?php echo phpversion(); ?></span></div>
                        <?php if ($pdo): ?>
                        <div class="info-item"><span class="info-label">Database</span><span class="info-value">MySQL <?php echo htmlspecialchars($pdo->getAttribute(PDO::ATTR_SERVER_VERSION)); ?></span></div>
                        <?php endif; ?>
                        <div class="info-item"><span class="info-label">Mail SMTP</span><span class="info-value"><?php echo (new MailService())->isConfigured() ? 'Configured' : 'Not configured (mail() fallback)'; ?></span></div>
                    </div>
                </section>
            </div>
        </main>
    </div>
</body>
</html>
