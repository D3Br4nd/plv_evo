<?php

namespace App\Http\Controllers;

use App\Models\ContentPage;
use Inertia\Inertia;

class PublicContentPageController extends Controller
{
    public function show(string $slug)
    {
        $page = ContentPage::query()
            ->where('slug', $slug)
            ->where('status', 'published')
            ->firstOrFail();

        return Inertia::render('Public/Page', [
            'page' => $page,
        ]);
    }

    public function showMember(string $slug)
    {
        $page = ContentPage::query()
            ->where('slug', $slug)
            ->where('status', 'published')
            ->firstOrFail();

        return Inertia::render('Member/ContentPage', [
            'page' => $page,
        ]);
    }
}


