<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class CustomResetPassword extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        if (static::$toMailCallback) {
            return call_user_func(static::$toMailCallback, $notifiable, $this->token);
        }

        $url    = $this->resetUrl($notifiable);
        $name   = $notifiable->name ?? 'User';
        $expire = config('auth.passwords.' . config('auth.defaults.passwords') . '.expire', 60);

        return (new MailMessage)
            ->greeting("Hello {$name}!")
            ->subject('Reset Password Notification')
            ->line('You are receiving this email because we received a password reset request for your account.')
            ->action('Reset Password', $url)
            ->line("This password reset link will expire in {$expire} minutes.")
            ->line('If you did not request a password reset, no further action is required.')
            ->salutation('Regards,\nVice Chancellery of Research');
    }
}