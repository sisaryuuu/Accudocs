<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SmsTestController extends Controller
{
    public function send(Request $request)
    {
        // ✅ Validate input fields
        $request->validate([
            'to' => 'required|string',
            'text' => 'required|string|max:160',
        ]);

        // ✅ Prepare and send SMS using Infobip API
        $response = Http::withHeaders([
            'Authorization' => env('INFOBIP_API_KEY'),
            'Content-Type' => 'application/json',
        ])->post(env('INFOBIP_BASE_URL') . '/sms/2/text/advanced', [
            'messages' => [
                [
                    'from' => env('INFOBIP_FROM'),
                    'destinations' => [
                        ['to' => $request->to],
                    ],
                    'text' => $request->text,
                ]
            ],
        ]);

        // ✅ Check response
        if ($response->successful()) {
            return response()->json([
                'success' => true,
                'data' => $response->json(),
            ]);
        }

        // ❌ Return error info
        return response()->json([
            'success' => false,
            'error' => $response->body(),
        ], $response->status());
    }
}
