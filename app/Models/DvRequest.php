<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class DvRequest extends Model
{
    use HasFactory;

    protected $table = 'dv_requests';

    protected $fillable = [
        'dv_number',
        'qr_code_path',
        'qr_code_token',
        'qr_code_generated_at',
         'qr_status',
         'qr_code_data',
        'reference_number',
        'accounting_control_number',
        'user_sequence',     
        'accountingunit_id',
        'received_by_id',
        'focalperson_id',
        'program_id',
        'respocenter_id',
        'particulars',
        'amount',
        'status',
        'tupad_modality',
        'tev_modality',
        'confirmed_by_focal_person',
        'confirmed_at',
        'locked_at',
        'locked_by',
    ];

      protected $casts = [
        'qr_code_generated_at' => 'datetime',
        'locked_at' => 'datetime',
        'amount' => 'decimal:2',
        'confirmed_by_focal_person' => 'boolean',
        'confirmed_at' => 'datetime',
    ];



    public function logs()
    {
        return $this->hasMany(DvRequestLog::class, 'dv_request_id');
    }
    public function gipAttachments()
    {
        return $this->hasMany(GIPAttachment::class, 'dv_request_id');
    }
    public function tupadAttachments()
    {
        return $this->hasMany(TUPADAttachment::class, 'dv_request_id');
    }
    public function getTupadAttachmentsForModality($modality)
    {
        return $this->tupadAttachments()
            ->where('modality', $modality)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Check if all TUPAD attachments are present for current modality
     */
    public function allTupadAttachmentsPresent()
    {
        if (!$this->tupad_modality) {
            return false;
        }
    
        $attachments = $this->tupadAttachments()
            ->where('modality', $this->tupad_modality)
            ->get();
    
        if ($attachments->isEmpty()) {
            return false;
        }
    
        return $attachments->every(fn($attachment) => $attachment->is_present);
    }
    
    /**
     * Get count of present TUPAD attachments
     */
    public function presentTupadAttachmentsCount()
    {
        if (!$this->tupad_modality) {
            return 0;
        }
    
        return $this->tupadAttachments()
            ->where('modality', $this->tupad_modality)
            ->where('is_present', true)
            ->count();
    }
    
    public function tevAttachments()
    {
        return $this->hasMany(TEVAttachment::class, 'dv_request_id');
    }

        /**
     * Get TEV attachments for a specific modality
     */
    public function getTevAttachmentsForModality($modality)
    {
        return $this->tevAttachments()
            ->where('modality', $modality)
            ->orderBy('sort_order')
            ->get();
    }
    
    /**
     * Check if all TEV attachments are present for current modality
     */
    public function allTevAttachmentsPresent()
    {
        if (!$this->tev_modality) {
            return false;
        }
    
        $attachments = $this->tevAttachments()
            ->where('modality', $this->tev_modality)
            ->get();
    
        if ($attachments->isEmpty()) {
            return false;
        }
    
        return $attachments->every(fn($attachment) => $attachment->is_present);
    }
    
    /**
     * Get count of present TEV attachments
     */
    public function presentTevAttachmentsCount()
    {
        if (!$this->tev_modality) {
            return 0;
        }
    
        return $this->tevAttachments()
            ->where('modality', $this->tev_modality)
            ->where('is_present', true)
            ->count();
    }
    
    /**
     * Get total count of required TEV attachments for current modality
     */
    public function totalTevAttachmentsCount()
    {
        if (!$this->tev_modality) {
            return 0;
        }
    
        return $this->tevAttachments()
            ->where('modality', $this->tev_modality)
            ->count();
    }
    
    /**
     * Get TEV attachment completion percentage
     */
    public function tevAttachmentCompletionPercentage()
    {
        $total = $this->totalTevAttachmentsCount();
        
        if ($total === 0) {
            return 0;
        }
    
        $present = $this->presentTevAttachmentsCount();
        
        return round(($present / $total) * 100, 2);
    }
    public function focalperson()
    {
        return $this->belongsTo(FocalPerson::class, 'focalperson_id');
    }

    public function orsRequest()
    {
        return $this->hasOne(OrsRequest::class, 'dv_request_id');
    }



    
        public function modeOfPayments()
        {
            return $this->belongsToMany(
                ModeOfPayment::class,
                'dv_request_mode_of_payment',
                'dv_request_id',
                'mode_of_payment_id'
            )
            ->using(DvRequestModeOfPayment::class)
            ->withPivot('id', 'created_at', 'updated_at')
            ->withTimestamps();
        }

    public function paymentDetails()
    {
        return $this->hasManyThrough(
            PaymentDetail::class,
            DvRequestModeOfPayment::class,
            'dv_request_id', // FK on pivot
            'dv_request_mode_of_payment_id', // FK on details
            'id', // local DV id
            'id'  // pivot id
        );
    }
public function modeOfPaymentsWithDetails()
{
    return $this->modeOfPayments()->withPivotValue(['id'])->get()->each(function ($mop) {
        $mop->pivot->load('paymentDetail');
    });
}
    public function program()
    {
        return $this->belongsTo(Program::class, 'program_id');
    }
    public function respocenter()
    {
        return $this->belongsTo(ResponCenter::class, 'respocenter_id');
    }
    public function receivedBy() 
    { 
        return $this->belongsTo(AccountingUnit::class, 'received_by_id');
    }

    // Workflow stage relationships
    public function accountingUnit()
    {
        return $this->belongsTo(AccountingUnit::class, 'accountingunit_id');
    }


    public function verificationHistories()
{
    return $this->hasMany(BudgetObligationHistory::class, 'dv_request_id');
}

     public function regionalDirectorHistory()
    {
        return $this->hasMany(RegionalDirectorApprovedHistory::class);
    }

    /**
     * Cashier release history
     */
    public function cashierHistory()
    {
        return $this->hasMany(CashierReleasedHistory::class);
    }

    /**
     * RANCA validation history
     */
    public function rancaHistory()
    {
        return $this->hasMany(RANCAValidationHistory::class);
    }
}
