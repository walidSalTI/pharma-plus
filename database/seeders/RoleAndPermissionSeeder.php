<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()->make(PermissionRegistrar::class)->forgetCachedPermissions();

        // ==================== PERMISSIONS ====================

        // User Management
        Permission::updateOrCreate(['name' => 'users.manage',        'guard_name' => 'api']); // Suspend/rectify any user

        // Verification (Admin)
        Permission::updateOrCreate(['name' => 'doctors.verify',      'guard_name' => 'api']); // Approve/reject doctors
        Permission::updateOrCreate(['name' => 'pharmacists.verify',  'guard_name' => 'api']); // Approve/reject pharmacists
        Permission::updateOrCreate(['name' => 'specialists.verify',  'guard_name' => 'api']); // Approve/reject specialists
        Permission::updateOrCreate(['name' => 'companies.verify',    'guard_name' => 'api']); // Approve/suspend pharma companies
        Permission::updateOrCreate(['name' => 'pharmacies.verify',   'guard_name' => 'api']); // Approve pharmacies

        // Medical Knowledge Base
        Permission::updateOrCreate(['name' => 'chronic-diseases.manage',   'guard_name' => 'api']); // CRUD chronic diseases
        Permission::updateOrCreate(['name' => 'active-ingredients.manage', 'guard_name' => 'api']); // CRUD active ingredients
        Permission::updateOrCreate(['name' => 'risk-mappings.manage',      'guard_name' => 'api']); // Link diseases <> ingredients
        Permission::updateOrCreate(['name' => 'medications.manage',        'guard_name' => 'api']); // CRUD global medication catalog
        Permission::updateOrCreate(['name' => 'medication-ingredients.manage', 'guard_name' => 'api']); // Manage ingredient ratios

        // Proposals
        Permission::updateOrCreate(['name' => 'proposals.create', 'guard_name' => 'api']); // Submit proposals (pharmacist)
        Permission::updateOrCreate(['name' => 'proposals.review', 'guard_name' => 'api']); // Review proposals (specialist/admin)

        // Pharmacy Operations
        Permission::updateOrCreate(['name' => 'pharmacy.manage',        'guard_name' => 'api']); // Manage pharmacy profile
        Permission::updateOrCreate(['name' => 'inventory.manage',       'guard_name' => 'api']); // Manage inventory/pricing
        Permission::updateOrCreate(['name' => 'operating-hours.manage', 'guard_name' => 'api']); // Set operating hours
        Permission::updateOrCreate(['name' => 'orders.process',         'guard_name' => 'api']); // Process incoming orders

        // Orders & Reviews
        Permission::updateOrCreate(['name' => 'orders.place',    'guard_name' => 'api']); // Place medication orders (patient)
        Permission::updateOrCreate(['name' => 'orders.view-own', 'guard_name' => 'api']); // View own orders
        Permission::updateOrCreate(['name' => 'orders.manage',   'guard_name' => 'api']); // Override/mediate orders (admin)
        Permission::updateOrCreate(['name' => 'reviews.create',  'guard_name' => 'api']); // Rate pharmacies (patient)
        Permission::updateOrCreate(['name' => 'reviews.manage',  'guard_name' => 'api']); // Flag/remove reviews (admin)

        // Patient Medical Profile
        Permission::updateOrCreate(['name' => 'profile.manage',             'guard_name' => 'api']); // Manage personal info
        Permission::updateOrCreate(['name' => 'chronic-records.manage',     'guard_name' => 'api']); // Log chronic diseases
        Permission::updateOrCreate(['name' => 'medications.manage-own',     'guard_name' => 'api']); // Manage own medication registry
        Permission::updateOrCreate(['name' => 'medication-schedules.manage', 'guard_name' => 'api']); // Program intake schedules
        Permission::updateOrCreate(['name' => 'medication-logs.manage',     'guard_name' => 'api']); // Log intake responses
        Permission::updateOrCreate(['name' => 'medications.search',         'guard_name' => 'api']); // Search drugs
        Permission::updateOrCreate(['name' => 'requests.create',            'guard_name' => 'api']); // Create medication requests

        // B2B Company Operations
        Permission::updateOrCreate(['name' => 'company.manage',       'guard_name' => 'api']); // Update company profile
        Permission::updateOrCreate(['name' => 'reps.manage',          'guard_name' => 'api']); // Add/suspend/remove reps
        Permission::updateOrCreate(['name' => 'doctors.assign',       'guard_name' => 'api']); // Assign doctors to reps
        Permission::updateOrCreate(['name' => 'schedules.manage',     'guard_name' => 'api']); // Create/manage weekly schedules
        Permission::updateOrCreate(['name' => 'schedules.publish',    'guard_name' => 'api']); // Publish schedules
        Permission::updateOrCreate(['name' => 'visits.view-own',      'guard_name' => 'api']); // View own company's visits
        Permission::updateOrCreate(['name' => 'analytics.view-market', 'guard_name' => 'api']); // View demand analytics/heatmaps
        Permission::updateOrCreate(['name' => 'reports.export',       'guard_name' => 'api']); // Export visit reports

        // Field Operations (Scientific Rep)
        Permission::updateOrCreate(['name' => 'schedule.view-own',  'guard_name' => 'api']); // View own schedule
        Permission::updateOrCreate(['name' => 'visits.check-in',    'guard_name' => 'api']); // Check in via QR scan
        Permission::updateOrCreate(['name' => 'visits.add-notes',   'guard_name' => 'api']); // Add post-visit notes
        Permission::updateOrCreate(['name' => 'visits.view-history', 'guard_name' => 'api']); // View own visit history

        // Doctor
        Permission::updateOrCreate(['name' => 'qr.view', 'guard_name' => 'api']); // View dynamic QR code
        Permission::updateOrCreate(['name' => 'workplaces.manage', 'guard_name' => 'api']); // Manage doctor workplaces

        // Specialist-specific
        Permission::updateOrCreate(['name' => 'medication-ingredients.verify', 'guard_name' => 'api']); // Verify ingredient ratios
        Permission::updateOrCreate(['name' => 'chronic-diseases.audit',        'guard_name' => 'api']); // Review disease classifications

        // ==================== ROLES & PERMISSION ASSIGNMENT ====================

        // --- Admin ---
        $admin = Role::updateOrCreate(['name' => 'admin', 'guard_name' => 'api']);
        $admin->givePermissionTo([
            'users.manage',
            'doctors.verify',
            'pharmacists.verify',
            'specialists.verify',
            'companies.verify',
            'pharmacies.verify',
            'chronic-diseases.manage',
            'active-ingredients.manage',
            'risk-mappings.manage',
            'medications.manage',
            'medication-ingredients.manage',
            'proposals.review',
            'pharmacy.manage',
            'operating-hours.manage',
            'orders.manage',
            'orders.view-own',
            'reviews.manage',
            'visits.view-own',
            'doctors.assign',
            'analytics.view-market',
            'reports.export',
        ]);

        // --- Company Owner ---
        $companyOwner = Role::updateOrCreate(['name' => 'company_owner', 'guard_name' => 'api']);
        $companyOwner->givePermissionTo([
            'company.manage',
            'reps.manage',
            'doctors.assign',
            'schedules.manage',
            'schedules.publish',
            'visits.view-own',
            'analytics.view-market',
            'reports.export',
        ]);

        // --- Patient ---
        $patient = Role::updateOrCreate(['name' => 'patient', 'guard_name' => 'api']);
        $patient->givePermissionTo([
            'profile.manage',
            'chronic-records.manage',
            'medications.manage-own',
            'medication-schedules.manage',
            'medication-logs.manage',
            'medications.search',
            'orders.place',
            'orders.view-own',
            'reviews.create',
            'requests.create',
        ]);

        // --- Doctor ---
        $doctor = Role::updateOrCreate(['name' => 'doctor', 'guard_name' => 'api']);
        $doctor->givePermissionTo([
            'profile.manage',
            'qr.view',
            'workplaces.manage',
        ]);

        // --- Pharmacist ---
        // Pharmacy-level permissions (pharmacy.manage, inventory.manage,
        // operating-hours.manage, orders.process, orders.view-own) are now
        // managed per-pivot record on pharmacy_pharmacist table.
        $pharmacist = Role::updateOrCreate(['name' => 'pharmacist', 'guard_name' => 'api']);
        $pharmacist->givePermissionTo([
            'profile.manage',
            'proposals.create',
        ]);

        // --- Specialist ---
        $specialist = Role::updateOrCreate(['name' => 'specialist', 'guard_name' => 'api']);
        $specialist->givePermissionTo([
            'profile.manage',
            'proposals.review',
            'risk-mappings.manage',
            'medication-ingredients.verify',
            'medication-ingredients.manage',
            'chronic-diseases.audit',
            'active-ingredients.manage',
        ]);

        // --- Scientific Rep ---
        $scientificRep = Role::updateOrCreate(['name' => 'scientific_rep', 'guard_name' => 'api']);
        $scientificRep->givePermissionTo([
            'profile.manage',
            'schedule.view-own',
            'visits.check-in',
            'visits.add-notes',
            'visits.view-history',
        ]);
    }
}
