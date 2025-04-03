<?php
namespace App\Utils;

class Request {
    private $get;
    private $post;
    private $server;
    private $files;
    private $cookies;
    private $headers;

    public function __construct() {
        $this->get = $_GET;
        $this->post = $_POST;
        $this->server = $_SERVER;
        $this->files = $_FILES;
        $this->cookies = $_COOKIE;
        $this->headers = $this->getRequestHeaders();
    }

    private function getRequestHeaders(): array {
        $headers = [];
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
        } else {
            foreach ($_SERVER as $name => $value) {
                if (substr($name, 0, 5) == 'HTTP_') {
                    $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
                }
            }
        }
        return $headers;
    }

    public function isGet(): bool {
        return $this->getMethod() === 'GET';
    }

    public function isPost(): bool {
        return $this->getMethod() === 'POST';
    }

    public function isPut(): bool {
        return $this->getMethod() === 'PUT';
    }

    public function isDelete(): bool {
        return $this->getMethod() === 'DELETE';
    }

    public function isAjax(): bool {
        return isset($this->headers['X-Requested-With']) && 
               strtolower($this->headers['X-Requested-With']) === 'xmlhttprequest';
    }

    public function getMethod(): string {
        return strtoupper($this->server['REQUEST_METHOD']);
    }

    public function get(string $key = null, $default = null) {
        if ($key === null) {
            return $this->get;
        }
        return $this->get[$key] ?? $default;
    }

    public function post(string $key = null, $default = null) {
        if ($key === null) {
            return $this->post;
        }
        return $this->post[$key] ?? $default;
    }

    public function input(string $key = null, $default = null) {
        $input = array_merge($this->get, $this->post);
        if ($key === null) {
            return $input;
        }
        return $input[$key] ?? $default;
    }

    public function file(string $key = null) {
        if ($key === null) {
            return $this->files;
        }
        return $this->files[$key] ?? null;
    }

    public function hasFile(string $key): bool {
        return isset($this->files[$key]) && !empty($this->files[$key]['tmp_name']);
    }

    public function cookie(string $key = null, $default = null) {
        if ($key === null) {
            return $this->cookies;
        }
        return $this->cookies[$key] ?? $default;
    }

    public function header(string $key = null, $default = null) {
        if ($key === null) {
            return $this->headers;
        }
        return $this->headers[$key] ?? $default;
    }

    public function getUri(): string {
        return $this->server['REQUEST_URI'];
    }

    public function getQueryString(): ?string {
        return $this->server['QUERY_STRING'] ?? null;
    }

    public function getHost(): string {
        return $this->server['HTTP_HOST'];
    }

    public function getProtocol(): string {
        return $this->server['SERVER_PROTOCOL'];
    }

    public function isSecure(): bool {
        return (!empty($this->server['HTTPS']) && $this->server['HTTPS'] !== 'off') || 
               $this->server['SERVER_PORT'] == 443;
    }

    public function getClientIp(): ?string {
        $keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
        
        foreach ($keys as $key) {
            if (array_key_exists($key, $this->server)) {
                foreach (explode(',', $this->server[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return null;
    }

    public function getUserAgent(): ?string {
        return $this->server['HTTP_USER_AGENT'] ?? null;
    }

    public function getReferer(): ?string {
        return $this->server['HTTP_REFERER'] ?? null;
    }

    public function getContentType(): ?string {
        return $this->server['CONTENT_TYPE'] ?? null;
    }

    public function getRawBody(): string {
        return file_get_contents('php://input');
    }

    public function getJsonBody(): ?array {
        $contentType = $this->getContentType();
        if (strpos($contentType, 'application/json') !== false) {
            $json = json_decode($this->getRawBody(), true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $json;
            }
        }
        return null;
    }

    public function validate(array $rules): array {
        $validator = new Validator($this->input(), $rules);
        return $validator->validate() ? [] : $validator->getErrors();
    }
} 