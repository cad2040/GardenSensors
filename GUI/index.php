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
$activeTab = isset($_GET['tab']) ? sanitizeInput($_GET['tab']) : 'Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?></title>
    
    <!-- Fonts and Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="images/favicon.png">
</head>
<body class="dashboard-body">
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="navbar-brand">
            <i class="fas fa-leaf"></i>
            <span><?php echo APP_NAME; ?></span>
        </div>
        <div class="navbar-menu">
            <div class="navbar-user">
                <span>Welcome, <?php echo htmlspecialchars($user['username']); ?></span>
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="dashboard-container">
        <!-- Sidebar Navigation -->
        <div class="sidebar">
            <div class="sidebar-menu">
                <a href="#" class="menu-item <?php echo $activeTab === 'Dashboard' ? 'active' : ''; ?>" data-tab="Dashboard">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="#" class="menu-item <?php echo $activeTab === 'Sensors' ? 'active' : ''; ?>" data-tab="Sensors">
                    <i class="fas fa-microchip"></i> Sensors
                </a>
                <a href="#" class="menu-item <?php echo $activeTab === 'Plants' ? 'active' : ''; ?>" data-tab="Plants">
                    <i class="fas fa-seedling"></i> Plants
                </a>
                <a href="#" class="menu-item <?php echo $activeTab === 'Settings' ? 'active' : ''; ?>" data-tab="Settings">
                    <i class="fas fa-cog"></i> Settings
                </a>
            </div>
        </div>

        <!-- Content Area -->
        <div class="content-area">
            <!-- Dashboard Tab -->
            <div class="tab-content <?php echo $activeTab === 'Dashboard' ? 'active' : ''; ?>" id="Dashboard">
                <h2>Dashboard Overview</h2>
                <div class="dashboard-grid">
                    <!-- Sensor Status Cards -->
                    <div class="card sensor-status">
                        <h3><i class="fas fa-microchip"></i> Sensor Status</h3>
                        <div class="card-content" id="sensor-status">
                            Loading...
                        </div>
                    </div>

                    <!-- Plant Health Cards -->
                    <div class="card plant-health">
                        <h3><i class="fas fa-leaf"></i> Plant Health</h3>
                        <div class="card-content" id="plant-health">
                            Loading...
                        </div>
                    </div>

                    <!-- Recent Readings -->
                    <div class="card recent-readings">
                        <h3><i class="fas fa-chart-line"></i> Recent Readings</h3>
                        <div class="card-content" id="recent-readings">
                            Loading...
                        </div>
                    </div>

                    <!-- Alerts -->
                    <div class="card alerts">
                        <h3><i class="fas fa-bell"></i> Alerts</h3>
                        <div class="card-content" id="alerts">
                            Loading...
                        </div>
                    </div>
                </div>
            </div>

            <!-- Other tabs will be loaded dynamically -->
            <div class="tab-content <?php echo $activeTab === 'Sensors' ? 'active' : ''; ?>" id="Sensors"></div>
            <div class="tab-content <?php echo $activeTab === 'Plants' ? 'active' : ''; ?>" id="Plants"></div>
            <div class="tab-content <?php echo $activeTab === 'Settings' ? 'active' : ''; ?>" id="Settings"></div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div class="loading-overlay">
        <div class="spinner"></div>
    </div>

    <!-- JavaScript -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="js/main.js"></script>
</body>
</html> 