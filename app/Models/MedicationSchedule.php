<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MedicationSchedule extends Model
{
    use HasUuids;

    protected $table = 'medication_schedules';

    public function medicationPatient(): BelongsTo
    {
        return $this->belongsTo(MedicationPatient::class);
    }

    public function medicationLogs(): HasMany
    {
        return $this->hasMany(MedicationLog::class, 'schedule_id');
    }
}
