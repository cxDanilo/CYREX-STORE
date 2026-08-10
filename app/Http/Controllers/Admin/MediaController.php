<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\MediaFolder;
use App\Models\Tag;
use App\Support\ImageOptimizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaController extends Controller
{
    public function index(Request $request)
    {
        $query = Media::with(['folder', 'tags'])->orderByDesc('created_at');

        if ($request->filled('folder')) {
            $query->where('folder_id', $request->folder);
        }

        if ($request->filled('q')) {
            $term = $request->q;
            $query->where(function ($q) use ($term) {
                $q->where('original_name', 'like', "%{$term}%")
                    ->orWhere('alt_text', 'like', "%{$term}%");
            });
        }

        if ($request->filled('tag')) {
            $query->whereHas('tags', fn ($q) => $q->where('slug', $request->tag));
        }

        $media = $query->paginate(24)->withQueryString();
        $folders = MediaFolder::withCount('media')->orderBy('name')->get();
        $tags = Tag::orderBy('name')->get();

        return view('admin.media.index', compact('media', 'folders', 'tags'));
    }

    public function store(Request $request)
    {
        // SVG excluido a propósito: puede contener <script>/JS embebido y se
        // sirve tal cual (GD no puede rasterizarlo para optimizarlo), así
        // que sin un sanitizador dedicado es un vector de XSS almacenado.
        $request->validate([
            'files' => ['required', 'array'],
            'files.*' => ['image', 'mimes:jpg,jpeg,png,webp,gif', 'max:8192'],
            'folder_id' => ['nullable', 'exists:media_folders,id'],
            'tags' => ['nullable', 'string'],
        ]);

        $tagIds = $this->resolveTagIds($request->input('tags'));
        $created = [];

        foreach ($request->file('files', []) as $file) {
            $extension = $file->getClientOriginalExtension();
            $filename = 'media/'.Str::random(24).'.'.$extension;

            $file->storeAs('', $filename, 'uploads');

            $absolutePath = Storage::disk('uploads')->path($filename);

            if ($request->boolean('trim')) {
                ImageOptimizer::trimTransparentPadding($absolutePath, $file->getMimeType());
            }

            $derivatives = ImageOptimizer::process($absolutePath, $filename, $file->getMimeType());

            $media = Media::create([
                'folder_id' => $request->input('folder_id') ?: null,
                'path' => $filename,
                'webp_path' => $derivatives['webp'],
                'thumb_path' => $derivatives['thumb'],
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => Storage::disk('uploads')->size($filename),
                'uploaded_by' => auth()->id(),
            ]);

            if (! empty($tagIds)) {
                $media->tags()->sync($tagIds);
            }

            $created[] = $media;
        }

        if ($request->wantsJson()) {
            return response()->json([
                'created' => count($created),
                'items' => collect($created)->map(fn (Media $m) => [
                    'id' => $m->id,
                    'url' => $m->url,
                    'thumb_url' => $m->thumb_url,
                    'original_name' => $m->original_name,
                ])->values(),
            ]);
        }

        return back()->with('status', count($created).' archivo(s) subido(s).');
    }

    public function update(Request $request, Media $medium)
    {
        $request->validate([
            'alt_text' => ['nullable', 'string', 'max:255'],
            'folder_id' => ['nullable', 'exists:media_folders,id'],
            'tags' => ['nullable', 'string'],
            'file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:8192'],
        ]);

        $medium->alt_text = $request->input('alt_text');
        $medium->folder_id = $request->input('folder_id') ?: null;

        if ($request->hasFile('file')) {
            $file = $request->file('file');

            // Reemplaza el archivo EN LA MISMA ruta — cualquier lugar del
            // sitio que ya use esta URL (un producto, un bloque del CMS)
            // muestra el archivo nuevo automáticamente, sin tener que
            // actualizar esas referencias una por una.
            $file->storeAs('', $medium->path, 'uploads');

            $absolutePath = Storage::disk('uploads')->path($medium->path);
            $derivatives = ImageOptimizer::reprocess($absolutePath, $medium->path, $file->getMimeType());

            $medium->webp_path = $derivatives['webp'];
            $medium->thumb_path = $derivatives['thumb'];
            $medium->original_name = $file->getClientOriginalName();
            $medium->mime_type = $file->getMimeType();
            $medium->size = Storage::disk('uploads')->size($medium->path);
        }

        $medium->save();
        $medium->tags()->sync($this->resolveTagIds($request->input('tags')));

        return back()->with('status', 'Archivo actualizado.');
    }

    public function destroy(Media $medium)
    {
        foreach (array_filter([$medium->path, $medium->webp_path, $medium->thumb_path]) as $path) {
            Storage::disk($medium->disk)->delete($path);
        }

        $medium->delete();

        return back()->with('status', 'Archivo eliminado.');
    }

    private function resolveTagIds(?string $tagsInput): array
    {
        if (! $tagsInput) {
            return [];
        }

        $names = array_filter(array_map('trim', explode(',', $tagsInput)));

        return collect($names)->map(function ($name) {
            return Tag::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name])->id;
        })->values()->all();
    }
}
