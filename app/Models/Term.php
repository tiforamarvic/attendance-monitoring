<?php

namespace App\Models;

use Database\Factories\TermFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Term extends Model
{
    /** @use HasFactory<TermFactory> */
    use HasFactory;

    protected $fillable = [
        'start_date',
        'end_date',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }
}
