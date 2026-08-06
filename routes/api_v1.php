<?php

declare(strict_types=1);

// ─── Shared auth routes ───────────────────────────────────────────────────
require __DIR__.'/api/v1/auth.php';

// ─── Domain-specific route files ──────────────────────────────────────────
require __DIR__.'/api/v1/pharmacy.php';
require __DIR__.'/api/v1/medication.php';
require __DIR__.'/api/v1/patient.php';
require __DIR__.'/api/v1/disease.php';
require __DIR__.'/api/v1/doctor.php';
require __DIR__.'/api/v1/company.php';
require __DIR__.'/api/v1/rep.php';
require __DIR__.'/api/v1/specialist.php';
require __DIR__.'/api/v1/admin.php';
