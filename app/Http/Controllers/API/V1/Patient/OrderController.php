<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1\Patient;

use App\Events\MedicationHoldRequested;
use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\Patient\HoldMedicationRequest;
use App\Models\MedicationOrder;
use App\Models\Pharmacy;
use App\Models\PharmacyInventory;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Patient Medication Ordering & Real-Time Reservation (FR-P-6.3, FR-P-6.4).
 *
 * Handles medication hold/reservation requests from patients to pharmacies.
 * Ensures the pharmacy is currently open before accepting any orders.
 */
class OrderController extends Controller
{
    /**
     * Hold/reserve multiple medications at a pharmacy (FR-P-6.3).
     *
     * @bodyParam pharmacy_id string required The target pharmacy UUID.
     * @bodyParam items array required Array of medications to reserve.
     * @bodyParam items.*.medication_id string required The medication UUID.
     * @bodyParam items.*.quantity int required Quantity to reserve (1-100).
     * @bodyParam pharmacist_note string optional Note for the pharmacist.
     */
    public function holdMedication(HoldMedicationRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $patient = $request->user()->patient;
        $pharmacyId = $validated['pharmacy_id'];

        // Fetch the pharmacy and verify operating hours before opening the transaction
        $pharmacy = Pharmacy::find($pharmacyId);

        if (! $pharmacy) {
            return response()->json(['message' => 'Pharmacy not found.'], 404);
        }

        if (! $this->checkIfPharmacyIsOpen($pharmacyId)) {
            return response()->json([
                'message' => 'This pharmacy is currently closed. You cannot place hold requests until they re-open.',
            ], 422);
        }

        // Process the reservation and stock validation inside a DB transaction
        return DB::transaction(function () use ($validated, $patient, $pharmacyId) {

            $totalOrderPrice = 0;
            $orderItemsData = [];

            foreach ($validated['items'] as $item) {
                // Use lockForUpdate to protect stock from concurrent requests
                $inventory = PharmacyInventory::query()
                    ->where('pharmacy_id', $pharmacyId)
                    ->where('medication_id', $item['medication_id'])
                    ->lockForUpdate()
                    ->first();

                if (! $inventory) {
                    throw new Exception("Medication {$item['medication_id']} is not registered at this pharmacy.", 404);
                }

                if ($inventory->stock < $item['quantity']) {
                    throw new Exception("Insufficient stock for medication {$item['medication_id']}. Available: {$inventory->stock}", 422);
                }

                $itemPrice = $inventory->price * $item['quantity'];
                $totalOrderPrice += $itemPrice;

                $orderItemsData[] = [
                    'medication_id' => $item['medication_id'],
                    'quantity' => $item['quantity'],
                    'price' => $inventory->price,
                ];
            }

            $invoiceNumber = 'INV-'.strtoupper(Str::random(12));

            $order = MedicationOrder::create([
                'patient_id' => $patient->id,
                'pharmacy_id' => $pharmacyId,
                'status' => 'pending',
                'source' => 'app',
                'type' => 'sale',
                'total_price' => $totalOrderPrice,
                'invoice_number' => $invoiceNumber,
                'pharmacist_note' => $validated['pharmacist_note'] ?? null,
            ]);

            $order->items()->createMany($orderItemsData);

            // Broadcast real-time notification to the pharmacy dashboard via Reverb
            broadcast(new MedicationHoldRequested($order))->toOthers();

            return response()->json([
                'message' => 'Medication hold request submitted successfully.',
                'data' => [
                    'order_id' => $order->id,
                    'invoice_number' => $invoiceNumber,
                    'status' => $order->status,
                    'total_price' => $totalOrderPrice,
                    'pharmacy_id' => $pharmacyId,
                    'created_at' => $order->created_at,
                ],
            ], 201);
        });
    }

    /**
     * Check whether the pharmacy is currently open based on its operating hours schedule.
     */
    private function checkIfPharmacyIsOpen(string $pharmacyId): bool
    {
        $currentDayOfWeek = (int) now()->format('w');

        $hours = DB::table('pharmacy_operating_hours')
            ->where('pharmacy_id', $pharmacyId)
            ->where('day_of_week', $currentDayOfWeek)
            ->first();

        // No schedule record found or pharmacy is closed on this day
        if ($hours === null || (bool) $hours->is_closed) {
            return false;
        }

        // Pharmacy operates 24 hours
        if ((bool) $hours->is_24_hours) {
            return true;
        }

        // Check if current time falls within opening and closing hours
        $now = now()->format('H:i');

        return $now >= $hours->opening_time && $now <= $hours->closing_time;
    }
}
