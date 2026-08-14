<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SearchTelemetry extends Model
{
    protected $table = 'search_telemetries';

    public $timestamps = false;

    protected $fillable = [
        'searched_query',
        'resolved_product_name',
        'resolved_active_ingredient_id',
        'resolved_usage',
        'latitude',
        'longitude',
    ];
}
