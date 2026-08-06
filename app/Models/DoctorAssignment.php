<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoctorAssignment extends Model
{
    use HasUuids;

    protected $table = 'doctor_assignments';

    public function pharmaceuticalCompany(): BelongsTo
    {
        return $this->belongsTo(PharmaceuticalCompany::class, 'company_id');
    }

    public function scientificRep(): BelongsTo
    {
        return $this->belongsTo(ScientificRep::class, 'rep_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }
}
