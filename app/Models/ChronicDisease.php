<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChronicDisease extends Model
{
    use HasUuids;

    protected $table = 'chronic_diseases';

    public function chronicRecords(): HasMany
    {
        return $this->hasMany(ChronicRecord::class);
    }

    public function activeIngredients(): BelongsToMany
    {
        return $this->belongsToMany(ActiveIngredient::class, 'active_ingredients_chronic_disease', 'chronic_disease_id', 'active_ingredient_id')
            ->withPivot(['risk_level', 'is_ai_verified', 'conflict_reason', 'ai_explanation']);
    }
}
