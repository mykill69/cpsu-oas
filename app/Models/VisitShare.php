<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class VisitShare extends Model
{
    public const OTP_MINUTES = 60;
    public const OTP_MAX_ATTEMPTS = 5;
    public const RESEND_SECONDS = 60;

    protected $fillable = ['college_id', 'survey_visit_id', 'name', 'email', 'expires_at', 'note', 'created_by'];

    protected $hidden = ['token_hash', 'otp_hash'];

    protected $casts = [
        'otp_expires_at' => 'datetime',
        'otp_sent_at' => 'datetime',
        'emailed_at' => 'datetime',
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
        'first_verified_at' => 'datetime',
        'last_viewed_at' => 'datetime',
    ];

    public function college()
    {
        return $this->belongsTo(College::class);
    }

    public function surveyVisit()
    {
        return $this->belongsTo(SurveyVisit::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function findByToken(string $token): ?self
    {
        return strlen($token) === 48 ? static::where('token_hash', hash('sha256', $token))->first() : null;
    }

    /** New random link token; returns the raw value (only the hash is stored). */
    public function issueToken(): string
    {
        $token = Str::random(48);
        $this->token_hash = hash('sha256', $token);

        return $token;
    }

    /** New 6-digit one-time code; returns the raw value (only the hash is stored). */
    public function issueOtp(): string
    {
        $code = (string) random_int(100000, 999999);
        $this->forceFill([
            'otp_hash' => Hash::make($code),
            'otp_expires_at' => now()->addMinutes(self::OTP_MINUTES),
            'otp_attempts' => 0,
            'otp_sent_at' => now(),
        ])->save();

        return $code;
    }

    /** Checks and burns the code. Wrong guesses count toward a lockout. */
    public function consumeOtp(string $code): bool
    {
        if (! $this->otp_hash || $this->otp_expires_at?->isPast() || $this->otp_attempts >= self::OTP_MAX_ATTEMPTS) {
            return false;
        }

        if (! Hash::check($code, $this->otp_hash)) {
            $this->increment('otp_attempts');

            return false;
        }

        $this->forceFill(['otp_hash' => null, 'otp_expires_at' => null, 'otp_attempts' => 0])->save();
        $this->first_verified_at ??= now();
        $this->save();

        return true;
    }

    public function isUsable(): bool
    {
        return ! $this->revoked_at && $this->expires_at->isFuture();
    }

    public function canResend(): bool
    {
        return ! $this->otp_sent_at || $this->otp_sent_at->lte(now()->subSeconds(self::RESEND_SECONDS));
    }

    public function getStatusAttribute(): string
    {
        if ($this->revoked_at) return 'revoked';
        if ($this->expires_at->isPast()) return 'expired';

        return $this->first_verified_at ? 'active' : 'invited';
    }
}
