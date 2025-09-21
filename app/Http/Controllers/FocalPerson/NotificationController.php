<?php

namespace App\Http\Controllers\FocalPerson;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    // Fetch notifications for the authenticated focal person
    public function fetch()
    {
        $focalId = Auth::guard('focal')->id();

        $notifications = Notification::where('focalperson_id', $focalId)
        ->orderBy('created_at', 'desc')
        ->take(20) // only fetch last 20
        ->get();

        return response()->json($notifications);
    }

    // Mark a single notification as read
    public function markAsRead($id)
    {
        $notification = Notification::findOrFail($id);
        $notification->update(['read_at' => now()]);

        return response()->json(['success' => true]);
    }

    // Optional: Mark all notifications as read
    public function markAllAsRead()
    {
        $focalId = Auth::guard('focal')->id();
        Notification::where('focalperson_id', $focalId)
                    ->whereNull('read_at')
                    ->update(['read_at' => now()]);

        return response()->json(['success' => true]);
    }
}

