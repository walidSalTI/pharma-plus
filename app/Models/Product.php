<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Product extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'products';

    protected $fillable = [
        'name',
        'barcode',
        'image',
        'type',
        'added_by_pharmacy_id',
    ];

    public function addedByPharmacy(): BelongsTo
    {
        return $this->belongsTo(Pharmacy::class, 'added_by_pharmacy_id');
    }

    public function medication(): HasOne
    {
        return $this->hasOne(Medication::class);
    }
}
