<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Conversation extends Model
{
    protected $fillable = [
        'user_id',
        'title',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function belongsToCurrentUser(): bool
    {
        return $this->user_id === auth()->id();
    }

    public function rememberTitle(string $content): void
    {
        if ($this->title) {
            return;
        }

        $this->update(['title' => Str::limit(trim($content), 60)]);
    }
}
