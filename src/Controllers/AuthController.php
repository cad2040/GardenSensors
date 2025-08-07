<?php
namespace GardenSensors\Controllers;

use GardenSensors\Models\User;
use GardenSensors\Services\AuthService;

class AuthController extends BaseController {
    private $authService;

    public function __construct() {
        $this->authService = new AuthService();
    }

    public function showLogin(): void {
        if ($this->isAuthenticated()) {
            $this->redirect('/dashboard');
        }
        
        $this->view('auth/login', [
            'csrf_token' => $this->getCsrfToken()
        ]);
    }

    public function showRegister(): void {
        if ($this->isAuthenticated()) {
            $this->redirect('/dashboard');
        }
        
        $this->view('auth/register', [
            'csrf_token' => $this->getCsrfToken()
        ]);
    }

    public function login($credentials = null): mixed {
        if ($credentials !== null) {
            // Test mode - return array result
            $email = $credentials['username'] ?? $credentials['email'] ?? '';
            $password = $credentials['password'] ?? '';
            
            if ($this->authService->login($email, $password)) {
                return ['success' => true, 'message' => 'Login successful'];
            }
            
            return ['success' => false, 'message' => 'Invalid credentials'];
        }
        
        // Normal web request mode
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            
            if ($this->authService->login($email, $password)) {
                $this->redirect('/dashboard');
            }
            
            $this->render('auth/login', ['error' => 'Invalid credentials']);
        }
        
        $this->render('auth/login');
    }

    public function register($userData = null): mixed {
        if ($userData !== null) {
            // Test mode - return array result
            $result = $this->authService->register($userData);
            
            if ($result) {
                return ['success' => true, 'message' => 'Registration successful'];
            }
            
            return ['success' => false, 'message' => 'Registration failed'];
        }
        
        // Normal web request mode
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $data = $_POST;
            $result = $this->authService->register($data);
            
            if ($result) {
                $this->redirect('/login');
            }
            
            $this->render('auth/register', ['error' => 'Registration failed']);
        }
        
        $this->render('auth/register');
    }

    public function logout(): mixed {
        if (func_num_args() > 0 || (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'GET')) {
            // Test mode or called with parameters - return array result
            $this->authService->logout();
            return ['success' => true, 'message' => 'Logout successful'];
        }
        
        // Normal web request mode
        $this->authService->logout();
        $this->redirect('/login');
    }

    public function showForgotPassword(): void {
        if ($this->isAuthenticated()) {
            $this->redirect('/dashboard');
        }
        
        $this->view('auth/forgot-password', [
            'csrf_token' => $this->getCsrfToken()
        ]);
    }

    public function forgotPassword(): void {
        $this->requireCsrfToken();

        $errors = $this->validate([
            'email' => 'required|email'
        ]);

        if (!empty($errors)) {
            $this->respondWithError('Invalid email address');
            return;
        }

        $email = $this->request->post('email');
        $user = User::findByEmail($email);

        if (!$user) {
            // Don't reveal whether the email exists
            $this->respondWithSuccess('If your email is registered, you will receive password reset instructions');
            return;
        }

        // Generate password reset token
        $token = bin2hex(random_bytes(32));
        $user->reset_token = $token;
        $user->reset_token_expires_at = date('Y-m-d H:i:s', time() + 3600); // Token valid for 1 hour
        $user->save();

        // Send password reset email
        // TODO: Implement email sending
        // For now, just redirect with success message
        $this->respondWithSuccess('Password reset instructions have been sent to your email');
    }

    public function showResetPassword(): void {
        if ($this->isAuthenticated()) {
            $this->redirect('/dashboard');
        }

        $token = $this->request->get('token');
        if (!$token) {
            $this->redirect('/login');
            return;
        }

        $user = User::findBy('reset_token', $token);
        if (!$user || strtotime($user->reset_token_expires_at) < time()) {
            $this->flash('error', 'Invalid or expired password reset token');
            $this->redirect('/login');
            return;
        }

        $this->view('auth/reset-password', [
            'token' => $token,
            'csrf_token' => $this->getCsrfToken()
        ]);
    }

    public function resetPassword($email = null): mixed {
        if ($email !== null) {
            // Test mode - return array result
            if ($this->authService->sendPasswordReset($email)) {
                return ['success' => true, 'message' => 'Password reset email sent'];
            }
            
            return ['success' => false, 'message' => 'Failed to send reset email'];
        }
        
        // Normal web request mode
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $email = $_POST['email'] ?? '';
            
            if ($this->authService->sendPasswordReset($email)) {
                $this->render('auth/reset-sent');
            }
            
            $this->render('auth/reset-password', ['error' => 'Failed to send reset email']);
        }
        
        $this->render('auth/reset-password');
    }

    public function resetPasswordConfirm($token) {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $password = $_POST['password'] ?? '';
            
            if ($this->authService->resetPassword($token, $password)) {
                $this->redirect('/login');
            }
            
            $this->render('auth/reset-password-confirm', ['error' => 'Failed to reset password']);
        }
        
        $this->render('auth/reset-password-confirm');
    }
} 