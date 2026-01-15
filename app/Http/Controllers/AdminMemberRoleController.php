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

        $memberRole = $member->role instanceof \UnitEnum ? $member->role->value : $member->role;
        $userRole = $request->user()?->role instanceof \UnitEnum ? $request->user()->role->value : $request->user()?->role;

        if ($memberRole === 'super_admin' && $userRole !== 'super_admin') {
            abort(403);
        }

        $member->update([
            'role' => $validated['role'],
        ]);

        return redirect()->back()->with('success', 'Ruolo aggiornato con successo.');
    }
}


