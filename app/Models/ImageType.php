<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImageType extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'code',
        'label',
        'module',
    ];

    /**
     * Get the images for this image type.
     */
    public function images(): HasMany
    {
        return $this->hasMany(Imageable::class);
    }

    /**
     * Scope a query to only include image types for a specific module.
     */
    public function scopeForModule($query, string $module)
    {
        return $query->where('module', $module);
    }
}
