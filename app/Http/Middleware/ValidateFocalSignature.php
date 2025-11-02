<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
use Symfony\Component\HttpFoundation\Response;

class ValidateFocalSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->hasValidSignature()) {
            return $next($request);
        }

        \Log::error('❌ Invalid focal signature', [
            'url' => $request->fullUrl(),
            'id' => $request->route('id'),
            'hash' => $request->route('hash'),
            'expires' => $request->query('expires'),
            'signature' => $request->query('signature'),
            'app_url' => config('app.url'),
        ]);

        throw new InvalidSignatureException('The verification link is invalid or has expired.');
    }
}