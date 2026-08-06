<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChronicRecord extends Model
{
    use HasUuids;

    protected $table = 'chronic_records';

    public function chronicDisease(): BelongsTo
    {
        return $this->belongsTo(ChronicDisease::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function medicationPatients(): HasMany
    {
        return $this->hasMany(MedicationPatient::class, 'chronic_id');
    }
}
