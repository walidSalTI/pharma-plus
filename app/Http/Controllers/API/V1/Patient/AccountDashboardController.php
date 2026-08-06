<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1\Patient;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\Patient\ExportLedgerRequest;
use App\Http\Resources\API\V1\Patient\PatientLedgerResource;
use App\Models\Patient;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Patient Account & Data Portability (FR-P-1.4).
 *
 * Handles account purging with cascading clinical metadata deletion
 * and medical ledger export in JSON or PDF format.
 *
 * Flow (exportLedger):
 * 1. Validate format (json|pdf).
 * 2. Load patient profile with chronic_records, medicationPatients, medicationOrders.
 * 3. If JSON, return resource collection as JSON response.
 * 4. If PDF, compile Blade view and stream download.
 *
 * Flow (destroyAccount):
 * 1. Authenticated user requests deletion.
 * 2. Delete the User record — cascading foreign keys handle clinical metadata removal.
 * 3. Return confirmation response.
 */
class AccountDashboardController extends Controller
{
    /**
     * Export patient medical ledger (FR-P-1.4).
     *
     * Downloads the patient's full clinical profile as JSON or PDF.
     * JSON returns a structured data payload; PDF compiles a Blade template.
     *
     * @bodyParam format string required Export format: 'json' or 'pdf'.
     */
    public function exportLedger(ExportLedgerRequest $request): JsonResponse|StreamedResponse|Response
    {
        $format = $request->validated()['format'];
        $patient = $request->user()->patient->load([
            'chronicRecords.chronicDisease',
            'medicationPatients.medication',
            'medicationOrders.pharmacy',
        ]);

        if ($format === 'json') {
            return response()->json([
                'data' => new PatientLedgerResource($patient),
            ]);
        }

        $pdf = Pdf::loadView('pdf.patient-ledger', [
            'patient' => $patient,
            'user' => $request->user(),
        ]);

        return $pdf->download('medical-ledger-'.$patient->id.'.pdf');
    }

    /**
     * Permanently delete patient account (FR-P-1.4).
     *
     * Deletes the authenticated user record. Cascading foreign key
     * constraints on the database automatically remove associated
     * clinical profile metadata (chronic_records, medication_patients,
     * medication_orders, etc.).
     */
    public function destroyAccount(Request $request): JsonResponse
    {
        $user = $request->user();

        $user->delete();

        return response()->json([
            'message' => 'Account and associated clinical data permanently deleted.',
        ]);
    }
}
