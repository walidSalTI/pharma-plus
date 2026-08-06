<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifiedDoctor
{
    public function handle(Request $request, Closure $next): Response
    {
        $doctor = $request->user()?->doctor;

        if (! $doctor || $doctor->verification_status !== 'approved') {
            return response()->json(['message' => 'Account must be verified to access this resource.'], 403);
        }

        return $next($request);
    }
}
