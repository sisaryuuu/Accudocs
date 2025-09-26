<?php

namespace App\Http\Controllers\Incoming;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DvRequest;
use App\Models\DvRequestLog;
use App\Models\Notification;
use App\Services\PusherService;
use App\Models\GIPAttachment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TEVDvRequestProcessingController extends Controller
{
      protected PusherService $pusher;

    public function __construct(PusherService $pusher)
    {
        $this->pusher = $pusher;
    }

}
