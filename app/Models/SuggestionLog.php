<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SuggestionLog extends Model
{
    use HasFactory;

    protected $table = 'suggestion_logs';

    protected $fillable = [
        'budget',
        'mood',
        'results_count',
        'chosen',
    ];

    protected $casts = [
        'budget' => 'integer',
        'results_count' => 'integer',
        'chosen' => 'boolean',
    ];
}

