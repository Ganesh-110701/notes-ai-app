<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Note extends Model
{
    use HasFactory;

    // "embedding" and "summary" are set by the app itself, never mass-assigned.
    protected $fillable = [
        'title',
        'content',
    ];

    protected function casts(): array
    {
        return [
            'embedding' => 'array',
        ];
    }
}
