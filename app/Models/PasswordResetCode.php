<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PasswordResetCode extends Model
{
    public const TTL_MINUTES = 10;

    public const MAX_ATTEMPTS = 5;

    protected $fillable = [
        'email',
        'code_hash',
        'attempts',
        'expires_at',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    public static function generateFor(string $email): string
    {
        static::where('email', $email)->delete();

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        static::create([
            'email' => $email,
            'code_hash' => Hash::make($code),
            'expires_at' => Carbon::now()->addMinutes(self::TTL_MINUTES),
        ]);

        return $code;
    }

    public static function activeFor(string $email): ?self
    {
        return static::where('email', $email)
            ->where('expires_at', '>', Carbon::now())
            ->where('attempts', '<', self::MAX_ATTEMPTS)
            ->latest('id')
            ->first();
    }

    public function matches(string $code): bool
    {
        if (! Hash::check($code, $this->code_hash)) {
            $this->increment('attempts');

            return false;
        }

        return true;
    }

    public function markVerified(): string
    {
        $token = Str::random(64);

        $this->forceFill([
            'verified_at' => Carbon::now(),
            'code_hash' => Hash::make($token),
            'expires_at' => Carbon::now()->addMinutes(self::TTL_MINUTES),
            'attempts' => 0,
        ])->save();

        return $token;
    }
}
