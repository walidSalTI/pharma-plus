<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1\Pharmacy;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\Pharmacy\StorePharmacistMedicationRequest;
use App\Http\Resources\API\V1\Pharmacy\SubmittedMedicationResource;
use App\Models\Medication;
use App\Models\Pharmacy;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MedicationController extends Controller
{
    public function store(StorePharmacistMedicationRequest $request, Pharmacy $pharmacy): JsonResponse
    {
        $this->authorize('manageInventory', $pharmacy);

        $validated = $request->validated();

        $medication = DB::transaction(function () use ($validated, $pharmacy) {
            $product = Product::create([
                'name' => $validated['trade_name'],
                'barcode' => $validated['barcode'] ?? null,
                'image' => $validated['image'] ?? null,
                'type' => 'medication',
                'added_by_pharmacy_id' => $pharmacy->id,
            ]);

            return Medication::create([
                'product_id' => $product->id,
                'form' => $validated['form'],
                'arabic_form' => $validated['arabic_form'] ?? null,
                'manufacture_id' => $validated['manufacture_id'] ?? null,
                'status' => 'pending',
            ]);
        });

        return response()->json([
            'message' => 'Medication submitted for verification. You can now add it to your inventory.',
            'data' => new SubmittedMedicationResource($medication->load('manufacture', 'usage', 'product')),
        ], 201);
    }

    public function index(Request $request, Pharmacy $pharmacy): JsonResponse
    {
        $this->authorize('manageInventory', $pharmacy);

        Product::where('added_by_pharmacy_id', $pharmacy->id)
            ->where('type', 'medication')
            ->pluck('id');

        $medications = Medication::with('manufacture', 'usage', 'product')
            ->whereHas('product', function ($q) use ($pharmacy) {
                $q->where('added_by_pharmacy_id', $pharmacy->id);
            })
            ->whereIn('status', ['pending', 'rejected'])
            ->latest()
            ->paginate(20);

        return response()->json([
            'data' => SubmittedMedicationResource::collection($medications),
            'meta' => [
                'current_page' => $medications->currentPage(),
                'last_page' => $medications->lastPage(),
                'per_page' => $medications->perPage(),
                'total' => $medications->total(),
            ],
        ]);
    }

    public function show(Pharmacy $pharmacy, Medication $medication): JsonResponse
    {
        $this->authorize('manageInventory', $pharmacy);

        $hasProduct = Product::where('added_by_pharmacy_id', $pharmacy->id)
            ->where('type', 'medication')
            ->exists();

        if (! $hasProduct) {
            return response()->json(['message' => 'Medication not found.'], 404);
        }

        return response()->json([
            'data' => new SubmittedMedicationResource($medication->load('manufacture', 'usage', 'product')),
        ]);
    }
}
