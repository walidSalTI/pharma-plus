<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Manufacture extends Model
{
    use HasUuids;

    public function medications(): HasMany
    {
        return $this->hasMany(Medication::class, 'manufacture_id');
    }
}
