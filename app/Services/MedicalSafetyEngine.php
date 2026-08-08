<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MedicalSafetyEngine
{
    public function __construct(
        private readonly LlamaApiService $llamaApi
    ) {}

    // ═══════════════════════════════════════════════════════════════════
    // Drug-disease conflicts (already handles missing records)
    // ═══════════════════════════════════════════════════════════════════
    /**
     * @return array{is_safe: bool, conflicts: array}
     */
    public function checkDiseaseConflicts(array $medicationIngredientIds, string $patientId): array
    {
        $patientDiseases = DB::table('chronic_records')
            ->join('chronic_diseases', 'chronic_records.chronic_disease_id', '=', 'chronic_diseases.id')
            ->where('chronic_records.patient_id', $patientId)
            ->select('chronic_diseases.id', 'chronic_diseases.name_en')
            ->get();

        if ($patientDiseases->isEmpty()) {
            return ['is_safe' => true, 'conflicts' => []];
        }

        $existingMappings = DB::table('active_ingredients_chronic_disease')
            ->whereIn('active_ingredient_id', $medicationIngredientIds)
            ->whereIn('chronic_disease_id', $patientDiseases->pluck('id'))
            ->get()
            ->keyBy(fn ($item) => $item->active_ingredient_id.'_'.$item->chronic_disease_id);

        $ingredients = DB::table('active_ingredients')
            ->whereIn('id', $medicationIngredientIds)
            ->get()
            ->keyBy('id');

        $conflicts = [];
        $unverifiedItems = [];
        $newItems = [];
        $isSafe = true;
        $seenKeys = [];
        $nextTempId = -1;

        foreach ($medicationIngredientIds as $ingredientId) {
            $ingredient = $ingredients->get($ingredientId);
            if (! $ingredient) {
                continue;
            }

            foreach ($patientDiseases as $disease) {
                $record = $existingMappings->get($ingredientId.'_'.$disease->id);

                if (! $record) {
                    $newItems[] = [
                        'id' => $nextTempId--,
                        'is_new' => true,
                        'ingredient_id' => $ingredientId,
                        'drug' => $ingredient->ingredient_name_en,
                        'disease' => $disease->id,
                        'disease_name' => $disease->name_en,
                    ];

                    continue;
                }

                if ($record->is_ai_verified) {
                    if ((int) $record->risk_level > 0) {
                        $key = 'disease_'.$disease->id;
                        if (in_array($key, $seenKeys, true)) {
                            continue;
                        }
                        $seenKeys[] = $key;

                        $isSafe = false;
                        $conflicts[] = [
                            'type' => 'disease',
                            'risk_level' => (int) $record->risk_level,
                            'reason' => $record->ai_explanation ?? $record->conflict_reason ?? 'تضارب مثبت مع المرض المزمن',
                            'disease' => $disease->name_en,
                            'disease_id' => $disease->id,
                            'verified_by_ai' => true,
                        ];
                    }
                } else {
                    $unverifiedItems[] = [
                        'id' => $record->id,
                        'drug' => $ingredient->ingredient_name_en,
                        'disease' => $disease->id,
                        'disease_name' => $disease->name_en,
                        'record' => $record,
                    ];
                }
            }
        }

        $allItems = array_merge($unverifiedItems, $newItems);

        if ($allItems !== []) {
            $aiResults = $this->llamaApi->evaluateDrugDiseaseBatch($allItems);

            if ($aiResults !== null) {
                $resultsById = collect($aiResults)->keyBy('id');

                DB::transaction(function () use ($allItems, $resultsById, &$isSafe, &$conflicts, &$seenKeys) {
                    foreach ($allItems as $item) {
                        $aiRes = $resultsById->get($item['id']);

                        if ($item['is_new'] ?? false) {
                            if ($aiRes) {
                                $riskLevel = (int) $aiRes['severity_rating'];
                                $explanation = $aiRes['clinical_explanation'] ?? '';

                                DB::table('active_ingredients_chronic_disease')
                                    ->updateOrInsert(
                                        [
                                            'active_ingredient_id' => $item['ingredient_id'],
                                            'chronic_disease_id' => $item['disease'],
                                        ],
                                        [
                                            'id' => (string) Str::uuid(),
                                            'risk_level' => $riskLevel,
                                            'ai_explanation' => $explanation,
                                            'is_ai_verified' => true,
                                            'created_at' => now(),
                                            'updated_at' => now(),
                                        ]
                                    );

                                if ($riskLevel > 0) {
                                    $key = 'disease_'.$item['disease'];
                                    if (in_array($key, $seenKeys, true)) {
                                        continue;
                                    }
                                    $seenKeys[] = $key;

                                    $isSafe = false;
                                    $conflicts[] = [
                                        'type' => 'disease',
                                        'risk_level' => $riskLevel,
                                        'reason' => $explanation,
                                        'disease' => $item['disease_name'],
                                        'disease_id' => $item['disease'],
                                        'verified_by_ai' => true,
                                    ];
                                }
                            }
                        } else {
                            $record = $item['record'];

                            if ($aiRes) {
                                $riskLevel = (int) $aiRes['severity_rating'];
                                $explanation = $aiRes['clinical_explanation'] ?? '';
                                DB::table('active_ingredients_chronic_disease')
                                    ->where('id', $item['id'])
                                    ->update([
                                        'risk_level' => $riskLevel,
                                        'ai_explanation' => $explanation,
                                        'is_ai_verified' => true,
                                        'updated_at' => now(),
                                    ]);
                                if ($riskLevel > 0) {
                                    $key = 'disease_'.$item['disease'];
                                    if (in_array($key, $seenKeys, true)) {
                                        continue;
                                    }
                                    $seenKeys[] = $key;

                                    $isSafe = false;
                                    $conflicts[] = [
                                        'type' => 'disease',
                                        'risk_level' => $riskLevel,
                                        'reason' => $explanation,
                                        'disease' => $item['disease_name'],
                                        'disease_id' => $item['disease'],
                                        'verified_by_ai' => true,
                                    ];
                                }
                            } elseif ((int) $record->risk_level > 0) {
                                $key = 'disease_'.$item['disease'];
                                if (in_array($key, $seenKeys, true)) {
                                    continue;
                                }
                                $seenKeys[] = $key;
                                $isSafe = false;
                                $conflicts[] = [
                                    'type' => 'disease',
                                    'risk_level' => (int) $record->risk_level,
                                    'reason' => $record->ai_explanation ?? $record->conflict_reason ?? 'تضارب محتمل (سقط من تقرير الذكاء الاصطناعي)',
                                    'disease' => $item['disease_name'],
                                    'disease_id' => $item['disease'],
                                    'verified_by_ai' => false,
                                ];
                            }
                        }
                    }
                });
            } else {
                foreach ($allItems as $item) {
                    if ($item['is_new'] ?? false) {
                        continue;
                    }

                    $record = $item['record'];
                    if ((int) $record->risk_level > 0) {
                        $key = 'disease_'.$item['disease'];
                        if (in_array($key, $seenKeys, true)) {
                            continue;
                        }
                        $seenKeys[] = $key;

                        $isSafe = false;
                        $conflicts[] = [
                            'type' => 'disease',
                            'risk_level' => (int) $record->risk_level,
                            'reason' => $record->ai_explanation ?? $record->conflict_reason ?? 'تضارب محتمل (فشل الاتصال بالذكاء الاصطناعي)',
                            'disease' => $item['disease_name'],
                            'disease_id' => $item['disease'],
                            'verified_by_ai' => false,
                        ];
                    }
                }
            }
        }

        return ['is_safe' => $isSafe, 'conflicts' => $conflicts];
    }

    // ═══════════════════════════════════════════════════════════════════
    // Drug-drug interactions between a new medication's ingredients and the
    // patient's current wallet. AI batch logic lives in processDrugBatch().
    // ═══════════════════════════════════════════════════════════════════
    /**
     * @return array{is_safe: bool, conflicts: array}
     */
    public function checkDrugInteractions(array $medicationIngredientIds, string $patientId): array
    {
        $patientActiveIngredients = DB::table('medication_patients')
            ->join('medication_ingredients', 'medication_patients.medication_id', '=', 'medication_ingredients.medication_id')
            ->join('active_ingredients', 'medication_ingredients.active_ingredient_id', '=', 'active_ingredients.id')
            ->where('medication_patients.patient_id', $patientId)
            ->where('medication_patients.is_active', true)
            ->select('active_ingredients.id', 'active_ingredients.ingredient_name_en')
            ->get();

        if ($patientActiveIngredients->isEmpty()) {
            return ['is_safe' => true, 'conflicts' => []];
        }

        $patientIngIds = $patientActiveIngredients->pluck('id')->toArray();
        $allIngredientIds = array_merge($medicationIngredientIds, $patientIngIds);

        $allRecords = DB::table('composition_interactions')
            ->whereIn('composition_id', $allIngredientIds)
            ->whereIn('interaction_composition_id', $allIngredientIds)
            ->get();

        $recordIndex = [];
        foreach ($allRecords as $record) {
            $recordIndex[$record->composition_id.'_'.$record->interaction_composition_id] = $record;
            $recordIndex[$record->interaction_composition_id.'_'.$record->composition_id] = $record;
        }

        $targetIngredients = DB::table('active_ingredients')
            ->whereIn('id', $medicationIngredientIds)
            ->get()
            ->keyBy('id');

        $conflicts = [];
        $unverifiedItems = [];
        $newItems = [];
        $isSafe = true;
        $seenKeys = [];
        $newPairKeys = [];
        $nextTempId = -1;

        foreach ($medicationIngredientIds as $targetIngId) {
            $targetIng = $targetIngredients->get($targetIngId);
            if (! $targetIng) {
                continue;
            }

            foreach ($patientActiveIngredients as $patientIng) {
                if ($targetIngId === $patientIng->id) {
                    continue;
                }

                $record = $recordIndex[$targetIngId.'_'.$patientIng->id] ?? null;

                if (! $record) {
                    $ingA = $targetIngId;
                    $ingB = $patientIng->id;
                    $pairKey = $ingA < $ingB ? $ingA.'_'.$ingB : $ingB.'_'.$ingA;
                    if (isset($newPairKeys[$pairKey])) {
                        continue;
                    }
                    $newPairKeys[$pairKey] = true;

                    $newItems[] = [
                        'id' => $nextTempId--,
                        'is_new' => true,
                        'ingredient_id_1' => $ingA,
                        'ingredient_id_2' => $ingB,
                        'drug1' => $targetIng->ingredient_name_en,
                        'drug2' => $patientIng->ingredient_name_en,
                    ];

                    continue;
                }

                if ($record->is_ai_verified) {
                    if ((int) $record->risk_level > 0) {
                        $pair = [$targetIngId, $patientIng->id];
                        sort($pair);
                        $key = 'drug_'.$pair[0].'_'.$pair[1];
                        if (in_array($key, $seenKeys, true)) {
                            continue;
                        }
                        $seenKeys[] = $key;

                        $isSafe = false;
                        $conflicts[] = [
                            'type' => 'drug',
                            'risk_level' => (int) $record->risk_level,
                            'reason' => $record->ai_explanation ?? $record->interaction_effect,
                            'verified_by_ai' => true,
                        ];
                    }
                } else {
                    $unverifiedItems[] = [
                        'id' => $record->id,
                        'drug1' => $targetIng->ingredient_name_en,
                        'drug2' => $patientIng->ingredient_name_en,
                        'pair_ids' => [$targetIngId, $patientIng->id],
                        'record' => $record,
                    ];
                }
            }
        }

        $allItems = array_merge($unverifiedItems, $newItems);
        $this->processDrugBatch($allItems, $isSafe, $conflicts, $seenKeys);

        return ['is_safe' => $isSafe, 'conflicts' => $conflicts];
    }

    // ═══════════════════════════════════════════════════════════════════
    // Shared drug-drug AI batch processing. Used by checkDrugInteractions()
    // and checkIngredientInteractions(). Handles existing (UPDATE) and
    // missing (INSERT via updateOrInsert with sorted IDs) records, plus
    // partial/full AI failure fallback.
    // ═══════════════════════════════════════════════════════════════════
    private function processDrugBatch(array $allItems, bool &$isSafe, array &$conflicts, array &$seenKeys): void
    {
        if ($allItems === []) {
            return;
        }

        $aiResults = $this->llamaApi->evaluateDrugDrugBatch($allItems);
        logger($aiResults);
        if ($aiResults !== null) {
            $resultsById = collect($aiResults)->keyBy('id');

            DB::transaction(function () use ($allItems, $resultsById, &$isSafe, &$conflicts, &$seenKeys) {
                foreach ($allItems as $item) {
                    $aiRes = $resultsById->get($item['id']);

                    if ($item['is_new'] ?? false) {
                        if ($aiRes) {
                            $riskLevel = (int) $aiRes['severity_rating'];
                            $explanation = $aiRes['clinical_explanation'] ?? '';

                            $ids = [$item['ingredient_id_1'], $item['ingredient_id_2']];
                            sort($ids);

                            DB::table('composition_interactions')
                                ->updateOrInsert(
                                    [
                                        'composition_id' => $ids[0],
                                        'interaction_composition_id' => $ids[1],
                                    ],
                                    [
                                        'id' => (string) Str::uuid(),
                                        'risk_level' => $riskLevel,
                                        'ai_explanation' => $explanation,
                                        'interaction_effect' => $explanation,
                                        'is_ai_verified' => true,
                                        'created_at' => now(),
                                        'updated_at' => now(),
                                    ]
                                );

                            if ($riskLevel > 0) {
                                $key = 'drug_'.$ids[0].'_'.$ids[1];
                                if (in_array($key, $seenKeys, true)) {
                                    continue;
                                }
                                $seenKeys[] = $key;

                                $isSafe = false;
                                $conflicts[] = [
                                    'type' => 'drug',
                                    'risk_level' => $riskLevel,
                                    'drug1' => $item['drug1'] ?? null,
                                    'drug2' => $item['drug2'] ?? null,
                                    'reason' => $explanation,
                                    'verified_by_ai' => true,
                                ];
                            }
                        }
                    } else {
                        $record = $item['record'];

                        if ($aiRes) {
                            $riskLevel = (int) $aiRes['severity_rating'];
                            $explanation = $aiRes['clinical_explanation'] ?? $record->interaction_effect;

                            DB::table('composition_interactions')
                                ->where('id', $item['id'])
                                ->update([
                                    'risk_level' => $riskLevel,
                                    'ai_explanation' => $explanation,
                                    'is_ai_verified' => true,
                                    'updated_at' => now(),
                                ]);

                            if ($riskLevel > 0) {
                                $pair = $item['pair_ids'];
                                sort($pair);
                                $key = 'drug_'.$pair[0].'_'.$pair[1];
                                if (in_array($key, $seenKeys, true)) {
                                    continue;
                                }
                                $seenKeys[] = $key;

                                $isSafe = false;
                                $conflicts[] = [
                                    'type' => 'drug',
                                    'risk_level' => $riskLevel,
                                    'drug1' => $item['drug1'] ?? null,
                                    'drug2' => $item['drug2'] ?? null,
                                    'reason' => $explanation,
                                    'verified_by_ai' => true,
                                ];
                            }
                        } else {
                            $pair = $item['pair_ids'];
                            sort($pair);
                            $key = 'drug_'.$pair[0].'_'.$pair[1];
                            if (in_array($key, $seenKeys, true)) {
                                continue;
                            }
                            $seenKeys[] = $key;

                            $isSafe = false;
                            $conflicts[] = [
                                'type' => 'drug',
                                'risk_level' => (int) ($record->risk_level ?? 1),
                                'drug1' => $item['drug1'] ?? null,
                                'drug2' => $item['drug2'] ?? null,
                                'reason' => $record->ai_explanation ?? $record->interaction_effect ?? 'تضارب دوائي محتمل (سقط من تقرير الذكاء الاصطناعي)',
                                'verified_by_ai' => false,
                            ];
                        }
                    }
                }
            });
        } else {
            foreach ($allItems as $item) {
                if ($item['is_new'] ?? false) {
                    continue;
                }

                $record = $item['record'];
                $pair = $item['pair_ids'];
                sort($pair);
                $key = 'drug_'.$pair[0].'_'.$pair[1];
                if (in_array($key, $seenKeys, true)) {
                    continue;
                }
                $seenKeys[] = $key;

                $isSafe = false;
                $conflicts[] = [
                    'type' => 'drug',
                    'risk_level' => (int) ($record->risk_level ?? 1),
                    'drug1' => $item['drug1'] ?? null,
                    'drug2' => $item['drug2'] ?? null,
                    'reason' => $record->ai_explanation ?? $record->interaction_effect ?? 'تضارب دوائي محتمل (فشل الاتصال بالذكاء الاصطناعي)',
                    'verified_by_ai' => false,
                ];
            }
        }
    }

    // ═══════════════════════════════════════════════════════════════════
    // Drug-drug interactions among a set of ingredients (queried meds only).
    // Enumerates all unordered pairs (i < j) within the given ingredient IDs.
    // ═══════════════════════════════════════════════════════════════════
    /**
     * @param  array  $ingredientIds  List of active_ingredient.id values.
     * @return array{is_safe: bool, conflicts: array}
     */
    private function checkIngredientInteractions(array $ingredientIds): array
    {
        $ingredientIds = array_values(array_unique($ingredientIds));

        if (count($ingredientIds) < 2) {
            return ['is_safe' => true, 'conflicts' => []];
        }

        $ingredients = DB::table('active_ingredients')
            ->whereIn('id', $ingredientIds)
            ->get()
            ->keyBy('id');

        $allRecords = DB::table('composition_interactions')
            ->whereIn('composition_id', $ingredientIds)
            ->whereIn('interaction_composition_id', $ingredientIds)
            ->get();

        $recordIndex = [];
        foreach ($allRecords as $record) {
            $recordIndex[$record->composition_id.'_'.$record->interaction_composition_id] = $record;
            $recordIndex[$record->interaction_composition_id.'_'.$record->composition_id] = $record;
        }

        $conflicts = [];
        $unverifiedItems = [];
        $newItems = [];
        $isSafe = true;
        $seenKeys = [];
        $newPairKeys = [];
        $nextTempId = -1;

        sort($ingredientIds);
        $count = count($ingredientIds);

        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $ingA = $ingredientIds[$i];
                $ingB = $ingredientIds[$j];

                $ingredientA = $ingredients->get($ingA);
                $ingredientB = $ingredients->get($ingB);
                if (! $ingredientA || ! $ingredientB) {
                    continue;
                }

                $record = $recordIndex[$ingA.'_'.$ingB] ?? null;

                if (! $record) {
                    $pairKey = $ingA.'_'.$ingB;
                    if (isset($newPairKeys[$pairKey])) {
                        continue;
                    }
                    $newPairKeys[$pairKey] = true;

                    $newItems[] = [
                        'id' => $nextTempId--,
                        'is_new' => true,
                        'ingredient_id_1' => $ingA,
                        'ingredient_id_2' => $ingB,
                        'drug1' => $ingredientA->ingredient_name_en,
                        'drug2' => $ingredientB->ingredient_name_en,
                    ];

                    continue;
                }

                if ($record->is_ai_verified) {
                    if ((int) $record->risk_level > 0) {
                        $key = 'drug_'.$ingA.'_'.$ingB;
                        if (in_array($key, $seenKeys, true)) {
                            continue;
                        }
                        $seenKeys[] = $key;

                        $isSafe = false;
                        $conflicts[] = [
                            'type' => 'drug',
                            'risk_level' => (int) $record->risk_level,
                            'drug1' => $ingredientA->ingredient_name_en,
                            'drug2' => $ingredientB->ingredient_name_en,
                            'reason' => $record->ai_explanation ?? $record->interaction_effect,
                            'verified_by_ai' => true,
                        ];
                    }
                } else {
                    $unverifiedItems[] = [
                        'id' => $record->id,
                        'drug1' => $ingredientA->ingredient_name_en,
                        'drug2' => $ingredientB->ingredient_name_en,
                        'pair_ids' => [$ingA, $ingB],
                        'record' => $record,
                    ];
                }
            }
        }

        $allItems = array_merge($unverifiedItems, $newItems);
        $this->processDrugBatch($allItems, $isSafe, $conflicts, $seenKeys);

        return ['is_safe' => $isSafe, 'conflicts' => $conflicts];
    }

    // ═══════════════════════════════════════════════════════════════════
    // Public entry point for the precheck endpoint. Checks only interactions
    // between the queried medications (no wallet, no disease).
    // ═══════════════════════════════════════════════════════════════════
    /**
     * @param  array  $ingredientIds  List of active_ingredient.id values.
     * @return array{is_safe: bool, conflicts: array}
     */
    public function evaluateQueried(array $ingredientIds): array
    {
        return $this->checkIngredientInteractions($ingredientIds);
    }

    // ═══════════════════════════════════════════════════════════════════
    // Single-medication safety check (disease + wallet drug interactions).
    // ═══════════════════════════════════════════════════════════════════
    /**
     * @return array{is_safe: bool, conflicts: array}
     */
    public function evaluate(string $medicationId, string $patientId): array
    {
        $medicationIngredientIds = DB::table('medication_ingredients')
            ->where('medication_id', $medicationId)
            ->pluck('active_ingredient_id')
            ->toArray();

        if (empty($medicationIngredientIds)) {
            return [
                'is_safe' => true,
                'conflicts' => [],
            ];
        }

        $diseaseResult = $this->checkDiseaseConflicts($medicationIngredientIds, $patientId);
        $drugResult = $this->checkDrugInteractions($medicationIngredientIds, $patientId);

        return [
            'is_safe' => $diseaseResult['is_safe'] && $drugResult['is_safe'],
            'conflicts' => array_merge($diseaseResult['conflicts'], $drugResult['conflicts']),
        ];
    }
}
