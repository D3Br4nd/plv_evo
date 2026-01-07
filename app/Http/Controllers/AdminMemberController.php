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
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class AdminMemberController extends Controller
{
    use AuthorizesRequests;

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

        // Filter by PLV role
        if ($request->filled('plv_role')) {
            $query->where('plv_role', $request->input('plv_role'));
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
            ->whereHas('user', fn($q) => $q->where('must_set_password', true))
            ->count();

        // Chart Data: Status Distribution
        $statusDistribution = [
            'active' => $activeMatching,
            'expired' => max(0, $totalMatching - $activeMatching),
            'pending' => $pendingInvites,
        ];

        // Chart Data: Role Distribution (PLV Roles)
        $roleDistribution = User::query()
            ->selectRaw('COALESCE(plv_role, \'Socio\') as role_label, count(*) as count')
            ->whereNotNull('plv_role') // Only show assigned roles + 'Socio' if we wanted, but let's stick to assigned special roles
            ->groupBy('plv_role')
            ->orderByDesc('count')
            ->get()
            ->map(fn($item) => ['role' => $item->role_label, 'visitors' => $item->count]); // 'visitors' matches shadcn chart example props often

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
            'chartData' => [
                'status' => $statusDistribution,
                'roles' => $roleDistribution,
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
            'invitations' => fn($q) => $q->whereNull('used_at')->where('expires_at', '>', now())->latest(),
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
            'email' => 'nullable|string|email|max:255|unique:users',
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

        // Auto-activate membership for the current year
        $currentYear = now()->year;
        $member->memberships()->create([
            'year' => $currentYear,
            'paid_at' => now(), 
            'amount' => 0,
            'qr_token' => (string) \Illuminate\Support\Str::uuid(),
        ]);

        // Create invitation + email (only if email is provided)
        $token = Str::random(64);
        $inviteUrl = url("/invite/{$token}");

        MemberInvitation::create([
            'user_id' => $member->id,
            'created_by_user_id' => $request->user()?->id,
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addDays(7),
        ]);

        // Create storage directories for the user
        $disk = Storage::disk('public');
        $userDir = $member->id; // UUID
        if (!$disk->exists($userDir . '/avatar')) {
            $disk->makeDirectory($userDir . '/avatar');
        }
        if (!$disk->exists($userDir . '/documents')) {
            $disk->makeDirectory($userDir . '/documents');
        }

        $mailSent = false;
        if ($member->email) {
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
        }

        // After creation, send admin directly to the member profile page to fill out all fields.
        $message = 'Socio creato con successo.';
        if ($member->email) {
            $message = $mailSent ? 'Socio creato e invito inviato via email.' : 'Socio creato. Email invito non inviata: copia il link e invialo manualmente.';
        }
        
        return redirect()
            ->to("/admin/members/{$member->id}")
            ->with('success', $message)
            ->with('invite_url', $member->email ? $inviteUrl : null);
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
            'email' => ['sometimes', 'nullable', 'string', 'email', 'max:255', Rule::unique('users')->ignore($member->id)],
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

        if (array_key_exists('role', $validated)) {
             // Access raw value to avoid strict Enum casting errors if DB has invalid/legacy string
             $currentRole = $member->getRawOriginal('role');
             if ($validated['role'] !== $currentRole) {
                 $this->authorize('manage-roles');
             }
        }

        if (array_key_exists('plv_joined_at', $validated)) {
            if (Schema::hasColumn('users', 'plv_expires_at')) {
                $validated['plv_expires_at'] = $validated['plv_joined_at']
                    ? Carbon::parse($validated['plv_joined_at'])->addYear()->toDateString()
                    : null;
            }
        }

        try {
            $member->update($validated);
        } catch (\Throwable $e) {
            Log::error('Update Member Failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            throw $e;
        }

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

    /**
     * Export all members to CSV
     */
    public function exportCsv()
    {
        $year = now()->year;
        
        $members = User::query()
            ->with(['memberships' => fn($q) => $q->where('year', $year)])
            ->orderBy('name')
            ->get();

        $filename = 'soci_' . now()->format('Y-m-d') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($members, $year) {
            $file = fopen('php://output', 'w');
            
            // UTF-8 BOM for Excel compatibility
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // CSV Header
            fputcsv($file, [
                'ID',
                'Nome visualizzato',
                'Nome',
                'Cognome',
                'Email',
                'Telefono',
                'Ruolo Applicativo',
                'Ruolo Pro Loco',
                'Data Nascita',
                'Luogo Nascita (IT/Estero)',
                'Provincia Nascita',
                'Città Nascita',
                'Nazione Nascita',
                'Residenza (IT/Estero)',
                'Via',
                'Numero Civico',
                'Frazione',
                'Provincia Residenza',
                'Città Residenza',
                'Nazione Residenza',
                'Data Iscrizione PLV',
                'Data Scadenza PLV',
                'Tessera ' . $year,
                'Stato Account',
            ]);

            // Data rows
            foreach ($members as $member) {
                try {
                    $hasMembership = $member->memberships && $member->memberships->count() > 0;
                    $mustSetPassword = $member->must_set_password ? 'Da attivare' : 'Attivo';
                    
                    // Safely get role value (avoid Enum casting errors)
                    $roleValue = 'member';
                    try {
                        $roleValue = $member->getRawOriginal('role') ?? 'member';
                    } catch (\Exception $e) {
                        $roleValue = 'member';
                    }
                    
                    fputcsv($file, [
                        $member->id,
                        $member->name,
                        $member->first_name ?? '',
                        $member->last_name ?? '',
                        $member->email,
                        $member->phone ?? '',
                        $roleValue,
                        $member->plv_role ?? '',
                        $member->birth_date ? $member->birth_date->format('Y-m-d') : '',
                        $member->birth_place_type ?? '',
                        $member->birth_province_code ?? '',
                        $member->birth_city ?? '',
                        $member->birth_country ?? '',
                        $member->residence_type ?? '',
                        $member->residence_street ?? '',
                        $member->residence_house_number ?? '',
                        $member->residence_locality ?? '',
                        $member->residence_province_code ?? '',
                        $member->residence_city ?? '',
                        $member->residence_country ?? '',
                        $member->plv_joined_at ? $member->plv_joined_at->format('Y-m-d') : '',
                        $member->plv_expires_at ? $member->plv_expires_at->format('Y-m-d') : '',
                        $hasMembership ? 'Sì' : 'No',
                        $mustSetPassword,
                    ]);
                } catch (\Exception $e) {
                    // Log the error but continue with other members
                    Log::warning('Error exporting member to CSV', [
                        'member_id' => $member->id ?? 'unknown',
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Import members from CSV
     */
    public function importCsv(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:5120', // 5MB max
        ]);

        $file = $request->file('file');
        $path = $file->getRealPath();
        
        $csvData = array_map('str_getcsv', file($path));
        
        // Remove BOM if present
        if (isset($csvData[0][0])) {
            $csvData[0][0] = preg_replace('/^\x{FEFF}/u', '', $csvData[0][0]);
        }
        
        $header = array_shift($csvData);
        
        // Map Italian headers to database columns
        $headerMap = [
            'Nome visualizzato' => 'name',
            'Nome' => 'first_name',
            'Nome (campo)' => 'first_name',
            'Cognome' => 'last_name',
            'Email' => 'email',
            'Telefono' => 'phone',
            'Ruolo Applicativo' => 'role',
            'Ruolo Pro Loco' => 'plv_role',
            'Data Nascita' => 'birth_date',
            'Luogo Nascita (IT/Estero)' => 'birth_place_type',
            'Provincia Nascita' => 'birth_province_code',
            'Città Nascita' => 'birth_city',
            'Nazione Nascita' => 'birth_country',
            'Residenza (IT/Estero)' => 'residence_type',
            'Via' => 'residence_street',
            'Numero Civico' => 'residence_house_number',
            'Frazione' => 'residence_locality',
            'Provincia Residenza' => 'residence_province_code',
            'Città Residenza' => 'residence_city',
            'Nazione Residenza' => 'residence_country',
            'Data Iscrizione PLV' => 'plv_joined_at',
        ];

        // Create index map
        $columnIndexes = [];
        foreach ($header as $index => $columnName) {
            $trimmed = trim($columnName);
            if (isset($headerMap[$trimmed])) {
                $columnIndexes[$headerMap[$trimmed]] = $index;
            } elseif (in_array($trimmed, array_values($headerMap))) {
                $columnIndexes[$trimmed] = $index;
            }
        }

        $imported = 0;
        $updated = 0;
        $errors = [];
        $currentYear = now()->year;

        foreach ($csvData as $rowIndex => $row) {
            $rowNumber = $rowIndex + 2; // +2 because we removed header and arrays are 0-indexed
            
            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }
            
            try {
                $data = [];
                
                // Extract data based on column mapping
                foreach ($columnIndexes as $field => $index) {
                    if (isset($row[$index])) {
                        $value = trim($row[$index]);
                        $data[$field] = $value === '' ? null : $value;
                    }
                }

                // Build full name from first_name + last_name if name is empty
                if (empty($data['name']) && (!empty($data['first_name']) || !empty($data['last_name']))) {
                    $data['name'] = trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? ''));
                }

                // Name is required
                if (empty($data['name'])) {
                    $errors[] = "Riga {$rowNumber}: Nome obbligatorio";
                    continue;
                }

                // Validate email format only if provided
                if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                    $errors[] = "Riga {$rowNumber}: Email non valida ({$data['email']})";
                    continue;
                }

                // Filter out columns that don't exist in the database
                $data = array_filter(
                    $data,
                    fn($_v, $k) => Schema::hasColumn('users', $k),
                    ARRAY_FILTER_USE_BOTH
                );

                // Calculate plv_expires_at if plv_joined_at is provided
                if (!empty($data['plv_joined_at']) && Schema::hasColumn('users', 'plv_expires_at')) {
                    try {
                        $data['plv_expires_at'] = Carbon::parse($data['plv_joined_at'])->addYear()->toDateString();
                    } catch (\Exception $e) {
                        // Invalid date format, skip expires_at calculation
                    }
                }

                // Find existing user by email, phone, or name (in that priority)
                $existingUser = null;
                if (!empty($data['email'])) {
                    $existingUser = User::where('email', $data['email'])->first();
                }
                // Fallback to phone if no email match
                if (!$existingUser && !empty($data['phone'])) {
                    $existingUser = User::where('phone', $data['phone'])->first();
                }

                if ($existingUser) {
                    // Update existing user
                    $existingUser->update($data);
                    $updated++;
                    
                    ActivityLog::create([
                        'actor_user_id' => $request->user()?->id,
                        'action' => 'updated',
                        'subject_type' => 'User',
                        'subject_id' => $existingUser->id,
                        'summary' => 'Aggiornato socio da import CSV: '.$existingUser->name,
                    ]);
                } else {
                    // Create new user
                    $data['password'] = Hash::make(Str::random(64));
                    $data['must_set_password'] = true;
                    $data['membership_status'] = 'inactive';
                    
                    $newUser = User::create($data);
                    $imported++;
                    
                    // Auto-activate membership for the current year
                    $newUser->memberships()->create([
                        'year' => $currentYear,
                        'paid_at' => now(),
                        'amount' => 0,
                        'qr_token' => (string) Str::uuid(),
                    ]);
                    
                    ActivityLog::create([
                        'actor_user_id' => $request->user()?->id,
                        'action' => 'created',
                        'subject_type' => 'User',
                        'subject_id' => $newUser->id,
                        'summary' => 'Creato socio da import CSV: '.$newUser->name,
                    ]);

                    // Create storage directories
                    $disk = Storage::disk('public');
                    $userDir = $newUser->id;
                    if (!$disk->exists($userDir . '/avatar')) {
                        $disk->makeDirectory($userDir . '/avatar');
                    }
                    if (!$disk->exists($userDir . '/documents')) {
                        $disk->makeDirectory($userDir . '/documents');
                    }
                }
            } catch (\Exception $e) {
                $errors[] = "Riga {$rowNumber}: {$e->getMessage()}";
            }
        }

        $message = "Import completato: {$imported} nuovi soci, {$updated} aggiornati";
        if (!empty($errors)) {
            $message .= '. Errori: ' . count($errors);
        }

        return redirect()
            ->back()
            ->with('success', $message)
            ->with('import_errors', $errors);
    }

    /**
     * Upload or update member avatar (admin)
     */
    public function updateMemberAvatar(Request $request, User $member)
    {
        $validated = $request->validate([
            'avatar' => ['required', 'image', 'max:2048'], // 2MB
        ]);

        // Delete old avatar (if any)
        if ($member->avatar_path) {
            Storage::disk('public')->delete($member->avatar_path);
        }

        $dir = $member->id.'/avatar';
        $ext = $validated['avatar']->extension() ?: 'jpg';
        $path = $validated['avatar']->storePubliclyAs($dir, 'avatar.'.$ext, 'public');

        $member->update(['avatar_path' => $path]);

        ActivityLog::create([
            'actor_user_id' => $request->user()?->id,
            'action' => 'updated',
            'subject_type' => User::class,
            'subject_id' => $member->id,
            'summary' => 'Avatar aggiornato da admin',
        ]);

        return redirect()->back()->with('success', 'Avatar caricato con successo.');
    }

    /**
     * Delete member avatar (admin)
     */
    public function destroyMemberAvatar(User $member)
    {
        if ($member->avatar_path) {
            Storage::disk('public')->delete($member->avatar_path);
        }

        $member->update(['avatar_path' => null]);

        ActivityLog::create([
            'actor_user_id' => request()->user()?->id,
            'action' => 'updated',
            'subject_type' => User::class,
            'subject_id' => $member->id,
            'summary' => 'Avatar rimosso da admin',
        ]);

        return redirect()->back()->with('success', 'Avatar eliminato con successo.');
    }
}
