<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class MemberProfileController extends Controller
{
    public function show()
    {
        $user = request()->user();
        
        $hasSubscription = \App\Models\PushSubscription::query()
            ->where('user_id', $user->id)
            ->exists();

        return Inertia::render('Member/Profile', [
            'vapidPublicKey' => config('push.vapid_public_key'),
            'hasPushSubscription' => $hasSubscription,
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:50'],
            'residence_type' => ['nullable', 'string', 'in:it,foreign'],
            'residence_street' => ['nullable', 'string', 'max:255'],
            'residence_house_number' => ['nullable', 'string', 'max:50'],
            'residence_city' => ['nullable', 'string', 'max:255'],
            'residence_province_code' => ['nullable', 'string', 'max:2'],
            'residence_country' => ['nullable', 'string', 'max:255'],
        ]);

        $user->update($validated);

        return redirect()->back()->with('success', 'Profilo aggiornato.');
    }

    public function updateAvatar(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'avatar' => ['required', 'image', 'max:2048'],
        ]);

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $dir = $user->id.'/avatar';
        $ext = $validated['avatar']->extension() ?: 'jpg';
        $path = $validated['avatar']->storePubliclyAs($dir, 'avatar.'.$ext, 'public');
        $user->update(['avatar_path' => $path]);

        return redirect()->back()->with('success', 'Avatar aggiornato.');
    }

    public function destroyAvatar(Request $request)
    {
        $user = $request->user();

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $user->update(['avatar_path' => null]);

        return redirect()->back()->with('success', 'Avatar rimosso.');
    }
}


