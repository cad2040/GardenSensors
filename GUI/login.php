<?php
require_once 'config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

// Generate CSRF token
$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="login-container">
            <h1><?php echo APP_NAME; ?></h1>
            
            <!-- Tab Navigation -->
            <div class="tab-nav">
                <button class="tab-btn active" data-tab="login">Login</button>
                <button class="tab-btn" data-tab="register">Register</button>
                <button class="tab-btn" data-tab="forgot">Forgot Password</button>
            </div>
            
            <!-- Login Form -->
            <div class="tab-content active" id="login">
                <form id="loginForm" method="post" action="auth.php">
                    <input type="hidden" name="action" value="login">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <div class="input-group">
                            <i class="fas fa-envelope"></i>
                            <input type="email" id="email" name="email" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-group">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="password" name="password" required>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Login</button>
                </form>
            </div>
            
            <!-- Register Form -->
            <div class="tab-content" id="register">
                <form id="registerForm" method="post" action="auth.php">
                    <input type="hidden" name="action" value="register">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <div class="form-group">
                        <label for="name">Name</label>
                        <div class="input-group">
                            <i class="fas fa-user"></i>
                            <input type="text" id="name" name="name" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="register_email">Email</label>
                        <div class="input-group">
                            <i class="fas fa-envelope"></i>
                            <input type="email" id="register_email" name="email" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="register_password">Password</label>
                        <div class="input-group">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="register_password" name="password" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <div class="input-group">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="confirm_password" name="confirm_password" required>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Register</button>
                </form>
            </div>
            
            <!-- Forgot Password Form -->
            <div class="tab-content" id="forgot">
                <form id="forgotForm" method="post" action="auth.php">
                    <input type="hidden" name="action" value="forgot_password">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <div class="form-group">
                        <label for="forgot_email">Email</label>
                        <div class="input-group">
                            <i class="fas fa-envelope"></i>
                            <input type="email" id="forgot_email" name="email" required>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Reset Password</button>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="js/main.js"></script>
    <script>
        $(document).ready(function() {
            // Tab switching
            $('.tab-btn').click(function() {
                $('.tab-btn').removeClass('active');
                $(this).addClass('active');
                
                const tabId = $(this).data('tab');
                $('.tab-content').removeClass('active');
                $(`#${tabId}`).addClass('active');
            });
            
            // Form submissions
            $('#loginForm, #registerForm, #forgotForm').submit(function(e) {
                e.preventDefault();
                
                const form = $(this);
                const formData = form.serialize();
                
                $.ajax({
                    url: form.attr('action'),
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            displayAlert(response.message, 'success');
                            if (response.redirect) {
                                setTimeout(function() {
                                    window.location.href = response.redirect;
                                }, 1500);
                            }
                        } else {
                            displayAlert(response.message, 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error:', error);
                        displayAlert('An error occurred. Please try again.', 'error');
                    }
                });
            });
        });
    </script>
</body>
</html> 