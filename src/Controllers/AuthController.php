<?php
namespace App\Controllers;

use App\Models\User;
use App\Services\AuthService;

class AuthController extends Controller {
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

    public function login(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            
            if ($this->authService->login($email, $password)) {
                $this->redirect('/dashboard');
            }
            
            $this->render('auth/login', ['error' => 'Invalid credentials']);
        }
        
        $this->render('auth/login');
    }

    public function register(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $_POST;
            $result = $this->authService->register($data);
            
            if ($result) {
                $this->redirect('/login');
            }
            
            $this->render('auth/register', ['error' => 'Registration failed']);
        }
        
        $this->render('auth/register');
    }

    public function logout(): void {
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

    public function resetPassword(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $_POST['email'] ?? '';
            
            if ($this->authService->sendPasswordReset($email)) {
                $this->render('auth/reset-sent');
            }
            
            $this->render('auth/reset-password', ['error' => 'Failed to send reset email']);
        }
        
        $this->render('auth/reset-password');
    }

    public function resetPasswordConfirm($token) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $password = $_POST['password'] ?? '';
            
            if ($this->authService->resetPassword($token, $password)) {
                $this->redirect('/login');
            }
            
            $this->render('auth/reset-password-confirm', ['error' => 'Failed to reset password']);
        }
        
        $this->render('auth/reset-password-confirm');
    }
} 