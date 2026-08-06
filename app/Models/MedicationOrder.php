<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MedicationOrder extends Model
{
    use HasUuids;

    protected $table = 'medication_orders';

    protected $fillable = [
        'patient_id',
        'pharmacy_id',
        'pharmacist_id',
        'status',
        'source',
        'type',
        'total_price',
        'total_cost',
        'is_returned',
        'supplier_name',
        'invoice_number',
        'pharmacist_note',
        'notes',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function pharmacy(): BelongsTo
    {
        return $this->belongsTo(Pharmacy::class);
    }

    public function pharmacist(): BelongsTo
    {
        return $this->belongsTo(Pharmacist::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(MedicationOrderItem::class, 'medication_order_id');
    }

    public function review(): HasOne
    {
        return $this->hasOne(PharmacyReview::class, 'order_id');
    }

    public function pharmacyReview(): HasOne
    {
        return $this->hasOne(PharmacyReview::class, 'order_id');
    }
}
