<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1\Patient;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\Patient\SearchPrecheckRequest;
use App\Http\Requests\API\V1\Patient\SearchRequest;
use App\Models\Medication;
use App\Services\AlternativeMappingEngine;
use App\Services\MedicalSafetyEngine;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Log;

class SearchController extends Controller
{
    public function __construct(
        private readonly MedicalSafetyEngine $safetyEngine,
        private readonly AlternativeMappingEngine $alternativeEngine,
    ) {}

    /**
     * Search multiple medications and rank nearby pharmacies.
     */
    public function __invoke(SearchRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $queries = $validated['queries'];
        $patientLat = (float) $validated['latitude'];
        $patientLng = (float) $validated['longitude'];

        $patient = auth('sanctum')->user()?->patient;

        $matchedMedications = collect();
        foreach ($queries as $query) {
            $currentMatches = $this->resolveMedications($query);
            $matchedMedications = $matchedMedications->merge($currentMatches);
            $this->logSearchTelemetry($query, $currentMatches, $patientLat, $patientLng);
        }

        $matchedMedications = $matchedMedications->unique('id')->values();

        if ($matchedMedications->isEmpty()) {
            return response()->json([
                'data' => [],
                'message' => 'No medications found',
            ]);
        }

        $allMedicationIds = collect();
        $brandMap = collect();
        $alternativeMap = collect();

        $medicationNames = $matchedMedications->mapWithKeys(fn ($m) => [$m->id => $m->product?->name])->toArray();

        foreach ($matchedMedications as $medication) {
            $allMedicationIds->push($medication->id);
            $brandMap->put($medication->id, true);

            $alternatives = $this->alternativeEngine->findAlternatives($medication->id);
            foreach ($alternatives as $alt) {
                $allMedicationIds->push($alt->id);
                $alternativeMap->put($alt->id, $medication->id);

                if (! isset($medicationNames[$alt->id])) {
                    $medicationNames[$alt->id] = $alt->product?->name ?? Medication::with('product')->find($alt->id)?->product?->name;
                }
            }
        }

        $allMedicationIds = $allMedicationIds->unique();

        $radiusKm = 10;
        $latDelta = $radiusKm / 111.32;
        $lngDelta = $radiusKm / (111.32 * cos(deg2rad($patientLat)));

        $nearbyPharmacies = DB::table('pharmacy_inventories')
            ->join('pharmacies', 'pharmacies.id', '=', 'pharmacy_inventories.pharmacy_id')
            ->whereIn('pharmacy_inventories.medication_id', $allMedicationIds)
            ->where('pharmacy_inventories.stock', '>', 0)
            ->whereBetween('pharmacies.latitude', [$patientLat - $latDelta, $patientLat + $latDelta])
            ->whereBetween('pharmacies.longitude', [$patientLng - $lngDelta, $patientLng + $lngDelta])
            ->select([
                'pharmacies.id as pharmacy_id',
                'pharmacies.name as pharmacy_name',
                'pharmacies.address as pharmacy_address',
                'pharmacies.latitude as pharmacy_latitude',
                'pharmacies.longitude as pharmacy_longitude',
                'pharmacy_inventories.medication_id',
                'pharmacy_inventories.price',
                'pharmacy_inventories.stock',
                DB::raw('(SELECT AVG(rating) FROM pharmacy_reviews WHERE pharmacy_id = pharmacies.id) as average_rating'),
                DB::raw('(SELECT AVG(availability_rating) FROM pharmacy_reviews WHERE pharmacy_id = pharmacies.id) as average_availability_rating'),
            ])
            ->get();

        $uniquePharmacyIds = $nearbyPharmacies->pluck('pharmacy_id')->unique()->toArray();
        $currentDayName = strtolower(now()->format('w'));

        $hoursCache = DB::table('pharmacy_operating_hours')
            ->whereIn('pharmacy_id', $uniquePharmacyIds)
            ->where('day_of_week', $currentDayName)
            ->get()
            ->keyBy('pharmacy_id');

        $results = collect();

        foreach ($nearbyPharmacies as $pharmacy) {
            $distance = $this->haversine(
                $patientLat,
                $patientLng,
                (float) $pharmacy->pharmacy_latitude,
                (float) $pharmacy->pharmacy_longitude
            );

            if ($distance > $radiusKm) {
                continue;
            }

            $medicationId = $pharmacy->medication_id;
            $isBrandMatch = $brandMap->has($medicationId);
            $isAlternativeMatch = $alternativeMap->has($medicationId);

            $safetyStatus = 'unknown';
            $safetyConflicts = [];
            if ($patient) {
                $safetyResult = $this->safetyEngine->evaluate($medicationId, $patient->id);
                $safetyStatus = $safetyResult['is_safe'] ? 'green' : 'red';
                $safetyConflicts = $safetyResult['conflicts'];
            }

            $suitabilityScore = 0;

            if ($safetyStatus !== 'red') {
                if ($isBrandMatch) {
                    $suitabilityScore += 50;
                } elseif ($isAlternativeMatch) {
                    $suitabilityScore += 25;
                }
            }

            $generalRating = (float) ($pharmacy->average_rating ?? 0);
            $availabilityRating = (float) ($pharmacy->average_availability_rating ?? 0);

            $suitabilityScore += ($generalRating * 3) + ($availabilityRating * 6);
            if ($distance <= 0.1) {
                $suitabilityScore += 30;
            } else {
                $suitabilityScore += max(0, (10 - $distance) * 2);
            }
            $matchType = $isBrandMatch ? 'brand' : ($isAlternativeMatch ? 'alternative' : 'generic');
            $isOpen = $this->checkIfPharmacyIsOpen((string) $pharmacy->pharmacy_id, $hoursCache);

            $results->push((object) [
                'pharmacy_id' => $pharmacy->pharmacy_id,
                'pharmacy_name' => $pharmacy->pharmacy_name,
                'pharmacy_address' => $pharmacy->pharmacy_address,
                'pharmacy_latitude' => $pharmacy->pharmacy_latitude,
                'pharmacy_longitude' => $pharmacy->pharmacy_longitude,
                'distance_km' => $distance,
                'suitability_score' => max(0, $suitabilityScore),
                'match_type' => $matchType,
                'medication_id' => $medicationId,
                'trade_name' => $medicationNames[$medicationId] ?? 'Unknown',
                'price' => $pharmacy->price,
                'stock' => $pharmacy->stock,
                'average_rating' => $pharmacy->average_rating,
                'conflicts' => $safetyConflicts,
                'is_open' => $isOpen,
            ]);
        }

        $groupedResults = $results->groupBy('pharmacy_id')->map(function ($pharmacyGroup): array {
            $first = $pharmacyGroup->first();

            $availableMedications = $pharmacyGroup->map(fn ($item) => [
                'medication_id' => $item->medication_id,
                'trade_name' => $item->trade_name,
                'price' => $item->price,
                'stock' => $item->stock,
                'match_type' => $item->match_type,
                'conflicts' => $item->conflicts,
            ])->values();

            $medicationCountBonus = $availableMedications->count() * 100;

            return [
                'pharmacy_id' => $first->pharmacy_id,
                'pharmacy_name' => $first->pharmacy_name,
                'pharmacy_address' => $first->pharmacy_address,
                'pharmacy_latitude' => $first->pharmacy_latitude,
                'pharmacy_longitude' => $first->pharmacy_longitude,
                'distance_km' => $first->distance_km,
                'suitability_score' => $pharmacyGroup->max('suitability_score') + $medicationCountBonus,
                'is_open' => $first->is_open,
                'medications' => $availableMedications,
            ];
        })->values();

        $sorted = $groupedResults->sortByDesc('suitability_score')->values();

        return response()->json([
            'data' => $sorted,
            'message' => 'Search results retrieved successfully',
        ]);
    }

    /**
     * Pre-check interactions between the queried medications (FR-P-2.3).
     *
     * Resolves each search query to medications, collects all active
     * ingredients, and checks every ingredient pair against
     * composition_interactions (AI batch verifies unverified/missing pairs).
     * Returns a warning the client can show before continuing to the main
     * search endpoint.
     */
    public function precheck(SearchPrecheckRequest $request): JsonResponse
    {
        $queries = $request->validated()['queries'];

        $matchedMedications = collect();
        $resolvedMedications = [];

        foreach ($queries as $query) {
            $resolved = $this->resolveMedications($query);
            $matchedMedications = $matchedMedications->merge($resolved);

            $resolvedMedications[$query] = $resolved->map(fn ($m) => [
                'id' => $m->id,
                'name' => $m->product?->name ?? 'Unknown',
            ])->values();
        }

        $matchedMedications = $matchedMedications->unique('id')->values();

        $ingredientMap = [];
        foreach ($matchedMedications as $medication) {
            foreach ($medication->activeIngredients as $ingredient) {
                $ingredientMap[$ingredient->id] = $ingredient->ingredient_name_en;
            }
        }

        if ($ingredientMap === []) {
            return response()->json([
                'data' => [
                    'is_safe' => true,
                    'has_conflicts' => false,
                    'conflicts' => [],
                    'resolved_medications' => $resolvedMedications,
                ],
                'message' => 'No medications found.',
            ]);
        }

        $result = $this->safetyEngine->evaluateQueried(array_keys($ingredientMap));

        return response()->json([
            'data' => [
                'is_safe' => $result['is_safe'],
                'has_conflicts' => ! $result['is_safe'],
                'conflicts' => $result['conflicts'],
                'resolved_medications' => $resolvedMedications,
            ],
            'message' => $result['is_safe'] ? 'No interactions detected.' : 'Potential interactions detected.',
        ]);
    }

    private function resolveMedications(string $query): Collection
    {
        $byTradeName = Medication::with('activeIngredients', 'product', 'usage')
            ->whereHas('product', fn ($q) => $q->where('name', 'like', '%'.$query.'%'))
            ->get();

        $byIngredient = Medication::with('activeIngredients', 'product', 'usage')
            ->whereHas('activeIngredients', fn ($q) => $q->where('ingredient_name_en', 'like', '%'.$query.'%'))
            ->get();

        return $byTradeName->merge($byIngredient)->unique('id')->values();
    }

    private function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) * sin($dLat / 2)
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
            * sin($dLng / 2) * sin($dLng / 2);

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    private function checkIfPharmacyIsOpen(string $pharmacyId, Collection $hoursCache): bool
    {
        $hours = $hoursCache->get($pharmacyId);

        if ($hours === null || $hours->is_closed) {
            return false;
        }

        if ($hours->is_24_hours) {
            return true;
        }

        $now = now()->format('H:i');

        return $now >= $hours->opening_time && $now <= $hours->closing_time;
    }

    private function logSearchTelemetry(string $query, Collection $matchedMedications, float $lat, float $lng): void
    {
        try {
            // إذا لم نجد نتائج (بحث فاشل)، نسجل الكلمة فقط لمعرفة النواقص
            if ($matchedMedications->isEmpty()) {
                DB::table('search_telemetries')->insert([
                    'searched_query' => $query,
                    'resolved_product_name' => null,
                    'resolved_active_ingredient_id' => null,
                    'resolved_usage' => null,
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'created_at' => now(),
                ]);

                return;
            }

            $records = [];
            $now = now();

            // نمر على كل دواء تم استخراجه مطابقةً للبحث
            foreach ($matchedMedications as $medication) {
                $productName = $medication->product?->name ?? 'Unknown';
                $medicationUsage = $medication->usage?->name;

                // إذا كان للدواء مواد فعالة، نسجل كل مادة مع اسم المنتج الصحيح
                if ($medication->activeIngredients->isNotEmpty()) {
                    foreach ($medication->activeIngredients as $ingredient) {
                        $records[] = [
                            'searched_query' => $query,                  // الكلمة كما كتبها المريض (panadl)
                            'resolved_product_name' => $productName,     // الاسم الصريح والدقيق (Panadol)
                            'resolved_active_ingredient_id' => $ingredient->id,
                            'resolved_usage' => $medicationUsage,
                            'latitude' => $lat,
                            'longitude' => $lng,
                            'created_at' => $now,
                        ];
                    }
                } else {
                    // في حال عدم وجود مواد فعالة معرفة للدواء
                    $records[] = [
                        'searched_query' => $query,
                        'resolved_product_name' => $productName,
                        'resolved_active_ingredient_id' => null,
                        'resolved_usage' => $medicationUsage,
                        'latitude' => $lat,
                        'longitude' => $lng,
                        'created_at' => $now,
                    ];
                }
            }

            // إدخال دفعة واحدة لتقليل الاستعلامات (Batch Insert)
            if ($records !== []) {
                // استخدام unique لمنع تكرار نفس (المادة + اسم المنتج) لنفس طلب البحث
                $uniqueRecords = collect($records)->unique(fn ($item) => $item['resolved_product_name'].'_'.$item['resolved_active_ingredient_id'])->values()->toArray();

                DB::table('search_telemetries')->insert($uniqueRecords);
            }
        } catch (Exception $e) {
            Log::error('Failed to log search telemetry: '.$e->getMessage());
        }
    }
}
