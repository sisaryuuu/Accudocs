<?php
// app/Http/Controllers/SMSController.php

namespace App\Http\Controllers;

use App\Services\IprogSmsService;
use Illuminate\Http\Request;

class SMSController extends Controller
{
    protected $smsService;

    public function __construct(IprogSmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * Test all SMS endpoints to find working one
     */
    public function testEndpoints(Request $request)
    {
        $phone = $request->get('phone', '09235323646');
        $message = $request->get('message', 'Testing SMS endpoints PISTING YAWA from ACCUDOCS');
        
        $result = $this->smsService->testSmsSending($phone, $message);
        return response()->json($result);
    }

    /**
     * Test single SMS send
     */
    public function testSMS(Request $request)
    {
        $phoneNumber = $request->get('phone', '09235323646');
        $message = $request->get('message', 'Test SMS from ACCUDOCS DOLE XI KINASON PAYONG REPAIR - Please ignore');
        
        $result = $this->smsService->sendSMS($phoneNumber, $message);
        return response()->json($result);
    }

    /**
     * Quick connection test
     */
    public function testConnection()
    {
        $result = $this->smsService->testConnection();
        return response()->json($result);
    }

    /**
     * Send actual notification
     */
    public function sendNotification(Request $request)
    {
        $request->validate([
            'phone_number' => 'required',
            'message' => 'required'
        ]);

        $result = $this->smsService->sendSMS(
            $request->phone_number, 
            $request->message
        );

        return response()->json($result);
    }
}