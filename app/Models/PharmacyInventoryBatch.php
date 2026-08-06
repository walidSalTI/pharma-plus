<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PharmacyInventoryBatch extends Model
{
    use HasUuids;

    protected $table = 'pharmacy_inventory_batches';

    protected $fillable = [
        'pharmacy_inventory_id',
        'batch_number',
        'quantity',
        'wholesale_price',
        'expiration_date',
    ];

    protected $casts = [
        'wholesale_price' => 'decimal:2',
        'expiration_date' => 'date',
    ];

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(PharmacyInventory::class, 'pharmacy_inventory_id');
    }
}
