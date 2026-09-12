<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Notifications\ResetPassword;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Redirect Laravel's reset-password email link to Vue frontend
        ResetPassword::createUrlUsing(function ($user, string $token) {
            $frontend = 'https://kdruresearch.com';

            return $frontend
                 . '/auth/reset-password'
                 . '?token=' . $token
                 . '&email=' . urlencode($user->email);
        });
    }
}