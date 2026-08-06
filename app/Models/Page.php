<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Page extends Model
{
    protected $fillable = [
        'template_id', 'title', 'slug', 'meta_title', 'meta_description',
        'status', 'published_at', 'show_in_footer', 'footer_sort_order',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'show_in_footer' => 'boolean',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    public function blocks(): HasMany
    {
        return $this->hasMany(PageBlock::class)->orderBy('sort_order');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeInFooter($query)
    {
        return $query->where('show_in_footer', true)->orderBy('footer_sort_order');
    }
}
