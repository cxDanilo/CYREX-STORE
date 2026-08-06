<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    protected $table = 'media';

    protected $fillable = [
        'disk', 'folder_id', 'path', 'webp_path', 'thumb_path',
        'original_name', 'mime_type', 'size', 'alt_text', 'uploaded_by',
    ];

    protected $attributes = [
        'disk' => 'uploads',
    ];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(MediaFolder::class, 'folder_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'media_tag');
    }

    public function getUrlAttribute(): ?string
    {
        return $this->path ? Storage::disk($this->disk)->url($this->path) : null;
    }

    public function getWebpUrlAttribute(): ?string
    {
        return $this->webp_path ? Storage::disk($this->disk)->url($this->webp_path) : null;
    }

    public function getThumbUrlAttribute(): ?string
    {
        $path = $this->thumb_path ?: $this->path;

        return $path ? Storage::disk($this->disk)->url($path) : null;
    }
}
