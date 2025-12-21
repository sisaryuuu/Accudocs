<?php

namespace App\Notifications;

use App\Models\DvRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;

class DocumentAgingNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $dvRequest;
    protected $daysOld;
    protected $urgencyLevel;

    public function __construct(DvRequest $dvRequest, int $daysOld, string $urgencyLevel)
    {
        $this->dvRequest = $dvRequest;
        $this->daysOld = $daysOld;
        $this->urgencyLevel = $urgencyLevel;
    }

    public function via($notifiable): array
    {
        return ['database', 'broadcast', 'mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $urgencyText = $this->urgencyLevel === 'critical' ? 'CRITICAL' : 'HIGH PRIORITY';

        return (new MailMessage)
            ->subject("⚠️ {$urgencyText}: DV Request Delayed - " . $this->dvRequest->reference_number)
            ->line("Your DV request has been pending for {$this->daysOld} days without processing.")
            ->line("**Reference Number:** {$this->dvRequest->reference_number}")
            ->line("**Control Number:** " . $this->formatControlNumber())
            ->line("**Current Status:** " . $this->formatStatus())
            ->line("**Days Pending:** {$this->daysOld} days")
            ->action('View Request Details', route('dv.verify', $this->dvRequest->reference_number))
            ->line('Please follow up with the accounting office if this delay is unexpected.');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'document_aging',
            'urgency' => $this->urgencyLevel,
            'dv_request_id' => $this->dvRequest->id,
            'reference_number' => $this->dvRequest->reference_number,
            'control_number' => $this->formatControlNumber(),
            'status' => $this->dvRequest->status,
            'days_old' => $this->daysOld,
            'submitted_date' => $this->dvRequest->created_at->format('M d, Y'),
            'message' => "Your DV request has been pending for {$this->daysOld} days",
            'action_url' => route('dv.verify', $this->dvRequest->reference_number),
        ];
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'type' => 'document_aging',
            'urgency' => $this->urgencyLevel,
            'dv_request_id' => $this->dvRequest->id,
            'reference_number' => $this->dvRequest->reference_number,
            'days_old' => $this->daysOld,
            'message' => "Your DV request has been pending for {$this->daysOld} days",
            'action_url' => route('dv.verify', $this->dvRequest->reference_number),
            'timestamp' => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
        ]);
    }

    protected function formatControlNumber(): string
    {
        if ($this->dvRequest->accounting_control_number) {
            return 'CN #-' . now()->year . '-' . str_pad($this->dvRequest->accounting_control_number, 4, '0', STR_PAD_LEFT);
        }
        return 'Not yet assigned';
    }

    protected function formatStatus(): string
    {
        return str_replace('_', ' ', ucwords($this->dvRequest->status, '_'));
    }
}
