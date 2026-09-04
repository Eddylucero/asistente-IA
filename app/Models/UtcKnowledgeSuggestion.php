<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UtcKnowledgeSuggestion extends Model
{
    protected $fillable = [
        'user_id',
        'conversation_id',
        'question',
        'answer',
        'status',
    ];
}