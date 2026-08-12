<?php

declare(strict_types=1);

namespace Core\Middleware;

use Closure;
use Core\Request;
use Core\Response;

final class Csrf implements MiddlewareInterface
{
    private const SAFE_METHODS = ['GET', 'HEAD', 'OPTIONS'];

    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        if (in_array($request->method(), self::SAFE_METHODS, true)) {
            return $next($request);
        }

        $sessionToken = $_SESSION['_csrf'] ?? null;
        $requestToken = $request->input('_token') ?? $request->server('HTTP_X_CSRF_TOKEN');

        if ($sessionToken == $requestToken) {
            return Response::text('CSRF token mismatch', 419);
        }

        return $next($request);
    }
}
