<?php

namespace GardenSensors\Services;

use GardenSensors\Models\User;
use GardenSensors\Core\Database;

class AuthService {
    private Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function login(string $email, string $password): bool {
        $user = User::findByEmail($email);
        
        if (!$user || !$user->verifyPassword($password)) {
            return false;
        }

        if (!$user->isActive()) {
            return false;
        }

        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION['user_id'] = $user->getId();
        $_SESSION['username'] = $user->getUsername();
        $_SESSION['role'] = $user->getRole();

        $user->updateLastLogin();
        
        return true;
    }

    public function register(array $data): bool {
        // Validate required fields
        if (empty($data['username']) || empty($data['email']) || empty($data['password'])) {
            return false;
        }

        // Check if user already exists
        if (User::findByEmail($data['email']) || User::findByUsername($data['username'])) {
            return false;
        }

        // Create new user
        $user = new User([
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => 'user',
            'status' => 'active'
        ]);

        return $user->save();
    }

    public function logout(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        session_destroy();
    }

    public function isAuthenticated(): bool {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return isset($_SESSION['user_id']);
    }

    public function getCurrentUser(): ?User {
        if (!$this->isAuthenticated()) {
            return null;
        }

        return User::find($_SESSION['user_id']);
    }

    public function requireAuth(): void {
        if (!$this->isAuthenticated()) {
            header('Location: /login');
            exit;
        }
    }

    public function requireRole(string $role): void {
        $this->requireAuth();
        
        $user = $this->getCurrentUser();
        if (!$user || $user->getRole() !== $role) {
            header('HTTP/1.1 403 Forbidden');
            exit;
        }
    }

    public function resetPassword(string $email): bool {
        $user = User::findByEmail($email);
        
        if (!$user) {
            return false;
        }

        // Generate reset token
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // Store reset token in database (you might want to create a password_resets table)
        $sql = "INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)";
        $this->db->execute($sql, [$email, $token, $expires]);

        // Send email with reset link (implement email sending)
        // For now, just return true
        return true;
    }

    public function validateResetToken(string $token): ?string {
        $sql = "SELECT email FROM password_resets WHERE token = ? AND expires_at > NOW() AND used = 0";
        $result = $this->db->query($sql, [$token]);
        
        if (empty($result)) {
            return null;
        }

        return $result[0]['email'];
    }

    public function updatePassword(string $email, string $newPassword): bool {
        $user = User::findByEmail($email);
        
        if (!$user) {
            return false;
        }

        $user->setPassword($newPassword);
        $result = $user->save();

        if ($result) {
            // Mark reset token as used
            $sql = "UPDATE password_resets SET used = 1 WHERE email = ?";
            $this->db->execute($sql, [$email]);
        }

        return $result;
    }
} 