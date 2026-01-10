<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdminMembershipController extends Controller
{
    /**
     * Activate membership for a specific year.
     */
    public function store(Request $request, User $member): RedirectResponse
    {
        $year = (int) $request->input('year', now()->year);

        // Check if already exists
        $exists = $member->memberships()->where('year', $year)->exists();

        if (!$exists) {
            $member->memberships()->create([
                'year' => $year,
                'paid_at' => now(), 
                'amount' => 0,
            ]);
            \Illuminate\Support\Facades\Log::info('Membership created', ['member_id' => $member->id, 'year' => $year]);
        }
        return redirect()->back()->with('success', "Tesseramento $year attivato.");
    }

    /**
     * Deactivate membership for a specific year.
     */
    public function destroy(Request $request, User $member): RedirectResponse
    {
        $year = (int) $request->input('year', now()->year);

        $member->memberships()->where('year', $year)->delete();

        return redirect()->back()->with('success', "Tesseramento $year annullato.");
    }
}
