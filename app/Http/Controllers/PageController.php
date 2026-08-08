<?php

namespace App\Http\Controllers;

use App\Models\Page;

class PageController extends Controller
{
    public function home()
    {
        $page = Page::published()->where('slug', 'home')->with('blocks')->firstOrFail();

        return view('cms.show', compact('page'));
    }

    public function show(string $slug)
    {
        $page = Page::published()->where('slug', $slug)->with('blocks')->firstOrFail();

        return view('cms.show', compact('page'));
    }
}
