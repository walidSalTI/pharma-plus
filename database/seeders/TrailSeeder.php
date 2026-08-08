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

        foreach ($patientsData as $key => $item) {
            $patientId = (string) Str::uuid();

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
    }
}
