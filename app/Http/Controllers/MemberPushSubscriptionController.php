<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MemberPushSubscriptionController extends Controller
{
    public function store(Request $request)
    {
        $user = $request->user();

        \Log::info('Push subscription attempt:', ['user' => $user->id, 'data' => $request->all()]);

        try {
            $validated = $request->validate([
                'endpoint' => ['required', 'string'],
                'keys' => ['required', 'array'],
                'keys.p256dh' => ['required', 'string'],
                'keys.auth' => ['required', 'string'],
                'contentEncoding' => ['nullable', 'string'],
            ]);

            $user->updatePushSubscription(
                $validated['endpoint'],
                $validated['keys']['p256dh'],
                $validated['keys']['auth'],
                $validated['contentEncoding'] ?? 'aesgcm'
            );

            return response()->json(['ok' => true]);
        } catch (\Exception $e) {
            \Log::error('Push subscription failed:', ['error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function destroy(Request $request)
    {
        $user = $request->user();

        // The package expects the endpoint to be provided to delete it
        $endpoint = $request->input('endpoint');
        
        if ($endpoint) {
            $user->deletePushSubscription($endpoint);
        } else {
            // Fallback: delete all if endpoint not provided?
            // Usually we want to delete a specific one.
            $user->pushSubscriptions()->delete();
        }

        return response()->json(['ok' => true]);
    }
}


