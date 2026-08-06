<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScientificRep extends Model
{
    use HasUuids;

    protected $table = 'scientific_reps';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function pharmaceuticalCompany(): BelongsTo
    {
        return $this->belongsTo(PharmaceuticalCompany::class, 'company_id');
    }

    public function doctorAssignments(): HasMany
    {
        return $this->hasMany(DoctorAssignment::class, 'rep_id');
    }

    public function weeklySchedules(): HasMany
    {
        return $this->hasMany(WeeklySchedule::class, 'rep_id');
    }

    public function representativeVisits(): HasMany
    {
        return $this->hasMany(RepresentativeVisit::class, 'rep_id');
    }
}
