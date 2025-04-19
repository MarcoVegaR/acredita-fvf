<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Imageable extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'image_id',
        'imageable_id',
        'imageable_type',
        'image_type_id',
    ];

    /**
     * Get the image that owns the imageable.
     */
    public function image(): BelongsTo
    {
        return $this->belongsTo(Image::class);
    }

    /**
     * Get the image type that owns the imageable.
     */
    public function imageType(): BelongsTo
    {
        return $this->belongsTo(ImageType::class);
    }

    /**
     * Get the parent imageable model.
     */
    public function imageable(): MorphTo
    {
        return $this->morphTo();
    }
}
