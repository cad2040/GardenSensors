<?php

namespace App\Controllers;

class BaseController {
    protected function render($view, $data = []) {
        extract($data);
        require_once __DIR__ . "/../Views/{$view}.php";
    }

    protected function json($data, $statusCode = 200) {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }

    protected function redirect($url) {
        header("Location: {$url}");
        exit;
    }
} 