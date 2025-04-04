<?php
require_once 'config.php';
require_once 'includes/functions.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

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
            sendJsonResponse(['success' => false, 'message' => 'Invalid action']);
    }
}

function handleLogin() {
    try {
        // Log request data
        error_log("Login attempt - POST data: " . print_r($_POST, true));
        
        // Validate input
        $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $password = $_POST['password'] ?? '';
        
        if (!$email || empty($password)) {
            throw new Exception('Please provide both email and password');
        }
        
        // Get user from database
        $conn = getDbConnection();
        
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        // Log user data (without password)
        $logUser = $user;
        unset($logUser['password']);
        error_log("User data: " . print_r($logUser, true));
        
        if (!$user) {
            throw new Exception('Invalid email or password');
        }
        
        if (!password_verify($password, $user['password'])) {
            error_log("Password verification failed for user: " . $email);
            throw new Exception('Invalid email or password');
        }
        
        // Update last login
        $updateStmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
        if (!$updateStmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        $updateStmt->bind_param("i", $user['user_id']);
        $updateStmt->execute();
        
        // Set session variables
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['user_name'] = $user['name'] ?? $user['username'];
        $_SESSION['user_role'] = $user['role'];
        
        // Log session data
        error_log("Session data: " . print_r($_SESSION, true));
        
        // Log successful login
        $logStmt = $conn->prepare("INSERT INTO systemlog (action, details, user_id) VALUES ('login', 'User logged in successfully', ?)");
        if (!$logStmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        $logStmt->bind_param("i", $user['user_id']);
        $logStmt->execute();
        
        sendJsonResponse(['success' => true, 'message' => 'Login successful', 'redirect' => 'index.php']);
        
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        logError('Login error: ' . $e->getMessage());
        sendJsonResponse(['success' => false, 'message' => $e->getMessage()]);
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
        $conn = getDbConnection();
        
        $checkStmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        if (!$checkStmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        $checkStmt->bind_param("s", $email);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        
        if ($result->num_rows > 0) {
            throw new Exception('Email already registered');
        }
        
        // Generate username from name
        $username = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $name));
        
        // Check if username exists and append number if needed
        $baseUsername = $username;
        $counter = 1;
        while (true) {
            $checkUsernameStmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
            if (!$checkUsernameStmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            $checkUsernameStmt->bind_param("s", $username);
            $checkUsernameStmt->execute();
            $result = $checkUsernameStmt->get_result();
            
            if ($result->num_rows === 0) {
                break;
            }
            $username = $baseUsername . $counter;
            $counter++;
        }
        
        // Insert new user
        $stmt = $conn->prepare("INSERT INTO users (username, name, email, password, role) VALUES (?, ?, ?, ?, 'user')");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt->bind_param("ssss", $username, $name, $email, $hashedPassword);
        $stmt->execute();
        
        $userId = $conn->insert_id;
        
        // Log registration
        $logStmt = $conn->prepare("INSERT INTO systemlog (action, details, user_id) VALUES ('register', 'New user registered', ?)");
        if (!$logStmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        $logStmt->bind_param("i", $userId);
        $logStmt->execute();
        
        sendJsonResponse(['success' => true, 'message' => 'Registration successful. Your username is: ' . $username]);
        
    } catch (Exception $e) {
        logError('Registration error: ' . $e->getMessage());
        sendJsonResponse(['success' => false, 'message' => $e->getMessage()]);
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
        $conn = getDbConnection();
        
        $query = "SELECT user_id, name FROM users WHERE email = ? AND status = 'active'";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        if (!$user) {
            throw new Exception('No account found with this email address');
        }
        
        // Generate reset token
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Store reset token
        $updateQuery = "UPDATE users SET reset_token = ?, reset_expires = ? WHERE user_id = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bind_param("sss", $token, $expires, $user['user_id']);
        $updateStmt->execute();
        
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
        $logStmt = $conn->prepare("INSERT INTO systemlog (action, details, user_id) VALUES ('forgot_password', 'Password reset requested', ?)");
        if (!$logStmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        $logStmt->bind_param("i", $user['user_id']);
        $logStmt->execute();
        
        sendJsonResponse(['success' => true, 'message' => 'Password reset instructions sent to your email']);
        
    } catch (Exception $e) {
        logError('Forgot password error: ' . $e->getMessage());
        sendJsonResponse(['success' => false, 'message' => $e->getMessage()]);
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
        $conn = getDbConnection();
        
        $query = "SELECT user_id FROM users WHERE reset_token = ? AND reset_expires > NOW() AND status = 'active'";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        if (!$user) {
            throw new Exception('Invalid or expired reset token');
        }
        
        // Update password
        $updateQuery = "UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE user_id = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bind_param("si", password_hash($password, PASSWORD_DEFAULT), $user['user_id']);
        $updateStmt->execute();
        
        // Log password reset
        $logStmt = $conn->prepare("INSERT INTO systemlog (action, details, user_id) VALUES ('reset_password', 'Password reset completed', ?)");
        if (!$logStmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        $logStmt->bind_param("i", $user['user_id']);
        $logStmt->execute();
        
        sendJsonResponse(['success' => true, 'message' => 'Password reset successful']);
        
    } catch (Exception $e) {
        logError('Reset password error: ' . $e->getMessage());
        sendJsonResponse(['success' => false, 'message' => $e->getMessage()]);
    }
}

// Helper function to send JSON responses
function sendJsonResponse($response) {
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
} 