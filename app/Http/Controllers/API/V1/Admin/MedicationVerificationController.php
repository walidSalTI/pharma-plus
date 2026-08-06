<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\Admin\AcceptMedicationVerificationRequest;
use App\Http\Requests\API\V1\Admin\RejectMedicationVerificationRequest;
use App\Http\Resources\API\V1\Admin\MedicationResource;
use App\Models\Medication;
use App\Models\MedicationIngredient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MedicationVerificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $medications = Medication::with('manufacture', 'product')
            ->where('status', 'pending')
            ->latest()
            ->paginate(20);

        return response()->json([
            'data' => MedicationResource::collection($medications),
            'meta' => [
                'current_page' => $medications->currentPage(),
                'last_page' => $medications->lastPage(),
                'per_page' => $medications->perPage(),
                'total' => $medications->total(),
            ],
        ]);
    }

    public function accept(AcceptMedicationVerificationRequest $request, Medication $medication): JsonResponse
    {
        if ($medication->status !== 'pending') {
            return response()->json(['message' => 'Only pending medications can be accepted.'], 422);
        }

        $validated = $request->validated();
        $activeIngredients = $validated['active_ingredients'];
        unset($validated['active_ingredients']);

        $medication = DB::transaction(function () use ($medication, $validated, $activeIngredients): Medication {
            $medication->product->update([
                'name' => $validated['trade_name'],
                'barcode' => $validated['barcode'] ?? null,
                'image' => $validated['image'] ?? null,
                'added_by_pharmacy_id' => null,
            ]);

            $medication->update([
                'form' => $validated['form'] ?? null,
                'arabic_form' => $validated['arabic_form'] ?? null,
                'manufacture_id' => $validated['manufacture_id'] ?? null,
                'usage_id' => $validated['usage_id'] ?? null,
                'status' => 'accepted',
                'rejection_reason' => null,
            ]);

            MedicationIngredient::where('medication_id', $medication->id)->delete();

            foreach ($activeIngredients as $ingredient) {
                MedicationIngredient::create([
                    'medication_id' => $medication->id,
                    'active_ingredient_id' => $ingredient['active_ingredient_id'],
                    'active_ratio' => $ingredient['active_ratio'] ?? null,
                ]);
            }

            return $medication;
        });

        return response()->json([
            'message' => 'Medication accepted and added to the global catalog.',
            'data' => new MedicationResource($medication->fresh()->load('manufacture', 'activeIngredients', 'usage.title.category', 'product')),
        ]);
    }

    public function reject(RejectMedicationVerificationRequest $request, Medication $medication): JsonResponse
    {
        if ($medication->status !== 'pending') {
            return response()->json(['message' => 'Only pending medications can be rejected.'], 422);
        }

        $medication->update([
            'status' => 'rejected',
            'rejection_reason' => $request->input('rejection_reason'),
        ]);

        return response()->json([
            'message' => 'Medication rejected.',
            'data' => new MedicationResource($medication->fresh()->load('manufacture', 'activeIngredients', 'usage.title.category', 'product')),
        ]);
    }
}
