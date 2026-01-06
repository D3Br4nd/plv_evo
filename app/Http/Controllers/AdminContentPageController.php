<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\ContentPage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class AdminContentPageController extends Controller
{
    public function index()
    {
        $pages = ContentPage::query()
            ->orderByDesc('updated_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/ContentPages/Index', [
            'pages' => $pages,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'excerpt' => 'nullable|string|max:500',
            'body' => 'required|string',
            'status' => 'required|string|in:draft,published',
        ]);

        $slug = $validated['slug'] ?: Str::slug($validated['title']);

        $page = ContentPage::create([
            'title' => $validated['title'],
            'slug' => $slug,
            'excerpt' => $validated['excerpt'] ?? null,
            'body' => $validated['body'],
            'status' => $validated['status'],
            'published_at' => $validated['status'] === 'published' ? now() : null,
            'created_by_user_id' => $request->user()->id,
            'updated_by_user_id' => $request->user()->id,
        ]);

        ActivityLog::create([
            'actor_user_id' => $request->user()?->id,
            'action' => 'created',
            'subject_type' => 'ContentPage',
            'subject_id' => $page->id,
            'summary' => 'Creata pagina contenuto: '.$page->title,
            'meta' => ['slug' => $page->slug],
        ]);

        return redirect()->back()->with('success', 'Pagina creata.');
    }

    public function update(Request $request, ContentPage $content_page)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:content_pages,slug,'.$content_page->id.',id',
            'excerpt' => 'nullable|string|max:500',
            'body' => 'required|string',
            'status' => 'required|string|in:draft,published',
        ]);

        $content_page->update([
            'title' => $validated['title'],
            'slug' => $validated['slug'],
            'excerpt' => $validated['excerpt'] ?? null,
            'body' => $validated['body'],
            'status' => $validated['status'],
            'published_at' => $validated['status'] === 'published'
                ? ($content_page->published_at ?? now())
                : null,
            'updated_by_user_id' => $request->user()->id,
        ]);

        ActivityLog::create([
            'actor_user_id' => $request->user()?->id,
            'action' => 'updated',
            'subject_type' => 'ContentPage',
            'subject_id' => $content_page->id,
            'summary' => 'Aggiornata pagina contenuto: '.$content_page->title,
            'meta' => ['slug' => $content_page->slug],
        ]);

        return redirect()->back()->with('success', 'Pagina aggiornata.');
    }

    public function destroy(ContentPage $content_page)
    {
        $summary = 'Eliminata pagina contenuto: '.$content_page->title;
        $content_page->delete();

        ActivityLog::create([
            'actor_user_id' => request()->user()?->id,
            'action' => 'deleted',
            'subject_type' => 'ContentPage',
            'subject_id' => $content_page->id,
            'summary' => $summary,
            'meta' => ['slug' => $content_page->slug],
        ]);

        return redirect()->back()->with('success', 'Pagina eliminata.');
    }
}


