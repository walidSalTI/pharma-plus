<?php

declare(strict_types=1);

use App\Models\Expense;
use App\Models\Medication;
use App\Models\MedicationOrder;
use App\Models\MedicationOrderItem;
use App\Models\Pharmacist;
use App\Models\Pharmacy;
use App\Models\PharmacyInventory;
use App\Models\PharmacyInventoryBatch;
use App\Models\Salary;
use App\Models\SearchTelemetry;
use App\Models\User;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::updateOrCreate(['name' => 'pharmacist', 'guard_name' => 'api']);
});

function createCompletedOrder(array $overrides = []): MedicationOrder
{
    return MedicationOrder::create(array_merge([
        'status' => 'completed',
        'type' => 'sale',
        'total_price' => 0,
        'total_cost' => 0,
        'invoice_number' => 'INV-'.Str::uuid(),
        'created_at' => now(),
    ], $overrides));
}

it('returns an accurate financial summary', function () {
    extract(actingAsPharmacist());

    $today = now()->toDateString();

    createCompletedOrder([
        'pharmacy_id' => $pharmacy->id,
        'total_price' => 100,
        'total_cost' => 30,
    ]);

    createCompletedOrder([
        'pharmacy_id' => $pharmacy->id,
        'type' => 'customer_return',
        'total_price' => 20,
        'total_cost' => 6,
    ]);

    createCompletedOrder([
        'pharmacy_id' => $pharmacy->id,
        'type' => 'damaged',
        'total_price' => 0,
        'total_cost' => 10,
    ]);

    Expense::create([
        'pharmacy_id' => $pharmacy->id,
        'title' => 'Monthly rent',
        'amount' => 15,
        'category' => 'rent',
        'expense_date' => $today,
    ]);

    Salary::create([
        'pharmacy_id' => $pharmacy->id,
        'recipient_name' => 'Staff Member',
        'base_amount' => 20,
        'bonus' => 0,
        'deductions' => 0,
        'net_amount' => 20,
        'salary_period' => now()->format('Y-m'),
        'paid_at' => $today,
    ]);

    $response = $this->withToken($token)
        ->getJson("/api/v1/pharmacist/pharmacies/{$pharmacy->id}/reports/financial-summary?start_date={$today}&end_date={$today}");

    $response->assertStatus(200)
        ->assertJsonPath('data.gross_sales', 100)
        ->assertJsonPath('data.returns_amount', 20)
        ->assertJsonPath('data.returns_count', 1)
        ->assertJsonPath('data.net_revenue', 80)
        ->assertJsonPath('data.gross_cogs', 30)
        ->assertJsonPath('data.returns_cogs', 6)
        ->assertJsonPath('data.net_cogs', 24)
        ->assertJsonPath('data.gross_profit', 56)
        ->assertJsonPath('data.operational_losses.damaged_cost', 10)
        ->assertJsonPath('data.operational_losses.expenses', 15)
        ->assertJsonPath('data.operational_losses.salaries', 20)
        ->assertJsonPath('data.expense_breakdown.0.category', 'rent')
        ->assertJsonPath('data.expense_breakdown.0.total', 15)
        ->assertJsonPath('data.net_profit', 11);
});

it('requires a date range for the financial summary', function () {
    extract(actingAsPharmacist());

    $this->withToken($token)
        ->getJson("/api/v1/pharmacist/pharmacies/{$pharmacy->id}/reports/financial-summary")
        ->assertStatus(422);
});

it('returns top profitable medications ordered by net profit', function () {
    extract(actingAsPharmacist());

    $medA = Medication::factory()->create();
    $medB = Medication::factory()->create();
    $today = now()->toDateString();

    $orderA = createCompletedOrder([
        'pharmacy_id' => $pharmacy->id,
        'total_price' => 100,
        'total_cost' => 60,
    ]);
    MedicationOrderItem::create([
        'medication_order_id' => $orderA->id,
        'medication_id' => $medA->id,
        'wholesale_price_at_sale' => 30,
        'quantity' => 2,
        'price' => 50,
    ]);

    $orderB = createCompletedOrder([
        'pharmacy_id' => $pharmacy->id,
        'total_price' => 25,
        'total_cost' => 10,
    ]);
    MedicationOrderItem::create([
        'medication_order_id' => $orderB->id,
        'medication_id' => $medB->id,
        'wholesale_price_at_sale' => 10,
        'quantity' => 1,
        'price' => 25,
    ]);

    $response = $this->withToken($token)
        ->getJson("/api/v1/pharmacist/pharmacies/{$pharmacy->id}/reports/top-medications?start_date={$today}&end_date={$today}&limit=5");

    $response->assertStatus(200);

    $data = $response->json('data');

    expect($data)->toHaveCount(2)
        ->and($data[0]['medication_id'])->toBe($medA->id)
        ->and($data[0]['name'])->toBe($medA->product->name)
        ->and($data[0]['units_sold'])->toBe(2)
        ->and($data[0]['net_profit'])->toBe(40)
        ->and($data[1]['medication_id'])->toBe($medB->id)
        ->and($data[1]['net_profit'])->toBe(15);
});

it('returns most demanded medications within the pharmacy radius', function () {
    extract(actingAsPharmacist());

    $pharmacy->update(['latitude' => 30.0, 'longitude' => 31.0]);
    $today = now()->toDateString();

    foreach (range(1, 4) as $i) {
        SearchTelemetry::create([
            'searched_query' => 'Panadol',
            'resolved_product_name' => 'Panadol Extra',
            'latitude' => 30.001 + $i * 0.0001,
            'longitude' => 31.001,
            'created_at' => now(),
        ]);
    }

    SearchTelemetry::create([
        'searched_query' => 'Aspirin',
        'resolved_product_name' => 'Aspirin 100mg',
        'latitude' => 30.002,
        'longitude' => 31.002,
        'created_at' => now(),
    ]);

    SearchTelemetry::create([
        'searched_query' => 'Faraway Drug',
        'latitude' => 35.0,
        'longitude' => 35.0,
        'created_at' => now(),
    ]);

    $response = $this->withToken($token)
        ->getJson("/api/v1/pharmacist/pharmacies/{$pharmacy->id}/reports/demand?start_date={$today}&end_date={$today}&radius=10");

    $response->assertStatus(200);

    $data = $response->json('data');

    expect($data)->toHaveCount(2)
        ->and($data[0]['medication'])->toBe('Panadol Extra')
        ->and($data[0]['demand_count'])->toBe(4)
        ->and($data[1]['medication'])->toBe('Aspirin 100mg')
        ->and($data[1]['demand_count'])->toBe(1);
});

it('returns expired and nearing-expiry inventory', function () {
    extract(actingAsPharmacist());

    $medication = Medication::factory()->create();
    $inventory = PharmacyInventory::factory()->create([
        'pharmacy_id' => $pharmacy->id,
        'medication_id' => $medication->id,
        'stock' => 30,
        'price' => 50,
    ]);

    PharmacyInventoryBatch::create([
        'pharmacy_inventory_id' => $inventory->id,
        'batch_number' => 'EXP-1',
        'quantity' => 10,
        'wholesale_price' => 20,
        'expiration_date' => now()->subDay()->toDateString(),
    ]);

    PharmacyInventoryBatch::create([
        'pharmacy_inventory_id' => $inventory->id,
        'batch_number' => 'NEAR-1',
        'quantity' => 20,
        'wholesale_price' => 30,
        'expiration_date' => now()->addDays(10)->toDateString(),
    ]);

    $response = $this->withToken($token)
        ->getJson("/api/v1/pharmacist/pharmacies/{$pharmacy->id}/reports/inventory-expiring?days=30");

    $response->assertStatus(200)
        ->assertJsonPath('data.expired.total_units', 10)
        ->assertJsonPath('data.expired.total_loss_value', 200)
        ->assertJsonPath('data.expired.items.0.name', $medication->product->name)
        ->assertJsonPath('data.nearing_expiry.days_window', 30)
        ->assertJsonPath('data.nearing_expiry.total_units', 20)
        ->assertJsonPath('data.nearing_expiry.total_stock_value', 600);
});

it('detects stagnant and slow-moving stock', function () {
    extract(actingAsPharmacist());

    $neverSold = Medication::factory()->create();
    $oldSold = Medication::factory()->create();
    $recentSold = Medication::factory()->create();

    PharmacyInventory::factory()->create([
        'pharmacy_id' => $pharmacy->id,
        'medication_id' => $neverSold->id,
        'stock' => 50,
        'price' => 20,
    ]);

    PharmacyInventory::factory()->create([
        'pharmacy_id' => $pharmacy->id,
        'medication_id' => $oldSold->id,
        'stock' => 10,
        'price' => 30,
    ]);

    PharmacyInventory::factory()->create([
        'pharmacy_id' => $pharmacy->id,
        'medication_id' => $recentSold->id,
        'stock' => 5,
        'price' => 40,
    ]);

    $oldOrder = createCompletedOrder([
        'pharmacy_id' => $pharmacy->id,
        'total_price' => 30,
        'total_cost' => 20,
        'created_at' => now()->subDays(120),
    ]);
    MedicationOrderItem::create([
        'medication_order_id' => $oldOrder->id,
        'medication_id' => $oldSold->id,
        'wholesale_price_at_sale' => 20,
        'quantity' => 1,
        'price' => 30,
    ]);

    $recentOrder = createCompletedOrder([
        'pharmacy_id' => $pharmacy->id,
        'total_price' => 40,
        'total_cost' => 30,
        'created_at' => now()->subDays(5),
    ]);
    MedicationOrderItem::create([
        'medication_order_id' => $recentOrder->id,
        'medication_id' => $recentSold->id,
        'wholesale_price_at_sale' => 30,
        'quantity' => 1,
        'price' => 40,
    ]);

    $response = $this->withToken($token)
        ->getJson("/api/v1/pharmacist/pharmacies/{$pharmacy->id}/reports/slow-moving?days=90");

    $response->assertStatus(200);

    $data = collect($response->json('data'));

    expect($data->pluck('medication_id'))
        ->toContain($neverSold->id)
        ->toContain($oldSold->id)
        ->not->toContain($recentSold->id);
});

it('returns staff performance metrics grouped by the processing staff member', function () {
    extract(actingAsPharmacist());

    $staffUser = User::factory()->create();
    $staff = Pharmacist::factory()->create(['user_id' => $staffUser->id]);
    $today = now()->toDateString();

    createCompletedOrder([
        'pharmacy_id' => $pharmacy->id,
        'pharmacist_id' => $staff->id,
        'total_price' => 100,
        'total_cost' => 60,
    ]);

    createCompletedOrder([
        'pharmacy_id' => $pharmacy->id,
        'pharmacist_id' => $staff->id,
        'total_price' => 300,
        'total_cost' => 180,
    ]);

    createCompletedOrder([
        'pharmacy_id' => $pharmacy->id,
        'pharmacist_id' => $staff->id,
        'type' => 'customer_return',
        'total_price' => 50,
        'total_cost' => 30,
    ]);

    $response = $this->withToken($token)
        ->getJson("/api/v1/pharmacist/pharmacies/{$pharmacy->id}/reports/staff-performance?start_date={$today}&end_date={$today}");

    $response->assertStatus(200);

    $data = $response->json('data');

    expect($data)->toHaveCount(1)
        ->and($data[0]['pharmacist_id'])->toBe($staff->id)
        ->and($data[0]['name'])->toBe($staffUser->f_name.' '.$staffUser->l_name)
        ->and($data[0]['total_orders'])->toBe(2)
        ->and($data[0]['total_sales_volume'])->toBe(400)
        ->and($data[0]['avg_order_value'])->toBe(200)
        ->and($data[0]['total_returns'])->toBe(1)
        ->and($data[0]['returns_amount'])->toBe(50)
        ->and($data[0]['return_rate'])->toBe(0.5);
});

it('forbids staff without the manage permission from viewing reports', function () {
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
        ->getJson("/api/v1/pharmacist/pharmacies/{$pharmacy->id}/reports/financial-summary?start_date={$today}&end_date={$today}")
        ->assertStatus(403);
});
