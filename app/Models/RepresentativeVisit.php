<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RepresentativeVisit extends Model
{
    use HasUuids;

    protected $table = 'representative_visits';

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function scientificRep(): BelongsTo
    {
        return $this->belongsTo(ScientificRep::class, 'rep_id');
    }

    public function weeklySchedule(): BelongsTo
    {
        return $this->belongsTo(WeeklySchedule::class, 'schedule_id');
    }
}
