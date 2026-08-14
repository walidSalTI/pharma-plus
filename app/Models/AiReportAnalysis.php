<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Stores the latest AI-generated report analysis (financial / inventory / full)
 * produced by the Qwen LLM for a pharmacy. Only the newest record per
 * pharmacy + report type is kept (updateOrCreate in the controller).
 */
class AiReportAnalysis extends Model
{
    use HasUuids;

    protected $table = 'ai_report_analyses';

    protected $fillable = [
        'pharmacy_id',
        'type',
        'start_date',
        'end_date',
        'input_snapshot',
        'ai_insights',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'input_snapshot' => 'array',
        'ai_insights' => 'array',
    ];

    public function pharmacy(): BelongsTo
    {
        return $this->belongsTo(Pharmacy::class);
    }
}
