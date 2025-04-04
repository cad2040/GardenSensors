<?php
require_once 'config.php';
require_once 'includes/functions.php';

// Initialize database connection
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
        DB_USER,
        DB_PASSWORD,
        array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
    );
} catch (PDOException $e) {
    $error = "Database connection failed: " . $e->getMessage();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = '';
    $messageType = '';

    // Update notification settings
    if (isset($_POST['update_notifications'])) {
        try {
            $stmt = $pdo->prepare("
                UPDATE settings 
                SET 
                    email_notifications = ?,
                    notification_threshold = ?,
                    notification_email = ?
                WHERE id = 1
            ");
            
            $stmt->execute([
                isset($_POST['email_notifications']) ? 1 : 0,
                $_POST['notification_threshold'],
                $_POST['notification_email']
            ]);
            
            $message = "Notification settings updated successfully.";
            $messageType = "success";
        } catch (PDOException $e) {
            $message = "Failed to update notification settings: " . $e->getMessage();
            $messageType = "danger";
        }
    }

    // Update reading intervals
    if (isset($_POST['update_intervals'])) {
        try {
            $stmt = $pdo->prepare("
                UPDATE settings 
                SET 
                    reading_interval = ?,
                    data_retention_days = ?
                WHERE id = 1
            ");
            
            $stmt->execute([
                $_POST['reading_interval'],
                $_POST['data_retention_days']
            ]);
            
            $message = "Reading intervals updated successfully.";
            $messageType = "success";
        } catch (PDOException $e) {
            $message = "Failed to update reading intervals: " . $e->getMessage();
            $messageType = "danger";
        }
    }
}

// Fetch current settings
$settings = [];
if (!isset($error)) {
    try {
        $stmt = $pdo->query("SELECT * FROM settings WHERE id = 1");
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error = "Failed to fetch settings: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - <?php echo htmlspecialchars(APP_NAME); ?></title>
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="/assets/css/style.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="app-container">
        <!-- Sidebar Navigation -->
        <nav class="sidebar">
            <div class="sidebar-header">
                <a href="/" class="app-logo">
                    <i class="fas fa-leaf"></i>
                    <span><?php echo htmlspecialchars(APP_NAME); ?></span>
                </a>
            </div>
            <div class="nav-menu">
                <a href="/" class="nav-link">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
                <a href="/sensors.php" class="nav-link">
                    <i class="fas fa-microchip"></i>
                    <span>Sensors</span>
                </a>
                <a href="/readings.php" class="nav-link">
                    <i class="fas fa-chart-line"></i>
                    <span>Readings</span>
                </a>
                <a href="/settings.php" class="nav-link active">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
                <a href="/logout.php" class="nav-link">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($message)): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <div class="dashboard-header">
                <h1 class="dashboard-title">Settings</h1>
                <p class="dashboard-subtitle">Configure your garden monitoring system</p>
            </div>

            <!-- Settings Grid -->
            <div class="settings-grid">
                <!-- Notification Settings -->
                <section class="settings-section">
                    <div class="settings-header">
                        <h2 class="settings-title">
                            <i class="fas fa-bell"></i>
                            Notification Settings
                        </h2>
                    </div>
                    <form action="settings.php" method="POST" class="settings-form">
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" 
                                       name="email_notifications"
                                       <?php echo isset($settings['email_notifications']) && $settings['email_notifications'] ? 'checked' : ''; ?>>
                                Enable Email Notifications
                            </label>
                        </div>
                        <div class="form-group">
                            <label for="notification_email">Notification Email</label>
                            <input type="email" 
                                   id="notification_email" 
                                   name="notification_email" 
                                   class="form-control"
                                   value="<?php echo htmlspecialchars($settings['notification_email'] ?? ''); ?>"
                                   placeholder="Enter email address">
                        </div>
                        <div class="form-group">
                            <label for="notification_threshold">Alert Threshold (minutes)</label>
                            <input type="number" 
                                   id="notification_threshold" 
                                   name="notification_threshold" 
                                   class="form-control"
                                   min="1"
                                   value="<?php echo htmlspecialchars($settings['notification_threshold'] ?? '30'); ?>">
                            <small class="form-text">Send alerts if no readings received within this time period</small>
                        </div>
                        <button type="submit" name="update_notifications" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            Save Notification Settings
                        </button>
                    </form>
                </section>

                <!-- Reading Intervals -->
                <section class="settings-section">
                    <div class="settings-header">
                        <h2 class="settings-title">
                            <i class="fas fa-clock"></i>
                            Reading Intervals
                        </h2>
                    </div>
                    <form action="settings.php" method="POST" class="settings-form">
                        <div class="form-group">
                            <label for="reading_interval">Reading Interval (minutes)</label>
                            <input type="number" 
                                   id="reading_interval" 
                                   name="reading_interval" 
                                   class="form-control"
                                   min="1"
                                   value="<?php echo htmlspecialchars($settings['reading_interval'] ?? '5'); ?>">
                            <small class="form-text">How often to collect sensor readings</small>
                        </div>
                        <div class="form-group">
                            <label for="data_retention_days">Data Retention (days)</label>
                            <input type="number" 
                                   id="data_retention_days" 
                                   name="data_retention_days" 
                                   class="form-control"
                                   min="1"
                                   value="<?php echo htmlspecialchars($settings['data_retention_days'] ?? '30'); ?>">
                            <small class="form-text">How long to keep historical data</small>
                        </div>
                        <button type="submit" name="update_intervals" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            Save Interval Settings
                        </button>
                    </form>
                </section>

                <!-- System Information -->
                <section class="settings-section">
                    <div class="settings-header">
                        <h2 class="settings-title">
                            <i class="fas fa-info-circle"></i>
                            System Information
                        </h2>
                    </div>
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">PHP Version</span>
                            <span class="info-value"><?php echo phpversion(); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Database</span>
                            <span class="info-value">MySQL <?php echo $pdo->getAttribute(PDO::ATTR_SERVER_VERSION); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Server</span>
                            <span class="info-value"><?php echo $_SERVER['SERVER_SOFTWARE']; ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Application Version</span>
                            <span class="info-value"><?php echo htmlspecialchars(APP_VERSION); ?></span>
                        </div>
                    </div>
                </section>
            </div>
        </main>
    </div>

    <!-- JavaScript -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="/assets/js/main.js"></script>
</body>
</html> 