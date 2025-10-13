<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DvRequestSubmittedToImsd implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $dvRequest;
    public $submittedBy;

    public function __construct($dvRequest)
    {
        $this->dvRequest = $dvRequest;
        $this->submittedBy = trim(
            ($dvRequest->focalPerson->fname ?? '') . ' ' . 
            ($dvRequest->focalPerson->lname ?? '')
        );
    }

    public function broadcastOn()
    {
        // General IMSD channel - all IMSD users listen here
        return new Channel('dv-requests-imsd');
    }

    public function broadcastAs()
    {
        return 'dv-request-submitted';
    }

    public function broadcastWith()
    {
        return [
            'id' => $this->dvRequest->id,
            'control_number' => $this->dvRequest->accounting_control_number,
            'submitted_by' => $this->submittedBy,
            'program' => $this->dvRequest->program->prcode ?? 'N/A',
            'amount' => number_format($this->dvRequest->amount, 2),
            'status' => 'submitted',
        ];
    }
}