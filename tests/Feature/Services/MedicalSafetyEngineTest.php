<?php

declare(strict_types=1);

use App\Models\ActiveIngredient;
use App\Models\ActiveIngredientsChronicDisease;
use App\Models\ChronicDisease;
use App\Models\ChronicRecord;
use App\Models\CompositionInteraction;
use App\Models\Medication;
use App\Models\Patient;
use App\Services\LlamaApiService;
use App\Services\MedicalSafetyEngine;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

afterEach(fn () => Mockery::close());

function createIngredient(string $name): ActiveIngredient
{
    $ingredient = new ActiveIngredient;
    $ingredient->ingredient_name_en = $name;
    $ingredient->save();

    return $ingredient;
}

/**
 * @return array{0: Patient, 1: ChronicDisease, 2: ActiveIngredient}
 */
function patientWithDisease(): array
{
    $patient = Patient::factory()->create();

    $disease = new ChronicDisease;
    $disease->code = 'HTN';
    $disease->name_ar = 'ارتفاع ضغط الدم';
    $disease->name_en = 'Hypertension';
    $disease->save();

    $record = new ChronicRecord;
    $record->chronic_disease_id = $disease->id;
    $record->patient_id = $patient->id;
    $record->diagnosis_year = 2020;
    $record->severity = 'medium';
    $record->save();

    return [$patient, $disease, createIngredient('Ibuprofen')];
}

/**
 * @return array{0: Patient, 1: ActiveIngredient, 2: ActiveIngredient}
 */
function patientWithWallet(): array
{
    $patient = Patient::factory()->create();

    $walletMed = Medication::factory()->create();
    $walletIngredient = createIngredient('Aspirin');

    DB::table('medication_ingredients')->insert([
        'id' => (string) Str::uuid(),
        'medication_id' => $walletMed->id,
        'active_ingredient_id' => $walletIngredient->id,
        'active_ratio' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('medication_patients')->insert([
        'id' => (string) Str::uuid(),
        'medication_id' => $walletMed->id,
        'patient_id' => $patient->id,
        'state' => 'permanent',
        'dosage' => '1 tablet daily',
        'frequency' => 'daily',
        'refill_risk' => false,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return [$patient, $walletIngredient, createIngredient('Warfarin')];
}

describe('checkDiseaseConflicts', function () {
    it('calls the AI and persists a new mapping when no mapping exists', function () {
        [$patient, $disease, $ingredient] = patientWithDisease();

        $llama = Mockery::spy(LlamaApiService::class);
        $llama->shouldReceive('evaluateDrugDiseaseBatch')
            ->andReturn([['id' => -1, 'severity_rating' => 2, 'clinical_explanation' => 'NSAIDs can raise blood pressure.']]);

        $result = (new MedicalSafetyEngine($llama))->checkDiseaseConflicts([$ingredient->id], $patient->id);

        $llama->shouldHaveReceived('evaluateDrugDiseaseBatch')->once();

        expect($result['is_safe'])->toBeFalse();
        expect($result['conflicts'])->toHaveCount(1);
        expect($result['conflicts'][0]['type'])->toBe('disease');
        expect($result['conflicts'][0]['risk_level'])->toBe(2);
        expect($result['conflicts'][0]['disease'])->toBe('Hypertension');
        expect($result['conflicts'][0]['disease_id'])->toBe($disease->id);
        expect($result['conflicts'][0]['verified_by_ai'])->toBeTrue();

        $this->assertDatabaseHas('active_ingredients_chronic_disease', [
            'active_ingredient_id' => $ingredient->id,
            'chronic_disease_id' => $disease->id,
            'risk_level' => 2,
            'is_ai_verified' => 1,
            'ai_explanation' => 'NSAIDs can raise blood pressure.',
        ]);
    });

    it('calls the AI and updates an existing unverified mapping', function () {
        [$patient, $disease, $ingredient] = patientWithDisease();

        $mapping = ActiveIngredientsChronicDisease::create([
            'chronic_disease_id' => $disease->id,
            'active_ingredient_id' => $ingredient->id,
            'risk_level' => 0,
            'is_ai_verified' => false,
            'conflict_reason' => 'old reason',
        ]);

        $llama = Mockery::spy(LlamaApiService::class);
        $llama->shouldReceive('evaluateDrugDiseaseBatch')
            ->andReturn([['id' => $mapping->id, 'severity_rating' => 2, 'clinical_explanation' => 'Confirmed interaction.']]);

        $result = (new MedicalSafetyEngine($llama))->checkDiseaseConflicts([$ingredient->id], $patient->id);

        $llama->shouldHaveReceived('evaluateDrugDiseaseBatch')->once();

        expect($result['is_safe'])->toBeFalse();
        expect($result['conflicts'][0]['risk_level'])->toBe(2);
        expect($result['conflicts'][0]['disease'])->toBe('Hypertension');
        expect($result['conflicts'][0]['disease_id'])->toBe($disease->id);

        $this->assertDatabaseHas('active_ingredients_chronic_disease', [
            'id' => $mapping->id,
            'risk_level' => 2,
            'is_ai_verified' => 1,
            'ai_explanation' => 'Confirmed interaction.',
        ]);
    });

    it('uses the stored verdict without calling the AI for verified mappings', function () {
        [$patient, $disease, $ingredient] = patientWithDisease();

        ActiveIngredientsChronicDisease::create([
            'chronic_disease_id' => $disease->id,
            'active_ingredient_id' => $ingredient->id,
            'risk_level' => 2,
            'is_ai_verified' => true,
            'ai_explanation' => 'Known contraindication.',
        ]);

        $llama = Mockery::spy(LlamaApiService::class);
        $result = (new MedicalSafetyEngine($llama))->checkDiseaseConflicts([$ingredient->id], $patient->id);

        $llama->shouldNotHaveReceived('evaluateDrugDiseaseBatch');

        expect($result['is_safe'])->toBeFalse();
        expect($result['conflicts'])->toHaveCount(1);
        expect($result['conflicts'][0]['reason'])->toBe('Known contraindication.');
        expect($result['conflicts'][0]['disease'])->toBe('Hypertension');
        expect($result['conflicts'][0]['disease_id'])->toBe($disease->id);
        expect($result['conflicts'][0]['verified_by_ai'])->toBeTrue();
    });

    it('reports safe and skips the AI when a verified mapping has zero risk', function () {
        [$patient, $disease, $ingredient] = patientWithDisease();

        ActiveIngredientsChronicDisease::create([
            'chronic_disease_id' => $disease->id,
            'active_ingredient_id' => $ingredient->id,
            'risk_level' => 0,
            'is_ai_verified' => true,
            'ai_explanation' => 'No interaction expected.',
        ]);

        $llama = Mockery::spy(LlamaApiService::class);
        $result = (new MedicalSafetyEngine($llama))->checkDiseaseConflicts([$ingredient->id], $patient->id);

        $llama->shouldNotHaveReceived('evaluateDrugDiseaseBatch');

        expect($result['is_safe'])->toBeTrue();
        expect($result['conflicts'])->toBeEmpty();
    });

    it('treats new mappings as safe when the AI response is null', function () {
        [$patient, $disease, $ingredient] = patientWithDisease();

        $llama = Mockery::spy(LlamaApiService::class);
        $llama->shouldReceive('evaluateDrugDiseaseBatch')->andReturn(null);

        $result = (new MedicalSafetyEngine($llama))->checkDiseaseConflicts([$ingredient->id], $patient->id);

        $llama->shouldHaveReceived('evaluateDrugDiseaseBatch')->once();

        expect($result['is_safe'])->toBeTrue();
        expect($result['conflicts'])->toBeEmpty();

        $this->assertDatabaseMissing('active_ingredients_chronic_disease', [
            'active_ingredient_id' => $ingredient->id,
            'chronic_disease_id' => $disease->id,
        ]);
    });

    it('returns safe without calling the AI when the patient has no chronic diseases', function () {
        $patient = Patient::factory()->create();
        $ingredient = createIngredient('Ibuprofen');

        $llama = Mockery::spy(LlamaApiService::class);
        $result = (new MedicalSafetyEngine($llama))->checkDiseaseConflicts([$ingredient->id], $patient->id);

        $llama->shouldNotHaveReceived('evaluateDrugDiseaseBatch');

        expect($result['is_safe'])->toBeTrue();
        expect($result['conflicts'])->toBeEmpty();
    });
});

describe('checkDrugInteractions', function () {
    it('calls the AI and persists a new interaction when none exists', function () {
        [$patient, $walletIngredient, $targetIngredient] = patientWithWallet();

        $llama = Mockery::spy(LlamaApiService::class);
        $llama->shouldReceive('evaluateDrugDrugBatch')
            ->andReturn([['id' => -1, 'severity_rating' => 3, 'clinical_explanation' => 'Aspirin with Warfarin increases bleeding risk.']]);

        $result = (new MedicalSafetyEngine($llama))->checkDrugInteractions([$targetIngredient->id], $patient->id);

        $llama->shouldHaveReceived('evaluateDrugDrugBatch')->once();

        expect($result['is_safe'])->toBeFalse();
        expect($result['conflicts'])->toHaveCount(1);
        expect($result['conflicts'][0]['type'])->toBe('drug');
        expect($result['conflicts'][0]['risk_level'])->toBe(3);

        $ids = [$walletIngredient->id, $targetIngredient->id];
        sort($ids);

        $this->assertDatabaseHas('composition_interactions', [
            'composition_id' => $ids[0],
            'interaction_composition_id' => $ids[1],
            'risk_level' => 3,
            'is_ai_verified' => 1,
            'ai_explanation' => 'Aspirin with Warfarin increases bleeding risk.',
        ]);
    });

    it('calls the AI and updates an existing unverified interaction', function () {
        [$patient, $walletIngredient, $targetIngredient] = patientWithWallet();

        $ids = [$walletIngredient->id, $targetIngredient->id];
        sort($ids);

        $interaction = CompositionInteraction::create([
            'composition_id' => $ids[0],
            'interaction_composition_id' => $ids[1],
            'interaction_effect' => 'increased bleeding',
            'risk_level' => 0,
            'is_ai_verified' => false,
        ]);

        $llama = Mockery::spy(LlamaApiService::class);
        $llama->shouldReceive('evaluateDrugDrugBatch')
            ->andReturn([['id' => $interaction->id, 'severity_rating' => 3, 'clinical_explanation' => 'Bleeding risk confirmed.']]);

        $result = (new MedicalSafetyEngine($llama))->checkDrugInteractions([$targetIngredient->id], $patient->id);

        $llama->shouldHaveReceived('evaluateDrugDrugBatch')->once();

        expect($result['is_safe'])->toBeFalse();
        expect($result['conflicts'][0]['risk_level'])->toBe(3);

        $this->assertDatabaseHas('composition_interactions', [
            'id' => $interaction->id,
            'risk_level' => 3,
            'is_ai_verified' => 1,
            'ai_explanation' => 'Bleeding risk confirmed.',
        ]);
    });

    it('uses the stored verdict without calling the AI for verified interactions', function () {
        [$patient, $walletIngredient, $targetIngredient] = patientWithWallet();

        $ids = [$walletIngredient->id, $targetIngredient->id];
        sort($ids);

        CompositionInteraction::create([
            'composition_id' => $ids[0],
            'interaction_composition_id' => $ids[1],
            'interaction_effect' => 'increased bleeding',
            'risk_level' => 2,
            'is_ai_verified' => true,
            'ai_explanation' => 'Verified bleeding risk.',
        ]);

        $llama = Mockery::spy(LlamaApiService::class);
        $result = (new MedicalSafetyEngine($llama))->checkDrugInteractions([$targetIngredient->id], $patient->id);

        $llama->shouldNotHaveReceived('evaluateDrugDrugBatch');

        expect($result['is_safe'])->toBeFalse();
        expect($result['conflicts'])->toHaveCount(1);
        expect($result['conflicts'][0]['reason'])->toBe('Verified bleeding risk.');
        expect($result['conflicts'][0]['verified_by_ai'])->toBeTrue();
    });

    it('reports safe and skips the AI when a verified interaction has zero risk', function () {
        [$patient, $walletIngredient, $targetIngredient] = patientWithWallet();

        $ids = [$walletIngredient->id, $targetIngredient->id];
        sort($ids);

        CompositionInteraction::create([
            'composition_id' => $ids[0],
            'interaction_composition_id' => $ids[1],
            'interaction_effect' => 'none',
            'risk_level' => 0,
            'is_ai_verified' => true,
            'ai_explanation' => 'No interaction expected.',
        ]);

        $llama = Mockery::spy(LlamaApiService::class);
        $result = (new MedicalSafetyEngine($llama))->checkDrugInteractions([$targetIngredient->id], $patient->id);

        $llama->shouldNotHaveReceived('evaluateDrugDrugBatch');

        expect($result['is_safe'])->toBeTrue();
        expect($result['conflicts'])->toBeEmpty();
    });

    it('treats new interactions as safe when the AI response is null', function () {
        [$patient, $walletIngredient, $targetIngredient] = patientWithWallet();

        $llama = Mockery::spy(LlamaApiService::class);
        $llama->shouldReceive('evaluateDrugDrugBatch')->andReturn(null);

        $result = (new MedicalSafetyEngine($llama))->checkDrugInteractions([$targetIngredient->id], $patient->id);

        $llama->shouldHaveReceived('evaluateDrugDrugBatch')->once();

        expect($result['is_safe'])->toBeTrue();
        expect($result['conflicts'])->toBeEmpty();

        $ids = [$walletIngredient->id, $targetIngredient->id];
        sort($ids);

        $this->assertDatabaseMissing('composition_interactions', [
            'composition_id' => $ids[0],
            'interaction_composition_id' => $ids[1],
        ]);
    });

    it('returns safe without calling the AI when the patient wallet is empty', function () {
        $patient = Patient::factory()->create();
        $targetIngredient = createIngredient('Warfarin');

        $llama = Mockery::spy(LlamaApiService::class);
        $result = (new MedicalSafetyEngine($llama))->checkDrugInteractions([$targetIngredient->id], $patient->id);

        $llama->shouldNotHaveReceived('evaluateDrugDrugBatch');

        expect($result['is_safe'])->toBeTrue();
        expect($result['conflicts'])->toBeEmpty();
    });
});

describe('evaluateQueried', function () {
    it('calls the AI and persists an interaction for a new queried pair', function () {
        $ingA = createIngredient('Aspirin');
        $ingB = createIngredient('Warfarin');

        $llama = Mockery::spy(LlamaApiService::class);
        $llama->shouldReceive('evaluateDrugDrugBatch')
            ->andReturn([['id' => -1, 'severity_rating' => 2, 'clinical_explanation' => 'Bleeding risk.']]);

        $result = (new MedicalSafetyEngine($llama))->evaluateQueried([$ingA->id, $ingB->id]);

        $llama->shouldHaveReceived('evaluateDrugDrugBatch')->once();

        expect($result['is_safe'])->toBeFalse();

        $ids = [$ingA->id, $ingB->id];
        sort($ids);

        $this->assertDatabaseHas('composition_interactions', [
            'composition_id' => $ids[0],
            'interaction_composition_id' => $ids[1],
            'risk_level' => 2,
            'is_ai_verified' => 1,
        ]);
    });

    it('calls the AI and updates an existing unverified queried pair', function () {
        $ingA = createIngredient('Aspirin');
        $ingB = createIngredient('Warfarin');

        $ids = [$ingA->id, $ingB->id];
        sort($ids);

        $interaction = CompositionInteraction::create([
            'composition_id' => $ids[0],
            'interaction_composition_id' => $ids[1],
            'interaction_effect' => 'increased bleeding',
            'risk_level' => 0,
            'is_ai_verified' => false,
        ]);

        $llama = Mockery::spy(LlamaApiService::class);
        $llama->shouldReceive('evaluateDrugDrugBatch')
            ->andReturn([['id' => $interaction->id, 'severity_rating' => 2, 'clinical_explanation' => 'Confirmed bleeding risk.']]);

        $result = (new MedicalSafetyEngine($llama))->evaluateQueried([$ingA->id, $ingB->id]);

        $llama->shouldHaveReceived('evaluateDrugDrugBatch')->once();

        expect($result['is_safe'])->toBeFalse();

        $this->assertDatabaseHas('composition_interactions', [
            'id' => $interaction->id,
            'risk_level' => 2,
            'is_ai_verified' => 1,
        ]);
    });

    it('uses the stored verdict without calling the AI for a verified queried pair', function () {
        $ingA = createIngredient('Aspirin');
        $ingB = createIngredient('Warfarin');

        $ids = [$ingA->id, $ingB->id];
        sort($ids);

        CompositionInteraction::create([
            'composition_id' => $ids[0],
            'interaction_composition_id' => $ids[1],
            'interaction_effect' => 'increased bleeding',
            'risk_level' => 2,
            'is_ai_verified' => true,
            'ai_explanation' => 'Verified bleeding risk.',
        ]);

        $llama = Mockery::spy(LlamaApiService::class);
        $result = (new MedicalSafetyEngine($llama))->evaluateQueried([$ingA->id, $ingB->id]);

        $llama->shouldNotHaveReceived('evaluateDrugDrugBatch');

        expect($result['is_safe'])->toBeFalse();
        expect($result['conflicts'][0]['reason'])->toBe('Verified bleeding risk.');
    });
});

describe('evaluate', function () {
    it('combines verified disease and drug conflicts for a single medication', function () {
        $patient = Patient::factory()->create();

        $disease = new ChronicDisease;
        $disease->code = 'HTN';
        $disease->name_ar = 'hypertension';
        $disease->name_en = 'Hypertension';
        $disease->save();

        $record = new ChronicRecord;
        $record->chronic_disease_id = $disease->id;
        $record->patient_id = $patient->id;
        $record->diagnosis_year = 2020;
        $record->severity = 'medium';
        $record->save();

        $walletMed = Medication::factory()->create();
        $walletIngredient = createIngredient('Aspirin');
        $targetIngredient = createIngredient('Ibuprofen');

        DB::table('medication_ingredients')->insert([
            'id' => (string) Str::uuid(),
            'medication_id' => $walletMed->id,
            'active_ingredient_id' => $walletIngredient->id,
            'active_ratio' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('medication_patients')->insert([
            'id' => (string) Str::uuid(),
            'medication_id' => $walletMed->id,
            'patient_id' => $patient->id,
            'state' => 'permanent',
            'dosage' => '1 tablet daily',
            'frequency' => 'daily',
            'refill_risk' => false,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $newMed = Medication::factory()->create();

        DB::table('medication_ingredients')->insert([
            'id' => (string) Str::uuid(),
            'medication_id' => $newMed->id,
            'active_ingredient_id' => $targetIngredient->id,
            'active_ratio' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        ActiveIngredientsChronicDisease::create([
            'chronic_disease_id' => $disease->id,
            'active_ingredient_id' => $targetIngredient->id,
            'risk_level' => 2,
            'is_ai_verified' => true,
            'ai_explanation' => 'NSAID contraindicated.',
        ]);

        $ids = [$walletIngredient->id, $targetIngredient->id];
        sort($ids);

        CompositionInteraction::create([
            'composition_id' => $ids[0],
            'interaction_composition_id' => $ids[1],
            'interaction_effect' => 'increased bleeding',
            'risk_level' => 2,
            'is_ai_verified' => true,
            'ai_explanation' => 'Bleeding risk.',
        ]);

        $llama = Mockery::spy(LlamaApiService::class);
        $result = (new MedicalSafetyEngine($llama))->evaluate($newMed->id, $patient->id);

        $llama->shouldNotHaveReceived('evaluateDrugDiseaseBatch');
        $llama->shouldNotHaveReceived('evaluateDrugDrugBatch');

        expect($result['is_safe'])->toBeFalse();
        expect($result['conflicts'])->toHaveCount(2);
        expect(collect($result['conflicts'])->pluck('type')->sort()->values()->all())->toBe(['disease', 'drug']);
    });
});
