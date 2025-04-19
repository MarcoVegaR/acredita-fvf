<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentType extends Model
{
    use HasFactory;
    
    protected $fillable = ['code', 'label', 'module'];
    
    public function documents()
    {
        return $this->hasMany(Document::class);
    }
    
    public function scopeForModule($query, $moduleCode)
    {
        return $query->where('module', $moduleCode)
            ->orWhereNull('module'); // Also include global types
    }
}
