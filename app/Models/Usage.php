<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Usage extends Model
{
    use HasUuids;

    public function title(): BelongsTo
    {
        return $this->belongsTo(Title::class);
    }

    public function medications(): HasMany
    {
        return $this->hasMany(Medication::class);
    }
}
