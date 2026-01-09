<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use Inertia\Inertia;

class MemberOnboardingController extends Controller
{
    public function show()
    {
        $user = request()->user();

        $hasSubscription = PushSubscription::query()
            ->where('subscribable_type', $user->getMorphClass())
            ->where('subscribable_id', $user->id)
            ->exists();

        return Inertia::render('Member/Onboarding', [
            'user' => $user->only(['id', 'name', 'email', 'must_set_password']),
            'vapidPublicKey' => config('push.vapid_public_key'),
            'hasPushSubscription' => $hasSubscription,
        ]);
    }
}


