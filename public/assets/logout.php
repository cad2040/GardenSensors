<?php
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Start session
session_start();

// Check if user is logged in
if (isset($_SESSION['user_id'])) {
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        // Log the logout
        $logQuery = "INSERT INTO SystemLog (action, details, user_id) VALUES ('logout', 'User logged out', :user_id)";
        $logStmt = $conn->prepare($logQuery);
        $logStmt->execute([':user_id' => $_SESSION['user_id']]);
        
    } catch (Exception $e) {
        logError('Logout error: ' . $e->getMessage());
    }
}

// Clear all session variables
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destroy the session
session_destroy();

// Redirect to login page
header('Location: login.php');
exit; 