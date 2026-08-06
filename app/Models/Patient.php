<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\PatientFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Patient extends Model
{
    /** @use HasFactory<PatientFactory> */
    use HasFactory, HasUuids;

    protected $table = 'patients';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function chronicRecords(): HasMany
    {
        return $this->hasMany(ChronicRecord::class);
    }

    public function medicationPatients(): HasMany
    {
        return $this->hasMany(MedicationPatient::class);
    }

    public function medicationOrders(): HasMany
    {
        return $this->hasMany(MedicationOrder::class);
    }

    public function pharmacyReviews(): HasMany
    {
        return $this->hasMany(PharmacyReview::class);
    }
}
