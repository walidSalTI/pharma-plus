<?php

declare(strict_types=1);

use App\Models\AiReportAnalysis;
use App\Models\Pharmacist;
use App\Models\Pharmacy;
use App\Models\SearchTelemetry;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::updateOrCreate(['name' => 'pharmacist', 'guard_name' => 'api']);
});

function fakeQwenResponse(): void
{
    Http::fake([
        '*11434*' => Http::response([
            'response' => json_encode([
                'executive_summary' => 'ملخص تنفيذي قصير عن أداء الصيدلية',
                'financial_health_score' => 'جيد',
                'key_findings' => ['هامش الربح مقبول'],
                'actionable_recommendations' => ['تقليل المخزون البطيء الحركة'],
                'inventory_risk_alerts' => ['أدوية قريبة من الانتهاء'],
            ]),
        ]),
    ]);
}

function fakeGroqResponse(): void
{
    Http::fake([
        'api.groq.com*' => Http::response([
            'choices' => [
                [
                    'message' => [
                        'content' => json_encode([
                            'has_epidemic_warning' => true,
                            'detected_disease' => 'Bacterial Pharyngitis / Upper Respiratory Infection',
                            'threat_level' => 'High',
                            'combined_demand_score' => 120,
                            'clinical_summary' => 'ارتفاع ملحوظ في الطلب على علاجات العدوى التنفسية.',
                            'actionable_pharmacy_advice' => [
                                'زيادة مخزون المضادات الحيوية',
                                'الاستعداد لارتفاع الطلب على خافضات الحرارة',
                            ],
                        ], JSON_UNESCAPED_UNICODE),
                    ],
                ],
            ],
        ]),
    ]);
}

it('triggers AI analysis generation and completes the queued job', function () {
    extract(actingAsPharmacist());

    fakeQwenResponse();

    $today = now()->toDateString();

    $response = $this->withToken($token)
        ->postJson("/api/v1/pharmacist/pharmacies/{$pharmacy->id}/reports/ai-insights", [
            'start_date' => $today,
            'end_date' => $today,
        ]);

    $response->assertStatus(202)
        ->assertJsonPath('status', 'accepted')
        ->assertJsonPath('data.type', 'full')
        ->assertJsonPath('data.status', 'pending');

    $analysis = AiReportAnalysis::where('pharmacy_id', $pharmacy->id)->first();

    expect($analysis)->not->toBeNull()
        ->and($analysis->type)->toBe('full')
        ->and($analysis->status)->toBe('completed')
        ->and($analysis->input_snapshot)->toBeArray()
        ->and($analysis->ai_insights)->toBeArray()
        ->and($analysis->ai_insights['financial_health_score'])->toBe('جيد');

    Http::assertSent(fn ($request) => str_contains((string) $request->url(), '11434'));
});

it('keeps only the latest analysis per pharmacy and report type', function () {
    extract(actingAsPharmacist());

    fakeQwenResponse();

    $today = now()->toDateString();

    $this->withToken($token)
        ->postJson("/api/v1/pharmacist/pharmacies/{$pharmacy->id}/reports/ai-insights", [
            'start_date' => $today,
            'end_date' => $today,
            'type' => 'financial',
        ])
        ->assertStatus(202);

    $this->withToken($token)
        ->postJson("/api/v1/pharmacist/pharmacies/{$pharmacy->id}/reports/ai-insights", [
            'start_date' => $today,
            'end_date' => $today,
            'type' => 'financial',
        ])
        ->assertStatus(202);

    expect(AiReportAnalysis::where('pharmacy_id', $pharmacy->id)->where('type', 'financial')->count())->toBe(1);
});

it('returns the latest AI insight for the requested type', function () {
    extract(actingAsPharmacist());

    $today = now()->toDateString();

    AiReportAnalysis::create([
        'pharmacy_id' => $pharmacy->id,
        'type' => 'full',
        'start_date' => $today,
        'end_date' => $today,
        'input_snapshot' => ['financials' => []],
        'ai_insights' => ['financial_health_score' => 'ممتاز'],
        'status' => 'completed',
    ]);

    $response = $this->withToken($token)
        ->getJson("/api/v1/pharmacist/pharmacies/{$pharmacy->id}/reports/ai-insights?type=full");

    $response->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.type', 'full')
        ->assertJsonPath('data.ai_insights.financial_health_score', 'ممتاز')
        ->assertJsonPath('data.status', 'completed');
});

it('returns 404 when no AI analysis has been generated yet', function () {
    extract(actingAsPharmacist());

    $this->withToken($token)
        ->getJson("/api/v1/pharmacist/pharmacies/{$pharmacy->id}/reports/ai-insights")
        ->assertStatus(404);
});

it('forbids staff without the manage permission from AI insights', function () {
    $ownerUser = User::factory()->create();
    $ownerUser->assignRole('pharmacist');
    $owner = Pharmacist::factory()->create(['user_id' => $ownerUser->id]);
    $pharmacy = Pharmacy::factory()->create(['pharmacist_id' => $owner->id]);

    $staffUser = User::factory()->create();
    $staffUser->assignRole('pharmacist');
    $staff = Pharmacist::factory()->create(['user_id' => $staffUser->id]);
    $pharmacy->staffPharmacists()->attach($staff->id, [
        'pharmacy_manage' => false,
        'inventory_manage' => false,
        'operating_hours_manage' => false,
        'orders_process' => true,
        'orders_view_own' => true,
    ]);
    $token = $staffUser->createToken('test')->plainTextToken;

    $today = now()->toDateString();

    $this->withToken($token)
        ->getJson("/api/v1/pharmacist/pharmacies/{$pharmacy->id}/reports/ai-insights")
        ->assertStatus(403);
});

it('triggers epidemic AI analysis and completes the queued job', function () {
    extract(actingAsPharmacist());

    fakeGroqResponse();

    $pharmacy->update(['latitude' => 30.0, 'longitude' => 31.0]);

    SearchTelemetry::create([
        'searched_query' => 'Panadol',
        'resolved_product_name' => 'Panadol Extra',
        'resolved_usage' => 'Antipyretic',
        'latitude' => 30.001,
        'longitude' => 31.001,
        'created_at' => now(),
    ]);

    $response = $this->withToken($token)
        ->postJson("/api/v1/pharmacist/pharmacies/{$pharmacy->id}/reports/ai-insights/epidemic");

    $response->assertStatus(202)
        ->assertJsonPath('message', 'تم إرسال طلب تحليل انتشار الجائحة بنجاح، التقرير قيد المعالجة.')
        ->assertJsonPath('data.type', 'epidemic_demand')
        ->assertJsonPath('data.status', 'pending');

    $analysis = AiReportAnalysis::where('pharmacy_id', $pharmacy->id)
        ->where('type', 'epidemic_demand')
        ->first();

    expect($analysis)->not->toBeNull()
        ->and($analysis->status)->toBe('completed')
        ->and($analysis->ai_insights)->toBeArray()
        ->and($analysis->ai_insights['has_epidemic_warning'])->toBeTrue()
        ->and($analysis->ai_insights['detected_disease'])->toBe('Bacterial Pharyngitis / Upper Respiratory Infection')
        ->and($analysis->input_snapshot)->toBeArray()
        ->and($analysis->input_snapshot['top_usages'])->toBeArray()
        ->and($analysis->input_snapshot['radius_km'])->toBe(10);

    Http::assertSent(fn ($request) => str_contains((string) $request->url(), 'api.groq.com'));
});

it('completes epidemic analysis with a no-data insight when there is no telemetry', function () {
    extract(actingAsPharmacist());

    $pharmacy->update(['latitude' => 30.0, 'longitude' => 31.0]);

    $this->withToken($token)
        ->postJson("/api/v1/pharmacist/pharmacies/{$pharmacy->id}/reports/ai-insights/epidemic")
        ->assertStatus(202);

    $analysis = AiReportAnalysis::where('pharmacy_id', $pharmacy->id)
        ->where('type', 'epidemic_demand')
        ->first();

    expect($analysis)->not->toBeNull()
        ->and($analysis->status)->toBe('completed')
        ->and($analysis->ai_insights['has_epidemic_warning'])->toBeFalse()
        ->and($analysis->ai_insights['message'])->toContain('لا توجد بيانات بحث كافية');
});

it('returns the latest epidemic AI report', function () {
    extract(actingAsPharmacist());

    AiReportAnalysis::create([
        'pharmacy_id' => $pharmacy->id,
        'type' => 'epidemic_demand',
        'input_snapshot' => ['top_usages' => [], 'radius_km' => 10],
        'ai_insights' => ['has_epidemic_warning' => true, 'detected_disease' => 'Bacterial Pharyngitis'],
        'status' => 'completed',
    ]);

    $response = $this->withToken($token)
        ->getJson("/api/v1/pharmacist/pharmacies/{$pharmacy->id}/reports/ai-insights/epidemic");

    $response->assertStatus(200)
        ->assertJsonPath('status', 'success')
        ->assertJsonPath('data.type', 'epidemic_demand')
        ->assertJsonPath('data.ai_insights.has_epidemic_warning', true)
        ->assertJsonPath('data.status', 'completed');
});

it('returns 404 when no epidemic analysis has been generated yet', function () {
    extract(actingAsPharmacist());

    $this->withToken($token)
        ->getJson("/api/v1/pharmacist/pharmacies/{$pharmacy->id}/reports/ai-insights/epidemic")
        ->assertStatus(404);
});

it('forbids staff without the manage permission from epidemic insights', function () {
    $ownerUser = User::factory()->create();
    $ownerUser->assignRole('pharmacist');
    $owner = Pharmacist::factory()->create(['user_id' => $ownerUser->id]);
    $pharmacy = Pharmacy::factory()->create(['pharmacist_id' => $owner->id]);

    $staffUser = User::factory()->create();
    $staffUser->assignRole('pharmacist');
    $staff = Pharmacist::factory()->create(['user_id' => $staffUser->id]);
    $pharmacy->staffPharmacists()->attach($staff->id, [
        'pharmacy_manage' => false,
        'inventory_manage' => false,
        'operating_hours_manage' => false,
        'orders_process' => true,
        'orders_view_own' => true,
    ]);
    $token = $staffUser->createToken('test')->plainTextToken;

    $this->withToken($token)
        ->postJson("/api/v1/pharmacist/pharmacies/{$pharmacy->id}/reports/ai-insights/epidemic")
        ->assertStatus(403);
});
