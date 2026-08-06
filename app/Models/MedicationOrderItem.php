<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicationOrderItem extends Model
{
    use HasUuids;

    protected $table = 'medication_order_items';

    protected $fillable = [
        'medication_order_id',
        'medication_id',
        'batch_id',
        'wholesale_price_at_sale',
        'quantity',
        'price',
    ];

    public function medicationOrder(): BelongsTo
    {
        return $this->belongsTo(MedicationOrder::class, 'medication_order_id');
    }

    public function medication(): BelongsTo
    {
        return $this->belongsTo(Medication::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(PharmacyInventoryBatch::class);
    }
}
