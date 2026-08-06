<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PharmaceuticalCompany extends Model
{
    use HasUuids;

    protected $table = 'pharmaceutical_companies';

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function medications(): HasMany
    {
        return $this->hasMany(Medication::class, 'manufacture_id');
    }

    public function scientificReps(): HasMany
    {
        return $this->hasMany(ScientificRep::class, 'company_id');
    }

    public function doctorAssignments(): HasMany
    {
        return $this->hasMany(DoctorAssignment::class, 'company_id');
    }
}
