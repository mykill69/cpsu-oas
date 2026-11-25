<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasRoles, Notifiable;

    public const ROLE_LABELS = [
        'qa_director' => 'QA Director',
        'administrator' => 'Administrator',
        'campus_incharge' => 'Campus / College In-charge',
        'staff' => 'Staff',
    ];

    protected $fillable = ['name', 'email', 'password', 'college_id'];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function college()
    {
        return $this->belongsTo(College::class);
    }

    public function isQa(): bool
    {
        return $this->hasAnyRole(['qa_director', 'administrator']);
    }

    public function isInCharge(): bool
    {
        return $this->hasRole('campus_incharge');
    }

    public function getRoleLabelAttribute()
    {
        $role = $this->getRoleNames()->first();

        return self::ROLE_LABELS[$role] ?? ($role ? ucwords(str_replace('_', ' ', $role)) : 'No role');
    }
}
