<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Email reset password (Bahasa Indonesia) berisi tautan ke halaman Reset Password di frontend.
 */
class ResetPasswordNotification extends Notification
{
    public function __construct(public readonly string $token) {}

    /** @return list<string> */
    public function via(User $notifiable): array
    {
        return ['mail'];
    }

    public function url(User $notifiable): string
    {
        return config('app.frontend_url').'/reset-password?'.http_build_query([
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);
    }

    public function toMail(User $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('[Intellisys Talent Hub] Reset password')
            ->view(['text' => 'mail.reset-password'], [
                'nama' => $notifiable->namaTampilan(),
                'email' => $notifiable->email,
                'url' => $this->url($notifiable),
                'menit' => config('auth.passwords.users.expire'),
            ]);
    }
}
