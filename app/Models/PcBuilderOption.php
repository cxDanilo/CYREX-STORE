<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PcBuilderOption extends Model
{
    protected $fillable = ['group', 'value', 'label', 'sort_order'];

    public function scopeGroup($query, string $group)
    {
        return $query->where('group', $group)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @return array<string,string> value => label, en el mismo formato que
     *         antes tenían hardcodeado los arrays de config/pc_builder.php.
     */
    public static function optionsFor(string $group): array
    {
        return static::group($group)->pluck('label', 'value')->all();
    }
}
