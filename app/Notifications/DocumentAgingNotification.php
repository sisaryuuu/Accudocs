<?php

namespace App\Notifications;

use App\Models\DvRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentAgingNotification extends Notification
{
    use Queueable;

    protected DvRequest $dvRequest;
    protected int $daysOld;
    protected string $urgencyLevel;

    /**
     * Create a new notification instance.
     */
     public function __construct(DvRequest $dvRequest, int $daysOld)
    {
        $this->dvRequest = $dvRequest;
        $this->daysOld = $daysOld;
        $this->urgencyLevel = $this->getUrgencyLevel($daysOld);
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
         return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $controlNumber = $this->dvRequest->accounting_control_number 
            ? 'CN #-' . now()->year . '-' . str_pad($this->dvRequest->accounting_control_number, 5, '0', STR_PAD_LEFT)
            : 'N/A';

        $submittedBy = trim(
            ($this->dvRequest->focalPerson->fname ?? '') . ' ' . 
            ($this->dvRequest->focalPerson->lname ?? '')
        );

        return [
            'dv_request_id' => $this->dvRequest->id,
            'control_number' => $controlNumber,
            'submitted_by' => $submittedBy,
            'program' => $this->dvRequest->program->prcode ?? 'N/A',
            'status' => $this->dvRequest->status,
            'days_old' => $this->daysOld,
            'urgency_level' => $this->urgencyLevel,
            'message' => "DV Request {$controlNumber} from {$submittedBy} is {$this->daysOld} days old and requires processing.",
            'submitted_date' => $this->dvRequest->created_at->format('M d, Y h:i A'),
        ];
    }
    protected function getUrgencyLevel(int $daysOld): string
    {
        if ($daysOld >= 10) return 'critical';
        if ($daysOld >= 7) return 'high';
        if ($daysOld >= 3) return 'medium';
        return 'low';
    }
}
