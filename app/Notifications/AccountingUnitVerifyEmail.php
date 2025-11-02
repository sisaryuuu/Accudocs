<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\URL;

class AccountingUnitVerifyEmail extends VerifyEmail implements ShouldQueue
{
    use Queueable;

    public function toMail($notifiable): MailMessage
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('Verify Your Email Address - ACCUDOCS DOLE XI')
            ->greeting('Hello ' . $notifiable->full_name . '!')
            ->line('Your account has been created in **ACCUDOCS DOLE XI**.')
            ->line('Please click the button below to verify your email address.')
            ->action('Verify Email Address', $verificationUrl)
            ->line('⚠️ This verification link will expire in **' . config('auth.verification.expire', 60) . ' minutes**.')
            ->line('If you did not request this account, please contact the administrator.')
            ->salutation('Best regards, The ACCUDOCS DOLE XI Team');
    }

    protected function verificationUrl($notifiable): string
    {
        URL::forceScheme('https');
        
        $url = URL::temporarySignedRoute(
            'accountingunit.verification.verify',
            now()->addMinutes(config('auth.verification.expire', 60)),
            [
                'id'   => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );
        
        return str_replace('http://', 'https://', $url);
    }
}