<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoctorWorkplace extends Model
{
    use HasUuids;

    protected $table = 'doctor_workplaces';

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }
}
