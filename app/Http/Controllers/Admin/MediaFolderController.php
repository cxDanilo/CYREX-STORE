<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MediaFolder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class MediaFolderController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);

        $slug = Str::slug($data['name']);
        $original = $slug;
        $i = 1;

        while (MediaFolder::where('slug', $slug)->exists()) {
            $slug = $original.'-'.(++$i);
        }

        MediaFolder::create(['name' => $data['name'], 'slug' => $slug]);

        return back()->with('status', 'Carpeta creada.');
    }

    public function destroy(MediaFolder $folder)
    {
        // nullOnDelete en la FK — los archivos NO se borran, solo quedan sin carpeta.
        $folder->delete();

        return back()->with('status', 'Carpeta eliminada. Los archivos que tenía siguen disponibles, sin carpeta.');
    }
}
