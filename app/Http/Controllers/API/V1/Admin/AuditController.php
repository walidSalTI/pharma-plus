<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    public function activity(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Audit logging is available in a future release.', 'data' => []]);
    }
}
