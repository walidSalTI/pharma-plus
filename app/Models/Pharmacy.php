<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\PharmacyFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pharmacy extends Model
{
    /** @use HasFactory<PharmacyFactory> */
    use HasFactory, HasUuids;

    protected $table = 'pharmacies';

    protected $fillable = [
        'pharmacist_id',
        'name',
        'address',
        'latitude',
        'longitude',
        'support_email',
        'support_number',
        'front_image',
    ];

    public function pharmacist(): BelongsTo
    {
        return $this->belongsTo(Pharmacist::class);
    }

    public function staffPharmacists(): BelongsToMany
    {
        return $this->belongsToMany(Pharmacist::class, 'pharmacy_pharmacist')
            ->withPivot([
                'pharmacy_manage',
                'inventory_manage',
                'operating_hours_manage',
                'orders_process',
                'orders_view_own',
            ])
            ->withTimestamps();
    }

    public function pharmacyOperatingHours(): HasMany
    {
        return $this->hasMany(PharmacyOperatingHour::class);
    }

    public function pharmacyInventories(): HasMany
    {
        return $this->hasMany(PharmacyInventory::class);
    }

    public function medicationOrders(): HasMany
    {
        return $this->hasMany(MedicationOrder::class);
    }

    public function pharmacyReviews(): HasMany
    {
        return $this->hasMany(PharmacyReview::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'added_by_pharmacy_id');
    }
}
