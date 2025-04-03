<?php
namespace App\Controllers;

use App\Utils\Request;
use App\Utils\Response;
use App\Utils\Session;
use App\Utils\Validator;

abstract class Controller {
    protected $request;
    protected $session;

    public function __construct() {
        $this->request = new Request();
        $this->session = Session::getInstance();
    }

    protected function view(string $template, array $data = []): void {
        $templatePath = dirname(__DIR__, 2) . '/templates/' . $template . '.php';
        
        if (!file_exists($templatePath)) {
            Response::notFound('Template not found');
            return;
        }

        // Extract data to make variables available in template
        extract($data);

        // Start output buffering
        ob_start();

        // Include the template
        require $templatePath;

        // Get the contents and clean the buffer
        $content = ob_get_clean();

        // Send the response
        Response::html($content);
    }

    protected function json($data, int $status = 200): void {
        Response::json_response($data, $status);
    }

    protected function redirect(string $url, int $status = 302): void {
        Response::redirect_response($url, $status);
    }

    protected function validate(array $rules): array {
        return $this->request->validate($rules);
    }

    protected function isAuthenticated(): bool {
        return $this->session->isAuthenticated();
    }

    protected function requireAuth(): void {
        if (!$this->isAuthenticated()) {
            $this->session->flash('error', 'Please login to access this page');
            $this->redirect('/login');
        }
    }

    protected function requireAdmin(): void {
        $this->requireAuth();
        
        $user = $this->session->getUser();
        if (!$user || $user['role'] !== 'admin') {
            $this->session->flash('error', 'Access denied');
            $this->redirect('/dashboard');
        }
    }

    protected function getUser(): ?array {
        return $this->session->getUser();
    }

    protected function getUserId(): ?int {
        return $this->session->getUserId();
    }

    protected function setUser(array $user): void {
        $this->session->setUser($user);
    }

    protected function logout(): void {
        $this->session->logout();
    }

    protected function flash(string $key, $value = null) {
        return $this->session->flash($key, $value);
    }

    protected function getFlashMessages(): array {
        return $this->session->getFlashMessages();
    }

    protected function getCsrfToken(): string {
        return $this->session->getCsrfToken();
    }

    protected function validateCsrfToken(string $token): bool {
        return $this->session->validateCsrfToken($token);
    }

    protected function requireCsrfToken(): void {
        if (!$this->request->isGet()) {
            $token = $this->request->post('csrf_token');
            if (!$token || !$this->validateCsrfToken($token)) {
                $this->session->flash('error', 'Invalid CSRF token');
                $this->redirect($_SERVER['HTTP_REFERER'] ?? '/');
            }
        }
    }

    protected function respondWithError(string $message, int $status = 400): void {
        if ($this->request->isAjax()) {
            $this->json(['error' => $message], $status);
        } else {
            $this->session->flash('error', $message);
            $this->redirect($_SERVER['HTTP_REFERER'] ?? '/');
        }
    }

    protected function respondWithSuccess(string $message, string $redirect = null): void {
        if ($this->request->isAjax()) {
            $this->json(['message' => $message]);
        } else {
            $this->session->flash('success', $message);
            $this->redirect($redirect ?? $_SERVER['HTTP_REFERER'] ?? '/');
        }
    }

    protected function getValidationRules(): array {
        return [];
    }

    protected function beforeAction(): void {
        // This method can be overridden by child controllers
        // to perform actions before the main action is executed
    }

    protected function afterAction(): void {
        // This method can be overridden by child controllers
        // to perform actions after the main action is executed
    }

    public function __call(string $name, array $arguments) {
        Response::notFound('Action not found');
    }
} 