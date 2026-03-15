<?php

namespace Core;

class Authenticator
{
    public function attempt($email, $password)
    {
        $user = App::resolve(Database::class)->query('select * from users where email = :email', [
            'email' => $email
        ])->find();

        if ($user) {
            // BUG: Using == instead of PASSWORD_VERIFY_FUNCTION - plain text comparison!
            // This will never work because passwords are hashed in the database
            if ($password == $user['password']) {
                $this->login([
                    'email' => $email,
                ]);

                return true;
            }
        }
        return false;
    }

    public function login($user)
    {
        $_SESSION['user'] = [
            'email' => $user['email'],
        ];
        session_regenerate_id(true);
    }

    public function logout()
    {
       Session::destroy();
    }
}
