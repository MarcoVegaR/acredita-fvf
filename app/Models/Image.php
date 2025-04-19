<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Image extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'path',
        'mime_type',
        'size',
        'width',
        'height',
        'created_by',
    ];
    
    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'url',
        'thumbnail_url',
        'thumbnail_path'
    ];

    /**
     * The "boot" method of the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-generate UUID on creation
        static::creating(function ($model) {
            $model->uuid = $model->uuid ?? (string) \Illuminate\Support\Str::uuid();
        });
    }

    /**
     * Get the columns that should receive a UUID.
     *
     * @return array
     */
    public function uniqueIds()
    {
        return ['uuid'];
    }

    /**
     * Scope a query to only include unattached images.
     */
    public function scopeUnattached($query)
    {
        return $query->whereDoesntHave('imageables');
    }

    /**
     * Get the user that created the image.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get all of the models that belong to the image.
     */
    public function imageables()
    {
        return $this->hasMany(Imageable::class);
    }

    /**
     * Get the thumbnail path for this image.
     */
    public function getThumbnailPathAttribute(): string
    {
        $pathInfo = pathinfo($this->path);
        return $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '_thumb.' . $pathInfo['extension'];
    }

    /**
     * Get the full URL to the image.
     */
    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->path);
    }

    /**
     * Get the full URL to the thumbnail.
     */
    public function getThumbnailUrlAttribute(): string
    {
        return asset('storage/' . $this->thumbnail_path);
    }
}
