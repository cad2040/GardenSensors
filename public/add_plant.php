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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $species = trim($_POST['species'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $planting_date = $_POST['planting_date'] ?? null;
    $location = trim($_POST['location'] ?? '');
    $min_soil_moisture = intval($_POST['min_soil_moisture'] ?? 30);
    $max_soil_moisture = intval($_POST['max_soil_moisture'] ?? 70);
    $watering_frequency = intval($_POST['watering_frequency'] ?? 24);
    $min_temperature = !empty($_POST['min_temperature']) ? floatval($_POST['min_temperature']) : null;
    $max_temperature = !empty($_POST['max_temperature']) ? floatval($_POST['max_temperature']) : null;
    
    // Validation
    if (empty($name)) {
        $errors[] = 'Plant name is required';
    }
    if ($min_soil_moisture < 0 || $min_soil_moisture > 100) {
        $errors[] = 'Minimum soil moisture must be between 0 and 100';
    }
    if ($max_soil_moisture < 0 || $max_soil_moisture > 100) {
        $errors[] = 'Maximum soil moisture must be between 0 and 100';
    }
    if ($min_soil_moisture >= $max_soil_moisture) {
        $errors[] = 'Minimum soil moisture must be less than maximum soil moisture';
    }
    if ($watering_frequency <= 0) {
        $errors[] = 'Watering frequency must be greater than 0';
    }
    if ($min_temperature !== null && $max_temperature !== null && $min_temperature >= $max_temperature) {
        $errors[] = 'Minimum temperature must be less than maximum temperature';
    }
    
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO plants (name, species, description, planting_date, location, 
                                  min_soil_moisture, max_soil_moisture, watering_frequency,
                                  min_temperature, max_temperature, user_id, status, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW(), NOW())
            ");
            $stmt->execute([
                $name,
                $species ?: null,
                $description ?: null,
                $planting_date ?: null,
                $location ?: null,
                $min_soil_moisture,
                $max_soil_moisture,
                $watering_frequency,
                $min_temperature,
                $max_temperature,
                $_SESSION['user_id']
            ]);
            
            $success = 'Plant added successfully!';
            // Redirect after 2 seconds
            header('Refresh: 2; url=plants.php');
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $errors[] = 'A plant with this name already exists';
            } else {
                $errors[] = 'Failed to add plant: ' . $e->getMessage();
            }
        }
    }
}

// Set page title
$page_title = 'Add New Plant';
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
                <a href="/sensors.php" class="nav-link">
                    <i class="fas fa-microchip"></i>
                    <span>Sensors</span>
                </a>
                <a href="/plants.php" class="nav-link active">
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
            <div class="dashboard-header">
                <h1 class="dashboard-title">Add New Plant</h1>
                <p class="dashboard-subtitle">Add a new plant to monitor and manage</p>
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
                    <p>Redirecting to plants page...</p>
                </div>
            <?php else: ?>
                <section class="readings-section">
                    <div class="readings-header">
                        <h2 class="readings-title">Plant Information</h2>
                    </div>
                    <form method="POST" action="" class="sensor-form">
                        <div class="form-group">
                            <label for="name" class="form-label">Plant Name *</label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" 
                                   required maxlength="255">
                        </div>

                        <div class="form-group">
                            <label for="species" class="form-label">Species</label>
                            <input type="text" class="form-control" id="species" name="species" 
                                   value="<?php echo htmlspecialchars($_POST['species'] ?? ''); ?>" 
                                   placeholder="e.g., Tomato, Basil, Lettuce" 
                                   maxlength="255">
                        </div>

                        <div class="form-group">
                            <label for="location" class="form-label">Location</label>
                            <input type="text" class="form-control" id="location" name="location" 
                                   value="<?php echo htmlspecialchars($_POST['location'] ?? ''); ?>" 
                                   placeholder="e.g., Greenhouse 1, Garden Bed A" 
                                   maxlength="255">
                        </div>

                        <div class="form-group">
                            <label for="planting_date" class="form-label">Planting Date</label>
                            <input type="date" class="form-control" id="planting_date" name="planting_date" 
                                   value="<?php echo htmlspecialchars($_POST['planting_date'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" 
                                      rows="3" 
                                      placeholder="Optional description of the plant"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="min_soil_moisture" class="form-label">Minimum Soil Moisture (%) *</label>
                            <input type="number" class="form-control" id="min_soil_moisture" name="min_soil_moisture" 
                                   value="<?php echo htmlspecialchars($_POST['min_soil_moisture'] ?? '30'); ?>" 
                                   min="0" max="100" required>
                            <small class="form-text">Watering will be triggered when moisture falls below this level</small>
                        </div>

                        <div class="form-group">
                            <label for="max_soil_moisture" class="form-label">Maximum Soil Moisture (%) *</label>
                            <input type="number" class="form-control" id="max_soil_moisture" name="max_soil_moisture" 
                                   value="<?php echo htmlspecialchars($_POST['max_soil_moisture'] ?? '70'); ?>" 
                                   min="0" max="100" required>
                            <small class="form-text">Ideal maximum moisture level</small>
                        </div>

                        <div class="form-group">
                            <label for="watering_frequency" class="form-label">Watering Frequency (hours) *</label>
                            <input type="number" class="form-control" id="watering_frequency" name="watering_frequency" 
                                   value="<?php echo htmlspecialchars($_POST['watering_frequency'] ?? '24'); ?>" 
                                   min="1" required>
                            <small class="form-text">How often to check if watering is needed (in hours)</small>
                        </div>

                        <div class="form-group">
                            <label for="min_temperature" class="form-label">Minimum Temperature (°C)</label>
                            <input type="number" class="form-control" id="min_temperature" name="min_temperature" 
                                   value="<?php echo htmlspecialchars($_POST['min_temperature'] ?? ''); ?>" 
                                   step="0.1" placeholder="Optional">
                        </div>

                        <div class="form-group">
                            <label for="max_temperature" class="form-label">Maximum Temperature (°C)</label>
                            <input type="number" class="form-control" id="max_temperature" name="max_temperature" 
                                   value="<?php echo htmlspecialchars($_POST['max_temperature'] ?? ''); ?>" 
                                   step="0.1" placeholder="Optional">
                        </div>

                        <div class="form-group" style="margin-top: 20px;">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Add Plant
                            </button>
                            <a href="plants.php" class="btn" style="margin-left: 10px; background: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block;">
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
</body>
</html>

