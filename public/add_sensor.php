<?php
// Load configuration
require_once __DIR__ . '/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

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

$success = '';
$errors = [];

// Fetch plants for dropdown
$plants = [];
if (!isset($error)) {
    try {
        $stmt = $pdo->prepare("SELECT id, name, species FROM plants WHERE user_id = ? AND status = 'active' ORDER BY name");
        $stmt->execute([$_SESSION['user_id']]);
        $plants = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Silently fail - plants dropdown will be empty
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $type = $_POST['type'] ?? '';
    $location = trim($_POST['location'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $plant_id = !empty($_POST['plant_id']) ? intval($_POST['plant_id']) : null;
    // Set plot_type based on sensor type
    $plot_type = in_array($type, ['temperature', 'humidity', 'moisture']) ? $type : 'moisture';
    $unit = $_POST['unit'] ?? 'percentage';
    
    // Validation
    if (empty($name)) {
        $errors[] = 'Sensor name is required';
    }
    if (empty($type)) {
        $errors[] = 'Sensor type is required';
    }
    
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Insert sensor (without thresholds - they come from the plant)
            $stmt = $pdo->prepare("
                INSERT INTO sensors (name, type, location, description, plot_type, unit, user_id, status, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'active', NOW(), NOW())
            ");
            $stmt->execute([
                $name,
                $type,
                $location ?: null,
                $description ?: null,
                $plot_type,
                $unit,
                $_SESSION['user_id']
            ]);
            
            $sensor_id = $pdo->lastInsertId();
            
            // Link sensor to plant if plant_id is provided
            if ($plant_id) {
                // Verify plant belongs to user
                $stmt = $pdo->prepare("SELECT id FROM plants WHERE id = ? AND user_id = ?");
                $stmt->execute([$plant_id, $_SESSION['user_id']]);
                if ($stmt->fetch()) {
                    // Get plant's watering settings
                    $stmt = $pdo->prepare("SELECT watering_frequency FROM plants WHERE id = ?");
                    $stmt->execute([$plant_id]);
                    $plant = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    // Calculate next watering time
                    $next_watering = date('Y-m-d H:i:s', strtotime('+' . $plant['watering_frequency'] . ' hours'));
                    
                    // Link sensor to plant
                    $stmt = $pdo->prepare("
                        INSERT INTO plant_sensors (sensor_id, plant_id, water_amount, next_watering, created_at, updated_at)
                        VALUES (?, ?, 100, ?, NOW(), NOW())
                        ON DUPLICATE KEY UPDATE updated_at = NOW()
                    ");
                    $stmt->execute([$sensor_id, $plant_id, $next_watering]);
                }
            }
            
            $pdo->commit();
            $success = 'Sensor added successfully!';
            // Redirect after 2 seconds
            header('Refresh: 2; url=sensors.php');
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors[] = 'Failed to add sensor: ' . $e->getMessage();
        }
    }
}

// Set page title
$page_title = 'Add New Sensor';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - <?php echo htmlspecialchars(APP_NAME); ?></title>
    
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
                <a href="/sensors.php" class="nav-link active">
                    <i class="fas fa-microchip"></i>
                    <span>Sensors</span>
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
            <div class="dashboard-header">
                <h1 class="dashboard-title">Add New Sensor</h1>
                <p class="dashboard-subtitle">Add a new sensor to monitor your garden</p>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success); ?>
                    <p>Redirecting to sensors page...</p>
                </div>
            <?php else: ?>
                <section class="readings-section">
                    <div class="readings-header">
                        <h2 class="readings-title">Sensor Information</h2>
                    </div>
                    <form method="POST" action="" class="sensor-form">
                        <div class="form-group">
                            <label for="name" class="form-label">Sensor Name *</label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" 
                                   required maxlength="100">
                        </div>

                        <div class="form-group">
                            <label for="type" class="form-label">Sensor Type *</label>
                            <select class="form-control" id="type" name="type" required>
                                <option value="">Select sensor type...</option>
                                <option value="temperature" <?php echo (($_POST['type'] ?? '') === 'temperature') ? 'selected' : ''; ?>>Temperature</option>
                                <option value="humidity" <?php echo (($_POST['type'] ?? '') === 'humidity') ? 'selected' : ''; ?>>Humidity</option>
                                <option value="moisture" <?php echo (($_POST['type'] ?? '') === 'moisture') ? 'selected' : ''; ?>>Moisture</option>
                                <option value="light" <?php echo (($_POST['type'] ?? '') === 'light') ? 'selected' : ''; ?>>Light</option>
                                <option value="soil_pH" <?php echo (($_POST['type'] ?? '') === 'soil_pH') ? 'selected' : ''; ?>>Soil pH</option>
                            </select>
                            <input type="hidden" id="plot_type" name="plot_type" value="">
                        </div>

                        <div class="form-group">
                            <label for="location" class="form-label">Location</label>
                            <input type="text" class="form-control" id="location" name="location" 
                                   value="<?php echo htmlspecialchars($_POST['location'] ?? ''); ?>" 
                                   placeholder="e.g., Greenhouse 1, Garden Bed A" 
                                   maxlength="100">
                        </div>

                        <div class="form-group">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" 
                                      rows="3" 
                                      placeholder="Optional description of the sensor"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="plant_id" class="form-label">Link to Plant (Optional)</label>
                            <select class="form-control" id="plant_id" name="plant_id">
                                <option value="">No plant selected</option>
                                <?php foreach ($plants as $plant): ?>
                                    <option value="<?php echo $plant['id']; ?>" 
                                            <?php echo (isset($_POST['plant_id']) && $_POST['plant_id'] == $plant['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($plant['name']); ?>
                                        <?php if ($plant['species']): ?>
                                            (<?php echo htmlspecialchars($plant['species']); ?>)
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-text">Link this sensor to a plant to use the plant's moisture thresholds for automatic watering</small>
                        </div>

                        <div class="form-group">
                            <label for="unit" class="form-label">Unit</label>
                            <select class="form-control" id="unit" name="unit">
                                <option value="percentage" <?php echo (($_POST['unit'] ?? 'percentage') === 'percentage') ? 'selected' : ''; ?>>Percentage (%)</option>
                                <option value="celsius" <?php echo (($_POST['unit'] ?? '') === 'celsius') ? 'selected' : ''; ?>>Celsius (°C)</option>
                                <option value="fahrenheit" <?php echo (($_POST['unit'] ?? '') === 'fahrenheit') ? 'selected' : ''; ?>>Fahrenheit (°F)</option>
                                <option value="ppm" <?php echo (($_POST['unit'] ?? '') === 'ppm') ? 'selected' : ''; ?>>PPM</option>
                            </select>
                        </div>

                        <div class="form-group" style="margin-top: 20px;">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Add Sensor
                            </button>
                            <a href="sensors.php" class="btn" style="margin-left: 10px; background: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block;">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </section>
            <?php endif; ?>
        </main>
    </div>

    <!-- JavaScript -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="/assets/js/main.js"></script>
    <script>
    // Auto-set plot_type based on sensor type selection
    document.getElementById('type').addEventListener('change', function() {
        var sensorType = this.value;
        var plotTypeInput = document.getElementById('plot_type');
        // Map sensor types to plot types
        if (['temperature', 'humidity', 'moisture'].includes(sensorType)) {
            plotTypeInput.value = sensorType;
        } else {
            plotTypeInput.value = 'moisture'; // Default for other types
        }
    });
    </script>
</body>
</html>

