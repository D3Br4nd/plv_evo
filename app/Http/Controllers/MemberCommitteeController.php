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
        
        // Ensure the user is a member of this committee
        if (!$committee->members()->where('users.id', $user->id)->exists()) {
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

        // Ensure user belongs to the committee of this post
        if (!$post->committee->members()->where('users.id', $user->id)->exists()) {
            abort(403);
        }

        $post->readers()->syncWithoutDetaching([$user->id => ['read_at' => now()]]);

        return back();
    }
}
