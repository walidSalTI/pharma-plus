<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Specialist extends Model
{
    use HasUuids;

    protected $table = 'specialists';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function medicationProposals(): HasMany
    {
        return $this->hasMany(MedicationProposal::class);
    }
}
