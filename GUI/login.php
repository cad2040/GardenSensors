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

// Generate CSRF token
$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Garden Sensors Dashboard</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <div class="auth-container">
            <h1>Garden Sensors Dashboard</h1>
            
            <div class="auth-tabs">
                <button class="tablinks active" data-tab="login">Login</button>
                <button class="tablinks" data-tab="register">Register</button>
                <button class="tablinks" data-tab="forgot-password">Forgot Password</button>
            </div>
            
            <!-- Login Form -->
            <div id="login" class="tabcontent active">
                <form id="login-form" class="auth-form">
                    <input type="hidden" name="action" value="login">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    
                    <div class="form-group">
                        <label class="form-label" for="login-email">Email</label>
                        <input type="email" 
                               class="form-control" 
                               id="login-email" 
                               name="email" 
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="login-password">Password</label>
                        <input type="password" 
                               class="form-control" 
                               id="login-password" 
                               name="password" 
                               required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Login</button>
                </form>
            </div>
            
            <!-- Register Form -->
            <div id="register" class="tabcontent">
                <form id="register-form" class="auth-form">
                    <input type="hidden" name="action" value="register">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    
                    <div class="form-group">
                        <label class="form-label" for="register-name">Name</label>
                        <input type="text" 
                               class="form-control" 
                               id="register-name" 
                               name="name" 
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="register-email">Email</label>
                        <input type="email" 
                               class="form-control" 
                               id="register-email" 
                               name="email" 
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="register-password">Password</label>
                        <input type="password" 
                               class="form-control" 
                               id="register-password" 
                               name="password" 
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="register-confirm-password">Confirm Password</label>
                        <input type="password" 
                               class="form-control" 
                               id="register-confirm-password" 
                               name="confirm_password" 
                               required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Register</button>
                </form>
            </div>
            
            <!-- Forgot Password Form -->
            <div id="forgot-password" class="tabcontent">
                <form id="forgot-password-form" class="auth-form">
                    <input type="hidden" name="action" value="forgot_password">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    
                    <div class="form-group">
                        <label class="form-label" for="forgot-email">Email</label>
                        <input type="email" 
                               class="form-control" 
                               id="forgot-email" 
                               name="email" 
                               required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Reset Password</button>
                </form>
            </div>
        </div>
    </div>
    
    <script src="js/main.js"></script>
    <script>
        // Initialize auth tabs
        document.addEventListener('DOMContentLoaded', function() {
            const tabButtons = document.querySelectorAll('.auth-tabs .tablinks');
            const tabContents = document.querySelectorAll('.tabcontent');
            
            tabButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const tabId = this.getAttribute('data-tab');
                    
                    // Update active tab button
                    tabButtons.forEach(btn => btn.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Show selected tab content
                    tabContents.forEach(content => {
                        content.classList.remove('active');
                        if (content.id === tabId) {
                            content.classList.add('active');
                        }
                    });
                });
            });
            
            // Handle form submissions
            const forms = document.querySelectorAll('.auth-form');
            forms.forEach(form => {
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
                            if (this.id === 'login-form') {
                                window.location.href = 'index.php';
                            }
                        } else {
                            displayAlert(result.message, 'error');
                        }
                    } catch (error) {
                        displayAlert('An error occurred. Please try again.', 'error');
                        console.error('Form submission error:', error);
                    }
                });
            });
        });
    </script>
</body>
</html> 