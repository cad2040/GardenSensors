<?php
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Start session
session_start();

// Check if user is already logged in
if (isset($_SESSION['user_id']) && basename($_SERVER['PHP_SELF']) !== 'logout.php') {
    header('Location: index.php');
    exit;
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = sanitizeInput($_POST['action']);
    
    switch ($action) {
        case 'login':
            handleLogin();
            break;
            
        case 'register':
            handleRegister();
            break;
            
        case 'forgot_password':
            handleForgotPassword();
            break;
            
        case 'reset_password':
            handleResetPassword();
            break;
            
        default:
            sendJsonResponse(false, 'Invalid action');
    }
}

function handleLogin() {
    try {
        // Validate input
        $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $password = $_POST['password'] ?? '';
        
        if (!$email || empty($password)) {
            throw new Exception('Please provide both email and password');
        }
        
        // Get user from database
        $db = new Database();
        $conn = $db->getConnection();
        
        $query = "SELECT * FROM Users WHERE email = :email AND status = 'active'";
        $stmt = $conn->prepare($query);
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user || !password_verify($password, $user['password'])) {
            throw new Exception('Invalid email or password');
        }
        
        // Update last login
        $updateQuery = "UPDATE Users SET last_login = NOW() WHERE id = :id";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->execute([':id' => $user['id']]);
        
        // Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        
        // Log successful login
        $logQuery = "INSERT INTO SystemLog (action, details, user_id) VALUES ('login', 'User logged in successfully', :user_id)";
        $logStmt = $conn->prepare($logQuery);
        $logStmt->execute([':user_id' => $user['id']]);
        
        sendJsonResponse(true, 'Login successful');
        
    } catch (Exception $e) {
        logError('Login error: ' . $e->getMessage());
        sendJsonResponse(false, $e->getMessage());
    }
}

function handleRegister() {
    try {
        // Validate input
        $name = sanitizeInput($_POST['name'] ?? '');
        $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if (empty($name) || !$email || empty($password) || empty($confirmPassword)) {
            throw new Exception('Please fill in all required fields');
        }
        
        if ($password !== $confirmPassword) {
            throw new Exception('Passwords do not match');
        }
        
        if (strlen($password) < 8) {
            throw new Exception('Password must be at least 8 characters long');
        }
        
        // Check if email already exists
        $db = new Database();
        $conn = $db->getConnection();
        
        $checkQuery = "SELECT id FROM Users WHERE email = :email";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->execute([':email' => $email]);
        
        if ($checkStmt->fetch()) {
            throw new Exception('Email already registered');
        }
        
        // Insert new user
        $query = "INSERT INTO Users (name, email, password, role, status) 
                 VALUES (:name, :email, :password, 'user', 'active')";
        $stmt = $conn->prepare($query);
        $stmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':password' => password_hash($password, PASSWORD_DEFAULT)
        ]);
        
        $userId = $conn->lastInsertId();
        
        // Log registration
        $logQuery = "INSERT INTO SystemLog (action, details, user_id) VALUES ('register', 'New user registered', :user_id)";
        $logStmt = $conn->prepare($logQuery);
        $logStmt->execute([':user_id' => $userId]);
        
        sendJsonResponse(true, 'Registration successful');
        
    } catch (Exception $e) {
        logError('Registration error: ' . $e->getMessage());
        sendJsonResponse(false, $e->getMessage());
    }
}

function handleForgotPassword() {
    try {
        // Validate input
        $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
        
        if (!$email) {
            throw new Exception('Please provide a valid email address');
        }
        
        // Get user from database
        $db = new Database();
        $conn = $db->getConnection();
        
        $query = "SELECT id, name FROM Users WHERE email = :email AND status = 'active'";
        $stmt = $conn->prepare($query);
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            throw new Exception('No account found with this email address');
        }
        
        // Generate reset token
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Store reset token
        $updateQuery = "UPDATE Users SET reset_token = :token, reset_expires = :expires WHERE id = :id";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->execute([
            ':token' => $token,
            ':expires' => $expires,
            ':id' => $user['id']
        ]);
        
        // Send reset email
        $resetLink = APP_URL . '/reset_password.php?token=' . $token;
        $to = $email;
        $subject = 'Password Reset Request';
        $message = "Hello {$user['name']},\n\n";
        $message .= "You have requested to reset your password. Click the link below to proceed:\n\n";
        $message .= $resetLink . "\n\n";
        $message .= "This link will expire in 1 hour.\n\n";
        $message .= "If you did not request this reset, please ignore this email.\n\n";
        $message .= "Best regards,\nGarden Sensors Team";
        
        $headers = 'From: ' . APP_EMAIL . "\r\n" .
                  'Reply-To: ' . APP_EMAIL . "\r\n" .
                  'X-Mailer: PHP/' . phpversion();
        
        mail($to, $subject, $message, $headers);
        
        // Log password reset request
        $logQuery = "INSERT INTO SystemLog (action, details, user_id) VALUES ('forgot_password', 'Password reset requested', :user_id)";
        $logStmt = $conn->prepare($logQuery);
        $logStmt->execute([':user_id' => $user['id']]);
        
        sendJsonResponse(true, 'Password reset instructions sent to your email');
        
    } catch (Exception $e) {
        logError('Forgot password error: ' . $e->getMessage());
        sendJsonResponse(false, $e->getMessage());
    }
}

function handleResetPassword() {
    try {
        // Validate input
        $token = sanitizeInput($_POST['token'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if (empty($token) || empty($password) || empty($confirmPassword)) {
            throw new Exception('Please fill in all required fields');
        }
        
        if ($password !== $confirmPassword) {
            throw new Exception('Passwords do not match');
        }
        
        if (strlen($password) < 8) {
            throw new Exception('Password must be at least 8 characters long');
        }
        
        // Get user from database
        $db = new Database();
        $conn = $db->getConnection();
        
        $query = "SELECT id FROM Users WHERE reset_token = :token AND reset_expires > NOW() AND status = 'active'";
        $stmt = $conn->prepare($query);
        $stmt->execute([':token' => $token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            throw new Exception('Invalid or expired reset token');
        }
        
        // Update password
        $updateQuery = "UPDATE Users SET password = :password, reset_token = NULL, reset_expires = NULL WHERE id = :id";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->execute([
            ':password' => password_hash($password, PASSWORD_DEFAULT),
            ':id' => $user['id']
        ]);
        
        // Log password reset
        $logQuery = "INSERT INTO SystemLog (action, details, user_id) VALUES ('reset_password', 'Password reset completed', :user_id)";
        $logStmt = $conn->prepare($logQuery);
        $logStmt->execute([':user_id' => $user['id']]);
        
        sendJsonResponse(true, 'Password reset successful');
        
    } catch (Exception $e) {
        logError('Reset password error: ' . $e->getMessage());
        sendJsonResponse(false, $e->getMessage());
    }
}

// Helper function to send JSON responses
function sendJsonResponse($success, $message) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message
    ]);
    exit;
} 