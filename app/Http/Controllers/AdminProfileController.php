<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class AdminProfileController extends Controller
{
    public function edit(Request $request)
    {
        return Inertia::render('Admin/Profile', [
            'user' => $request->user(),
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
        ]);

        $user->update($validated);

        return redirect()->back()->with('success', 'Profilo aggiornato.');
    }

    public function updateAvatar(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'avatar' => ['required', 'image', 'max:2048'], // 2MB
        ]);

        // Delete old avatar (if any)
        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $dir = $user->id.'/avatar';
        $ext = $validated['avatar']->extension() ?: 'jpg';
        $path = $validated['avatar']->storePubliclyAs($dir, 'avatar.'.$ext, 'public');

        $user->update([
            'avatar_path' => $path,
        ]);

        return redirect()->back()->with('success', 'Avatar aggiornato.');
    }

    public function destroyAvatar(Request $request)
    {
        $user = $request->user();

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $user->update([
            'avatar_path' => null,
        ]);

        return redirect()->back()->with('success', 'Avatar rimosso.');
    }

    public function updatePassword(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->back()->with('success', 'Password aggiornata.');
    }
}


