<?php

/**
 * Garden Sensors Dashboard - Entry Point
 */

// Load configuration
require_once __DIR__ . '/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page
    header('Location: login.php');
    exit;
}

// Include necessary files
require_once __DIR__ . '/includes/functions.php';

// Initialize database connection
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
    );
} catch (PDOException $e) {
    $error = "Database connection failed: " . $e->getMessage();
}

// Fetch latest sensor readings
$latestReadings = [];
if (!isset($error)) {
    try {
        $stmt = $pdo->query("
            SELECT sensor_id, sensor_type, reading_value, reading_timestamp 
            FROM sensor_readings 
            WHERE reading_timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ORDER BY reading_timestamp DESC 
            LIMIT 10
        ");
        $latestReadings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error = "Failed to fetch sensor readings: " . $e->getMessage();
    }
}

// Calculate averages
$averages = [
    'temperature' => 0,
    'humidity' => 0,
    'moisture' => 0
];

if (!empty($latestReadings)) {
    $counts = array_fill_keys(array_keys($averages), 0);
    foreach ($latestReadings as $reading) {
        if (isset($averages[$reading['sensor_type']])) {
            $averages[$reading['sensor_type']] += $reading['reading_value'];
            $counts[$reading['sensor_type']]++;
        }
    }
    foreach ($averages as $type => &$value) {
        if ($counts[$type] > 0) {
            $value = round($value / $counts[$type], 1);
        }
    }
}

// Fetch plants for filter dropdown
$plants = [];
if (!isset($error)) {
    try {
        // First try to get plants for the current user
        $stmt = $pdo->prepare("SELECT id, name, species FROM plants WHERE user_id = ? AND status = 'active' ORDER BY name");
        $stmt->execute([$_SESSION['user_id']]);
        $plants = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // If no plants found and user is not admin, also include test plants (user_id = 1) for development
        if (empty($plants) && $_SESSION['user_id'] != 1) {
            $stmt = $pdo->prepare("SELECT id, name, species FROM plants WHERE user_id = 1 AND name IN ('Tomato Plant', 'Basil Plant', 'Lettuce Plant') AND status = 'active' ORDER BY name");
            $stmt->execute();
            $plants = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        // Silently fail - plants dropdown will be empty
    }
}

// Set page title
$page_title = 'Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - <?php echo htmlspecialchars(APP_NAME); ?></title>
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Bokeh CSS -->
    <link href="https://cdn.bokeh.org/bokeh/release/bokeh-3.8.0.min.css" rel="stylesheet">
    <link href="https://cdn.bokeh.org/bokeh/release/bokeh-widgets-3.8.0.min.css" rel="stylesheet">
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
                <a href="/" class="nav-link active">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
                <a href="/sensors.php" class="nav-link">
                    <i class="fas fa-microchip"></i>
                    <span>Sensors</span>
                </a>
                <a href="/plants.php" class="nav-link">
                    <i class="fas fa-seedling"></i>
                    <span>Plants</span>
                </a>
                <a href="/readings.php" class="nav-link">
                    <i class="fas fa-chart-line"></i>
                    <span>Readings</span>
                </a>
                <a href="/settings.php" class="nav-link">
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

            <div class="dashboard-header">
                <h1 class="dashboard-title">Dashboard</h1>
                <p class="dashboard-subtitle">Overview of your garden's sensor data</p>
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon temperature">
                        <i class="fas fa-thermometer-half"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo $averages['temperature']; ?>°C</div>
                        <div class="stat-label">Average Temperature</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon humidity">
                        <i class="fas fa-tint"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo $averages['humidity']; ?>%</div>
                        <div class="stat-label">Average Humidity</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon moisture">
                        <i class="fas fa-water"></i>
                    </div>
                    <div class="stat-info">
                        <div class="stat-value"><?php echo $averages['moisture']; ?>%</div>
                        <div class="stat-label">Average Soil Moisture</div>
                    </div>
                </div>
            </div>

            <!-- Interactive Plot Section -->
            <section class="plot-section">
                <div class="plot-header">
                    <h2 class="plot-title">Sensor Readings Over Time</h2>
                    <div class="plot-controls">
                        <label for="plant-filter" class="control-label">Filter by Plant:</label>
                        <select id="plant-filter" class="control-select">
                            <option value="">All Plants</option>
                            <?php foreach ($plants as $plant): ?>
                                <option value="<?php echo $plant['id']; ?>">
                                    <?php echo htmlspecialchars($plant['name']); ?>
                                    <?php if ($plant['species']): ?>
                                        (<?php echo htmlspecialchars($plant['species']); ?>)
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <label for="days-filter" class="control-label">Days:</label>
                        <select id="days-filter" class="control-select">
                            <option value="7">Last 7 days</option>
                            <option value="14">Last 14 days</option>
                            <option value="30">Last 30 days</option>
                            <option value="90">Last 90 days</option>
                        </select>
                        <button id="refresh-plot" class="btn btn-primary">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                    </div>
                </div>
                <div id="plot-container" class="plot-container">
                    <div class="plot-loading">
                        <i class="fas fa-spinner fa-spin"></i>
                        <p>Loading plot...</p>
                    </div>
                </div>
            </section>

            <!-- Recent Readings -->
            <section class="readings-section">
                <div class="readings-header">
                    <h2 class="readings-title">Recent Sensor Readings</h2>
                </div>
                <?php if (!empty($latestReadings)): ?>
                    <table class="readings-table">
                        <thead>
                            <tr>
                                <th>Sensor ID</th>
                                <th>Type</th>
                                <th>Value</th>
                                <th>Timestamp</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($latestReadings as $reading): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($reading['sensor_id']); ?></td>
                                    <td><?php echo htmlspecialchars(ucfirst($reading['sensor_type'])); ?></td>
                                    <td>
                                        <?php 
                                        echo htmlspecialchars($reading['reading_value']);
                                        echo $reading['sensor_type'] === 'temperature' ? '°C' : '%';
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars(date('Y-m-d H:i:s', strtotime($reading['reading_timestamp']))); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-data">
                        <p>No sensor readings available for the last 24 hours.</p>
                    </div>
                <?php endif; ?>
            </section>
        </main>
    </div>

    <!-- JavaScript -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bokeh JS -->
    <script src="https://cdn.bokeh.org/bokeh/release/bokeh-3.8.0.min.js"></script>
    <script src="https://cdn.bokeh.org/bokeh/release/bokeh-widgets-3.8.0.min.js"></script>
    <script src="/assets/js/main.js"></script>
    <script>
        // Initialize plot on page load
        $(document).ready(function() {
            loadPlot();
            
            // Set up plot controls
            $('#plant-filter, #days-filter').on('change', function() {
                loadPlot();
            });
            
            $('#refresh-plot').on('click', function() {
                loadPlot();
            });
        });
        
        function loadPlot() {
            const plantId = $('#plant-filter').val();
            const days = $('#days-filter').val();
            const container = $('#plot-container');
            
            // Show loading
            container.html('<div class="plot-loading"><i class="fas fa-spinner fa-spin"></i><p>Loading plot...</p></div>');
            
            // Build request data - only include plant_id if it's not empty
            const requestData = {
                days: days,
                format: 'components'
            };
            if (plantId && plantId !== '' && plantId !== '0') {
                requestData.plant_id = plantId;
            }
            
            // Fetch plot data
            $.ajax({
                url: 'api/plot.php',
                method: 'GET',
                data: requestData,
                dataType: 'json',
                xhrFields: {
                    withCredentials: true
                },
                success: function(response) {
                    console.log('Plot API response:', response);
                    
                    // Handle case where response might be a string (JSON parse happened automatically)
                    if (typeof response === 'string') {
                        try {
                            response = JSON.parse(response);
                        } catch (e) {
                            console.error('Failed to parse response:', e);
                            container.html('<div class="plot-error"><i class="fas fa-exclamation-triangle"></i><p>Invalid response from server</p></div>');
                            return;
                        }
                    }
                    
                    if (response.success && response.script && response.div) {
                        // Clear container
                        container.html('');
                        
                        // Add plot div first
                        container.html(response.div);
                        
                        // Extract JavaScript from script tag if present
                        let scriptContent = response.script;
                        // Remove script tags if they exist
                        scriptContent = scriptContent.replace(/<script[^>]*>/i, '').replace(/<\/script>/i, '');
                        
                        // Execute plot script after a small delay to ensure div is in DOM
                        setTimeout(function() {
                            try {
                                // Create a script element and inject it into the DOM
                                const scriptElement = document.createElement('script');
                                scriptElement.type = 'text/javascript';
                                scriptElement.textContent = scriptContent;
                                document.body.appendChild(scriptElement);
                                // Remove the script element after execution
                                setTimeout(function() {
                                    document.body.removeChild(scriptElement);
                                }, 100);
                            } catch (e) {
                                console.error('Error executing Bokeh script:', e);
                                container.html('<div class="plot-error"><i class="fas fa-exclamation-triangle"></i><p>Error rendering plot: ' + e.message + '</p></div>');
                            }
                        }, 100);
                    } else {
                        console.error('Invalid plot response:', response);
                        container.html('<div class="plot-error"><i class="fas fa-exclamation-triangle"></i><p>' + (response.error || 'No data available for plotting') + '</p></div>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Plot loading error:', {
                        status: xhr.status,
                        statusText: xhr.statusText,
                        responseText: xhr.responseText.substring(0, 200), // First 200 chars
                        error: error
                    });
                    
                    // Try to parse error response if it's JSON
                    let errorMsg = 'Failed to load plot. Please try again.';
                    try {
                        if (xhr.responseText && xhr.responseText.trim().startsWith('{')) {
                            const errorResponse = JSON.parse(xhr.responseText);
                            errorMsg = errorResponse.error || errorMsg;
                        } else if (xhr.responseText && xhr.responseText.trim().startsWith('<')) {
                            // HTML response (likely a redirect or error page)
                            errorMsg = 'Server returned HTML instead of JSON. Please refresh the page.';
                        }
                    } catch (e) {
                        // Not JSON, use default message
                    }
                    
                    if (xhr.status === 401) {
                        errorMsg = 'Authentication required. Please refresh the page and log in.';
                    } else if (xhr.status === 500) {
                        errorMsg = 'Server error. Please check the console for details.';
                    }
                    
                    container.html('<div class="plot-error"><i class="fas fa-exclamation-triangle"></i><p>' + errorMsg + '</p></div>');
                }
            });
        }
    </script>
</body>
</html> 