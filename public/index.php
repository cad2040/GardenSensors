<?php
session_start();
require_once 'config.php';
require_once 'includes/functions.php';
require_once 'includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get user information
$user_id = $_SESSION['user_id'];
$user = getUserById($user_id);

if (!$user) {
    // If user not found, log out
    session_destroy();
    header('Location: login.php');
    exit();
}

// Handle tab switching
$activeTab = isset($_GET['tab']) ? sanitizeInput($_GET['tab']) : 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo APP_NAME; ?></title>
    
    <!-- Fonts and Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="images/favicon.png">
</head>
<body>
    <!-- Main Navigation -->
    <nav class="main-nav">
        <div class="nav-links">
            <a href="index.php?tab=dashboard" class="<?php echo $activeTab === 'dashboard' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="index.php?tab=sensors" class="<?php echo $activeTab === 'sensors' ? 'active' : ''; ?>">
                <i class="fas fa-microchip"></i> Sensors
            </a>
            <a href="index.php?tab=plants" class="<?php echo $activeTab === 'plants' ? 'active' : ''; ?>">
                <i class="fas fa-leaf"></i> Plants
            </a>
            <a href="index.php?tab=settings" class="<?php echo $activeTab === 'settings' ? 'active' : ''; ?>">
                <i class="fas fa-cog"></i> Settings
            </a>
        </div>
    </nav>

    <!-- Dashboard Container -->
    <div class="dashboard-container">
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <div class="welcome-message">
                Welcome, <?php echo htmlspecialchars($user['name']); ?>
                <a href="logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <?php if ($activeTab === 'dashboard'): ?>
        <!-- Dashboard Overview -->
        <div class="dashboard-overview">
            <!-- Sensor Status -->
            <div class="overview-card">
                <h2><i class="fas fa-microchip"></i> Sensor Status</h2>
                <div id="sensor-status" class="loading-container">
                    <div class="loading-spinner"></div>
                    <div>Loading sensor status...</div>
                </div>
            </div>

            <!-- Plant Health -->
            <div class="overview-card">
                <h2><i class="fas fa-leaf"></i> Plant Health</h2>
                <div id="plant-health" class="loading-container">
                    <div class="loading-spinner"></div>
                    <div>Loading plant health data...</div>
                </div>
            </div>

            <!-- Recent Readings -->
            <div class="overview-card">
                <h2><i class="fas fa-chart-line"></i> Recent Readings</h2>
                <div id="recent-readings" class="loading-container">
                    <div class="loading-spinner"></div>
                    <div>Loading recent readings...</div>
                </div>
            </div>

            <!-- Alerts -->
            <div class="overview-card">
                <h2><i class="fas fa-bell"></i> Alerts</h2>
                <div id="alerts" class="loading-container">
                    <div class="loading-spinner"></div>
                    <div>Loading alerts...</div>
                </div>
            </div>
        </div>
        <?php elseif ($activeTab === 'sensors'): ?>
        <div id="sensors-content">
            <h2><i class="fas fa-microchip"></i> Sensors Management</h2>
            <div class="loading-container">
                <div class="loading-spinner"></div>
                <div>Loading sensors...</div>
            </div>
        </div>
        <?php elseif ($activeTab === 'plants'): ?>
        <div id="plants-content">
            <h2><i class="fas fa-leaf"></i> Plants Management</h2>
            <div class="loading-container">
                <div class="loading-spinner"></div>
                <div>Loading plants...</div>
            </div>
        </div>
        <?php elseif ($activeTab === 'settings'): ?>
        <div id="settings-content">
            <h2><i class="fas fa-cog"></i> Settings</h2>
            <div class="loading-container">
                <div class="loading-spinner"></div>
                <div>Loading settings...</div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="js/main.js"></script>
    <script src="js/actions.js"></script>
    <script>
        $(document).ready(function() {
            // Load dashboard data
            function loadDashboardData() {
                if ('<?php echo $activeTab; ?>' === 'dashboard') {
                    // Load sensor status
                    $.get('get_tab_data.php?tab=sensors&dashboard=1', function(response) {
                        if (response.success) {
                            $('#sensor-status').html(response.content);
                        } else {
                            $('#sensor-status').html('<div class="error-message">' + response.message + '</div>');
                        }
                    }).fail(function() {
                        $('#sensor-status').html('<div class="error-message">Failed to load sensor status</div>');
                    });

                    // Load plant health
                    $.get('get_tab_data.php?tab=plants&dashboard=1', function(response) {
                        if (response.success) {
                            $('#plant-health').html(response.content);
                        } else {
                            $('#plant-health').html('<div class="error-message">' + response.message + '</div>');
                        }
                    }).fail(function() {
                        $('#plant-health').html('<div class="error-message">Failed to load plant health</div>');
                    });

                    // Load recent readings
                    $.get('get_tab_data.php?tab=readings&dashboard=1', function(response) {
                        if (response.success) {
                            $('#recent-readings').html(response.content);
                        } else {
                            $('#recent-readings').html('<div class="error-message">' + response.message + '</div>');
                        }
                    }).fail(function() {
                        $('#recent-readings').html('<div class="error-message">Failed to load recent readings</div>');
                    });

                    // Load alerts
                    $.get('get_tab_data.php?tab=settings&dashboard=1', function(response) {
                        if (response.success) {
                            $('#alerts').html(response.content);
                        } else {
                            $('#alerts').html('<div class="error-message">' + response.message + '</div>');
                        }
                    }).fail(function() {
                        $('#alerts').html('<div class="error-message">Failed to load alerts</div>');
                    });
                } else {
                    // Load content based on active tab
                    const contentId = '<?php echo $activeTab; ?>-content';
                    $.get(`get_tab_data.php?tab=<?php echo $activeTab; ?>`, function(response) {
                        if (response.success) {
                            $(`#${contentId}`).html(response.content);
                        } else {
                            $(`#${contentId}`).html('<div class="error-message">' + response.message + '</div>');
                        }
                    }).fail(function() {
                        $(`#${contentId}`).html('<div class="error-message">Failed to load content</div>');
                    });
                }
            }

            // Initial load
            loadDashboardData();

            // Refresh every 5 minutes
            setInterval(loadDashboardData, 300000);
        });
    </script>
</body>
</html> 