<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class AdminMemberRoleController extends Controller
{
    public function update(Request $request, User $member)
    {
        $this->authorize('manage-roles');

        $validated = $request->validate([
            'role' => 'required|string|in:super_admin,admin,member',
        ]);

        // Admin users cannot demote a Super Admin.
        // Super Admin may change themselves (including removing their own super_admin role if desired).
        if ($member->role === 'super_admin' && $request->user()?->role !== 'super_admin') {
            abort(403);
        }

        $member->update([
            'role' => $validated['role'],
        ]);

        return redirect()->back()->with('success', 'Ruolo aggiornato con successo.');
    }
}


