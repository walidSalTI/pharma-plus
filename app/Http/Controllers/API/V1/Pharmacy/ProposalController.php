<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1\Pharmacy;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\Pharmacy\StoreProposalRequest;
use App\Http\Resources\API\V1\Pharmacy\ProposalResource;
use App\Models\MedicationProposal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Central DB Contribution & Review (FR-PH-5).
 *
 * Allows pharmacists to submit new medication proposals for items
 * missing from the global catalog. Submissions are reviewed by
 * Medical Specialists or Admins via a moderation queue.
 */
class ProposalController extends Controller
{
    /**
     * List the pharmacist's proposals (FR-PH-5.4).
     *
     * Returns all medication proposals submitted by the authenticated
     * pharmacist, ordered by most recent, with current review status.
     */
    public function index(Request $request): JsonResponse
    {
        $pharmacist = $request->user()->pharmacist;

        if (! $pharmacist) {
            return response()->json(['message' => 'Pharmacist profile not found.'], 404);
        }

        $proposals = $pharmacist->medicationProposals()
            ->with('specialist.user')
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 20));

        return response()->json([
            'data' => ProposalResource::collection($proposals),
            'meta' => [
                'current_page' => $proposals->currentPage(),
                'last_page' => $proposals->lastPage(),
                'per_page' => $proposals->perPage(),
                'total' => $proposals->total(),
            ],
        ]);
    }

    /**
     * Submit a new medication proposal (FR-PH-5.1).
     *
     * Creates a new proposal for a drug missing from the central catalog.
     * Requires medication name, dosage form, and an optional photo/PDF of
     * the inner pamphlet leaflet. The proposal enters the moderation queue
     * with a 'pending' status.
     */
    public function store(StoreProposalRequest $request): JsonResponse
    {
        $pharmacist = $request->user()->pharmacist;

        if (! $pharmacist) {
            return response()->json(['message' => 'Pharmacist profile not found.'], 404);
        }

        $validated = $request->validated();

        $imagePath = null;
        if ($request->hasFile('image_url')) {
            $imagePath = $request->file('image_url')->store('proposals', 'public');
        }

        $proposal = MedicationProposal::create([
            'pharmacist_id' => $pharmacist->id,
            'specialist_id' => null,
            'medication_name' => $validated['medication_name'],
            'form' => $validated['form'],
            'image_url' => $imagePath,
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Proposal submitted successfully. It is now pending review.',
            'data' => new ProposalResource($proposal),
        ], 201);
    }

    /**
     * Get a single proposal (FR-PH-5.4).
     *
     * Returns the full details of a specific proposal including its
     * current review status, specialist assignment, and any
     * rejection reason.
     */
    public function show(Request $request, MedicationProposal $proposal): JsonResponse
    {
        $pharmacist = $request->user()->pharmacist;

        if (! $pharmacist) {
            return response()->json(['message' => 'Pharmacist profile not found.'], 404);
        }

        if ($proposal->pharmacist_id !== $pharmacist->id) {
            return response()->json(['message' => 'Proposal not found.'], 404);
        }

        $proposal->load('specialist.user');

        return response()->json([
            'data' => new ProposalResource($proposal),
        ]);
    }
}
