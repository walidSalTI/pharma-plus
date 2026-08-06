<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1\Patient;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\Patient\StorePharmacyReviewRequest;
use App\Http\Resources\API\V1\Patient\PharmacyReviewResource;
use App\Models\MedicationOrder;
use App\Models\Pharmacy;
use App\Models\PharmacyReview;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PharmacyReviewController extends Controller
{
    public function store(StorePharmacyReviewRequest $request, Pharmacy $pharmacy): JsonResponse
    {
        $patient = $request->user()->patient;
        $validated = $request->validated();

        $order = MedicationOrder::query()
            ->where('id', $validated['order_id'])
            ->where('patient_id', $patient->id)
            ->where('pharmacy_id', $pharmacy->id)
            ->where('status', 'completed')
            ->first();

        if (! $order) {
            return response()->json([
                'message' => 'You can only review a pharmacy after a completed order.',
            ], 403);
        }

        $exists = PharmacyReview::query()
            ->where('order_id', $validated['order_id'])
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'You have already reviewed this order.',
            ], 422);
        }

        $review = PharmacyReview::create([
            'patient_id' => $patient->id,
            'pharmacy_id' => $pharmacy->id,
            'order_id' => $validated['order_id'],
            'rating' => $validated['rating'],
            'availability_rating' => $validated['availability_rating'],
            'comment' => $validated['comment'] ?? null,
        ]);

        return response()->json([
            'message' => 'Review submitted successfully.',
            'data' => new PharmacyReviewResource($review),
        ], 201);
    }

    public function unreviewedOrders(Request $request, ?string $pharmacyId = null): JsonResponse
    {
        $patient = $request->user()->patient;

        $query = MedicationOrder::query()
            ->where('patient_id', $patient->id)
            ->where('status', 'completed')
            ->whereDoesntHave('review');

        if ($pharmacyId) {
            $query->where('pharmacy_id', $pharmacyId);
        }

        $orders = $query->with('items.medication', 'pharmacy')->get();

        return response()->json([
            'data' => $orders,
        ]);
    }
}
