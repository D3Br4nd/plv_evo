<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use App\Models\MemberInvitation;
use App\Mail\MemberInvitationMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AdminMemberController extends Controller
{
    private const PLV_ROLES = [
        'PRESIDENTE',
        'VICE PRESIDENTE',
        'CASSIERE',
        'SEGRETARIO',
        'MAGAZZINIERE',
        'CONSIGLIERE',
        'PRESIDENTE DEI REVISORI',
        'REVISORE',
        'SUPPLENTE REVISORE',
        'PRESIDENTE DEI PROBIVIRI',
        'PROBIVIRO',
    ];
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $year = (int) $request->input('year', now()->year);
        $perPage = (int) $request->input('per_page', 20);
        $allowedPerPage = [10, 20, 30, 40, 50];
        if (! in_array($perPage, $allowedPerPage, true)) {
            $perPage = 20;
        }

        $query = User::query()
            ->with(['memberships' => fn($q) => $q->where('year', $year)])
            ->latest();

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('email', 'ilike', "%{$search}%");
            });
        }

        $today = now()->toDateString();
        $totalMatching = (clone $query)->count();
        $activeMatching = (clone $query)
            ->where(function ($q) use ($today, $year) {
                $q->whereDate('plv_expires_at', '>=', $today)
                    ->orWhereHas('memberships', fn($m) => $m->where('year', $year));
            })
            ->count();

        $pendingInvites = MemberInvitation::query()
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->count();

        return Inertia::render('Admin/Members/Index', [
            'users' => $query->paginate($perPage)->withQueryString(),
            'filters' => $request->only(['search', 'per_page']),
            'year' => $year,
            'stats' => [
                'total' => $totalMatching,
                'active' => $activeMatching,
                'expired' => max(0, $totalMatching - $activeMatching),
                'pendingInvites' => $pendingInvites,
            ],
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, User $member)
    {
        $year = (int) $request->input('year', now()->year);

        $member->load([
            'memberships' => fn($q) => $q->where('year', $year),
        ]);

        return Inertia::render('Admin/Members/Show', [
            'member' => $member,
            'year' => $year,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $year = (int) $request->input('year', now()->year);

        return Inertia::render('Admin/Members/Create', [
            'year' => $year,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'role' => 'required|string|in:super_admin,admin,member',
            'plv_role' => ['sometimes', 'nullable', Rule::in(self::PLV_ROLES)],

            'first_name' => 'sometimes|nullable|string|max:255',
            'last_name' => 'sometimes|nullable|string|max:255',
            'phone' => 'sometimes|nullable|string|max:50',

            'birth_date' => 'sometimes|nullable|date',
            'birth_place_type' => 'sometimes|nullable|string|in:it,foreign',
            'birth_province_code' => 'sometimes|nullable|string|size:2',
            'birth_city' => 'sometimes|nullable|string|max:255',
            'birth_country' => 'sometimes|nullable|string|max:255',

            'residence_type' => 'sometimes|nullable|string|in:it,foreign',
            'residence_street' => 'sometimes|nullable|string|max:255',
            'residence_house_number' => 'sometimes|nullable|string|max:50',
            'residence_locality' => 'sometimes|nullable|string|max:255',
            'residence_province_code' => 'sometimes|nullable|string|size:2',
            'residence_city' => 'sometimes|nullable|string|max:255',
            'residence_country' => 'sometimes|nullable|string|max:255',

            'plv_joined_at' => 'sometimes|nullable|date',
        ]);

        if ($validated['role'] !== UserRole::Member->value) {
            $this->authorize('manage-roles');
        }

        // Safety net: if migrations haven't run yet (e.g. during deploy), avoid writing to missing columns.
        $validated = array_filter(
            $validated,
            fn($_v, $k) => Schema::hasColumn('users', $k),
            ARRAY_FILTER_USE_BOTH
        );

        if (array_key_exists('plv_joined_at', $validated) && Schema::hasColumn('users', 'plv_expires_at')) {
            $validated['plv_expires_at'] = $validated['plv_joined_at']
                ? Carbon::parse($validated['plv_joined_at'])->addYear()->toDateString()
                : null;
        }

        // Random password (user will set their own via invitation link)
        $member = User::create(array_merge($validated, [
            'password' => Hash::make(Str::random(64)),
            'must_set_password' => true,
            'membership_status' => 'inactive',
        ]));

        ActivityLog::create([
            'actor_user_id' => $request->user()?->id,
            'action' => 'created',
            'subject_type' => 'User',
            'subject_id' => $member->id,
            'summary' => 'Creato socio: '.$member->name.($member->email ? ' ('.$member->email.')' : ''),
        ]);

        // Create invitation + email
        $token = Str::random(64);
        $inviteUrl = url("/invite/{$token}");

        MemberInvitation::create([
            'user_id' => $member->id,
            'created_by_user_id' => $request->user()?->id,
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addDays(7),
        ]);

        $mailSent = false;
        try {
            Mail::to($member->email)->send(new MemberInvitationMail($member, $inviteUrl));
            $mailSent = true;
        } catch (\Throwable $e) {
            Log::warning('Member invitation email failed on member creation', [
                'member_id' => $member->id,
                'email' => $member->email,
                'error' => $e->getMessage(),
            ]);
        }

        // After creation, send admin directly to the member profile page to fill out all fields.
        return redirect()
            ->to("/admin/members/{$member->id}")
            ->with('success', $mailSent ? 'Socio creato e invito inviato via email.' : 'Socio creato. Email invito non inviata: copia il link e invialo manualmente.')
            ->with('invite_url', $inviteUrl);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $member)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'first_name' => 'sometimes|nullable|string|max:255',
            'last_name' => 'sometimes|nullable|string|max:255',
            'email' => ['sometimes', 'required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($member->id)],
            'phone' => 'sometimes|nullable|string|max:50',
            'plv_role' => ['sometimes', 'nullable', Rule::in(self::PLV_ROLES)],

            'birth_date' => 'sometimes|nullable|date',
            'birth_place_type' => 'sometimes|nullable|string|in:it,foreign',
            'birth_province_code' => 'sometimes|nullable|string|size:2',
            'birth_city' => 'sometimes|nullable|string|max:255',
            'birth_country' => 'sometimes|nullable|string|max:255',

            'residence_type' => 'sometimes|nullable|string|in:it,foreign',
            'residence_street' => 'sometimes|nullable|string|max:255',
            'residence_house_number' => 'sometimes|nullable|string|max:50',
            'residence_locality' => 'sometimes|nullable|string|max:255',
            'residence_province_code' => 'sometimes|nullable|string|size:2',
            'residence_city' => 'sometimes|nullable|string|max:255',
            'residence_country' => 'sometimes|nullable|string|max:255',

            'plv_joined_at' => 'sometimes|nullable|date',
            // plv_expires_at is computed server-side from plv_joined_at (1 year)

            'role' => 'sometimes|required|string|in:super_admin,admin,member',
        ]);

        // Safety net: if migrations haven't run yet (e.g. during deploy), avoid writing to missing columns.
        // This prevents 500s like: column "first_name" of relation "users" does not exist.
        $validated = array_filter(
            $validated,
            fn($_v, $k) => Schema::hasColumn('users', $k),
            ARRAY_FILTER_USE_BOTH
        );

        if (array_key_exists('role', $validated) && $validated['role'] !== $member->role) {
            $this->authorize('manage-roles');
        }

        if (array_key_exists('plv_joined_at', $validated)) {
            if (Schema::hasColumn('users', 'plv_expires_at')) {
                $validated['plv_expires_at'] = $validated['plv_joined_at']
                    ? Carbon::parse($validated['plv_joined_at'])->addYear()->toDateString()
                    : null;
            }
        }

        $member->update($validated);

        ActivityLog::create([
            'actor_user_id' => $request->user()?->id,
            'action' => 'updated',
            'subject_type' => 'User',
            'subject_id' => $member->id,
            'summary' => 'Aggiornato socio: '.$member->name.($member->email ? ' ('.$member->email.')' : ''),
        ]);

        return redirect()->back()->with('success', 'Socio aggiornato con successo.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $member)
    {
        $summary = 'Eliminato socio: '.$member->name.($member->email ? ' ('.$member->email.')' : '');
        $member->delete();

        ActivityLog::create([
            'actor_user_id' => request()->user()?->id,
            'action' => 'deleted',
            'subject_type' => 'User',
            'subject_id' => $member->id,
            'summary' => $summary,
        ]);

        return redirect()->back()->with('success', 'Socio eliminato con successo.');
    }
}
