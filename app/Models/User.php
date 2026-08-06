<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, HasUuids, Notifiable, SoftDeletes;

    protected string $guard_name = 'api';

    protected $fillable = [
        'f_name',
        'l_name',
        'age',
        'gender',
        'phone_number',
        'location',
        'email',
        'password',
        'google2fa_secret',
        'google2fa_enabled',
        'google2fa_recovery_codes',
    ];

    protected $hidden = [
        'password',
        'google2fa_secret',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'email_verified_at' => 'datetime',
            'google2fa_enabled' => 'boolean',
            'google2fa_recovery_codes' => 'array',
        ];
    }

    public function pharmacist(): HasOne
    {
        return $this->hasOne(Pharmacist::class);
    }

    public function patient(): HasOne
    {
        return $this->hasOne(Patient::class);
    }

    public function doctor(): HasOne
    {
        return $this->hasOne(Doctor::class);
    }

    public function scientificRep(): HasOne
    {
        return $this->hasOne(ScientificRep::class);
    }

    public function specialist(): HasOne
    {
        return $this->hasOne(Specialist::class);
    }

    public function pharmaceuticalCompany(): HasOne
    {
        return $this->hasOne(PharmaceuticalCompany::class, 'owner_id');
    }
}
