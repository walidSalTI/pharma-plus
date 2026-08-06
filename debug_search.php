<?php

use App\Models\Medication;
use App\Models\Pharmacy;
use App\Models\PharmacyInventory;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

echo "=== Medications matching %capo% ===\n";
$meds = Medication::with('product')
    ->whereHas('product', fn ($q) => $q->where('name', 'like', '%capo%'))
    ->get()
    ->pluck('product.name', 'id');
foreach ($meds as $id => $name) {
    echo "$id: $name\n";
}

echo "\n=== Pharmacies ===\n";
$pharmacies = Pharmacy::all();
foreach ($pharmacies as $p) {
    echo "{$p->id}: {$p->name} ({$p->latitude}, {$p->longitude})\n";
}

echo "\n=== Inventory sample (first 10) ===\n";
$inventories = PharmacyInventory::limit(10)->get();
foreach ($inventories as $inv) {
    echo "Medication ID: {$inv->medication_id}, Stock: {$inv->stock}, Price: {$inv->price}\n";
}

echo "\n=== Direct SQL check for capo in inventory ===\n";
$rows = DB::table('pharmacy_inventories')
    ->join('pharmacies', 'pharmacies.id', '=', 'pharmacy_inventories.pharmacy_id')
    ->join('medications', 'medications.id', '=', 'pharmacy_inventories.medication_id')
    ->join('products', 'products.id', '=', 'medications.product_id')
    ->where('products.name', 'like', '%capo%')
    ->select('pharmacies.name as pharmacy', 'products.name as trade_name', 'pharmacy_inventories.stock', 'pharmacies.latitude', 'pharmacies.longitude')
    ->get();
foreach ($rows as $r) {
    echo "Pharmacy: {$r->pharmacy}, Med: {$r->trade_name}, Stock: {$r->stock}, Lat: {$r->latitude}, Lng: {$r->longitude}\n";
}
if ($rows->isEmpty()) {
    echo "No inventory matches for capo medications.\n";
}
