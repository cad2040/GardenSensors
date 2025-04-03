<?php
namespace App\Controllers;

use App\Utils\Request;
use App\Utils\Response;
use App\Utils\Session;
use App\Utils\Validator;
use Exception;
use PDO;

abstract class Controller {
    protected $request;
    protected $session;
    protected PDO $db;
    protected array $config;

    public function __construct() {
        $this->request = new Request();
        $this->session = Session::getInstance();
        global $config;
        $this->config = $config;

        try {
            $this->db = new PDO(
                "mysql:host={$config['database']['host']};dbname={$config['database']['database']};charset={$config['database']['charset']}",
                $config['database']['username'],
                $config['database']['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (Exception $e) {
            throw new Exception('Database connection failed: ' . $e->getMessage());
        }
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
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
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
        if (!isset($_SESSION['user_id'])) {
            $this->error('Unauthorized', 401);
            exit;
        }
    }

    protected function requireAdmin(): void {
        $this->requireAuth();
        if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
            $this->error('Forbidden', 403);
            exit;
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

    protected function error(string $message, int $status = 400): void {
        $this->json(['error' => true, 'message' => $message], $status);
    }

    protected function success($data = null, string $message = ''): void {
        $response = ['success' => true];
        if ($data !== null) {
            $response['data'] = $data;
        }
        if ($message) {
            $response['message'] = $message;
        }
        $this->json($response);
    }

    protected function validateRequired(array $data, array $fields): bool {
        foreach ($fields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $this->error("Field '$field' is required");
                return false;
            }
        }
        return true;
    }

    protected function validateNumeric(array $data, array $fields): bool {
        foreach ($fields as $field) {
            if (isset($data[$field]) && !is_numeric($data[$field])) {
                $this->error("Field '$field' must be numeric");
                return false;
            }
        }
        return true;
    }

    protected function validateEmail(string $email): bool {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Invalid email address');
            return false;
        }
        return true;
    }

    protected function validateDate(string $date, string $format = 'Y-m-d'): bool {
        $d = \DateTime::createFromFormat($format, $date);
        if (!$d || $d->format($format) !== $date) {
            $this->error('Invalid date format');
            return false;
        }
        return true;
    }

    protected function getRequestData(): array {
        $data = [];
        
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $data = $_GET;
        } else {
            $input = file_get_contents('php://input');
            if (!empty($input)) {
                $data = json_decode($input, true) ?? [];
            }
            $data = array_merge($data, $_POST);
        }

        return array_map(function($value) {
            return is_string($value) ? trim($value) : $value;
        }, $data);
    }

    protected function validateApiKey(): bool {
        $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? null;
        if (!$apiKey || $apiKey !== $this->config['api']['key']) {
            $this->error('Invalid API key', 401);
            return false;
        }
        return true;
    }
} 