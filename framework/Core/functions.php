<?php

declare(strict_types=1);

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

function urlIs(string $value): bool
{
    $requestUri = $_SERVER['REQUEST_URI'] ?? '/';

    if (!is_string($requestUri)) {
        return false;
    }

    $path = parse_url($requestUri, PHP_URL_PATH);

    return is_string($path) && $path === $value;
}

function abort(int $code = 404, string $message = ''): never
{
    $defaultMessages = [
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        419 => 'Page Expired',
        422 => 'Unprocessable Entity',
        429 => 'Too Many Requests',
        500 => 'Internal Server Error',
        503 => 'Service Unavailable',
    ];

    throw new \Core\HttpException(
        $code,
        $message !== '' ? $message : ($defaultMessages[$code] ?? "HTTP {$code}"),
    );
}

function authorize(bool $condition, int $status = 403, string $message = ''): void
{
    if (!$condition) {
        abort($status, $message);
    }
}

function base_path(string $path = ''): string
{
    return rtrim(BASE_PATH, '/\\') . ($path === '' ? '' : DIRECTORY_SEPARATOR . ltrim($path, '/\\'));
}

function env(string $key, mixed $default = null): mixed
{
    if (array_key_exists($key, $_ENV)) {
        $value = $_ENV[$key];
    } elseif (array_key_exists($key, $_SERVER)) {
        $value = $_SERVER[$key];
    } else {
        $systemValue = getenv($key);
        $value = $systemValue === false ? $default : $systemValue;
    }

    if (!is_string($value)) {
        return $value;
    }

    return match (strtolower($value)) {
        'true', '(true)' => true,
        'false', '(false)' => false,
        'empty', '(empty)' => '',
        'null', '(null)' => null,
        default => $value,
    };
}

function config(?string $key = null, mixed $default = null): mixed
{
    return Core\App::resolve(Core\Config::class)->get($key, $default);
}

/** @param array<string, mixed> $attributes */
function view(string $path, array $attributes = []): string
{
    $container = Core\App::containerOrNull();
    $renderer = $container !== null
        ? $container->resolve(Core\View::class)
        : new Core\View();
    $content = $renderer->render($path, $attributes);
    echo $content;

    return $content;
}

function redirect(string $path, int $status = 302): Core\Response
{
    return Core\Response::redirect($path, $status);
}

function old(string $key, mixed $default = ''): mixed
{
    $old = Core\Session::get('old', []);

    return is_array($old) && array_key_exists($key, $old) ? $old[$key] : $default;
}

function vite(string $entryPath): string
{
    $manifestPathPrimary = base_path('public/build/.vite/manifest.json');
    $manifestPathFallback = base_path('public/build/manifest.json');
    $manifestPath = file_exists($manifestPathPrimary) ? $manifestPathPrimary : (file_exists($manifestPathFallback) ? $manifestPathFallback : null);

    // If pre-compiled manifest exists, ALWAYS use it.
    // This avoids port conflicts and 200ms timeouts when the dev server is offline.
    if ($manifestPath) {
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

    // Fallback to dev server only if there is no compiled build
    $devServerUrl = $_ENV['VITE_DEV_SERVER_URL'] ?? 'http://localhost:5173';

    if (vite_is_dev_server_running($devServerUrl)) {
        $client = '<script type="module" src="' . $devServerUrl . '/@vite/client"></script>';
        $entry = '<script type="module" src="' . $devServerUrl . '/' . ltrim($entryPath, '/') . '"></script>';
        return $client . "\n" . $entry;
    }

    // Ultimate fallback for missing assets
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
    return "<!-- Vite manifest not found and dev server is offline. -->";
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
    $file = $_ENV['APP_LOG_PATH'] ?? base_path('storage/logs/app.log');
    $dir = dirname($file);
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $message . "\n";
    @file_put_contents($file, $line, FILE_APPEND);
}
