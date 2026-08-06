<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\MedicationFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Medication extends Model
{
    /** @use HasFactory<MedicationFactory> */
    use HasFactory, HasUuids;

    protected $table = 'medications';

    protected $fillable = [
        'product_id',
        'manufacture_id',
        'form',
        'arabic_form',
        'usage_id',
        'status',
        'rejection_reason',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    public function manufacture(): BelongsTo
    {
        return $this->belongsTo(Manufacture::class, 'manufacture_id');
    }

    public function usage(): BelongsTo
    {
        return $this->belongsTo(Usage::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function activeIngredients(): BelongsToMany
    {
        return $this->belongsToMany(ActiveIngredient::class, 'medication_ingredients')
            ->withPivot('active_ratio');
    }

    public function medicationIngredients(): HasMany
    {
        return $this->hasMany(MedicationIngredient::class);
    }

    public function medicationPatients(): HasMany
    {
        return $this->hasMany(MedicationPatient::class);
    }

    public function pharmacyInventories(): HasMany
    {
        return $this->hasMany(PharmacyInventory::class);
    }
}
