<?php

namespace App\Notifications;

use App\Models\DvRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DvRequestRejected extends Notification implements ShouldQueue
{
    use Queueable;

    public $dvRequest;
    public $rejectionRemarks;

    public function __construct(DvRequest $dvRequest, string $rejectionRemarks)
    {
        $this->dvRequest = $dvRequest;
        $this->rejectionRemarks = $rejectionRemarks;
    }

    public function via($notifiable): array
    {
        return ['mail', 'database']; // You can add database notifications too
    }

    public function toMail($notifiable): MailMessage
    {
        $controlNumber = $this->dvRequest->accounting_control_number 
            ? 'CN #-' . now()->year . '-' . str_pad($this->dvRequest->accounting_control_number, 4, '0', STR_PAD_LEFT)
            : 'Pending-CN';

        return (new MailMessage)
            ->subject('DV Request Rejected - ' . $controlNumber)
            ->greeting('Hello ' . $notifiable->fname . '!')
            ->line('Your DV Request has been Rejected.')
            ->line('**Request Details:**')
            ->line('- **Control Number:** ' . $controlNumber)
            ->line('- **Program:** ' . ($this->dvRequest->program->prcode ?? 'N/A'))
            ->line('- **Amount:** ₱' . number_format($this->dvRequest->amount, 2))
            ->line('- **Rejection Date:** ' . now()->timezone('Asia/Manila')->format('M d, Y h:i A'))
            ->line('**Rejection Remarks:**')
            ->line($this->rejectionRemarks)
            ->action('View Request Details', url('/FocalPerson/dvrequest/logs/' . $this->dvRequest->id))
            ->line('Please review the remarks and resubmit your request with the necessary corrections.')
            ->salutation('Best regards, ACCUDOCS DOLE XI');
    }
    public function toArray($notifiable): array
    {
        return [
            'dv_request_id' => $this->dvRequest->id,
            'type' => 'dv_request_rejected',
            'message' => 'Your DV request has been rejected by IMSD',
            'remarks' => $this->rejectionRemarks,
        ];
    }
}