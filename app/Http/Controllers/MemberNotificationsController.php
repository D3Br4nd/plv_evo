<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MemberNotificationsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $hasSubscription = $user->pushSubscriptions()
            ->exists();

        return Inertia::render('Member/Notifications', [
            'vapidPublicKey' => config('push.vapid_public_key'),
            'hasPushSubscription' => $hasSubscription,
            'notifications' => $user->notifications()->latest()->paginate(20)->withQueryString(),
        ]);
    }

    public function destroy(Request $request, string $notificationId)
    {
        $user = $request->user();

        $user->notifications()->where('id', $notificationId)->delete();

        return redirect()->back()->with('success', 'Notifica eliminata.');
    }
}


