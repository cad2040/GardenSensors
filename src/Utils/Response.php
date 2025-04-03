<?php
namespace App\Utils;

class Response {
    private $content;
    private $statusCode;
    private $headers;
    private $version;

    public function __construct($content = '', int $status = 200, array $headers = []) {
        $this->content = $content;
        $this->statusCode = $status;
        $this->headers = array_merge([
            'Content-Type' => 'text/html; charset=UTF-8'
        ], $headers);
        $this->version = '1.1';
    }

    public function setContent($content): self {
        $this->content = $content;
        return $this;
    }

    public function getContent() {
        return $this->content;
    }

    public function setStatusCode(int $code): self {
        $this->statusCode = $code;
        return $this;
    }

    public function getStatusCode(): int {
        return $this->statusCode;
    }

    public function setHeader(string $name, string $value): self {
        $this->headers[$name] = $value;
        return $this;
    }

    public function getHeaders(): array {
        return $this->headers;
    }

    public function removeHeader(string $name): self {
        unset($this->headers[$name]);
        return $this;
    }

    public function setProtocolVersion(string $version): self {
        $this->version = $version;
        return $this;
    }

    public function getProtocolVersion(): string {
        return $this->version;
    }

    public function redirect(string $url, int $status = 302): void {
        $this->setHeader('Location', $url);
        $this->setStatusCode($status);
        $this->send();
    }

    public function json($data, int $status = 200): void {
        $this->setHeader('Content-Type', 'application/json');
        $this->setStatusCode($status);
        $this->setContent(json_encode($data));
        $this->send();
    }

    public function send(): void {
        if (headers_sent()) {
            return;
        }

        // Send status code
        http_response_code($this->statusCode);

        // Send headers
        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }

        // Send content
        echo $this->content;
        exit;
    }

    public static function text(string $content, int $status = 200): void {
        $response = new self($content, $status, ['Content-Type' => 'text/plain']);
        $response->send();
    }

    public static function html(string $content, int $status = 200): void {
        $response = new self($content, $status, ['Content-Type' => 'text/html; charset=UTF-8']);
        $response->send();
    }

    public static function json_response($data, int $status = 200): void {
        $response = new self();
        $response->json($data, $status);
    }

    public static function redirect_response(string $url, int $status = 302): void {
        $response = new self();
        $response->redirect($url, $status);
    }

    public static function notFound(string $message = 'Not Found'): void {
        $response = new self($message, 404);
        $response->send();
    }

    public static function forbidden(string $message = 'Forbidden'): void {
        $response = new self($message, 403);
        $response->send();
    }

    public static function unauthorized(string $message = 'Unauthorized'): void {
        $response = new self($message, 401);
        $response->send();
    }

    public static function badRequest(string $message = 'Bad Request'): void {
        $response = new self($message, 400);
        $response->send();
    }

    public static function serverError(string $message = 'Internal Server Error'): void {
        $response = new self($message, 500);
        $response->send();
    }

    public static function file(string $path, string $filename = null): void {
        if (!file_exists($path)) {
            self::notFound();
            return;
        }

        $filename = $filename ?? basename($path);
        $mime = mime_content_type($path);
        $size = filesize($path);

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . $size);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        readfile($path);
        exit;
    }

    public static function download(string $content, string $filename): void {
        header('Content-Type: application/octet-stream');
        header('Content-Length: ' . strlen($content));
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        echo $content;
        exit;
    }

    public static function cache(int $seconds): void {
        header('Cache-Control: public, max-age=' . $seconds);
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $seconds) . ' GMT');
    }

    public static function noCache(): void {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');
    }
} 