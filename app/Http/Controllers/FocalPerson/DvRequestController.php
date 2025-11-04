<?php

namespace App\Http\Controllers\FocalPerson;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use App\Models\DvRequest;
use App\Notifications\DvRequestUpdated;
use App\Models\DvRequestLog;
use App\Models\Program;
use App\Models\ModeOfPayment;
use App\Models\PaymentDetail;
use App\Models\DvRequestModeOfPayment;
use App\Models\DvRequestHistory;
use App\Services\PusherService;
use App\Services\DvRequestArchiveService;
use App\Models\Notification;
use App\Events\DashboardUpdated;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;  
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;



class DvRequestController extends Controller
{
    protected PusherService $pusher;

    public function __construct(PusherService $pusher)
    {
        $this->pusher = $pusher;
    }
public function index()
{ 
    \Log::info('Focal Person Auth Check', [
        'is_authenticated' => Auth::guard('focal')->check(),
        'user_id' => Auth::guard('focal')->id(),
        'session_id' => session()->getId(),
        'session_data' => session()->all()
    ]);

    if (!Auth::guard('focal')->check()) {
        \Log::warning('Focal Person not authenticated, redirecting to login');
        return redirect()->route('login')->with('error', 'Please log in first.');
    }
    
    $focalId = Auth::guard('focal')->id();

    try {
        $dvRequests = DvRequest::with(['focalperson', 'program', 'respocenter', 'modeOfPayments'])
            ->where('focalperson_id', $focalId)
            ->whereNotIn('status', ['Completed']) // Still exclude old Completed status
            ->where('status', '!=', 'Cashier_released') // Exclude Cashier_released as it's now completed
            ->where(function ($query) {
                $query->where('status', 'not like', '%_rejected');
            })
            ->latest()
            ->get();
    } catch (\Exception $e) {
        return back()->with('error', 'Database error: ' . $e->getMessage());
    }

    return view('FocalPerson.dvrequest.index', compact('dvRequests'));
}
public function history()
{
    if (!Auth::guard('focal')->check()) {
        return redirect()->route('login')->with('error', 'Please log in first.');
    }

    $focalId = Auth::guard('focal')->id();

    try {
        $dvRequests = DvRequest::with(['focalperson', 'program','respocenter', 'modeOfPayments'])
            ->where('focalperson_id', $focalId)
            ->where(function($query) {
                $query->where('status', 'rejected')
                      ->orWhere('status', 'completed')
                      ->orWhere('status', 'Cashier_released') // Include Cashier_released in history
                      ->orWhere('status', 'like', '%rejected%');
            })
            ->latest()
            ->get();
    } catch (\Exception $e) {
        return back()->with('error', 'Database error: ' . $e->getMessage());
    }

    return view('FocalPerson.dvrequest.history', compact('dvRequests'));
}
    public function searchHistory(Request $request)
    {
        if (!Auth::guard('focal')->check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $focalId = Auth::guard('focal')->id();
        $query   = $request->get('query');

        $dvRequests = DvRequest::with(['program', 'respocenter'])
            ->where('focalperson_id', $focalId)
            ->where(function($q) {
                $q->where('status', 'rejected')
                  ->orWhere('status', 'completed')
                  ->orWhere('status', 'like', '%rejected%');
            })
            ->where(function ($q) use ($query) {
                $q->where('control_no', 'like', "%{$query}%")
                  ->orWhere('particulars', 'like', "%{$query}%")
                  ->orWhere('amount', 'like', "%{$query}%")
                  ->orWhere('status', 'like', "%{$query}%")
                  ->orWhereHas('program', function ($p) use ($query) {
                      $p->where('prcode', 'like', "%{$query}%");
                  })
                  ->orWhereHas('respocenter', function ($r) use ($query) {
                      $r->where('rccode', 'like', "%{$query}%");
                  });
            })
            ->latest()
            ->get();

        return response()->json($dvRequests);
    }

    public function refresh()
    {
        $dvRequests = DvRequest::select('id', 'status', 'dv_number', 'updated_at')
            ->orderBy('updated_at', 'desc')
            ->get();

        return response()->json($dvRequests);
    } 

    public function create()
    {
        $focalPerson = Auth::guard('focal')->user();
        $programs = Program::all();
        $mops = ModeOfPayment::all();
        return view('FocalPerson.dvrequest.submitdvors', compact('programs','focalPerson','mops'));
    }

  public function store(Request $request)
{
    $request->validate([
        'program_id'  => 'required|exists:programs,id',
        'respocenter_id' =>'required|exists:responcenters,id',
        'amount'      => 'required|numeric|min:0',
        'particulars' => 'required|string|max:255',
        'mode_of_payment_id' => 'required|exists:mode_of_payments,id',
        'payment_details.bank_name'   => 'nullable|string|max:255',
        'payment_details.bank_accnum' => 'nullable|string|max:255',
        'payment_details.bank_address'=> 'nullable|string|max:255',
    ]);

    $focalpersonId = Auth::guard('focal')->id();

    if (!$focalpersonId) {
        abort(403, 'Only focal persons can submit DV requests.');
    }

    $submissionKey = 'dv_submission_' . $focalpersonId . '_' . md5(
        $request->program_id . 
        $request->amount . 
        $request->particulars . 
        $request->mode_of_payment_id
    );

    if (Cache::has($submissionKey)) {
        return redirect()->route('FocalPerson.dvrequest.index')
            ->with('warning', 'Duplicate submission detected. Your request was already submitted.');
    }

    Cache::put($submissionKey, true, now()->addSeconds(5));

    $recentDuplicate = DvRequest::where('focalperson_id', $focalpersonId)
        ->where('amount', $request->amount)
        ->where('program_id', $request->program_id)
        ->where('created_at', '>=', now()->subSeconds(10))
        ->first();

    if ($recentDuplicate) {
        return redirect()->route('FocalPerson.dvrequest.index')
            ->with('warning', 'A similar request was just submitted. Please check your submissions.');
    }

    try {
        // Use a variable outside the transaction to store the DV request
        $dv = null;
        
        DB::transaction(function () use ($request, $focalpersonId, &$dv) {
            $nextControlNumber = (DvRequest::max('accounting_control_number') ?? 0) + 1;
            $nextUserSequence = (DvRequest::where('focalperson_id', $focalpersonId)->max('user_sequence') ?? 0) + 1;

            do {
                $referenceNumber = 'DVORS-' . strtoupper(Str::random(8));
            } while (DvRequest::where('reference_number', $referenceNumber)->exists());

            $focalPerson = Auth::guard('focal')->user();

            $dv = DvRequest::create([
                'focalperson_id'            => $focalpersonId,
                'reference_number'          => $referenceNumber,
                'accounting_control_number' => $nextControlNumber,
                'user_sequence'             => $nextUserSequence,
                'program_id'                => $request->program_id,
                'amount'                    => $request->amount,
                'particulars'               => $request->particulars,
                'fname'                     => $focalPerson->fname,
                'mi'                        => $focalPerson->mi,
                'lname'                     => $focalPerson->lname,
                'tin_number'                => $focalPerson->TIN_number,
                'respocenter_id'            => $request->respocenter_id,
                'address'                   => $focalPerson->Address,
                'status'                    => 'submitted',
            ]);

            $dv->modeOfPayments()->attach($request->mode_of_payment_id);

            $dvRequestMop = DvRequestModeOfPayment::where('dv_request_id', $dv->id)
                ->where('mode_of_payment_id', $request->mode_of_payment_id)
                ->first();

            if ($dvRequestMop) {
                $dvRequestMop->paymentDetail()->create([
                    'bank_name'   => $request->input("payment_details.bank_name"),
                    'bank_accnum' => $request->input("payment_details.bank_accnum"),
                    'bank_address'=> $request->input("payment_details.bank_address"),
                ]);
            }
            
            // QR Code generation - make it non-blocking
            try {
                $verifyUrl = route('dv.verify', $dv->reference_number);
                $qrPath = 'qrcodes/' . $dv->reference_number . '.png';
                $fullPath = storage_path('app/public/' . $qrPath);

                // Create directory if it doesn't exist
                $directory = dirname($fullPath);
                if (!is_dir($directory)) {
                    mkdir($directory, 0777, true);
                }

                // Use external QR API
                $apiUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=600x600&format=png&data=' . urlencode($verifyUrl);
                $qrCodeContent = @file_get_contents($apiUrl);
                
                if ($qrCodeContent === false) {
                    // Fallback: Try alternative API
                    $apiUrl2 = 'https://chart.googleapis.com/chart?chs=600x600&cht=qr&chl=' . urlencode($verifyUrl);
                    $qrCodeContent = @file_get_contents($apiUrl2);
                }
                
                if ($qrCodeContent !== false) {
                    file_put_contents($fullPath, $qrCodeContent);
                    
                    // Verify file was created
                    if (file_exists($fullPath) && filesize($fullPath) > 0) {
                        $dv->qr_code_path = $qrPath;
                        $dv->save();
                    }
                }
                // If QR generation fails, just continue without it
            } catch (\Exception $qrError) {
                \Log::warning('QR Code generation failed but continuing', [
                    'dv_id' => $dv->id,
                    'error' => $qrError->getMessage()
                ]);
            }
        });

        // Check if DV was created successfully
        if (!$dv) {
            throw new \Exception('Failed to create DV request in transaction');
        }

        // Log the action
        $this->logAction($dv, 'SUBMITTED', 'submitted', 'Request submitted by Focal Person');

        // Trigger Pusher events
        $this->pusher->trigger('dv-requests-imsd', 'dv-request-submitted', [
            'id' => $dv->id,
            'control_number' => $dv->accounting_control_number,
            'program' => $dv->program->prcode,
            'amount' => number_format($dv->amount, 2),
            'status' => 'submitted',
        ]);
        Cache::forget($submissionKey);

        // EXPLICIT REDIRECT - This should fix the issue
        return redirect()->route('FocalPerson.dvrequest.preview', $dv->id)
            ->with('success', 'DV Request submitted successfully!');

    } catch (\Exception $e) {
        Cache::forget($submissionKey);

        \Log::error('DV Request submission failed', [
            'user_id' => $focalpersonId,
            'error_message' => $e->getMessage(),
            'error_line' => $e->getLine(),
            'error_file' => $e->getFile(),
            'request_data' => $request->all()
        ]);

        return redirect()->back()
            ->withInput()
            ->with('error', 'Failed to submit DV request: ' . $e->getMessage());
    }
}
    public function showQR($id)
    {
        $dvRequest = DvRequest::findOrFail($id); 
        
        if ($dvRequest->focalperson_id !== Auth::guard('focal')->id()) {
            abort(403, 'Unauthorized access.');
        }

        if (!$dvRequest->qr_code_path) {
            return response()->json(['error' => 'QR Code not found'], 404);
        }

        return response()->json([
            'qr_url' => Storage::url($dvRequest->qr_code_path),
            'reference_number' => $dvRequest->reference_number,
            'control_number' => $dvRequest->accounting_control_number,
            'amount' => number_format($dvRequest->amount, 2),
            'status' => ucfirst(str_replace('_', ' ', $dvRequest->status))
        ]);
    }

    // QR Verification Methods
    public function verifyQR($reference)
    {
        $dv = DvRequest::where('reference_number', $reference)
            ->with(['program', 'focalPerson'])
            ->firstOrFail();
        
        return view('qr.QRreqstatus', compact('dv'));
    }

    public function verifyManual(Request $request)
    {
        $request->validate([    
            'reference_number' => 'required|string|exists:dv_requests,reference_number'
        ]);
        
        return redirect()->route('dv.verify', $request->reference_number);
    }

    public function preview($id)
    {
        $dvRequest = DvRequest::with(['focalperson', 'program', 'respocenter', 'modeOfPayments'])->findOrFail($id);
        $dvRequest->modeOfPayments->each(fn($mop) => $mop->pivot->load('paymentDetail'));

        if ($dvRequest->focalperson_id !== Auth::guard('focal')->id()) {
            abort(403, 'Unauthorized access.');
        }

        $modeOfPayment = $dvRequest->modeOfPayments->first();
        $paymentDetail = $modeOfPayment?->pivot?->paymentDetail;

        $data = [
            'payee' => trim($dvRequest->focalperson->fname . ' ' . ($dvRequest->focalperson->mi ? $dvRequest->focalperson->mi . '. ' : '') . $dvRequest->focalperson->lname),
            'date' => $dvRequest->created_at->format('F d, Y'),
            'address' => $dvRequest->focalperson->Address,
            'tin' => $dvRequest->focalperson->TIN_number,
            'mode_of_payment' => $modeOfPayment?->mopname ?? 'Not specified',
            'particulars' => $dvRequest->particulars,
            'amount' => number_format($dvRequest->amount, 2),
            'respocenter_code' => $dvRequest->respocenter->rccode ?? '',
            'respocenter_desc' => $dvRequest->respocenter->description ?? '',
            'program_code' => $dvRequest->program->prcode ?? '',
            'program_desc' => $dvRequest->program->description ?? '',
            'bank_name' => $paymentDetail?->bank_name ?? '',
            'bank_accnum' => $paymentDetail?->bank_accnum ?? '',
            'bank_address' => $paymentDetail?->bank_address ?? '',
            'imsd_head' => \App\Models\Setting::get('imsd_head', 'NAME OF IMSD HEAD'),
            'accountant_name' => \App\Models\Setting::get('accountant_name', 'NAME OF ACCOUNTANT'),
            'regional_director' => \App\Models\Setting::get('regional_director', 'NAME OF REGIONAL DIRECTOR'),
        ];

        return view('FocalPerson.dvrequest.preview', compact('data', 'dvRequest'));
    }

    public function generatePDF($id)
    {
        $dvRequest = DvRequest::with(['focalperson', 'program', 'respocenter', 'modeOfPayments'])->findOrFail($id);
        $dvRequest->modeOfPayments->each(fn($mop) => $mop->pivot->load('paymentDetail'));

        if ($dvRequest->focalperson_id !== Auth::guard('focal')->id()) {
            abort(403, 'Unauthorized access.');
        }

        $modeOfPayment = $dvRequest->modeOfPayments->first();
        $paymentDetail = $modeOfPayment?->pivot?->paymentDetail;

        $data = [
            'payee' => trim($dvRequest->focalPerson->fname . ' ' . ($dvRequest->focalPerson->mi ? $dvRequest->focalPerson->mi . '. ' : '') . $dvRequest->focalPerson->lname),
            'date' => $dvRequest->created_at->format('F d, Y'),
            'address' => $dvRequest->focalPerson->Address,
            'tin' => $dvRequest->tin_number,
            'mode_of_payment' => $modeOfPayment?->mopname ?? 'Not specified',
            'particulars' => $dvRequest->particulars,
            'amount' => number_format($dvRequest->amount, 2),
            'respocenter_code' => $dvRequest->respocenter->rccode ?? '',
            'respocenter_desc' => $dvRequest->respocenter->description ?? '',
            'program_code' => $dvRequest->program->prcode ?? '',
            'program_desc' => $dvRequest->program->description ?? '',
            'bank_name' => $paymentDetail?->bank_name ?? '',
            'bank_accnum' => $paymentDetail?->bank_accnum ?? '',
            'bank_address' => $paymentDetail?->bank_address ?? '',
            'imsd_head' => \App\Models\Setting::get('imsd_head', 'NAME OF IMSD HEAD'),
            'accountant_name' => \App\Models\Setting::get('accountant_name', 'NAME OF ACCOUNTANT'),
            'regional_director' => \App\Models\Setting::get('regional_director', 'NAME OF REGIONAL DIRECTOR'),
        ];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('vouchers.disbursement', $data)
            ->setPaper('Legal', 'portrait');

        return $pdf->download('DV_' . $dvRequest->reference_number . '.pdf');
    }

    public function show(DvRequest $dvRequest)
    {
        if ($dvRequest->focalperson_id !== Auth::guard('focal')->id()) {
            abort(403, 'Unauthorized access.');
        }
        $dvRequest->load(['program', 'logs.focalPerson', 'logs.accountingUnit']);

        $logs = $dvRequest->logs;
        $lastLog = $dvRequest->logs()->latest()->first();
        return response()->json([
            'lastStep' => $lastLog ? $lastLog->role : 'Submitted'
        ]);

        return view('FocalPerson.dvrequest.show', compact('dvRequest', 'logs', 'lastStep'));
    }

    public function search(Request $request)
    {
        if (!Auth::guard('focal')->check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $focalId = Auth::guard('focal')->id();
        $query   = $request->get('query');

        $dvRequests = DvRequest::with(['program', 'respocenter'])
            ->where('focalperson_id', $focalId)
            ->whereNotIn('status', ['rejected', 'completed'])
            ->where(function ($q) use ($query) {
                $q->where('control_no', 'like', "%{$query}%")
                  ->orWhere('particulars', 'like', "%{$query}%")
                  ->orWhere('amount', 'like', "%{$query}%")
                  ->orWhere('status', 'like', "%{$query}%")
                  ->orWhereHas('program', function ($p) use ($query) {
                      $p->where('prcode', 'like', "%{$query}%");
                  })
                  ->orWhereHas('respocenter', function ($r) use ($query) {
                      $r->where('rccode', 'like', "%{$query}%");
                  });
            })
            ->latest()
            ->get();

        return response()->json($dvRequests);
    }

    public function edit(DvRequest $dvRequest)
    {
        if ($dvRequest->focalperson_id !== Auth::guard('focal')->id()) {
            abort(403, 'Unauthorized access.');
        }

        if (!in_array($dvRequest->status, ['submitted', 'ORS_for_revision'])) {
            return redirect()->route('FocalPerson.dvrequest.index')
                ->with('error', 'This DV request cannot be edited unless it is in ORS_for_revision status.');
        }

        $programs = Program::all();
        $dvRequest->load('modeOfPayments');

        return view('FocalPerson.dvrequest.edit', compact('dvRequest', 'programs'));
    }

    public function update(Request $request, DvRequest $dvRequest)
    {
        if ($dvRequest->focalperson_id !== Auth::guard('focal')->id()) {
            abort(403, 'Unauthorized access.');
        }

        if (!in_array($dvRequest->status, ['submitted', 'ORS_for_revision'])) {
            return redirect()->route('FocalPerson.dvrequest.index')
                ->with('error', 'This DV request cannot be updated unless it is in submitted or ORS_for_revision status.');
        }

        $request->validate([
            'program_id'            => 'required|exists:programs,id',
            'respocenter_id'        => 'required|exists:responcenters,id',
            'amount'                => 'required|numeric|min:0',
            'particulars'           => 'required|string|max:255',
            'fname'                 => 'nullable|string|max:255',
            'mi'                    => 'nullable|string|max:10',
            'lname'                 => 'nullable|string|max:255',
            'tin_number'            => 'nullable|string|max:20',
            'address'               => 'nullable|string|max:255',
            'mode_of_payment_id'    => 'required|exists:mode_of_payments,id',
            'payment_details.bank_name'   => 'nullable|string|max:255',
            'payment_details.bank_accnum' => 'nullable|string|max:255',
            'payment_details.bank_address'=> 'nullable|string|max:255',
        ]);

        $oldData = $dvRequest->only([
            'program_id', 'respocenter_id', 'amount', 'particulars',
            'fname', 'mi', 'lname', 'tin_number', 'address'
        ]);

        $dvRequest->update($request->only(array_keys($oldData)));

        $modeId = $request->mode_of_payment_id;
        if (!$dvRequest->modeOfPayments->contains($modeId)) {
            $dvRequest->modeOfPayments()->sync([$modeId]);
        }

        $dvRequestMop = DvRequestModeOfPayment::where('dv_request_id', $dvRequest->id)
            ->where('mode_of_payment_id', $modeId)
            ->first();

        if ($dvRequestMop) {
            $dvRequestMop->paymentDetail()->updateOrCreate(
                ['dv_request_mode_of_payment_id' => $dvRequestMop->id],
                [
                    'bank_name'   => $request->input("payment_details.bank_name"),
                    'bank_accnum' => $request->input("payment_details.bank_accnum"),
                    'bank_address'=> $request->input("payment_details.bank_address"),
                ]
            );
        }

        $changes = [];
        foreach ($oldData as $key => $oldValue) {
            $newValue = $dvRequest->$key;
            if ($oldValue != $newValue) {
                $changes[] = ucfirst(str_replace('_', ' ', $key)) . " changed from '{$oldValue}' to '{$newValue}'";
            }
        }

        $changes[] = "Mode of Payment updated";
        $remarks = implode('; ', $changes);

        $this->logAction($dvRequest, 'ORS_revision_updated', 'Updated', $remarks);

        return redirect()->route('FocalPerson.dvrequest.index')
            ->with('success', 'DV Request updated successfully.');
    }

    public function destroy(DvRequest $dvRequest)
    {
        if ($dvRequest->focalperson_id !== Auth::guard('focal')->id()) {
            abort(403, 'Unauthorized action.');
        }

        $dvRequest->delete();

        return redirect()->route('FocalPerson.dvrequest.index')
            ->with('success', 'DV Request deleted successfully.');
    }

   public function logs(DvRequest $dvRequest, Request $request)
{
    // Eager load roles for both focalperson and accountingUnit
    $logs = $dvRequest->logs()
        ->with([
            'focalperson.roles',
            'accountingUnit.roles'
        ])
        ->orderBy('created_at', 'desc')
        ->get();

    if ($request->ajax() || $request->get('ajax') == '1') {
        $html = '';
        
        foreach ($logs as $index => $log) {
            $actorName = 'System';
            $profileImage = asset('storage/profiles/default.png');
            $roleOrDepartment = $log->department ?? 'System';
            
            // FIXED: Properly handle actor names
            if ($log->focalperson) {
                $actorName = trim($log->focalperson->fname . ' ' . ($log->focalperson->mi ?? '') . ' ' . $log->focalperson->lname);
                if ($log->focalperson->profile_image) {
                    $profileImage = asset('storage/profiles/' . $log->focalperson->profile_image);
                }
                if ($log->focalperson->roles->first()) {
                    $roleOrDepartment = $log->focalperson->roles->first()->name;
                }
            } elseif ($log->accountingUnit) {
                $actorName = trim(($log->accountingUnit->fname ?? '') . ' ' . ($log->accountingUnit->lname ?? ''));
                if ($log->accountingUnit->profile_image) {
                    $profileImage = asset('storage/profiles/' . $log->accountingUnit->profile_image);
                }
                if ($log->accountingUnit->roles->first()) {
                    $roleOrDepartment = $log->accountingUnit->roles->first()->name;
                }
            } elseif ($log->external_actor) {
                $actorName = $log->external_actor;
                $roleOrDepartment = 'External Actor';
            }
            
            // Clean up actor name - remove extra spaces
            $actorName = preg_replace('/\s+/', ' ', $actorName);
            
            $html .= '<tr class="hover:bg-gray-50 transition-colors duration-150">';
            $html .= '<td class="px-6 py-4"><div class="flex items-center">';
            $html .= '<div class="flex-shrink-0 w-10 h-10 rounded-full overflow-hidden">';
            
            if ($log->focalperson || $log->accountingUnit) {
                $html .= '<img src="' . $profileImage . '" class="w-10 h-10 object-cover" onerror="this.src=\'' . asset('storage/profiles/default.png') . '\'">';
            } else {
                $html .= '<div class="w-10 h-10 bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center rounded-full">';
                $html .= '<svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">';
                $html .= '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>';
                $html .= '</svg></div>';
            }
            
            $html .= '</div><div class="ml-3">';
            $html .= '<div class="text-sm font-medium text-gray-900">' . htmlspecialchars($actorName) . '</div>';
            $html .= '<div class="text-xs text-gray-500">' . htmlspecialchars($roleOrDepartment) . '</div>';
            $html .= '</div></div></td>';
            
            $html .= '<td class="px-6 py-4">';
            if ($log->remarks && trim($log->remarks) !== '') {
                $html .= '<div class="bg-gray-50 rounded-lg p-3 border-l-4 border-blue-400">';
                $html .= '<p class="text-gray-700">' . htmlspecialchars(ucfirst($log->remarks)) . '</p></div>';
            } else {
                $html .= '<div class="bg-gray-50 rounded-lg p-3 border-l-4 border-gray-300">';
                $html .= '<p class="text-gray-500 italic">Document was ' . htmlspecialchars(strtolower($log->action)) . '</p></div>';
            }
            $html .= '</td>';
            
            $html .= '<td class="px-6 py-4 whitespace-nowrap">';
            $html .= '<div class="text-sm text-gray-900 font-medium">' . $log->created_at->format('M d, Y') . '</div>';
            $html .= '<div class="text-xs text-gray-500">' . $log->created_at->format('h:i A') . '</div>';
            $html .= '<div class="text-xs text-gray-400 mt-1">' . $log->created_at->diffForHumans() . '</div>';
            $html .= '</td></tr>';
        }
        
        return response()->json([
            'html' => $html,
            'count' => $logs->count(),
            'lastUpdated' => $logs->first()?->created_at->diffForHumans() ?? 'Never',
            'logs' => $logs->map(function($log) {
                // For JavaScript side if needed
                $role = null;
                $actorName = 'System';
                
                if ($log->focalperson) {
                    $actorName = trim($log->focalperson->fname . ' ' . ($log->focalperson->mi ?? '') . ' ' . $log->focalperson->lname);
                    if ($log->focalperson->roles->first()) {
                        $role = $log->focalperson->roles->first()->name;
                    }
                } elseif ($log->accountingUnit) {
                    $actorName = trim(($log->accountingUnit->fname ?? '') . ' ' . ($log->accountingUnit->lname ?? ''));
                    if ($log->accountingUnit->roles->first()) {
                        $role = $log->accountingUnit->roles->first()->name;
                    }
                } elseif ($log->external_actor) {
                    $actorName = $log->external_actor;
                    $role = 'External Actor';
                }
                
                return [
                    'id' => $log->id,
                    'action' => $log->action,
                    'remarks' => $log->remarks,
                    'department' => $log->department,
                    'role' => $role,
                    'actor_name' => $actorName,
                    'created_at_human' => $log->created_at->diffForHumans(),
                    'focal_person' => $log->focalperson ? [
                        'fname' => $log->focalperson->fname,
                        'lname' => $log->focalperson->lname,
                        'mi' => $log->focalperson->mi ?? '',
                        'profile_image' => $log->focalperson->profile_image
                    ] : null,
                    'accounting_unit' => $log->accountingUnit ? [
                        'fname' => $log->accountingUnit->fname,
                        'lname' => $log->accountingUnit->lname,
                        'profile_image' => $log->accountingUnit->profile_image
                    ] : null,
                ];
            })
        ]);
    }

    $steps = ['Submitted', 'IMSD', 'Incoming', 'Budget Officer', 'Outgoing','Accountant III','Regional Director','Cashier'];
    
    return view('FocalPerson.dvrequest.logs', compact('dvRequest', 'logs', 'steps'));
}


        // Add this method to your FocalPersonController or relevant controller

    public function confirmReceipt(DVRequest $dvRequest)
    {
        try {
            // Verify the request belongs to the authenticated focal person
            if ($dvRequest->focalperson_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }
            
            // Verify the status is Cashier_released
            if ($dvRequest->status !== 'Cashier_released') {
                return response()->json([
                    'success' => false,
                    'message' => 'This request is not ready for confirmation'
                ], 400);
            }
            
            // Update the DV request status to Completed
            $dvRequest->update([
                'status' => 'Completed',
                'confirmed_by_focal_person' => true,
                'confirmed_at' => now()
            ]);
            
            // Create a log entry for the confirmation
            DVRequestLog::create([
                'dv_request_id' => $dvRequest->id,
                'focalperson_id' => auth()->id(),
                'action' => 'Completed',
                'remarks' => 'Receipt of payment confirmed by focal person',
                'created_at' => now()
            ]);
            
            $this->pusher->trigger(
                'dv-requests-' . $dvRequest->focalperson_id,
                'status-updated',
                [
                    'dvRequestId' => $dvRequest->id,
                    'status' => 'Completed',
                    'actor_name' => auth()->user()->fname . ' ' . auth()->user()->lname,
                    'actor_avatar' => auth()->user()->profile_image 
                        ? asset('storage/profiles/' . auth()->user()->profile_image)
                        : asset('storage/profiles/default.png'),
                    'remarks' => 'Receipt of payment confirmed by focal person',
                    'action' => 'Completed',
                    'isRejected' => false,
                    'lastStep' => 'Completed'
                ]
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Receipt confirmed successfully',
                'status' => 'Completed'
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Confirmation error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while confirming receipt'
            ], 500);
        }
    }


    protected function logAction(DvRequest $dvRequest, string $status, string $action, string $remarks): void
    {
        $focalId = Auth::guard('focal')->id();
        $accountingUnitId = null;
        $actorName = 'System';
        $actorRole = 'System';
        $actorAvatar = asset('storage/profiles/default.png');

        if (Auth::guard('web')->check()) {
            $accountingUnit = Auth::guard('web')->user();
            $accountingUnitId = $accountingUnit->id;
            
            $actorName = trim(($accountingUnit->fname ?? '') . ' ' . ($accountingUnit->lname ?? '')) ?: 'Accounting Unit';
            $actorRole = 'Accounting Unit';
            
            if (!empty($accountingUnit->profile_image)) {
                $actorAvatar = asset('storage/profiles/' . $accountingUnit->profile_image);
            }
        } 
        elseif ($focalId) {
            $focal = Auth::guard('focal')->user();
            
            $actorName = trim(($focal->fname ?? '') . ' ' . ($focal->mi ?? '') . ' ' . ($focal->lname ?? '')) ?: 'Focal Person';
            $actorRole = 'Focal Person';
            
            if (!empty($focal->profile_image)) {
                $actorAvatar = asset('storage/profiles/' . $focal->profile_image);
            }
        }

        $log = DvRequestLog::create([
            'dv_request_id'     => $dvRequest->id,
            'focalperson_id'    => $focalId,
            'accountingunit_id' => $accountingUnitId,
            'status'            => $status,
            'action'            => $action,
            'remarks'           => $remarks,
        ]);

        $notification = Notification::create([
            'focalperson_id' => $focalId,
            'dv_request_id' => $dvRequest->id,
            'status' => $status,
            'action' => $action,
            'remarks' => $remarks,
        ]);

        $channelName = 'dv-requests-' . ($dvRequest->focalperson_id ?? $focalId);
        
        $this->pusher->trigger($channelName, 'status-updated', [
            'id'            => $notification->id,
            'dv_request_id' => $dvRequest->id,
            'dv_number'     => $dvRequest->dv_number,
            'status'        => $status,
            'action'        => $action,
            'remarks'       => $remarks,
            'actor_name'    => $actorName,
            'actor_role'    => $actorRole,
            'actor_avatar'  => $actorAvatar,
            'timestamp'     => now()->timezone('Asia/Manila')->format('M d, Y h:i A'),
            'relative'      => now()->diffForHumans(),
        ]);
    }

   public function latestStep(DvRequest $dvRequest)
{
    $lastLog = $dvRequest->logs()->latest()->first();
    $lastAction = $lastLog ? $lastLog->action : 'submitted';
    $currentStatus = $dvRequest->status;

    $statusToStepMap = [
        'submitted' => 'Submitted',
        'IMSD_received' => 'IMSD',
        'IMSD_in_process' => 'IMSD',
        'IMSD_verified' => 'IMSD',
        'IMSD_rejected' => 'IMSD',
        'Incoming-TUPAD_received' => 'Incoming',
        'Incoming-TUPAD_in_process' => 'Incoming',
        'Incoming-TUPAD_verified' => 'Incoming',
        'Incoming-TUPAD_rejected' => 'Incoming',
        'Incoming-GIP_received' => 'Incoming',
        'Incoming-GIP_in_process' => 'Incoming',
        'Incoming-GIP_verified' => 'Incoming',
        'Incoming-GIP_rejected' => 'Incoming',
        'Incoming-TEV_received' => 'Incoming',
        'Incoming-TEV_in_process' => 'Incoming',
        'Incoming-TEV_verified' => 'Incoming',
        'Incoming-TEV_rejected' => 'Incoming',
        'ORS_received' => 'Budget_Officer',
        'ORS_in_process' => 'Budget_Officer',
        'ORS_obligated' => 'Budget_Officer',
        'ORS_rejected' => 'Budget_Officer',
        'ORS_onhold' => 'Budget_Officer',
        'ORS_for_revision' => 'Budget_Officer',
        'Outgoing_received' => 'Outgoing',
        'Outgoing_in_process' => 'Outgoing',
        'Outgoing_finalized' => 'Outgoing',
        'Outgoing_rejected' => 'Outgoing',
        'RANCA_received' => 'Accounting_III',
        'RANCA_in_process' => 'Accounting_III',
        'RANCA_validated' => 'Accounting_III',
        'RANCA_rejected' => 'Accounting_III',
        'RANCA_amount_updated' => 'Accounting_III', 
        'Regional_Director_received' => 'Regional_Director',
        'Regional_Director_signed_approved' => 'Regional_Director',
        'Cashier_received' => 'Cashier',
        'Cashier_released' => 'Cashier',
    ];

    $lastStep = $statusToStepMap[$currentStatus] ?? $statusToStepMap[$lastAction] ?? $lastAction;

    $isRejected = stripos($currentStatus, 'rejected') !== false || 
                  stripos($lastAction, 'rejected') !== false;

    // Cashier_released is now considered a completed status
    $isStepCompleted = $currentStatus === 'Cashier_released' ||
                      stripos($currentStatus, '_verified') !== false ||
                      stripos($currentStatus, '_obligated') !== false ||
                      stripos($currentStatus, '_finalized') !== false ||
                      stripos($currentStatus, '_validated') !== false;

    // CRITICAL: Add isCompleted flag
    $isCompleted = $currentStatus === 'Cashier_released';

    return response()->json([
        'lastStep' => $lastStep,
        'isRejected' => $isRejected,
        'isStepCompleted' => $isStepCompleted,
        'isCompleted' => $isCompleted, // ← Add this!
        'currentStatus' => $currentStatus, // ← Add for debugging
        'debug' => [
            'currentStatus' => $currentStatus,
            'lastAction' => $lastAction,
            'mappedStep' => $lastStep,
            'statusHasRejected' => stripos($currentStatus, 'rejected') !== false,
            'actionHasRejected' => stripos($lastAction, 'rejected') !== false,
            'isCompleted' => $isCompleted, // ← Add for debugging
        ]
    ]);
}

    public function getLogs(DvRequest $dvRequest)
    {
        $logs = $dvRequest->logs()
            ->with(['focalPerson', 'accountingUnit'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($log) {
                return [
                    'id' => $log->id,
                    'action' => $log->action,
                    'remarks' => $log->remarks,
                    'external_actor' => $log->external_actor,
                    'created_at_human' => $log->created_at->diffForHumans(),
                    'created_at_formatted' => $log->created_at->format('M d, Y'),
                    'created_at_time' => $log->created_at->format('h:i A'),
                    'focal_person' => $log->focalPerson ? [
                        'fname' => $log->focalPerson->fname,
                        'lname' => $log->focalPerson->lname,
                        'mi' => $log->focalPerson->mi ?? '',
                        'profile_image' => $log->focalPerson->profile_image
                    ] : null,
                    'accounting_unit' => $log->accountingUnit ? [
                        'fname' => $log->accountingUnit->fname,
                        'lname' => $log->accountingUnit->lname,
                        'profile_image' => $log->accountingUnit->profile_image
                    ] : null,
                ];
            });

        $currentStatus = $dvRequest->status;
        $isStepCompleted = stripos($currentStatus, '_verified') !== false ||
                          stripos($currentStatus, '_obligated') !== false ||
                          stripos($currentStatus, '_finalized') !== false ||
                          stripos($currentStatus, '_validated') !== false;

        return response()->json([
            'logs' => $logs,
            'count' => $logs->count(),
            'lastUpdated' => $logs->first()?->created_at->diffForHumans() ?? 'Never',
            'isStepCompleted' => $isStepCompleted
        ]);
    }
public function autoComplete(DvRequest $dvRequest)
{
    try {
        \Log::info('🔍 Auto-complete called', [
            'dv_id' => $dvRequest->id,
            'current_status' => $dvRequest->status,
            'confirmed' => $dvRequest->confirmed_by_focal_person ?? 'column_missing',
            'user_id' => auth()->guard('focal')->id()
        ]);

        // Verify user owns this request
        if ($dvRequest->focalperson_id !== auth()->guard('focal')->id()) {
            \Log::warning('⚠️ Unauthorized auto-complete attempt', [
                'dv_id' => $dvRequest->id,
                'request_owner' => $dvRequest->focalperson_id,
                'attempting_user' => auth()->guard('focal')->id()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        // Check if status is Cashier_released
        if ($dvRequest->status !== 'Cashier_released') {
            \Log::warning('⚠️ Invalid status for auto-complete', [
                'dv_id' => $dvRequest->id,
                'current_status' => $dvRequest->status
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Request cannot be auto-completed. Current status: ' . $dvRequest->status
            ], 400);
        }

        // Check if columns exist (safety check)
        $tableColumns = \Schema::getColumnListing('dv_requests');
        $hasConfirmedColumn = in_array('confirmed_by_focal_person', $tableColumns);
        $hasConfirmedAtColumn = in_array('confirmed_at', $tableColumns);

        if (!$hasConfirmedColumn || !$hasConfirmedAtColumn) {
            \Log::error('❌ Database columns missing', [
                'has_confirmed_by_focal_person' => $hasConfirmedColumn,
                'has_confirmed_at' => $hasConfirmedAtColumn,
                'available_columns' => $tableColumns
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Database schema error. Please run migrations: php artisan migrate'
            ], 500);
        }

        // Check if already confirmed (prevent duplicate processing)
        if ($hasConfirmedColumn && $dvRequest->confirmed_by_focal_person) {
            \Log::info('✅ Already confirmed', [
                'dv_id' => $dvRequest->id,
                'confirmed_at' => $dvRequest->confirmed_at
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Request already confirmed',
                'already_confirmed' => true
            ]);
        }

        // Perform the auto-completion
        DB::transaction(function () use ($dvRequest) {
            // Update the DV request
            $updateData = [
                'confirmed_by_focal_person' => true,
                'confirmed_at' => now(),
            ];
            
            $dvRequest->update($updateData);

            // Create completion log
            DvRequestLog::create([
                'dv_request_id' => $dvRequest->id,
                'focalperson_id' => auth()->guard('focal')->id(),
                'action' => 'Cashier_released',
                'remarks' => 'Payment released by cashier and automatically confirmed as completed',
                'created_at' => now()
            ]);

            \Log::info('✅ Auto-completion successful', [
                'dv_id' => $dvRequest->id,
                'confirmed_at' => now()
            ]);
        });

        // Refresh the model to get updated data
        $dvRequest->refresh();

        // Trigger Pusher event for real-time updates
        try {
            $user = auth()->guard('focal')->user();
            
            $this->pusher->trigger(
                'dv-requests-' . $dvRequest->focalperson_id,
                'status-updated',
                [
                    'dvRequestId' => $dvRequest->id,
                    'status' => 'Cashier_released',
                    'actor_name' => $user->fname . ' ' . $user->lname,
                    'actor_avatar' => $user->profile_image 
                        ? asset('storage/profiles/' . $user->profile_image)
                        : asset('storage/profiles/default.png'),
                    'remarks' => 'Payment released and automatically confirmed',
                    'action' => 'Cashier_released',
                    'isRejected' => false,
                    'isStepCompleted' => true,
                    'lastStep' => 'Cashier'
                ]
            );
            
            \Log::info('📡 Pusher event triggered', [
                'dv_id' => $dvRequest->id,
                'channel' => 'dv-requests-' . $dvRequest->focalperson_id
            ]);
            
        } catch (\Exception $pusherError) {
            \Log::error('⚠️ Pusher event failed (non-fatal)', [
                'error' => $pusherError->getMessage(),
                'line' => $pusherError->getLine()
            ]);
            // Don't fail the whole request if Pusher fails
        }

        return response()->json([
            'success' => true,
            'message' => 'Request automatically confirmed as completed',
            'confirmed_at' => $dvRequest->confirmed_at->format('Y-m-d H:i:s')
        ]);

    } catch (\Exception $e) {
        \Log::error('❌ Auto-completion failed', [
            'dv_id' => $dvRequest->id ?? 'unknown',
            'error' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => $e->getFile(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Server error: ' . $e->getMessage(),
            'error_details' => config('app.debug') ? [
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ] : null
        ], 500);
    }
}
   public function moveRejectedToHistory()
{
    if (!Auth::guard('focal')->check()) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    $focalId = Auth::guard('focal')->id();
    $movedCount = 0;

    $rejectedRequests = DvRequest::with('orsRequest') // Eager load ORS relationship
        ->where('focalperson_id', $focalId)
        ->where(function($query) {
            $query->where('status', 'like', '%rejected%')
                  ->orWhere('status', 'like', '%onhold%')
                  ->orWhere('status', 'like', '%for_revision%');
        })
        ->get();

    foreach ($rejectedRequests as $request) {
        \Illuminate\Support\Facades\DB::transaction(function () use ($request, $focalId, &$movedCount) {
            
            // Copy to history with ORS number
            $historyData = $request->toArray();
            $historyData['history_reason'] = 'Rejected request moved to history';
            $historyData['moved_to_history_at'] = now();
            $historyData['moved_by'] = $focalId;
            $historyData['ors_number'] = $request->ors_number; // Include ORS number
            unset($historyData['id']);

            DvRequestHistory::create($historyData);

            // Delete from main table
            DvRequestLog::where('dv_request_id', $request->id)->delete();
            $request->delete();

            $movedCount++;
        });
    }

    return response()->json([
        'success' => true,
        'message' => "Moved {$movedCount} rejected requests to history",
        'moved_count' => $movedCount
    ]);
}
}