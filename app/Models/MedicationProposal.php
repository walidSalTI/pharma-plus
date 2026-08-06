<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\MedicationProposalFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicationProposal extends Model
{
    /** @use HasFactory<MedicationProposalFactory> */
    use HasFactory, HasUuids;

    protected $table = 'medication_proposals';

    public function pharmacist(): BelongsTo
    {
        return $this->belongsTo(Pharmacist::class);
    }

    public function specialist(): BelongsTo
    {
        return $this->belongsTo(Specialist::class);
    }
}
