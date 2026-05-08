<?php
/**
 * API endpoint for generating plant-based sensor plots
 */

// Prevent any output before JSON response
ob_start();

// Start session first, before any output
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0);
    session_start();
}

// Include configuration
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';

/**
 * Decode JSON from command output, tolerating warnings/noise lines.
 */
function decodeJsonFromOutput(array $outputLines): ?array {
    $rawOutput = trim(implode("\n", $outputLines));
    if ($rawOutput === '') {
        return null;
    }

    $decoded = json_decode($rawOutput, true);
    if (is_array($decoded)) {
        return $decoded;
    }

    for ($i = count($outputLines) - 1; $i >= 0; $i--) {
        $line = trim($outputLines[$i]);
        if ($line === '') {
            continue;
        }
        $decodedLine = json_decode($line, true);
        if (is_array($decodedLine)) {
            return $decodedLine;
        }
    }

    return null;
}

// Clear any output that might have been generated
ob_clean();

// Set JSON content type early
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Get parameters
$plant_id = null;
if (isset($_GET['plant_id']) && $_GET['plant_id'] !== '' && $_GET['plant_id'] !== '0') {
    $plant_id = intval($_GET['plant_id']);
}
$days = isset($_GET['days']) ? intval($_GET['days']) : 7;
$format = isset($_GET['format']) ? $_GET['format'] : 'components'; // 'components' or 'json'

// Validate days
if ($days < 1 || $days > 365) {
    $days = 7;
}

// Get Python path - use deployment directory
// Try to detect deployment directory from current file location.
// __DIR__ is public/api, so project root is two levels up.
$deploymentDir = dirname(dirname(__DIR__));
if (file_exists('/var/www/html/garden-sensors')) {
    $deploymentDir = '/var/www/html/garden-sensors';
}

$pythonPath = $deploymentDir . '/venv/bin/python3';
$altPythonPath = $deploymentDir . '/.venv/bin/python';
if (file_exists($altPythonPath)) {
    $pythonPath = $altPythonPath;
}
$scriptPath = $deploymentDir . '/python/generate_plot_api.py';

// Build command
$command = escapeshellcmd($pythonPath) . ' ' . escapeshellarg($scriptPath);
$command .= ' --days ' . escapeshellarg($days);
if ($plant_id !== null && $plant_id > 0) {
    $command .= ' --plant-id ' . escapeshellarg($plant_id);
}
$command .= ' --format ' . escapeshellarg($format);

// Execute Python script (capture stderr for diagnostics)
$output = [];
$returnVar = 0;
exec($command . ' 2>&1', $output, $returnVar);

if ($returnVar !== 0) {
    // The Python script reports expected "no data" cases with exit code 1.
    $rawOutput = implode("\n", $output);
    $parsedError = decodeJsonFromOutput($output);

    if (is_array($parsedError) && isset($parsedError['success']) && $parsedError['success'] === false) {
        echo json_encode($parsedError);
        exit;
    }

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to generate plot',
        'details' => $rawOutput
    ]);
    exit;
}

// Parse output
$result = decodeJsonFromOutput($output);

if ($result === null) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Invalid response from plot generator',
        'details' => implode("\n", $output)
    ]);
    exit;
}

// Return result
echo json_encode($result);

