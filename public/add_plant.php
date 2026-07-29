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
$catalogEntries = [];

if (!isset($error)) {
    try {
        $catalogEntries = $pdo->query("
            SELECT id, common_name, scientific_name, category,
                   min_soil_moisture, max_soil_moisture,
                   min_temperature, max_temperature,
                   watering_frequency_hours, water_requirement
            FROM plant_catalog
            WHERE is_active = 1
            ORDER BY common_name ASC
            LIMIT 500
        ")->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $catalogEntries = [];
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $catalog_id = !empty($_POST['catalog_id']) ? intval($_POST['catalog_id']) : null;
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

    if ($catalog_id && isset($pdo)) {
        try {
            $cstmt = $pdo->prepare('SELECT id FROM plant_catalog WHERE id = ? AND is_active = 1 LIMIT 1');
            $cstmt->execute([$catalog_id]);
            if (!$cstmt->fetchColumn()) {
                $catalog_id = null;
            }
        } catch (PDOException $e) {
            $catalog_id = null;
        }
    }
    
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
                                  min_temperature, max_temperature, user_id, catalog_id, status, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW(), NOW())
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
                $_SESSION['user_id'],
                $catalog_id
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
                    <form method="POST" action="" class="sensor-form" id="add-plant-form">
                        <div class="form-group">
                            <label for="catalog_id" class="form-label">Plant type (catalog)</label>
                            <select class="form-control" id="catalog_id" name="catalog_id">
                                <option value="">Custom / not listed</option>
                                <?php foreach ($catalogEntries as $entry): ?>
                                    <?php
                                        $label = $entry['common_name'];
                                        if (!empty($entry['scientific_name'])) {
                                            $label .= ' (' . $entry['scientific_name'] . ')';
                                        }
                                        $selected = (isset($_POST['catalog_id']) && (int)$_POST['catalog_id'] === (int)$entry['id']) ? ' selected' : '';
                                    ?>
                                    <option value="<?php echo (int)$entry['id']; ?>"
                                            data-scientific="<?php echo htmlspecialchars((string)($entry['scientific_name'] ?? ''), ENT_QUOTES); ?>"
                                            data-min-moisture="<?php echo htmlspecialchars((string)($entry['min_soil_moisture'] ?? ''), ENT_QUOTES); ?>"
                                            data-max-moisture="<?php echo htmlspecialchars((string)($entry['max_soil_moisture'] ?? ''), ENT_QUOTES); ?>"
                                            data-min-temp="<?php echo htmlspecialchars((string)($entry['min_temperature'] ?? ''), ENT_QUOTES); ?>"
                                            data-max-temp="<?php echo htmlspecialchars((string)($entry['max_temperature'] ?? ''), ENT_QUOTES); ?>"
                                            data-freq="<?php echo htmlspecialchars((string)($entry['watering_frequency_hours'] ?? ''), ENT_QUOTES); ?>"
                                            data-water="<?php echo htmlspecialchars((string)($entry['water_requirement'] ?? ''), ENT_QUOTES); ?>"
                                            <?php echo $selected; ?>>
                                        <?php echo htmlspecialchars($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (empty($catalogEntries)): ?>
                                <small class="form-text">Catalog is empty. Import later via <code>SEED_PLANT_CATALOG=1</code> / <code>database/import_plant_catalog.php</code>.</small>
                            <?php else: ?>
                                <small class="form-text">Selecting a type fills species and moisture/temperature defaults (you can still edit them).</small>
                            <?php endif; ?>
                        </div>

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
    <script>
    (function () {
        function freqFromWater(req) {
            var key = (req || '').toLowerCase();
            if (key === 'low' || key === 'drought' || key === 'sparse') return 48;
            if (key === 'high' || key === 'frequent' || key === 'wet') return 12;
            return 24;
        }
        var select = document.getElementById('catalog_id');
        if (!select) return;
        select.addEventListener('change', function () {
            var opt = select.options[select.selectedIndex];
            if (!opt || !opt.value) return;
            var species = document.getElementById('species');
            var name = document.getElementById('name');
            var minM = document.getElementById('min_soil_moisture');
            var maxM = document.getElementById('max_soil_moisture');
            var freq = document.getElementById('watering_frequency');
            var minT = document.getElementById('min_temperature');
            var maxT = document.getElementById('max_temperature');
            if (species && opt.dataset.scientific) species.value = opt.dataset.scientific;
            if (name && !name.value.trim()) name.value = opt.textContent.replace(/\s*\([^)]*\)\s*$/, '').trim();
            if (minM && opt.dataset.minMoisture !== '') minM.value = opt.dataset.minMoisture;
            if (maxM && opt.dataset.maxMoisture !== '') maxM.value = opt.dataset.maxMoisture;
            if (freq) {
                freq.value = opt.dataset.freq !== '' ? opt.dataset.freq : freqFromWater(opt.dataset.water);
            }
            if (minT && opt.dataset.minTemp !== '') minT.value = opt.dataset.minTemp;
            if (maxT && opt.dataset.maxTemp !== '') maxT.value = opt.dataset.maxTemp;
        });
    })();
    </script>
</body>
</html>

