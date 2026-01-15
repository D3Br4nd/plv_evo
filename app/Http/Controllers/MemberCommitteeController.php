<?php

namespace App\Http\Controllers;

use App\Models\Committee;
use App\Models\CommitteePost;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MemberCommitteeController extends Controller
{
    /**
     * Display a listing of committees the user belongs to.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        $committees = $user->committees()
            ->withCount(['posts as unread_posts_count' => function ($query) use ($user) {
                $query->whereDoesntHave('readers', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            }])
            ->get();

        return Inertia::render('Member/Committees/Index', [
            'committees' => $committees,
        ]);
    }

    /**
     * Display the specified committee.
     */
    public function show(Request $request, Committee $committee)
    {
        $user = $request->user();
        
        // Ensure the user is a member of this committee, or is an admin
        $userRole = $user->role instanceof \UnitEnum ? $user->role->value : $user->role;
        $isMember = $committee->members()->where('users.id', $user->id)->exists();
        $isAdmin = in_array($userRole, ['admin', 'super_admin'], true);

        if (!$isMember && !$isAdmin) {
            abort(403);
        }

        $committee->load([
            'members' => function ($query) {
                $query->select('users.id', 'users.name', 'users.avatar_path')
                    ->withPivot('role');
            },
            'posts' => function ($query) use ($user) {
                $query->with('author:id,name,avatar_path')
                    ->withExists(['readers as is_read' => function ($q) use ($user) {
                        $q->where('user_id', $user->id);
                    }]);
            }
        ]);

        return Inertia::render('Member/Committees/Show', [
            'committee' => $committee,
        ]);
    }

    /**
     * Mark a committee post as read.
     */
    public function markAsRead(Request $request, CommitteePost $post)
    {
        $user = $request->user();

        // Ensure user belongs to the committee of this post, or is an admin
        $userRole = $user->role instanceof \UnitEnum ? $user->role->value : $user->role;
        $isMember = $post->committee->members()->where('users.id', $user->id)->exists();
        $isAdmin = in_array($userRole, ['admin', 'super_admin'], true);

        if (!$isMember && !$isAdmin) {
            abort(403);
        }

        $post->readers()->syncWithoutDetaching([$user->id => ['read_at' => now()]]);

        return back();
    }

    /**
     * Display a single committee post.
     */
    public function showPost(Request $request, CommitteePost $post)
    {
        $user = $request->user();

        // Ensure user belongs to the committee of this post, or is an admin
        $userRole = $user->role instanceof \UnitEnum ? $user->role->value : $user->role;
        $isMember = $post->committee->members()->where('users.id', $user->id)->exists();
        $isAdmin = in_array($userRole, ['admin', 'super_admin'], true);

        if (!$isMember && !$isAdmin) {
            abort(403);
        }

        $post->load('author:id,name,avatar_path');
        
        // Mark as read automatically when viewing the detail
        $post->readers()->syncWithoutDetaching([$user->id => ['read_at' => now()]]);

        return Inertia::render('Member/Committees/Post', [
            'post' => $post,
            'committee' => $post->committee,
        ]);
    }
}
