<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1\Doctor;

use App\Http\Controllers\Controller;
use App\Http\Resources\API\V1\Doctor\DoctorListResource;
use App\Models\Doctor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DoctorController extends Controller
{
    /**
     * List all doctors with optional filtering.
     *
     * Returns a paginated list of doctors. Supports filtering by
     * name (f_name / l_name), specialization, and location.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Doctor::with(['user', 'doctorWorkplaces']);

        $query->whereHas('user', function ($q) use ($request) {
            if ($request->filled('name')) {
                $name = $request->input('name');
                $q->where(function ($sub) use ($name) {
                    $sub->where('f_name', 'LIKE', "%{$name}%")
                        ->orWhere('l_name', 'LIKE', "%{$name}%")
                        ->orWhereRaw("CONCAT(f_name, ' ', l_name) LIKE ?", ["%{$name}%"]);
                });
            }

            if ($request->filled('location')) {
                $q->where('location', 'LIKE', '%'.$request->input('location').'%');
            }
        });

        if ($request->filled('specialization')) {
            $query->where('specialization', 'LIKE', '%'.$request->input('specialization').'%');
        }

        $doctors = $query->orderBy('created_at', 'desc')->paginate($request->input('per_page', 20));

        return response()->json([
            'data' => DoctorListResource::collection($doctors),
            'meta' => [
                'current_page' => $doctors->currentPage(),
                'last_page' => $doctors->lastPage(),
                'per_page' => $doctors->perPage(),
                'total' => $doctors->total(),
            ],
        ]);
    }
}
