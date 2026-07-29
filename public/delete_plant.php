<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    if (!empty($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')) {
        sendJsonResponse(['success' => false, 'message' => 'User not authenticated'], 401);
    }
    header('Location: login.php');
    exit;
}

$id = sanitizeInput($_POST['id'] ?? $_GET['id'] ?? '');
if ($id === '') {
    sendJsonResponse(['success' => false, 'message' => 'Invalid plant ID'], 400);
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    $conn->beginTransaction();

    $conn->prepare("DELETE FROM plant_sensors WHERE plant_id = ?")->execute([$id]);
    $stmt = $conn->prepare("DELETE FROM plants WHERE id = ?");
    $stmt->execute([$id]);

    if ($stmt->rowCount() === 0) {
        $conn->rollBack();
        sendJsonResponse(['success' => false, 'message' => 'Plant not found'], 404);
    }

    $conn->commit();

    if (isset($_GET['id']) && empty($_POST['id'])) {
        header('Location: plants.php');
        exit;
    }

    sendJsonResponse(['success' => true, 'message' => 'Plant deleted']);
} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    logError('delete_plant failed: ' . $e->getMessage());
    sendJsonResponse(['success' => false, 'message' => 'Failed to delete plant'], 500);
}
