<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    protected $fillable = [
        'conversation_id',
        'role',
        'content',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function toPayload(): array
    {
        return [
            'content' => $this->content,
            'role' => $this->role,
            'time' => $this->created_at->format('g:i A'),
        ];
    }
}
