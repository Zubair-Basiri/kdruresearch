<?php

namespace App\Services;

use Illuminate\Support\Facades\Config;

class MailConfigurator
{
    /**
     * Apply Gmail SMTP configuration at runtime.
     */
    public static function apply(): void
    {
        Config::set('mail.default', 'smtp');

        Config::set('mail.mailers.smtp', [
            'transport'  => 'smtp',
            'host'       => 'smtp.gmail.com',
            'port'       => 587,
            'encryption' => 'tls',
            'username'   => 'vcresearchkdru@gmail.com',
            'password'   => 'rbwbprplzbgwanzz',
            'timeout'    => 30,
            'auth_mode'  => null,
        ]);

        Config::set('mail.from', [
            'address' => 'vcresearchkdru@gmail.com',
            'name'    => 'Research Database',
        ]);
    }
}