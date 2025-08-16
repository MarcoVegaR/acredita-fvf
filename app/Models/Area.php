<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Area extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'code',
        'name',
        'description',
        'active',
        'manager_user_id',
        'color',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }
    
    /**
     * Boot function from Laravel.
     * 
     * @return void
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($area) {
            if (!$area->uuid) {
                $area->uuid = Str::uuid()->toString();
            }
        });
    }

    /**
     * Scope a query to only include active areas.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
    
    /**
     * Scope a query to only include inactive areas.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInactive($query)
    {
        return $query->where('active', false);
    }
    
    /**
     * Get the providers belonging to this area.
     */
    public function providers()
    {
        return $this->hasMany(Provider::class);
    }
    
    /**
     * Get the manager (user) assigned to this area.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_user_id');
    }
}
