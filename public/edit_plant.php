<?php
require_once __DIR__ . '/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

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
$plantId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$plant = null;
$catalogEntries = [];

if (!isset($error) && $plantId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM plants WHERE id = ? AND (user_id = ? OR user_id IS NULL)");
    $stmt->execute([$plantId, $_SESSION['user_id']]);
    $plant = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$plant) {
        $errors[] = 'Plant not found';
    }
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
} else {
    $errors[] = 'Invalid plant ID';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $plant) {
    $catalog_id = !empty($_POST['catalog_id']) ? intval($_POST['catalog_id']) : null;
    $name = trim($_POST['name'] ?? '');
    $species = trim($_POST['species'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $planting_date = $_POST['planting_date'] ?? null;
    $location = trim($_POST['location'] ?? '');
    $min_soil_moisture = intval($_POST['min_soil_moisture'] ?? 30);
    $max_soil_moisture = intval($_POST['max_soil_moisture'] ?? 70);
    $watering_frequency = intval($_POST['watering_frequency'] ?? 24);
    $min_temperature = $_POST['min_temperature'] !== '' ? floatval($_POST['min_temperature']) : null;
    $max_temperature = $_POST['max_temperature'] !== '' ? floatval($_POST['max_temperature']) : null;
    $status = $_POST['status'] ?? 'active';

    if ($name === '') {
        $errors[] = 'Plant name is required';
    }
    if ($min_soil_moisture >= $max_soil_moisture) {
        $errors[] = 'Minimum soil moisture must be less than maximum soil moisture';
    }

    if ($catalog_id) {
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

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE plants SET
                    name = ?, species = ?, description = ?, planting_date = ?, location = ?,
                    min_soil_moisture = ?, max_soil_moisture = ?, watering_frequency = ?,
                    min_temperature = ?, max_temperature = ?, status = ?, user_id = ?,
                    catalog_id = ?, updated_at = NOW()
                WHERE id = ?
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
                $status,
                $_SESSION['user_id'],
                $catalog_id,
                $plantId
            ]);
            $success = 'Plant updated successfully!';
            $stmt = $pdo->prepare("SELECT * FROM plants WHERE id = ?");
            $stmt->execute([$plantId]);
            $plant = $stmt->fetch(PDO::FETCH_ASSOC);
            header('Refresh: 1; url=plants.php');
        } catch (PDOException $e) {
            $errors[] = 'Failed to update plant: ' . $e->getMessage();
        }
    }
}

$page_title = 'Edit Plant';
$currentCatalogId = isset($plant['catalog_id']) ? (int)$plant['catalog_id'] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - <?php echo htmlspecialchars(APP_NAME); ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="/assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="app-container">
    <nav class="sidebar">
        <div class="sidebar-header">
            <a href="/" class="app-logo"><i class="fas fa-leaf"></i><span>Garden Sensors</span></a>
        </div>
        <div class="nav-menu">
            <a href="/" class="nav-link"><i class="fas fa-home"></i><span>Dashboard</span></a>
            <a href="/sensors.php" class="nav-link"><i class="fas fa-microchip"></i><span>Sensors</span></a>
            <a href="/plants.php" class="nav-link active"><i class="fas fa-seedling"></i><span>Plants</span></a>
            <a href="/readings.php" class="nav-link"><i class="fas fa-chart-line"></i><span>Readings</span></a>
            <a href="/settings.php" class="nav-link"><i class="fas fa-cog"></i><span>Settings</span></a>
            <a href="/logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
        </div>
    </nav>
    <main class="main-content">
        <div class="dashboard-header">
            <h1 class="dashboard-title">Edit Plant</h1>
            <p class="dashboard-subtitle">Update plant details and moisture thresholds</p>
        </div>
        <?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
        <?php foreach ($errors as $err): ?><div class="alert alert-danger"><?php echo htmlspecialchars($err); ?></div><?php endforeach; ?>
        <?php if ($plant): ?>
        <form method="POST" class="readings-section" style="max-width:720px;padding:1.5rem;" id="edit-plant-form">
            <label>Plant type (catalog)
                <select class="form-control" id="catalog_id" name="catalog_id">
                    <option value="">Custom / not listed</option>
                    <?php foreach ($catalogEntries as $entry): ?>
                        <?php
                            $label = $entry['common_name'];
                            if (!empty($entry['scientific_name'])) {
                                $label .= ' (' . $entry['scientific_name'] . ')';
                            }
                            $sel = ($currentCatalogId === (int)$entry['id']) ? ' selected' : '';
                        ?>
                        <option value="<?php echo (int)$entry['id']; ?>"
                                data-scientific="<?php echo htmlspecialchars((string)($entry['scientific_name'] ?? ''), ENT_QUOTES); ?>"
                                data-min-moisture="<?php echo htmlspecialchars((string)($entry['min_soil_moisture'] ?? ''), ENT_QUOTES); ?>"
                                data-max-moisture="<?php echo htmlspecialchars((string)($entry['max_soil_moisture'] ?? ''), ENT_QUOTES); ?>"
                                data-min-temp="<?php echo htmlspecialchars((string)($entry['min_temperature'] ?? ''), ENT_QUOTES); ?>"
                                data-max-temp="<?php echo htmlspecialchars((string)($entry['max_temperature'] ?? ''), ENT_QUOTES); ?>"
                                data-freq="<?php echo htmlspecialchars((string)($entry['watering_frequency_hours'] ?? ''), ENT_QUOTES); ?>"
                                data-water="<?php echo htmlspecialchars((string)($entry['water_requirement'] ?? ''), ENT_QUOTES); ?>"
                                <?php echo $sel; ?>>
                            <?php echo htmlspecialchars($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label><br><br>
            <label>Name<input class="form-control" type="text" id="name" name="name" value="<?php echo htmlspecialchars($plant['name']); ?>" required></label><br><br>
            <label>Species<input class="form-control" type="text" id="species" name="species" value="<?php echo htmlspecialchars((string)$plant['species']); ?>"></label><br><br>
            <label>Location<input class="form-control" type="text" name="location" value="<?php echo htmlspecialchars((string)$plant['location']); ?>"></label><br><br>
            <label>Description<textarea class="form-control" name="description"><?php echo htmlspecialchars((string)$plant['description']); ?></textarea></label><br><br>
            <label>Planting date<input class="form-control" type="date" name="planting_date" value="<?php echo htmlspecialchars((string)$plant['planting_date']); ?>"></label><br><br>
            <label>Min moisture<input class="form-control" type="number" id="min_soil_moisture" name="min_soil_moisture" value="<?php echo htmlspecialchars((string)$plant['min_soil_moisture']); ?>"></label><br><br>
            <label>Max moisture<input class="form-control" type="number" id="max_soil_moisture" name="max_soil_moisture" value="<?php echo htmlspecialchars((string)$plant['max_soil_moisture']); ?>"></label><br><br>
            <label>Watering frequency (hours)<input class="form-control" type="number" id="watering_frequency" name="watering_frequency" value="<?php echo htmlspecialchars((string)$plant['watering_frequency']); ?>"></label><br><br>
            <label>Min temperature<input class="form-control" type="number" step="0.1" id="min_temperature" name="min_temperature" value="<?php echo htmlspecialchars((string)$plant['min_temperature']); ?>"></label><br><br>
            <label>Max temperature<input class="form-control" type="number" step="0.1" id="max_temperature" name="max_temperature" value="<?php echo htmlspecialchars((string)$plant['max_temperature']); ?>"></label><br><br>
            <label>Status
                <select class="form-control" name="status">
                    <?php foreach (['active','inactive','harvested'] as $st): ?>
                    <option value="<?php echo $st; ?>" <?php echo $plant['status'] === $st ? 'selected' : ''; ?>><?php echo ucfirst($st); ?></option>
                    <?php endforeach; ?>
                </select>
            </label><br><br>
            <button class="btn btn-primary" type="submit">Save Plant</button>
            <a class="btn" href="plants.php">Cancel</a>
        </form>
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
                var minM = document.getElementById('min_soil_moisture');
                var maxM = document.getElementById('max_soil_moisture');
                var freq = document.getElementById('watering_frequency');
                var minT = document.getElementById('min_temperature');
                var maxT = document.getElementById('max_temperature');
                if (species && opt.dataset.scientific) species.value = opt.dataset.scientific;
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
        <?php endif; ?>
    </main>
</div>
</body>
</html>
