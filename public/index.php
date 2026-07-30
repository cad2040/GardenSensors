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

            <!-- Per-plant latest readings versus catalog benchmarks -->
            <section class="plant-summary-section" aria-labelledby="plant-summary-title">
                <div class="plant-summary-heading">
                    <div>
                        <h2 id="plant-summary-title">Plant conditions</h2>
                        <p>Latest soil readings compared with each plant's ideal range. Select a plant to filter the dashboard.</p>
                    </div>
                    <div id="plant-filter-wrapper" class="plant-filter-wrapper is-hidden">
                        <label for="plant-filter" class="control-label">Showing:</label>
                        <select id="plant-filter" class="control-select">
                            <option value="">All plants</option>
                            <?php foreach ($plants as $plant): ?>
                                <option value="<?php echo $plant['id']; ?>">
                                    <?php echo htmlspecialchars($plant['name']); ?>
                                    <?php if ($plant['species']): ?>
                                        (<?php echo htmlspecialchars($plant['species']); ?>)
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div id="plant-summary-grid" class="plant-summary-grid">
                    <div class="plot-loading">
                        <i class="fas fa-spinner fa-spin"></i>
                        <p>Loading plant conditions...</p>
                    </div>
                </div>
                <p id="ec-benchmark-note" class="benchmark-note"></p>
            </section>

            <!-- Interactive Plot Section -->
            <section class="plot-section">
                <div class="plot-header">
                    <h2 class="plot-title">Sensor Readings Over Time</h2>
                    <div class="plot-controls">
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
            refreshDashboard();

            $('#plant-filter, #days-filter').on('change', refreshDashboard);

            $('#refresh-plot').on('click', refreshDashboard);

            // Selecting a plant tile filters both visualizations to that plant.
            $('#plant-summary-grid').on('click', '.plant-summary-card', function() {
                selectPlant($(this).data('plant-id'));
            });

            $('#plant-summary-grid').on('keydown', '.plant-summary-card', function(event) {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    selectPlant($(this).data('plant-id'));
                }
            });
        });

        function selectPlant(plantId) {
            const current = $('#plant-filter').val();
            const next = String(plantId);
            // Clicking the already-selected plant clears the filter.
            $('#plant-filter').val(current === next ? '' : next);
            refreshDashboard();
        }

        function refreshDashboard() {
            // The dropdown is only needed once the view is narrowed to one plant.
            $('#plant-filter-wrapper').toggleClass('is-hidden', !$('#plant-filter').val());
            loadPlot();
            loadPlantSummaries();
        }
        
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

        function escapeHtml(value) {
            return $('<div>').text(value ?? '').html();
        }

        function formatMetric(metric) {
            if (!metric || metric.value === null) {
                return '<div class="plant-metric no-reading"><span class="metric-name">No sensor data</span></div>';
            }

            const value = Number(metric.value).toFixed(1);
            const ideal = metric.ideal_min !== null && metric.ideal_max !== null
                ? `${Number(metric.ideal_min).toFixed(1)}–${Number(metric.ideal_max).toFixed(1)} ${escapeHtml(metric.unit)}`
                : 'Not configured';
            const statusLabel = metric.status === 'ideal'
                ? 'In range'
                : (metric.status === 'low' ? 'Need food' : (metric.status === 'high' ? 'Flush risk' : 'Out of range'));

            return `
                <div class="plant-metric metric-${escapeHtml(metric.status)}">
                    <div class="metric-header">
                        <span class="metric-name">${escapeHtml(metric.label)}</span>
                        <span class="metric-status">${statusLabel}</span>
                    </div>
                    <div class="metric-reading">${value} <span>${escapeHtml(metric.unit)}</span></div>
                    <div class="metric-benchmark">Ideal ${ideal}</div>
                </div>`;
        }

        function loadPlantSummaries() {
            const plantId = $('#plant-filter').val();
            const requestData = {};
            if (plantId && plantId !== '' && plantId !== '0') {
                requestData.plant_id = plantId;
            }

            $.ajax({
                url: 'api/summary.php',
                method: 'GET',
                data: requestData,
                dataType: 'json',
                success: function(response) {
                    if (!response.success || !Array.isArray(response.plants)) {
                        $('#plant-summary-grid').html('<div class="no-data">Unable to load plant conditions.</div>');
                        return;
                    }

                    if (response.plants.length === 0) {
                        $('#plant-summary-grid').html('<div class="no-data">No active plants found.</div>');
                        return;
                    }

                    const selectedId = $('#plant-filter').val();
                    const cards = response.plants.map(function(plant) {
                        const metrics = plant.metrics || {};
                        const isSelected = String(plant.id) === String(selectedId);
                        return `
                            <article class="plant-summary-card${isSelected ? ' is-selected' : ''}"
                                     data-plant-id="${escapeHtml(plant.id)}"
                                     role="button" tabindex="0"
                                     aria-pressed="${isSelected}"
                                     title="${isSelected ? 'Show all plants' : 'Filter dashboard to this plant'}">
                                <header>
                                    <h3>${escapeHtml(plant.name)}</h3>
                                    <span>${escapeHtml(plant.species || '')}</span>
                                </header>
                                <div class="plant-metrics">
                                    ${formatMetric(metrics.temperature)}
                                    ${formatMetric(metrics.moisture)}
                                    ${formatMetric(metrics.ec)}
                                </div>
                            </article>`;
                    }).join('');

                    $('#plant-summary-grid').html(cards);
                    $('#ec-benchmark-note').text(response.ec_note || '');
                },
                error: function() {
                    $('#plant-summary-grid').html('<div class="no-data">Unable to load plant conditions.</div>');
                }
            });
        }
    </script>
</body>
</html> 