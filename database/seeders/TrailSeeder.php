<?php

namespace Database\Seeders;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TrailSeeder extends Seeder
{
    public function run()
    {
        $now = Carbon::now();
        $hashedPassword = Hash::make('123456789'); // كلمة السر الافتراضية للجميع
        $todayDayOfWeek = $now->dayOfWeek;

        // ============================================================
        // 1. PHARMACISTS & PHARMACIES
        // ============================================================
        $pharmaciesData = [
            [
                'key' => 'asad',
                'pharmacy_name' => 'صيدلية اسد الدين',
                'lat' => 33.54098710189437,
                'lng' => 36.302386471867365,
                'location' => 'ركن الدين',
                'first_name' => 'أسد',
                'last_name' => 'الدين',
                'email' => 'asad_al_din@pharmacy.com',
                'phone' => '0911111111',
                'gender' => 'male',
                'syndicate_card' => 'SYN-1001',
                'is_24_today' => true,
            ],
            [
                'key' => 'hikma',
                'pharmacy_name' => 'صيدلية الحكمة',
                'lat' => 33.540125944730114,
                'lng' => 36.30347901949176,
                'location' => 'ركن الدين',
                'first_name' => 'حكمة',
                'last_name' => 'الشام',
                'email' => 'al_hikma@pharmacy.com',
                'phone' => '0922222222',
                'gender' => 'female',
                'syndicate_card' => 'SYN-1002',
                'is_24_today' => true,
            ],
            [
                'key' => 'hamish',
                'pharmacy_name' => 'صيدلية حاميش',
                'lat' => 33.55202219931939,
                'lng' => 36.32137646673937,
                'location' => 'حاميش',
                'first_name' => 'حاميش',
                'last_name' => 'الطبي',
                'email' => 'hamish@pharmacy.com',
                'phone' => '0933333333',
                'gender' => 'male',
                'syndicate_card' => 'SYN-1003',
                'is_24_today' => true,
            ],
            [
                'key' => 'safaa',
                'pharmacy_name' => 'صيدلية صفاء الشعار',
                'lat' => 33.55801111132364,
                'lng' => 36.32563487939663,
                'location' => 'برزة',
                'first_name' => 'صفاء',
                'last_name' => 'الشعار',
                'email' => 'safaa_alshaar@pharmacy.com',
                'phone' => '0944444444',
                'gender' => 'female',
                'syndicate_card' => 'SYN-1004',
                'is_24_today' => true,
            ],
            [
                'key' => 'yasmeen',
                'pharmacy_name' => 'صيدلية ياسمين الشام',
                'lat' => 33.48393107957557,
                'lng' => 36.25674240712815,
                'location' => 'كفرسوسة لوان',
                'first_name' => 'ياسمين',
                'last_name' => 'الشام',
                'email' => 'yasmeen_alsham@pharmacy.com',
                'phone' => '0955555555',
                'gender' => 'female',
                'syndicate_card' => 'SYN-1005',
                'is_24_today' => true,
            ],
            [
                'key' => 'adra',
                'pharmacy_name' => 'صيدلية عدرا',
                'lat' => 33.60440620039582,
                'lng' => 36.516000388345404,
                'location' => 'عدرا',
                'first_name' => 'عادل',
                'last_name' => 'عدرا',
                'email' => 'adra@pharmacy.com',
                'phone' => '0966666666',
                'gender' => 'male',
                'syndicate_card' => 'SYN-1006',
                'is_24_today' => true,
            ],
            [
                'key' => 'rami',
                'pharmacy_name' => 'صيدلية رامي بركات',
                'lat' => 33.53949290308723,
                'lng' => 36.30070488887808,
                'location' => 'ركن الدين',
                'first_name' => 'رامي',
                'last_name' => 'بركات',
                'email' => 'rami_barakat@pharmacy.com',
                'phone' => '0977777777',
                'gender' => 'male',
                'syndicate_card' => 'SYN-1007',
                'is_24_today' => false,
            ],
            [
                'key' => 'wael',
                'pharmacy_name' => 'صيدلية وائل القوسي',
                'lat' => 33.55588594874296,
                'lng' => 36.323361989678276,
                'location' => 'برزة',
                'first_name' => 'وائل',
                'last_name' => 'القوسي',
                'email' => 'wael_alqawsi@pharmacy.com',
                'phone' => '0988888888',
                'gender' => 'male',
                'syndicate_card' => 'SYN-1008',
                'is_24_today' => false,
            ],
        ];

        // Idempotency: skip entirely if the trail data was already seeded
        $seedEmail = $pharmaciesData[0]['email'] ?? null;
        if ($seedEmail !== null && User::where('email', $seedEmail)->exists()) {
            return;
        }

        $createdPharmacies = [];

        foreach ($pharmaciesData as $item) {
            $pharmacistId = (string) Str::uuid();
            $pharmacyId = (string) Str::uuid();

            $createdPharmacies[$item['key']] = $pharmacyId;

            // 1. إنشاء المستخدم باستخدام Eloquent
            $user = User::create([
                'f_name' => $item['first_name'],
                'l_name' => $item['last_name'],
                'age' => 35,
                'gender' => $item['gender'],
                'phone_number' => $item['phone'],
                'location' => $item['location'],
                'email' => $item['email'],
                'password' => $hashedPassword,
                'email_verified_at' => $now,
            ]);

            // 2. إسناد الـ Role مثل الـ Register تماماً
            $user->assignRole('pharmacist');

            // 3. إنشاء الصيدلي
            DB::table('pharmacists')->insert([
                'id' => $pharmacistId,
                'user_id' => $user->id,
                'syndicate_card' => $item['syndicate_card'],
                'verification_status' => 'approved',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // 4. إنشاء الصيدلية
            DB::table('pharmacies')->insert([
                'id' => $pharmacyId,
                'pharmacist_id' => $pharmacistId,
                'name' => $item['pharmacy_name'],
                'address' => $item['location'],
                'latitude' => $item['lat'],
                'longitude' => $item['lng'],
                'support_email' => $item['email'],
                'support_number' => $item['phone'],
                'front_image' => 'pharmacies/default.jpg',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('pharmacy_pharmacist')->insert([
                'pharmacy_id' => $pharmacyId,
                'pharmacist_id' => $pharmacistId,
                'pharmacy_manage' => true,
                'inventory_manage' => true,
                'operating_hours_manage' => true,
                'orders_process' => true,
                'orders_view_own' => true,
                'salary' => 500000.00,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            for ($day = 0; $day <= 6; $day++) {
                $isToday = ($day === $todayDayOfWeek);

                if ($isToday) {
                    $is24 = $item['is_24_today'];
                    $isClosed = ! $item['is_24_today'];
                } else {
                    $isClosed = (bool) rand(0, 1);
                    $is24 = $isClosed ? false : (bool) rand(0, 1);
                }

                DB::table('pharmacy_operating_hours')->insert([
                    'id' => (string) Str::uuid(),
                    'pharmacy_id' => $pharmacyId,
                    'day_of_week' => $day,
                    'opening_time' => ($is24 || $isClosed) ? null : '08:00:00',
                    'closing_time' => ($is24 || $isClosed) ? null : '22:00:00',
                    'is_24_hours' => $is24,
                    'is_closed' => $isClosed,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        // ============================================================
        // 2. PATIENTS, CHRONIC RECORDS & MEDICATION PATIENTS
        // ============================================================
        $patientsData = [
            'walid' => [
                'first_name' => 'وليد',
                'last_name' => 'السلطي',
                'lat' => 33.54071291550851,
                'lng' => 36.30109714374123,
                'location' => 'ركن الدين',
                'email' => 'walid@patient.com',
                'phone' => '0991111111',
                'gender' => 'male',
                'blood_type' => 'O+',
                'age' => 23,
                'chronic' => null,
            ],
            'ahmad' => [
                'first_name' => 'أحمد',
                'last_name' => 'المحمد',
                'lat' => 33.55783111216152,
                'lng' => 36.32580218776839,
                'location' => 'برزة',
                'email' => 'ahmad@patient.com',
                'phone' => '0992222222',
                'gender' => 'male',
                'blood_type' => 'A+',
                'age' => 28,
                'chronic' => [
                    'disease_id' => '13',
                    'medication_id' => null,
                ],
            ],
            'yasser' => [
                'first_name' => 'ياسر',
                'last_name' => 'العلي',
                'lat' => 33.48274440827064,
                'lng' => 36.25610077595711,
                'location' => 'كفرسوسة لوان',
                'email' => 'yasser@patient.com',
                'phone' => '0993333333',
                'gender' => 'male',
                'blood_type' => 'B+',
                'age' => 34,
                'chronic' => [
                    'disease_id' => '2',
                    'medication_id' => null,
                ],
            ],
            'ahd' => [
                'first_name' => 'عهد',
                'last_name' => 'عدرا',
                'lat' => 33.61298698753342,
                'lng' => 36.54134618848904,
                'location' => 'عدرا',
                'email' => 'ahd_adra@patient.com',
                'phone' => '0994444444',
                'gender' => 'female',
                'blood_type' => 'AB+',
                'age' => 26,
                'chronic' => [
                    'disease_id' => '8',
                    'medication_id' => '6571',
                ],
            ],
        ];

        $patientIds = [];

        foreach ($patientsData as $key => $item) {
            $patientId = (string) Str::uuid();
            $patientIds[$key] = $patientId;

            // 1. إنشاء المستخدم بـ Eloquent
            $user = User::create([
                'f_name' => $item['first_name'],
                'l_name' => $item['last_name'],
                'age' => $item['age'],
                'gender' => $item['gender'],
                'phone_number' => $item['phone'],
                'location' => $item['location'],
                'email' => $item['email'],
                'password' => $hashedPassword,
                'email_verified_at' => $now,
            ]);

            // 2. إسناد الـ Role مثل الـ Register تماماً
            $user->assignRole('patient');

            // 3. إنشاء المريض
            DB::table('patients')->insert([
                'id' => $patientId,
                'user_id' => $user->id,
                'blood_type' => $item['blood_type'],
                'latitude' => $item['lat'],
                'longitude' => $item['lng'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            if (! is_null($item['chronic'])) {
                $chronicRecordId = (string) Str::uuid();

                DB::table('chronic_records')->insert([
                    'id' => $chronicRecordId,
                    'chronic_disease_id' => (string) $item['chronic']['disease_id'],
                    'patient_id' => $patientId,
                    'diagnosis_year' => 2022,
                    'severity' => 'low',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                if (! is_null($item['chronic']['medication_id'])) {
                    DB::table('medication_patients')->insert([
                        'id' => (string) Str::uuid(),
                        'medication_id' => (string) $item['chronic']['medication_id'],
                        'patient_id' => $patientId,
                        'state' => 'permanent',
                        'chronic_id' => $chronicRecordId,
                        'dosage' => '1 حبة يومياً',
                        'available_pills' => 30,
                        'frequency' => 'daily',
                        'refill_risk' => false,
                        'is_active' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }

        // ============================================================
        // 3. INVENTORY & BATCHES
        // ============================================================
        $pharmacyMedicationMap = [
            'adra' => ['3303'],
            'safaa' => ['409'],
            'yasmeen' => ['10159', '10036'],
        ];

        foreach ($createdPharmacies as $pKey => $pharmacyId) {
            $medicationIds = $pharmacyMedicationMap[$pKey] ?? [];

            $randomMedCount = rand(5, 12);
            for ($i = 0; $i < $randomMedCount; $i++) {
                $medicationIds[] = (string) rand(1, 5000);
            }

            $medicationIds = array_unique($medicationIds);

            foreach ($medicationIds as $medId) {
                $inventoryId = (string) Str::uuid();
                $stockQuantity = rand(20, 150);
                $price = rand(5000, 45000);
                $wholesalePrice = $price * 0.8;

                DB::table('pharmacy_inventories')->insert([
                    'id' => $inventoryId,
                    'pharmacy_id' => $pharmacyId,
                    'medication_id' => (string) $medId,
                    'price' => $price,
                    'stock' => $stockQuantity,
                    'min_stock' => 10,
                    'last_updated' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                DB::table('pharmacy_inventory_batches')->insert([
                    'id' => (string) Str::uuid(),
                    'pharmacy_inventory_id' => $inventoryId,
                    'batch_number' => 'BATCH-'.strtoupper(Str::random(6)),
                    'quantity' => $stockQuantity,
                    'wholesale_price' => $wholesalePrice,
                    'expiration_date' => Carbon::now()->addMonths(rand(6, 24))->format('Y-m-d'),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        // ============================================================
        // 4. ORDERS, EXPENSES, SALARIES, EXTRA BATCHES & SEARCH TELEMETRY
        // ============================================================
        $seededPharmacyIds = array_values($createdPharmacies);
        $alreadySeeded = DB::table('medication_orders')
            ->whereIn('pharmacy_id', $seededPharmacyIds)
            ->exists();

        if ($alreadySeeded) {
            return;
        }

        $allPatientIds = array_values($patientIds);
        $ingredientIds = DB::table('active_ingredients')->pluck('id')->all();
        $suppliers = ['شركة الشرق', 'مستودع الشام', 'شركة أورينت', 'مستودع البركة', 'شركة الفيحاء'];
        $expenseTitles = ['إيجار المحل', 'فاتورة كهرباء', 'رواتب إضافية', 'صيانة وتصليح', 'حملة تسويقية', 'ضرائب', 'تأمين صحي'];
        $expenseCategories = ['إيجار', 'فواتير كهرباء', 'رواتب إضافية', 'صيانة', 'تسويق', 'ضرائب', 'تأمين'];

        // 4.1 Staff pharmacists per pharmacy (drives the staff performance report)
        $staffByPharmacy = [];
        foreach ($createdPharmacies as $pKey => $pharmacyId) {
            $owner = DB::table('pharmacies')->where('id', $pharmacyId)->first();
            $ownerUser = DB::table('users')->where('id', DB::table('pharmacists')->where('id', $owner->pharmacist_id)->value('user_id'))->first();

            $staffByPharmacy[$pKey] = [
                'owner' => $owner->pharmacist_id,
                'owner_user_id' => $ownerUser->id,
                'owner_name' => trim(($ownerUser->f_name ?? '').' '.($ownerUser->l_name ?? '')),
                'staff' => [],
            ];

            foreach (range(1, rand(1, 2)) as $s) {
                $staffUser = User::create([
                    'f_name' => 'موظف',
                    'l_name' => ucfirst($pKey).' '.$s,
                    'age' => rand(22, 45),
                    'gender' => rand(0, 1) ? 'male' : 'female',
                    'phone_number' => '09'.str_pad((string) rand(0, 99999999), 8, '0', STR_PAD_LEFT),
                    'location' => $owner->address,
                    'email' => 'staff_'.$pKey.'_'.$s.'@pharmacy.com',
                    'password' => $hashedPassword,
                    'email_verified_at' => $now,
                ]);
                $staffUser->assignRole('pharmacist');

                $staffId = (string) Str::uuid();
                DB::table('pharmacists')->insert([
                    'id' => $staffId,
                    'user_id' => $staffUser->id,
                    'syndicate_card' => 'SYN-'.strtoupper($pKey).'-'.$s,
                    'verification_status' => 'approved',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                DB::table('pharmacy_pharmacist')->insert([
                    'pharmacy_id' => $pharmacyId,
                    'pharmacist_id' => $staffId,
                    'pharmacy_manage' => false,
                    'inventory_manage' => (bool) rand(0, 1),
                    'operating_hours_manage' => false,
                    'orders_process' => true,
                    'orders_view_own' => (bool) rand(0, 1),
                    'salary' => rand(150000, 400000),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $staffByPharmacy[$pKey]['staff'][] = $staffId;
            }
        }

        // 4.2 Orders across the last two full months (financial narrative)
        // P1 = the month before last, P2 = the last full month, both closed.
        $p2End = Carbon::now()->startOfMonth();
        $periods = [
            'P1' => [
                'label' => $p2End->copy()->subMonths(2)->format('F'),
                'start' => $p2End->copy()->subMonths(2)->startOfMonth(),
                'end' => $p2End->copy()->subMonths(1)->startOfMonth(),
            ],
            'P2' => [
                'label' => $p2End->copy()->subMonths(1)->format('F'),
                'start' => $p2End->copy()->subMonths(1)->startOfMonth(),
                'end' => $p2End->copy(),
            ],
        ];

        // Per-pharmacy x per-period narrative profile (drives a deterministic generator)
        $orderProfiles = [
            'hikma' => [
                'P1' => ['sales' => 'MOD', 'damaged' => 'HEAVY', 'expenses' => 'NORMAL', 'salary' => 'NORMAL'],
                'P2' => ['sales' => 'HIGH', 'damaged' => 'NONE', 'expenses' => 'NORMAL', 'salary' => 'NORMAL'],
            ],
            'adra' => [
                'P1' => ['sales' => 'HIGH', 'damaged' => 'NONE', 'expenses' => 'LOW', 'salary' => 'LOW'],
                'P2' => ['sales' => 'HIGH', 'damaged' => 'NONE', 'expenses' => 'LOW', 'salary' => 'HIGH'],
            ],
            'hamish' => [
                'P1' => ['sales' => 'HIGH', 'damaged' => 'NONE', 'expenses' => 'LOW', 'salary' => 'LOW'],
                'P2' => ['sales' => 'HIGH', 'damaged' => 'NONE', 'expenses' => 'LOW', 'salary' => 'HIGH'],
            ],
            'rami' => [
                'P1' => ['sales' => 'MOD', 'damaged' => 'NONE', 'expenses' => 'VERY_HIGH', 'salary' => 'NORMAL'],
                'P2' => ['sales' => 'HIGH', 'damaged' => 'NONE', 'expenses' => 'NORMAL', 'salary' => 'NORMAL'],
            ],
            'yasmeen' => [
                'P1' => ['sales' => 'HIGH', 'damaged' => 'NONE', 'expenses' => 'LOW', 'salary' => 'LOW'],
                'P2' => ['sales' => 'MOD', 'damaged' => 'HEAVY', 'expenses' => 'NORMAL', 'salary' => 'NORMAL'],
            ],
            'safaa' => [
                'P1' => ['sales' => 'HIGH', 'damaged' => 'NONE', 'expenses' => 'NORMAL', 'salary' => 'LOW'],
                'P2' => ['sales' => 'MOD', 'damaged' => 'NONE', 'expenses' => 'LOW', 'salary' => 'LOW'],
            ],
            'wael' => [
                'P1' => ['sales' => 'HIGH', 'damaged' => 'NONE', 'expenses' => 'LOW', 'salary' => 'LOW'],
                'P2' => ['sales' => 'MOD', 'damaged' => 'HEAVY', 'expenses' => 'NORMAL', 'salary' => 'NORMAL'],
            ],
            'asad' => [
                'P1' => ['sales' => 'HIGH', 'damaged' => 'NONE', 'expenses' => 'LOW', 'salary' => 'LOW'],
                'P2' => ['sales' => 'HIGH', 'damaged' => 'NONE', 'expenses' => 'LOW', 'salary' => 'HIGH'],
            ],
        ];

        $salesTiers = [
            'MOD' => ['orders' => [2, 2], 'items' => [1, 3], 'qty' => [1, 3]],
            'HIGH' => ['orders' => [3, 4], 'items' => [2, 3], 'qty' => [2, 4]],
        ];
        $damagedTiers = [
            'NONE' => 0,
            'HEAVY' => ['count' => [6, 12], 'cost' => [200000, 600000]],
        ];
        $expenseTiers = [
            'LOW' => ['count' => [2, 3], 'amount' => [50000, 150000]],
            'NORMAL' => ['count' => [3, 5], 'amount' => [100000, 300000]],
            'VERY_HIGH' => ['count' => [5, 8], 'amount' => [400000, 1200000]],
        ];
        $salaryTiers = [
            'LOW' => 350000,
            'NORMAL' => 650000,
            'HIGH' => 1150000,
        ];

        $invoiceSeqByPharmacy = array_fill_keys(array_keys($createdPharmacies), 1);

        foreach ($periods as $periodKey => $period) {
            foreach ($createdPharmacies as $pKey => $pharmacyId) {
                $profile = $orderProfiles[$pKey][$periodKey] ?? null;

                if ($profile === null) {
                    continue;
                }

                // Deterministic randomness per pharmacy + period (reproducible output)
                mt_srand(hexdec(substr(md5($pKey.'-'.$periodKey), 0, 8)));

                $pharmacistOptions = array_merge([$staffByPharmacy[$pKey]['owner']], $staffByPharmacy[$pKey]['staff']);

                $this->generateMonthOrders(
                    $pKey,
                    $pharmacyId,
                    $period,
                    $profile,
                    $salesTiers,
                    $damagedTiers,
                    $pharmacistOptions,
                    $allPatientIds,
                    $suppliers,
                    $invoiceSeqByPharmacy[$pKey]
                );
            }
        }

        // 4.3 Expenses & salaries (financial summary) — scoped to June & July only
        foreach ($periods as $periodKey => $period) {
            foreach ($createdPharmacies as $pKey => $pharmacyId) {
                $profile = $orderProfiles[$pKey][$periodKey] ?? null;

                if ($profile === null) {
                    continue;
                }

                mt_srand(hexdec(substr(md5('fin-'.$pKey.'-'.$periodKey), 0, 8)));

                $lastDayIndex = $period['start']->copy()->daysInMonth - 1;

                // Expenses inside the month, driven by the profile tier
                $expenseTier = $expenseTiers[$profile['expenses']];

                foreach (range(1, mt_rand($expenseTier['count'][0], $expenseTier['count'][1])) as $e) {
                    DB::table('expenses')->insert([
                        'id' => (string) Str::uuid(),
                        'pharmacy_id' => $pharmacyId,
                        'title' => $expenseTitles[array_rand($expenseTitles)],
                        'amount' => mt_rand($expenseTier['amount'][0], $expenseTier['amount'][1]),
                        'category' => $expenseCategories[array_rand($expenseCategories)],
                        'payment_method' => ['cash', 'card', 'bank_transfer', 'apps'][array_rand(['cash', 'card', 'bank_transfer', 'apps'])],
                        'expense_date' => $period['start']->copy()->addDays(mt_rand(0, $lastDayIndex))->format('Y-m-d'),
                        'notes' => 'مصروف شهري',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }

                // Exactly one salary per month, paid in the last days of the month
                // (must fall inside the period so financialSummary counts it)
                $baseAmount = $salaryTiers[$profile['salary']];
                $bonus = mt_rand(0, 20000);
                $deductions = mt_rand(0, 15000);
                $paidAt = $period['start']->copy()->addDays(mt_rand(min(27, $lastDayIndex), $lastDayIndex));

                DB::table('salaries')->insert([
                    'id' => (string) Str::uuid(),
                    'pharmacy_id' => $pharmacyId,
                    'user_id' => $staffByPharmacy[$pKey]['owner_user_id'],
                    'recipient_name' => $staffByPharmacy[$pKey]['owner_name'],
                    'base_amount' => $baseAmount,
                    'bonus' => $bonus,
                    'deductions' => $deductions,
                    'net_amount' => $baseAmount + $bonus - $deductions,
                    'salary_period' => $period['start']->format('Y-m'),
                    'paid_at' => $paidAt->format('Y-m-d'),
                    'payment_method' => ['cash', 'card', 'bank_transfer', 'apps'][array_rand(['cash', 'card', 'bank_transfer', 'apps'])],
                    'notes' => 'راتب شهري',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        // 4.4 Extra batches: one expired + one nearing expiry (inventory-expiring report).
        // The 2–3 extra expired batches for yasmeen/wael (P2) are created inline inside
        // generateMonthOrders() so the heavy damaged orders can reference them directly.
        foreach ($createdPharmacies as $pKey => $pharmacyId) {
            $inventoryIds = DB::table('pharmacy_inventories')->where('pharmacy_id', $pharmacyId)->pluck('id')->all();

            if (empty($inventoryIds)) {
                continue;
            }

            DB::table('pharmacy_inventory_batches')->insert([
                'id' => (string) Str::uuid(),
                'pharmacy_inventory_id' => $inventoryIds[array_rand($inventoryIds)],
                'batch_number' => 'EXP-'.strtoupper(Str::random(6)),
                'quantity' => rand(3, 15),
                'wholesale_price' => rand(1000, 10000),
                'expiration_date' => Carbon::now()->subDays(rand(30, 90))->format('Y-m-d'),
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('pharmacy_inventory_batches')->insert([
                'id' => (string) Str::uuid(),
                'pharmacy_inventory_id' => $inventoryIds[array_rand($inventoryIds)],
                'batch_number' => 'NEAR-'.strtoupper(Str::random(6)),
                'quantity' => rand(3, 15),
                'wholesale_price' => rand(1000, 10000),
                'expiration_date' => Carbon::now()->addDays(rand(3, 28))->format('Y-m-d'),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // 4.5 Search telemetry (demand report) — only the 3 specified regions
        $telemetryRandomPool = [
            'aspirin 100', 'augmentin 457', 'omeprazole 20', 'voltaren med 50',
            'amoxicillin 500', 'paracetamol 500', 'diclofenac 50', 'metformin 850',
            'atorvastatin 20', 'azithromycin 500', 'losartan 50', 'pantoprazole 40',
        ];
        $telemetryRegions = [
            'adra' => [
                'meds' => [
                    ['name' => 'augmatex 1000', 'count' => 5, 'ingredients' => [70, 342], 'usage' => 'مضاد حيوي واسع الطيف (أموكسيسيلين + حمض الكلافولانيك) لعلاج العدوى الجرثومية المقاومة لجميع الأدوية'],
                    ['name' => 'augmenta 1000', 'count' => 4, 'ingredients' => [70, 342], 'usage' => 'مضاد حيوي واسع الطيف (أموكسيسيلين + حمض الكلافولانيك) لعلاج العدوى الجرثومية المقاومة لجميع الأدوية'],
                    ['name' => 'zednad 125', 'count' => 12, 'ingredients' => [283], 'usage' => 'هي مضاد حيوي (سيفالوسبورين) لعلاج العدوى الجرثومية'],
                    ['name' => 'dimalexine 250', 'count' => 3, 'ingredients' => [283], 'usage' => 'هي مضاد حيوي (سيفالوسبورين) لعلاج العدوى الجرثومية'],
                ],
            ],
            'hikma' => [
                'meds' => [
                    ['name' => 'entecavir 0.5', 'count' => 5, 'ingredients' => [509], 'usage' => 'مضاد فيروسي لعلاج التهاب الكبد الوبائي B'],
                    ['name' => 'entecavir 1', 'count' => 4, 'ingredients' => [509], 'usage' => 'مضاد فيروسي لعلاج التهاب الكبد الوبائي B'],
                    ['name' => 'tenofovir 300', 'count' => 20, 'ingredients' => [1404], 'usage' => 'مضاد فيروسي لعلاج التهاب الكبد الوبائي B'],
                ],
            ],
            'yasmeen' => [
                'meds' => [
                    ['name' => 'nifidine 10', 'count' => 22, 'ingredients' => [64], 'usage' => 'خافض لضغط الدم وموسّع للأوعية (حاصرات قنوات الكالسيوم)'],
                    ['name' => 'amlor mond 10', 'count' => 4, 'ingredients' => [64], 'usage' => 'خافض لضغط الدم وموسّع للأوعية (حاصرات قنوات الكالسيوم)'],
                    ['name' => 'amlovazide 160/10/12.5', 'count' => 20, 'ingredients' => [64], 'usage' => 'خافض لضغط الدم ثلاثي المكوّن لعلاج ارتفاع الضغط'],
                ],
            ],
        ];
        $randomUsages = [
            'مضاد حيوي لعلاج الالتهابات البكتيرية',
            'مسكن ألم وخافض حرارة',
            'مضاد فيروسي',
            'مضاد التهاب',
            'خافض للضغط',
        ];

        foreach ($telemetryRegions as $pKey => $region) {
            $pharmacy = DB::table('pharmacies')->where('id', $createdPharmacies[$pKey])->first();
            $lat = (float) $pharmacy->latitude;
            $lng = (float) $pharmacy->longitude;
            $rows = [];

            foreach ($region['meds'] as $med) {
                $ingredients = $med['ingredients'];

                for ($i = 0; $i < $med['count']; $i++) {
                    $ingredient = $ingredients[$i % count($ingredients)];
                    $rows[] = $this->telemetryRow($med['name'], $lat, $lng, (string) $ingredient, $med['usage']);
                }
            }

            // Random additional searches (8–12), every row is a strict match
            foreach (range(1, rand(8, 12)) as $t) {
                $name = $telemetryRandomPool[array_rand($telemetryRandomPool)];
                $ingredient = (! empty($ingredientIds) && rand(0, 100) <= 50)
                    ? $ingredientIds[array_rand($ingredientIds)]
                    : null;
                $usage = rand(0, 100) <= 50 ? $randomUsages[array_rand($randomUsages)] : null;

                $rows[] = $this->telemetryRow($name, $lat, $lng, $ingredient, $usage);
            }

            $this->insertTelemetry($rows);
        }

        // 4.6 Stagnant stock (slow-moving report): inventory never sold by any order
        foreach ($createdPharmacies as $pKey => $pharmacyId) {
            foreach (range(1, rand(1, 3)) as $st) {
                $stagnantId = (string) Str::uuid();
                $stagnantPrice = rand(8000, 40000);
                $stagnantQty = rand(10, 60);

                DB::table('pharmacy_inventories')->insert([
                    'id' => $stagnantId,
                    'pharmacy_id' => $pharmacyId,
                    'medication_id' => (string) rand(1, 5000),
                    'price' => $stagnantPrice,
                    'stock' => $stagnantQty,
                    'min_stock' => 10,
                    'last_updated' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                DB::table('pharmacy_inventory_batches')->insert([
                    'id' => (string) Str::uuid(),
                    'pharmacy_inventory_id' => $stagnantId,
                    'batch_number' => 'STAGNANT-'.strtoupper(Str::random(6)),
                    'quantity' => $stagnantQty,
                    'wholesale_price' => $stagnantPrice * 0.8,
                    'expiration_date' => Carbon::now()->addMonths(rand(3, 12))->format('Y-m-d'),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    /**
     * Inventory item pool (medication + batch + retail/wholesale prices) for a pharmacy.
     */
    protected function buildItemPool(string $pharmacyId): array
    {
        return DB::table('pharmacy_inventories as inv')
            ->leftJoin('pharmacy_inventory_batches as b', 'b.pharmacy_inventory_id', '=', 'inv.id')
            ->where('inv.pharmacy_id', $pharmacyId)
            ->select('inv.medication_id', 'inv.price', 'b.id as batch_id', 'b.wholesale_price')
            ->get()
            ->map(fn ($row) => [
                'medication_id' => $row->medication_id,
                'price' => (float) $row->price,
                'batch_id' => $row->batch_id,
                'wholesale_price' => $row->wholesale_price !== null ? (float) $row->wholesale_price : (float) $row->price * 0.8,
            ])
            ->values()
            ->all();
    }

    /**
     * Generate a full month of orders for a pharmacy from its narrative profile.
     * Guarantees >= 1 completed sale per day (no day-gaps), weekly purchases,
     * 2-3 customer returns, sporadic supplier returns and (for HEAVY tiers)
     * 6-12 damaged orders spread across the month.
     */
    protected function generateMonthOrders(
        string $pKey,
        string $pharmacyId,
        array $period,
        array $profile,
        array $salesTiers,
        array $damagedTiers,
        array $pharmacistOptions,
        array $allPatientIds,
        array $suppliers,
        int &$invoiceSeq
    ): void {
        $itemPool = $this->buildItemPool($pharmacyId);

        if (empty($itemPool)) {
            return;
        }

        $damagePool = $itemPool;

        if ($profile['damaged'] === 'HEAVY' && in_array($pKey, ['yasmeen', 'wael'], true)) {
            // Extra expired batches (section 4.4) are created inline so the damaged
            // orders can reference them: expired stock drives the loss narrative.
            $damagePool = array_merge($damagePool, $this->createExpiredBatches($pharmacyId, $period));
        }

        $daysInMonth = $period['start']->copy()->daysInMonth;
        $saleTier = $salesTiers[$profile['sales']];

        $damagedDays = [];
        if ($profile['damaged'] === 'HEAVY') {
            $damagedDays = $this->spreadDays(
                $daysInMonth,
                mt_rand($damagedTiers['HEAVY']['count'][0], $damagedTiers['HEAVY']['count'][1])
            );
        }

        $returnDays = $this->spreadDays($daysInMonth, mt_rand(2, 3));
        $supplierReturnDays = $this->spreadDays($daysInMonth, mt_rand(1, 2));

        $completedSaleIds = [];

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $orderDate = $period['start']->copy()->addDays($day - 1);

            // Daily sales (>= 1 guaranteed so no day-gaps)
            $saleCount = mt_rand($saleTier['orders'][0], $saleTier['orders'][1]);

            for ($i = 0; $i < $saleCount; $i++) {
                $orderTime = $orderDate->copy()->setTime(mt_rand(9, 21), mt_rand(0, 59), 0);
                $saleItems = [];

                for ($j = 0, $itemCount = mt_rand($saleTier['items'][0], $saleTier['items'][1]); $j < $itemCount; $j++) {
                    $base = $itemPool[array_rand($itemPool)];
                    $saleItems[] = [
                        'medication_id' => $base['medication_id'],
                        'batch_id' => $base['batch_id'],
                        'wholesale_price_at_sale' => $base['wholesale_price'],
                        'quantity' => mt_rand($saleTier['qty'][0], $saleTier['qty'][1]),
                        'price' => $base['price'],
                    ];
                }

                $orderId = $this->insertOrder([
                    'patient_id' => $allPatientIds[array_rand($allPatientIds)],
                    'pharmacy_id' => $pharmacyId,
                    'pharmacist_id' => $pharmacistOptions[array_rand($pharmacistOptions)],
                    'status' => 'completed',
                    'source' => mt_rand(0, 1) ? 'POS' : 'app',
                    'type' => 'sale',
                    'is_returned' => false,
                    'supplier_name' => null,
                    'invoice_number' => 'INV-TRAIL-'.strtoupper($pKey).'-'.str_pad((string) $invoiceSeq, 4, '0', STR_PAD_LEFT),
                    'pharmacist_note' => null,
                    'notes' => 'مبيعات يومية',
                    'created_at' => $orderTime,
                    'updated_at' => $orderTime,
                ], $saleItems);

                $invoiceSeq++;
                $completedSaleIds[] = $orderId;
            }

            // Weekly purchase (source is always POS)
            if ($day % 7 === 0) {
                $purchaseTime = $orderDate->copy()->setTime(mt_rand(9, 18), mt_rand(0, 59), 0);
                $purchaseItems = [];

                for ($j = 0; $j < mt_rand(1, 2); $j++) {
                    $base = $itemPool[array_rand($itemPool)];
                    $purchaseItems[] = [
                        'medication_id' => $base['medication_id'],
                        'batch_id' => $base['batch_id'],
                        'wholesale_price_at_sale' => $base['wholesale_price'],
                        'quantity' => mt_rand(5, 20),
                        'price' => $base['wholesale_price'],
                    ];
                }

                $this->insertOrder([
                    'patient_id' => null,
                    'pharmacy_id' => $pharmacyId,
                    'pharmacist_id' => $pharmacistOptions[array_rand($pharmacistOptions)],
                    'status' => 'completed',
                    'source' => 'POS',
                    'type' => 'purchase',
                    'is_returned' => false,
                    'supplier_name' => $suppliers[array_rand($suppliers)],
                    'invoice_number' => 'INV-TRAIL-PUR-'.strtoupper($pKey).'-'.str_pad((string) $invoiceSeq, 4, '0', STR_PAD_LEFT),
                    'pharmacist_note' => null,
                    'notes' => 'توريد من المورد',
                    'created_at' => $purchaseTime,
                    'updated_at' => $purchaseTime,
                ], $purchaseItems);

                $invoiceSeq++;
            }

            // Monthly customer return (small, references a completed sale)
            if (in_array($day, $returnDays, true) && ! empty($completedSaleIds)) {
                $original = DB::table('medication_orders')
                    ->where('id', $completedSaleIds[array_rand($completedSaleIds)])
                    ->first();
                $returnPct = mt_rand(1, 4) / 10;
                $returnTime = $orderDate->copy()->setTime(mt_rand(9, 21), mt_rand(0, 59), 0);

                $this->insertOrder([
                    'patient_id' => $original->patient_id,
                    'pharmacy_id' => $pharmacyId,
                    'pharmacist_id' => $original->pharmacist_id,
                    'status' => 'completed',
                    'source' => 'POS',
                    'type' => 'customer_return',
                    'total_price' => round((float) $original->total_price * $returnPct, 2),
                    'total_cost' => round((float) $original->total_cost * $returnPct, 2),
                    'is_returned' => true,
                    'supplier_name' => null,
                    'invoice_number' => 'INV-TRAIL-RET-'.strtoupper($pKey).'-'.str_pad((string) $invoiceSeq, 4, '0', STR_PAD_LEFT),
                    'pharmacist_note' => null,
                    'notes' => 'Return for invoice: '.$original->invoice_number,
                    'created_at' => $returnTime,
                    'updated_at' => $returnTime,
                ]);

                $invoiceSeq++;
            }

            // Sporadic supplier return
            if (in_array($day, $supplierReturnDays, true)) {
                $base = $itemPool[array_rand($itemPool)];
                $qty = mt_rand(1, 4);
                $supplierReturnTime = $orderDate->copy()->setTime(mt_rand(9, 18), mt_rand(0, 59), 0);

                $this->insertOrder([
                    'patient_id' => null,
                    'pharmacy_id' => $pharmacyId,
                    'pharmacist_id' => $pharmacistOptions[array_rand($pharmacistOptions)],
                    'status' => 'completed',
                    'source' => 'POS',
                    'type' => 'supplier_return',
                    'is_returned' => true,
                    'supplier_name' => $suppliers[array_rand($suppliers)],
                    'invoice_number' => 'INV-TRAIL-SRET-'.strtoupper($pKey).'-'.str_pad((string) $invoiceSeq, 4, '0', STR_PAD_LEFT),
                    'pharmacist_note' => null,
                    'notes' => 'إرجاع للمورد',
                    'created_at' => $supplierReturnTime,
                    'updated_at' => $supplierReturnTime,
                ], [[
                    'medication_id' => $base['medication_id'],
                    'batch_id' => $base['batch_id'],
                    'wholesale_price_at_sale' => $base['wholesale_price'],
                    'quantity' => $qty,
                    'price' => $base['wholesale_price'],
                ]]);

                $invoiceSeq++;
            }

            // Damaged orders (HEAVY tiers only), each a 200k-600k wholesale loss
            if (in_array($day, $damagedDays, true)) {
                $base = $damagePool[array_rand($damagePool)];
                $targetCost = mt_rand($damagedTiers['HEAVY']['cost'][0], $damagedTiers['HEAVY']['cost'][1]);
                $qty = max(1, (int) round($targetCost / $base['wholesale_price']));
                $damageTime = $orderDate->copy()->setTime(mt_rand(9, 21), mt_rand(0, 59), 0);

                $this->insertOrder([
                    'patient_id' => null,
                    'pharmacy_id' => $pharmacyId,
                    'pharmacist_id' => $pharmacistOptions[array_rand($pharmacistOptions)],
                    'status' => 'completed',
                    'source' => 'POS',
                    'type' => 'damaged',
                    'is_returned' => false,
                    'supplier_name' => null,
                    'invoice_number' => 'INV-TRAIL-DMG-'.strtoupper($pKey).'-'.str_pad((string) $invoiceSeq, 4, '0', STR_PAD_LEFT),
                    'pharmacist_note' => null,
                    'notes' => in_array($pKey, ['yasmeen', 'wael'], true)
                        ? 'أدوية منتهية الصلاحية (تم إتلافها)'
                        : 'منتجات تالفة (تم التخلص منها)',
                    'created_at' => $damageTime,
                    'updated_at' => $damageTime,
                ], [[
                    'medication_id' => $base['medication_id'],
                    'batch_id' => $base['batch_id'],
                    'wholesale_price_at_sale' => $base['wholesale_price'],
                    'quantity' => $qty,
                    'price' => 0,
                ]]);

                $invoiceSeq++;
            }
        }
    }

    /**
     * Insert a medication order (+ optional items) and return its id.
     * Totals are computed from items unless explicitly provided (customer returns).
     */
    protected function insertOrder(array $order, array $items = []): string
    {
        $order['id'] = (string) Str::uuid();

        if (! isset($order['total_price'])) {
            $order['total_price'] = round(array_sum(array_map(fn ($it) => $it['price'] * $it['quantity'], $items)), 2);
        }

        if (! isset($order['total_cost'])) {
            $order['total_cost'] = round(array_sum(array_map(fn ($it) => ($it['wholesale_price_at_sale'] ?? $it['price']) * $it['quantity'], $items)), 2);
        }

        $order['updated_at'] = $order['updated_at'] ?? $order['created_at'];

        DB::table('medication_orders')->insert($order);

        foreach ($items as $item) {
            DB::table('medication_order_items')->insert([
                'id' => (string) Str::uuid(),
                'medication_order_id' => $order['id'],
                'medication_id' => $item['medication_id'],
                'batch_id' => $item['batch_id'],
                'wholesale_price_at_sale' => $item['wholesale_price_at_sale'],
                'quantity' => $item['quantity'],
                'price' => $item['price'],
                'created_at' => $order['created_at'],
                'updated_at' => $order['created_at'],
            ]);
        }

        return $order['id'];
    }

    /**
     * 2-3 extra expired batches (expiring inside the period) for yasmeen/wael.
     * Returns pool entries so damaged orders can reference them.
     */
    protected function createExpiredBatches(string $pharmacyId, array $period): array
    {
        $inventoryIds = DB::table('pharmacy_inventories')
            ->where('pharmacy_id', $pharmacyId)
            ->pluck('id')
            ->all();

        $items = [];
        $count = mt_rand(2, 3);

        for ($i = 0; $i < $count; $i++) {
            if (empty($inventoryIds)) {
                break;
            }

            $inventoryId = $inventoryIds[array_rand($inventoryIds)];
            $medicationId = DB::table('pharmacy_inventories')->where('id', $inventoryId)->value('medication_id');
            $batchId = (string) Str::uuid();
            $wholesale = mt_rand(10000, 30000);
            $expiry = $period['start']->copy()->addDays(mt_rand(0, max(0, $period['start']->diffInDays($period['end']) - 1)));

            DB::table('pharmacy_inventory_batches')->insert([
                'id' => $batchId,
                'pharmacy_inventory_id' => $inventoryId,
                'batch_number' => 'EXP-'.strtoupper(Str::random(6)),
                'quantity' => mt_rand(5, 20),
                'wholesale_price' => $wholesale,
                'expiration_date' => $expiry->format('Y-m-d'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $items[] = [
                'medication_id' => $medicationId,
                'price' => 0,
                'batch_id' => $batchId,
                'wholesale_price' => (float) $wholesale,
            ];
        }

        return $items;
    }

    /**
     * Pick a deterministic set of day-of-month indexes spread across the month.
     */
    protected function spreadDays(int $daysInMonth, int $count): array
    {
        $days = range(1, $daysInMonth);
        shuffle($days);
        $picked = array_slice($days, 0, $count);
        sort($picked);

        return $picked;
    }

    /**
     * Jitter a lat/lng value by a small random offset (<= +/-$maxOffset).
     */
    protected function jitter(float $value, float $maxOffset = 0.0000003): float
    {
        return $value + (rand(-1000, 1000) / 1000) * $maxOffset;
    }

    /**
     * One strict-match search telemetry row (searched_query == resolved_product_name).
     */
    protected function telemetryRow(string $name, float $lat, float $lng, ?string $ingredientId, ?string $usage): array
    {
        return [
            'searched_query' => $name,
            'resolved_product_name' => $name,
            'resolved_active_ingredient_id' => $ingredientId,
            'resolved_usage' => $usage,
            'latitude' => $this->jitter($lat),
            'longitude' => $this->jitter($lng),
            'created_at' => Carbon::now()->subDays(rand(0, 6))->setTime(rand(0, 23), rand(0, 59)),
        ];
    }

    /**
     * Batch insert telemetry rows.
     */
    protected function insertTelemetry(array $rows): void
    {
        if ($rows !== []) {
            DB::table('search_telemetries')->insert($rows);
        }
    }
}
