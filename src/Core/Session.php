<?php
namespace App\Utils;

class Session {
    private static $instance = null;
    private $started = false;

    private function __construct() {}

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function start(): void {
        if (!$this->started) {
            if (session_status() === PHP_SESSION_NONE) {
                // Set secure session parameters
                ini_set('session.cookie_httponly', 1);
                ini_set('session.use_only_cookies', 1);
                ini_set('session.cookie_secure', 1);
                
                session_start();
                $this->regenerate(); // Regenerate session ID for security
            }
            $this->started = true;
        }
    }

    public function set(string $key, $value): void {
        $this->start();
        $_SESSION[$key] = $value;
    }

    public function get(string $key, $default = null) {
        $this->start();
        return $_SESSION[$key] ?? $default;
    }

    public function has(string $key): bool {
        $this->start();
        return isset($_SESSION[$key]);
    }

    public function remove(string $key): void {
        $this->start();
        unset($_SESSION[$key]);
    }

    public function clear(): void {
        $this->start();
        session_unset();
    }

    public function destroy(): void {
        $this->start();
        session_destroy();
        $this->started = false;

        // Clear session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
    }

    public function regenerate(bool $deleteOldSession = true): bool {
        $this->start();
        return session_regenerate_id($deleteOldSession);
    }

    public function flash(string $key, $value = null) {
        $this->start();
        
        if ($value !== null) {
            // Set flash data
            $this->set('_flash_' . $key, $value);
            return $value;
        } else {
            // Get and remove flash data
            $value = $this->get('_flash_' . $key);
            $this->remove('_flash_' . $key);
            return $value;
        }
    }

    public function getFlashMessages(): array {
        $this->start();
        $messages = [];
        
        foreach ($_SESSION as $key => $value) {
            if (strpos($key, '_flash_') === 0) {
                $messages[substr($key, 7)] = $value;
                unset($_SESSION[$key]);
            }
        }
        
        return $messages;
    }

    public function isAuthenticated(): bool {
        return $this->has('user_id');
    }

    public function getUserId() {
        return $this->get('user_id');
    }

    public function setUser(array $user): void {
        $this->set('user_id', $user['user_id']);
        $this->set('user_name', $user['username'] ?? '');
        $this->set('user_email', $user['email'] ?? '');
        $this->set('user_role', $user['role'] ?? 'user');
    }

    public function getUser(): ?array {
        if (!$this->isAuthenticated()) {
            return null;
        }

        return [
            'user_id' => $this->get('user_id'),
            'username' => $this->get('user_name'),
            'email' => $this->get('user_email'),
            'role' => $this->get('user_role')
        ];
    }

    public function logout(): void {
        $this->destroy();
    }

    public function getCsrfToken(): string {
        if (!$this->has('csrf_token')) {
            $this->set('csrf_token', bin2hex(random_bytes(32)));
        }
        return $this->get('csrf_token');
    }

    public function validateCsrfToken(string $token): bool {
        return hash_equals($this->getCsrfToken(), $token);
    }
} 