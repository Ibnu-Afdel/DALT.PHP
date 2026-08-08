<?php

declare(strict_types=1);

namespace Core\Middleware;

use Closure;
use Core\Request;
use Core\Response;

final class Guest implements MiddlewareInterface
{
    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        if ($_SESSION['user'] ?? false) {
            return Response::redirect('/');
        }

        return $next($request);
    }
}
