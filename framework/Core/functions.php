<?php

function dd(mixed ...$values): never
{
    echo '<style>body{background:#1e1e2e;color:#cdd6f4;font:14px/1.6 monospace;padding:2rem}</style>';
    foreach ($values as $value) {
        echo '<pre style="background:#313244;padding:1rem;border-radius:6px;overflow:auto;margin-bottom:1rem">';
        var_export($value);
        echo '</pre>';
    }
    exit(1);
}

function urlIs($value) {
return $_SERVER['REQUEST_URI'] === $value;
}

function abort(int $code = 404): never
{
    http_response_code($code);
    
    $messages = [
        403 => 'Forbidden',
        404 => 'Not Found',
        500 => 'Internal Server Error',
    ];
    
    $message = $messages[$code] ?? 'Error';
    
    throw new \Core\HttpException($code, $message);
}

function authorize($condition, $status = 403){
    if (!$condition) {
        abort($status);
    }
}

function  base_path($path)
{
    return BASE_PATH . $path;
}

function view($path, $attributes = [])
{
    extract($attributes);

    $appView  = base_path('resources/views/' . $path);
    $daltView = base_path('.dalt/resources/views/' . $path);

    if (file_exists($appView)) {
        return require $appView;
    }

    if (is_dir(base_path('.dalt')) && file_exists($daltView)) {
        return require $daltView;
    }

    throw new \RuntimeException("View not found: {$path}");
}

function redirect($path)
{
    header("Location: {$path}");
    exit();
}

function old($key, $default = '')
{
    $old = Core\Session::get('old', []);
    return $old[$key] ?? $default;
}

function vite(string $entryPath): string
{
    $devServerUrl = $_ENV['VITE_DEV_SERVER_URL'] ?? 'http://localhost:5173';

    if (vite_is_dev_server_running($devServerUrl)) {
        $client = '<script type="module" src="' . $devServerUrl . '/@vite/client"></script>';
        $entry = '<script type="module" src="' . $devServerUrl . '/' . ltrim($entryPath, '/') . '"></script>';
        return $client . "\n" . $entry;
    }

    $manifestPathPrimary = base_path('public/build/.vite/manifest.json');
    $manifestPathFallback = base_path('public/build/manifest.json');
    $manifestPath = file_exists($manifestPathPrimary) ? $manifestPathPrimary : $manifestPathFallback;

    if (!file_exists($manifestPath)) {
        $fallback = [];
        $cssCandidates = [
            'public/app.css',
            'public/js/app.css',
            'public/css/style.css',
        ];
        $jsCandidates = [
            'public/app.js',
            'public/js/app.js',
        ];
        foreach ($cssCandidates as $cssPath) {
            if (file_exists(base_path($cssPath))) {
                $href = '/' . ltrim(str_replace('public/', '', $cssPath), '/');
                $fallback[] = '<link rel="stylesheet" href="' . htmlspecialchars($href) . '">';
                break;
            }
        }
        foreach ($jsCandidates as $jsPath) {
            if (file_exists(base_path($jsPath))) {
                $src = '/' . ltrim(str_replace('public/', '', $jsPath), '/');
                $fallback[] = '<script defer src="' . htmlspecialchars($src) . '"></script>';
                break;
            }
        }
        if ($fallback) {
            return implode("\n", $fallback);
        }
        return "<!-- Vite manifest not found. Run 'npm run build'. -->";
    }

    $manifest = json_decode(file_get_contents($manifestPath), true);
    if (!isset($manifest[$entryPath])) {
        return "<!-- Vite entry '$entryPath' not present in manifest. -->";
    }

    $tags = [];

    if (!empty($manifest[$entryPath]['css'])) {
        foreach ($manifest[$entryPath]['css'] as $cssFile) {
            $tags[] = '<link rel="stylesheet" href="/build/' . $cssFile . '">';
        }
    }

    if (!empty($manifest[$entryPath]['file'])) {
        $tags[] = '<script type="module" src="/build/' . $manifest[$entryPath]['file'] . '"></script>';
    }

    return implode("\n", $tags);
}

function vite_is_dev_server_running(string $url): bool
{
    $host = parse_url($url, PHP_URL_HOST) ?: 'localhost';
    $port = (int) (parse_url($url, PHP_URL_PORT) ?: 5173);

    $connection = @fsockopen($host, $port, $errno, $errstr, 0.2);
    if (is_resource($connection)) {
        fclose($connection);
        return true;
    }

    return false;
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

function app_log(string $message): void
{
    $dir = base_path('storage/logs');
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    $file = $dir . '/app.log';
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $message . "\n";
    @file_put_contents($file, $line, FILE_APPEND);
}
