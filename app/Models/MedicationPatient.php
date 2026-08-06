<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MedicationPatient extends Model
{
    use HasUuids;

    protected $table = 'medication_patients';

    public function medication(): BelongsTo
    {
        return $this->belongsTo(Medication::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function chronicRecord(): BelongsTo
    {
        return $this->belongsTo(ChronicRecord::class, 'chronic_id');
    }

    public function medicationSchedules(): HasMany
    {
        return $this->hasMany(MedicationSchedule::class);
    }
}
