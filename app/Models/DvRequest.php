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
        
        // NEW TIMESTAMP FIELDS
        'last_action_at',
        'last_action_type', 
        'last_acted_by',
        
        // Department received timestamps
        'gip_received_at',
        'budget_received_at',
        'accountant_received_at',
        'outgoing_received_at',
        'tupad_received_at',
        'tev_received_at',
        
        // Department last action timestamps
        'gip_last_action_at',
        'budget_last_action_at',
        'accountant_last_action_at',
        'outgoing_last_action_at',
        'tupad_last_action_at',
        'tev_last_action_at',

        // ✅ ADD PAYEE FIELDS TO FILLABLE
        'payee_fname',
        'payee_mi',
        'payee_lname',
        'payee_tin_number',
        'payee_address',
        'multiple_payees',
        'payee_data',
    ];

    protected $casts = [
        'qr_code_generated_at' => 'datetime',
        'locked_at' => 'datetime',
        'amount' => 'decimal:2',
        'confirmed_by_focal_person' => 'boolean',
        'confirmed_at' => 'datetime',
        'multiple_payees' => 'boolean',
        'payee_data' => 'array',
        'last_action_at' => 'datetime',
        'gip_received_at' => 'datetime',
        'budget_received_at' => 'datetime',
        'accountant_received_at' => 'datetime',
        'outgoing_received_at' => 'datetime',
        'tupad_received_at' => 'datetime',
        'tev_received_at' => 'datetime',
        'gip_last_action_at' => 'datetime',
        'budget_last_action_at' => 'datetime',
        'accountant_last_action_at' => 'datetime',
        'outgoing_last_action_at' => 'datetime',
        'tupad_last_action_at' => 'datetime',
        'tev_last_action_at' => 'datetime',
    ];

    // Your existing relationships remain the same...
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

    public function getTevAttachmentsForModality($modality)
    {
        return $this->tevAttachments()
            ->where('modality', $modality)
            ->orderBy('sort_order')
            ->get();
    }
    
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
    
    public function totalTevAttachmentsCount()
    {
        if (!$this->tev_modality) {
            return 0;
        }
    
        return $this->tevAttachments()
            ->where('modality', $this->tev_modality)
            ->count();
    }
    
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
            'dv_request_id',
            'dv_request_mode_of_payment_id',
            'id',
            'id'
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

    public function cashierHistory()
    {
        return $this->hasMany(CashierReleasedHistory::class);
    }

    public function rancaHistory()
    {
        return $this->hasMany(RANCAValidationHistory::class);
    }

    // ✅ HELPER METHOD: Get full payee name (with et al. if multiple)
    public function getPayeeFullNameAttribute()
    {
        if (!$this->payee_fname) {
            return null;
        }

        $name = trim(
            $this->payee_fname . ' ' . 
            ($this->payee_mi ? $this->payee_mi . '. ' : '') . 
            $this->payee_lname
        );

        // Add "et al." if multiple payees
        if ($this->multiple_payees && $this->payee_data) {
            $payees = is_string($this->payee_data) 
                ? json_decode($this->payee_data, true) 
                : $this->payee_data;
                
            if (is_array($payees) && count($payees) > 1) {
                $name .= ' et al.';
            }
        }

        return $name;
    }

    // ✅ HELPER METHOD: Get all payees as array
    public function getAllPayeesAttribute()
    {
        if (!$this->multiple_payees || !$this->payee_data) {
            return [[
                'fname' => $this->payee_fname,
                'mi' => $this->payee_mi,
                'lname' => $this->payee_lname,
                'tin' => $this->payee_tin_number,
                'address' => $this->payee_address,
            ]];
        }

        $payees = is_string($this->payee_data) 
            ? json_decode($this->payee_data, true) 
            : $this->payee_data;

        return is_array($payees) ? $payees : [];
    }

    // ✅ HELPER METHOD: Get payee count
    public function getPayeeCountAttribute()
    {
        if (!$this->multiple_payees) {
            return 1;
        }

        $payees = $this->all_payees;
        return count($payees);
    }

    // ✅ HELPER METHOD: Get formatted payee list for display
    public function getPayeeListFormattedAttribute()
    {
        $payees = $this->all_payees;
        
        if (count($payees) === 1) {
            $p = $payees[0];
            return trim($p['fname'] . ' ' . ($p['mi'] ?? '') . ' ' . $p['lname']);
        }

        $names = array_map(function($p) {
            return trim($p['fname'] . ' ' . ($p['mi'] ?? '') . ' ' . $p['lname']);
        }, $payees);

        if (count($names) <= 3) {
            return implode(', ', $names);
        }

        // Show first 2 names + "and X others"
        $first = array_slice($names, 0, 2);
        $remaining = count($names) - 2;
        
        return implode(', ', $first) . " and {$remaining} other" . ($remaining > 1 ? 's' : '');
    }

    // ✅ HELPER METHOD: Get full focal person name (encoder)
    public function getEncoderFullNameAttribute()
    {
        if (!$this->focalperson) {
            return 'Unknown';
        }

        return trim(
            $this->focalperson->fname . ' ' . 
            ($this->focalperson->mi ? $this->focalperson->mi . '. ' : '') . 
            $this->focalperson->lname
        );
    }

    // ✅ HELPER METHOD: Check if has payee data (vs using focal person data)
    public function hasPayeeData()
    {
        return !empty($this->payee_fname) && !empty($this->payee_lname);
    }

    public function LastActor()
    {
        return $this->belongsTo(AccountingUnit::class, 'last_acted_by');
    }
}