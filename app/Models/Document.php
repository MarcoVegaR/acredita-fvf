<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Document extends Model
{
    use HasFactory, HasUuids;
    
    protected $fillable = [
        'document_type_id',
        'user_id',
        'filename',
        'original_filename',
        'file_size',
        'mime_type',
        'path',
        'is_validated'
    ];
    
    protected $casts = [
        'is_validated' => 'boolean',
        'file_size' => 'integer'
    ];
    
    /**
     * Get the columns that should receive a unique identifier.
     *
     * @return array<int, string>
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }
    
    public function type()
    {
        return $this->belongsTo(DocumentType::class, 'document_type_id');
    }
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function documentables()
    {
        return $this->hasMany(Documentable::class);
    }
    
    public function scopeUnvalidated($query)
    {
        return $query->where('is_validated', false);
    }
}
