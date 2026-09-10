<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SavedBuild;

class SavedBuildController extends Controller
{
    public function index()
    {
        $builds = SavedBuild::withCount('items')->latest()->paginate(20);

        return view('admin.saved-builds.index', compact('builds'));
    }

    public function show(SavedBuild $savedBuild)
    {
        $savedBuild->load('items');

        return view('admin.saved-builds.show', ['build' => $savedBuild]);
    }

    public function approve(SavedBuild $savedBuild)
    {
        $savedBuild->update(['status' => 'approved', 'approved_at' => now()]);

        return back()->with('status', 'Armado publicado en la galería pública.');
    }

    public function reject(SavedBuild $savedBuild)
    {
        $savedBuild->update(['status' => 'rejected', 'approved_at' => null]);

        return back()->with('status', 'Armado rechazado — ya no es visible en la galería.');
    }

    public function destroy(SavedBuild $savedBuild)
    {
        $savedBuild->delete();

        return back()->with('status', 'Armado eliminado.');
    }
}
