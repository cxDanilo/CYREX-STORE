<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SocialLink;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SocialLinkController extends Controller
{
    private const PLATFORMS = ['instagram', 'facebook', 'tiktok', 'youtube', 'x'];

    public function index()
    {
        $socialLinks = SocialLink::ordered()->get();

        return view('admin.social-links.index', compact('socialLinks'));
    }

    public function create()
    {
        $socialLink = new SocialLink(['sort_order' => 0]);

        return view('admin.social-links.form', ['socialLink' => $socialLink, 'platforms' => self::PLATFORMS]);
    }

    public function store(Request $request)
    {
        SocialLink::create($this->validated($request));

        return redirect()->route('admin.redes.index')->with('status', 'Red social agregada.');
    }

    public function edit(SocialLink $socialLink)
    {
        return view('admin.social-links.form', ['socialLink' => $socialLink, 'platforms' => self::PLATFORMS]);
    }

    public function update(Request $request, SocialLink $socialLink)
    {
        $socialLink->update($this->validated($request));

        return redirect()->route('admin.redes.index')->with('status', 'Red social actualizada.');
    }

    public function destroy(SocialLink $socialLink)
    {
        $socialLink->delete();

        return back()->with('status', 'Red social eliminada.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'platform' => ['required', Rule::in(self::PLATFORMS)],
            'url' => ['required', 'url', 'max:255'],
            'sort_order' => ['required', 'integer', 'min:0'],
        ]);
    }
}
