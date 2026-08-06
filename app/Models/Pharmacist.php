<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\PharmacistFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pharmacist extends Model
{
    /** @use HasFactory<PharmacistFactory> */
    use HasFactory, HasUuids;

    protected $table = 'pharmacists';

    protected $fillable = [
        'user_id',
        'syndicate_card',
        'verification_status',
    ];

    protected function casts(): array
    {
        return [
            'verification_status' => 'string',
        ];
    }

    public function isVerified(): bool
    {
        return $this->verification_status === 'approved';
    }

    public function isOwnerOf(Pharmacy $pharmacy): bool
    {
        return $this->id === $pharmacy->pharmacist_id;
    }

    public function resolvePharmacy(Pharmacy $pharmacy): ?Pharmacy
    {
        if ($this->isOwnerOf($pharmacy)) {
            return $pharmacy;
        }

        return $this->staffPharmacies()->where('pharmacy_id', $pharmacy->id)->first();
    }

    public function hasAccessTo(Pharmacy $pharmacy): bool
    {
        if ($this->isOwnerOf($pharmacy)) {
            return true;
        }

        return $this->staffPharmacies()->where('pharmacy_id', $pharmacy->id)->exists();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function pharmacies(): HasMany
    {
        return $this->hasMany(Pharmacy::class, 'pharmacist_id');
    }

    public function staffPharmacies(): BelongsToMany
    {
        return $this->belongsToMany(Pharmacy::class, 'pharmacy_pharmacist')
            ->withPivot([
                'pharmacy_manage',
                'inventory_manage',
                'operating_hours_manage',
                'orders_process',
                'orders_view_own',
                'salary',
            ])->withCasts([
                'pharmacy_manage' => 'boolean',
                'inventory_manage' => 'boolean',
                'operating_hours_manage' => 'boolean',
                'orders_process' => 'boolean',
                'orders_view_own' => 'boolean',
                'salary' => 'float',
            ])
            ->withTimestamps();
    }

    public function medicationProposals(): HasMany
    {
        return $this->hasMany(MedicationProposal::class);
    }
}
