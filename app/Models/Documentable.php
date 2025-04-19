<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Documentable extends Model
{
    use HasFactory;
    // Only use HasUuids if uuid is not null
    use HasUuids;
    
    protected $fillable = [
        'document_id',
        'documentable_id',
        'documentable_type'
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
    
    public function document()
    {
        return $this->belongsTo(Document::class);
    }
    
    public function documentable()
    {
        return $this->morphTo();
    }
}
