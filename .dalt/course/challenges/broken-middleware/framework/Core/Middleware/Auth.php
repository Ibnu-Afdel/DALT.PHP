<?php
namespace Core\Middleware;

class Auth
{
    public function handle()
    {
        // BUG: Checking wrong session key - should be 'user' not 'authenticated'
        if(!($_SESSION['authenticated'] ?? false)){
            header('location: /' );
            exit();
        }
    }
}
