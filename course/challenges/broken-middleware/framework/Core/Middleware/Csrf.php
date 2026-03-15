<?php

namespace Core\Middleware;

class Csrf
{
    public function handle(): void
    {
        $method = $_POST['_method'] ?? $_SERVER['REQUEST_METHOD'];
        $method = strtoupper($method);
        
        // Only validate for non-GET
        if ($method === 'GET') {
            return;
        }

        $sessionToken = $_SESSION['_csrf'] ?? null;
        $formToken = $_POST['_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;

        // BUG: Using == instead of hash_equals() - timing attack vulnerability
        // BUG: Logic inverted - rejects when tokens MATCH instead of when they DON'T match
        if ($sessionToken == $formToken) {
            http_response_code(419);
            echo 'CSRF token mismatch';
            exit;
        }
    }
}
