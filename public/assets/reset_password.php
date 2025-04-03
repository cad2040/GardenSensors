<?php
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Start session
session_start();

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Validate reset token
$token = sanitizeInput($_GET['token'] ?? '');
if (empty($token)) {
    header('Location: login.php');
    exit;
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Check if token is valid and not expired
    $query = "SELECT user_id FROM users WHERE reset_token = :token AND reset_expires > NOW() AND status = 'active'";
    $stmt = $conn->prepare($query);
    $stmt->execute([':token' => $token]);
    
    if (!$stmt->fetch()) {
        displayAlert('Invalid or expired reset token. Please request a new one.', 'error');
        header('Location: login.php');
        exit;
    }
    
} catch (Exception $e) {
    logError('Reset password validation error: ' . $e->getMessage());
    header('Location: login.php');
    exit;
}

// Generate CSRF token
$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Garden Sensors Dashboard</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <div class="auth-container">
            <h1>Reset Password</h1>
            
            <form id="reset-password-form" class="auth-form">
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="token" value="<?php echo $token; ?>">
                
                <div class="form-group">
                    <label class="form-label" for="reset-password">New Password</label>
                    <input type="password" 
                           class="form-control" 
                           id="reset-password" 
                           name="password" 
                           required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="reset-confirm-password">Confirm New Password</label>
                    <input type="password" 
                           class="form-control" 
                           id="reset-confirm-password" 
                           name="confirm_password" 
                           required>
                </div>
                
                <button type="submit" class="btn btn-primary">Reset Password</button>
            </form>
        </div>
    </div>
    
    <script src="js/main.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('reset-password-form');
            
            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                try {
                    const formData = new FormData(this);
                    const response = await fetch('auth.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        displayAlert(result.message, 'success');
                        setTimeout(() => {
                            window.location.href = 'login.php';
                        }, 2000);
                    } else {
                        displayAlert(result.message, 'error');
                    }
                } catch (error) {
                    displayAlert('An error occurred. Please try again.', 'error');
                    console.error('Form submission error:', error);
                }
            });
        });
    </script>
</body>
</html> 