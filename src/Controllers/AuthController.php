<?php
namespace App\Controllers;

use App\Models\User;

class AuthController extends Controller {
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
        $this->requireCsrfToken();

        $errors = $this->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if (!empty($errors)) {
            $this->respondWithError('Invalid input data');
            return;
        }

        $email = $this->request->post('email');
        $password = $this->request->post('password');
        $remember = (bool) $this->request->post('remember');

        $user = User::findByEmail($email);

        if (!$user || !$user->verifyPassword($password)) {
            $this->respondWithError('Invalid email or password');
            return;
        }

        if ($remember) {
            // Set remember me cookie - valid for 30 days
            $token = bin2hex(random_bytes(32));
            setcookie('remember_token', $token, time() + (86400 * 30), '/', '', true, true);
            
            // Store token in database
            $user->remember_token = $token;
            $user->save();
        }

        $user->updateLastLogin();
        $this->setUser($user->toArray());
        
        $this->respondWithSuccess('Login successful', '/dashboard');
    }

    public function register(): void {
        $this->requireCsrfToken();

        $errors = $this->validate([
            'username' => 'required|min:3|max:50',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
            'password_confirmation' => 'required'
        ]);

        if (!empty($errors)) {
            $this->respondWithError('Invalid input data');
            return;
        }

        $username = $this->request->post('username');
        $email = $this->request->post('email');
        $password = $this->request->post('password');

        // Check if email already exists
        if (User::findByEmail($email)) {
            $this->respondWithError('Email already registered');
            return;
        }

        // Check if username already exists
        if (User::findByUsername($username)) {
            $this->respondWithError('Username already taken');
            return;
        }

        // Create new user
        $user = new User([
            'username' => $username,
            'email' => $email,
            'role' => 'user'
        ]);
        
        $user->setPassword($password);
        
        if (!$user->save()) {
            $this->respondWithError('Failed to create account');
            return;
        }

        // Log the user in
        $user->updateLastLogin();
        $this->setUser($user->toArray());
        
        $this->respondWithSuccess('Registration successful', '/dashboard');
    }

    public function logout(): void {
        $this->requireCsrfToken();
        
        // Clear remember me cookie if exists
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/', '', true, true);
            
            // Clear token from database if user is logged in
            if ($user = $this->getUser()) {
                $user = User::find($user['user_id']);
                if ($user) {
                    $user->remember_token = null;
                    $user->save();
                }
            }
        }

        $this->logout();
        $this->respondWithSuccess('Logout successful', '/login');
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
        $this->requireCsrfToken();

        $errors = $this->validate([
            'token' => 'required',
            'password' => 'required|min:8|confirmed',
            'password_confirmation' => 'required'
        ]);

        if (!empty($errors)) {
            $this->respondWithError('Invalid input data');
            return;
        }

        $token = $this->request->post('token');
        $password = $this->request->post('password');

        $user = User::findBy('reset_token', $token);
        if (!$user || strtotime($user->reset_token_expires_at) < time()) {
            $this->respondWithError('Invalid or expired password reset token');
            return;
        }

        $user->setPassword($password);
        $user->reset_token = null;
        $user->reset_token_expires_at = null;
        
        if (!$user->save()) {
            $this->respondWithError('Failed to reset password');
            return;
        }

        $this->respondWithSuccess('Password has been reset successfully', '/login');
    }
} 