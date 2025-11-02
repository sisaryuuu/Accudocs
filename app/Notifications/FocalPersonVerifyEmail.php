<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\URL;

class FocalPersonVerifyEmail extends VerifyEmail implements ShouldQueue
{
    use Queueable;

    public function toMail($notifiable): MailMessage
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('Verify Your Email Address - ACCUDOCS DOLE XI')
            ->greeting('Hello ' . ($notifiable->fname ?? 'Focal Person') . '!')
            ->line('Thank you for registering in **ACCUDOCS DOLE XI**.')
            ->line('Please click the button below to verify your email address.')
            ->action('Verify Email Address', $verificationUrl)
            ->line('⚠️ This verification link will expire in **' . config('auth.verification.expire', 60) . ' minutes**.')
            ->line('If you did not create an account, no further action is required.')
            ->salutation('Best regards,  The ACCUDOCS DOLE XI Team');
    }

    protected function verificationUrl($notifiable): string
    {
        // ✅ Force HTTPS before generating the signed URL
        URL::forceScheme('https');
        
        $url = URL::temporarySignedRoute(
            'focal.verification.verify',
            now()->addMinutes(config('auth.verification.expire', 60)),
            [
                'id'   => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );
        
        // ✅ Double-check: Replace http:// with https:// if somehow it's still http
        return str_replace('http://', 'https://', $url);
    }
}