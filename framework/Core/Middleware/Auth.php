<?php

declare(strict_types=1);

namespace Core\Middleware;

use Closure;
use Core\Authenticator;
use Core\Request;
use Core\Response;

final class Auth implements MiddlewareInterface
{
    private readonly Authenticator $auth;

    public function __construct()
    {
        $this->auth = new Authenticator();
    }

    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->auth->guest()) {
            $this->auth->rememberIntended($request);

            return Response::redirect('/login');
        }

        return $next($request);
    }
}
