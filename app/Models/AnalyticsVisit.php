<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AnalyticsVisit extends Model
{
    // first_seen_at/last_seen_at ya cubren lo que created_at/updated_at
    // harían acá — no hace falta duplicar columnas de fecha.
    public $timestamps = false;

    protected $fillable = [
        'session_id',
        'ip_address',
        'user_agent',
        'device_type',
        'referrer_domain',
        'entry_url',
        'entry_label',
        'exit_url',
        'exit_label',
        'page_count',
        'first_seen_at',
        'last_seen_at',
    ];

    protected $casts = [
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    public function pageViews(): HasMany
    {
        return $this->hasMany(AnalyticsPageView::class, 'visit_id');
    }

    public function searches(): HasMany
    {
        return $this->hasMany(AnalyticsSearch::class, 'visit_id');
    }
}
