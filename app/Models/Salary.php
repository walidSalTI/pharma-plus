<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Salary extends Model
{
    use HasUuids;

    protected $table = 'salaries';

    protected $fillable = [
        'pharmacy_id',
        'user_id',
        'recipient_name',
        'base_amount',
        'bonus',
        'deductions',
        'net_amount',
        'salary_period',
        'paid_at',
        'payment_method',
        'notes',
    ];

    protected $casts = [
        'base_amount' => 'decimal:2',
        'bonus' => 'decimal:2',
        'deductions' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'paid_at' => 'date',
    ];

    public function pharmacy(): BelongsTo
    {
        return $this->belongsTo(Pharmacy::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
