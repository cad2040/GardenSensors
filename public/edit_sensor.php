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
$sensorId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$sensor = null;
$plants = [];
$linkedPlantId = null;

if (!isset($error)) {
    $stmt = $pdo->prepare("SELECT id, name FROM plants WHERE user_id = ? OR user_id IS NULL ORDER BY name");
    $stmt->execute([$_SESSION['user_id']]);
    $plants = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if (!isset($error) && $sensorId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM sensors WHERE id = ?");
    $stmt->execute([$sensorId]);
    $sensor = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$sensor) {
        $errors[] = 'Sensor not found';
    } else {
        $link = $pdo->prepare("SELECT plant_id FROM plant_sensors WHERE sensor_id = ? ORDER BY id DESC LIMIT 1");
        $link->execute([$sensorId]);
        $linkedPlantId = $link->fetchColumn() ?: null;
    }
} else {
    $errors[] = 'Invalid sensor ID';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $sensor) {
    $name = trim($_POST['name'] ?? '');
    $type = $_POST['type'] ?? '';
    $location = trim($_POST['location'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $unit = trim($_POST['unit'] ?? 'percentage');
    $plant_id = !empty($_POST['plant_id']) ? (int)$_POST['plant_id'] : null;
    $plot_type = in_array($type, ['temperature', 'humidity', 'moisture'], true) ? $type : 'moisture';

    if ($name === '' || $type === '') {
        $errors[] = 'Name and type are required';
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("
                UPDATE sensors
                SET name = ?, type = ?, location = ?, description = ?, unit = ?, plot_type = ?, user_id = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([
                $name, $type, $location ?: null, $description ?: null, $unit, $plot_type, $_SESSION['user_id'], $sensorId
            ]);
            $pdo->prepare("DELETE FROM plant_sensors WHERE sensor_id = ?")->execute([$sensorId]);
            if ($plant_id) {
                $pdo->prepare("INSERT INTO plant_sensors (plant_id, sensor_id) VALUES (?, ?)")->execute([$plant_id, $sensorId]);
            }
            $pdo->commit();
            $success = 'Sensor updated successfully!';
            $stmt = $pdo->prepare("SELECT * FROM sensors WHERE id = ?");
            $stmt->execute([$sensorId]);
            $sensor = $stmt->fetch(PDO::FETCH_ASSOC);
            $linkedPlantId = $plant_id;
            header('Refresh: 1; url=sensors.php');
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = 'Failed to update sensor: ' . $e->getMessage();
        }
    }
}

$page_title = 'Edit Sensor';
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
            <a href="/sensors.php" class="nav-link active"><i class="fas fa-microchip"></i><span>Sensors</span></a>
            <a href="/plants.php" class="nav-link"><i class="fas fa-seedling"></i><span>Plants</span></a>
            <a href="/readings.php" class="nav-link"><i class="fas fa-chart-line"></i><span>Readings</span></a>
            <a href="/settings.php" class="nav-link"><i class="fas fa-cog"></i><span>Settings</span></a>
            <a href="/logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
        </div>
    </nav>
    <main class="main-content">
        <div class="dashboard-header">
            <h1 class="dashboard-title">Edit Sensor</h1>
            <p class="dashboard-subtitle">Update sensor details and plant linking</p>
        </div>
        <?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>
        <?php foreach ($errors as $err): ?><div class="alert alert-danger"><?php echo htmlspecialchars($err); ?></div><?php endforeach; ?>
        <?php if ($sensor): ?>
        <form method="POST" class="readings-section" style="max-width:720px;padding:1.5rem;">
            <label>Name<input class="form-control" type="text" name="name" value="<?php echo htmlspecialchars($sensor['name']); ?>" required></label><br><br>
            <label>Type
                <select class="form-control" name="type" required>
                    <?php foreach (['temperature','humidity','moisture'] as $t): ?>
                    <option value="<?php echo $t; ?>" <?php echo $sensor['type'] === $t ? 'selected' : ''; ?>><?php echo ucfirst($t); ?></option>
                    <?php endforeach; ?>
                </select>
            </label><br><br>
            <label>Location<input class="form-control" type="text" name="location" value="<?php echo htmlspecialchars((string)$sensor['location']); ?>"></label><br><br>
            <label>Unit<input class="form-control" type="text" name="unit" value="<?php echo htmlspecialchars((string)$sensor['unit']); ?>"></label><br><br>
            <label>Description<textarea class="form-control" name="description"><?php echo htmlspecialchars((string)$sensor['description']); ?></textarea></label><br><br>
            <label>Linked plant
                <select class="form-control" name="plant_id">
                    <option value="">None</option>
                    <?php foreach ($plants as $p): ?>
                    <option value="<?php echo (int)$p['id']; ?>" <?php echo ((int)$linkedPlantId === (int)$p['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($p['name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </label><br><br>
            <button class="btn btn-primary" type="submit">Save Sensor</button>
            <a class="btn" href="sensors.php">Cancel</a>
        </form>
        <?php endif; ?>
    </main>
</div>
</body>
</html>
