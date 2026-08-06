<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\PharmacyInventoryFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PharmacyInventory extends Model
{
    /** @use HasFactory<PharmacyInventoryFactory> */
    use HasFactory, HasUuids;

    protected $table = 'pharmacy_inventories';

    protected $fillable = [
        'pharmacy_id',
        'medication_id',
        'price',
        'stock',
        'min_stock',
    ];

    public function pharmacy(): BelongsTo
    {
        return $this->belongsTo(Pharmacy::class);
    }

    public function medication(): BelongsTo
    {
        return $this->belongsTo(Medication::class);
    }

    public function batches(): HasMany
    {
        return $this->hasMany(PharmacyInventoryBatch::class, 'pharmacy_inventory_id');
    }

    public function activeBatches(): HasMany
    {
        return $this->hasMany(PharmacyInventoryBatch::class, 'pharmacy_inventory_id')
            ->where('quantity', '>', 0)
            ->orderBy('expiration_date', 'asc');
    }

    public function syncStock(): void
    {
        $this->update(['stock' => $this->batches()->sum('quantity')]);
    }
}
